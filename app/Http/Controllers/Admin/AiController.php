<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageTranslation;
use App\Models\PostTranslation;
use App\Models\WritingStyle;
use App\Services\Ai\Tasks\ContentRefinementTask;
use App\Services\Ai\Tasks\TranslationTask;
use App\Services\Html\Sanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    public function __construct(
        private TranslationTask $translation,
        private ContentRefinementTask $refinement,
        private Sanitizer $sanitizer,
    ) {}

    /**
     * Hasilkan saran terjemahan AI tanpa menyimpan ke database.
     *
     * Mendukung dua mode: teks mentah (`source_text`, mis. draft yang belum
     * disimpan di editor) — tanpa resolusi entity/otorisasi parent (otorisasi
     * cukup lewat middleware permission route); atau entity tersimpan
     * (`entity_type` + `entity_id` + `field`) seperti semula.
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_locale' => ['required', 'string', 'size:2'],
            'target_locale' => ['required', 'string', 'size:2'],
            'source_text' => ['nullable', 'string'],
            'entity_type' => ['required_without:source_text', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required_without:source_text', 'integer'],
            'field' => ['required_without:source_text', 'in:title,body,meta_title,meta_description,content'],
        ]);

        $rawSourceText = $validated['source_text'] ?? null;

        if ($rawSourceText !== null) {
            $sourceText = trim($rawSourceText);

            if ($sourceText === '') {
                return response()->json(['suggestion' => '', 'error' => 'Source kosong.'], 422);
            }

            $suggestion = $this->translation->translate(
                text: $sourceText,
                sourceLocale: $validated['source_locale'],
                targetLocale: $validated['target_locale'],
            );

            return response()->json(['suggestion' => $suggestion]);
        }

        $entity = $this->resolveEntity($validated['entity_type'], (int) $validated['entity_id']);
        $this->authorizeParentUpdate($entity);

        $sourceText = $this->extractSourceText($entity, $validated['field']);

        if ($sourceText === '') {
            return response()->json(['suggestion' => '', 'error' => 'Source kosong.'], 422);
        }

        $suggestion = $this->translation->translate(
            text: $sourceText,
            sourceLocale: $validated['source_locale'],
            targetLocale: $validated['target_locale'],
        );

        return response()->json(['suggestion' => $suggestion]);
    }

    /**
     * Hasilkan saran koreksi/penyempurnaan gaya bahasa AI tanpa menyimpan ke
     * database. Selalu bekerja atas teks mentah dari client (editor belum
     * tentu punya entity tersimpan) — otorisasi cukup lewat middleware
     * permission route (`ai.update`).
     */
    public function refine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_text' => ['required', 'string'],
            'writing_style_id' => ['nullable', 'integer', 'exists:writing_styles,id'],
        ]);

        $stylePrompt = '';

        if (! empty($validated['writing_style_id'])) {
            $writingStyle = WritingStyle::query()->find((int) $validated['writing_style_id']);
            $stylePrompt = $writingStyle->prompt ?? '';
        }

        $suggestion = $this->refinement->suggest($validated['source_text'], $stylePrompt);

        return response()->json(['suggestion' => $suggestion]);
    }

    /**
     * Simpan nilai terjemahan yang sudah di-review ke field entity.
     */
    public function applyTranslation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required', 'integer'],
            'target_locale' => ['required', 'string', 'size:2'],
            'field' => ['required', 'in:title,body,meta_title,meta_description,content'],
            'value' => ['required'],
        ]);

        $entity = $this->resolveEntity($validated['entity_type'], (int) $validated['entity_id']);
        $this->authorizeParentUpdate($entity);

        $value = $this->normalizeApplyValue($validated['field'], $validated['value']);
        $entity->update([$validated['field'] => $value]);

        return response()->json(['ok' => true]);
    }

    /**
     * Muat entity terjemahan berdasarkan tipe.
     */
    private function resolveEntity(string $entityType, int $entityId): Model
    {
        $class = match ($entityType) {
            'PostTranslation' => PostTranslation::class,
            'PageTranslation' => PageTranslation::class,
            default => abort(422, 'Tipe entity tidak didukung.'),
        };

        return $class::query()->findOrFail($entityId);
    }

    /**
     * Pastikan user boleh memperbarui parent (Post/Page) dari terjemahan.
     */
    private function authorizeParentUpdate(Model $entity): void
    {
        if ($entity instanceof PostTranslation) {
            $entity->loadMissing('post');
            $this->authorize('update', $entity->post);

            return;
        }

        if ($entity instanceof PageTranslation) {
            $entity->loadMissing('page');
            $this->authorize('update', $entity->page);

            return;
        }

        abort(403);
    }

    /**
     * Ambil teks sumber untuk diterjemahkan; hindari cast array → "(string) Array".
     */
    private function extractSourceText(Model $entity, string $field): string
    {
        $raw = $entity->{$field} ?? null;

        if ($raw === null) {
            return '';
        }

        if (is_array($raw)) {
            if (isset($raw['html']) && is_string($raw['html'])) {
                return $raw['html'];
            }

            $encoded = json_encode($raw, JSON_UNESCAPED_UNICODE);

            return is_string($encoded) ? $encoded : '';
        }

        return trim((string) $raw);
    }

    /**
     * Normalisasi & sanitasi nilai yang akan disimpan.
     *
     * @return string|array<string, mixed>
     */
    private function normalizeApplyValue(string $field, mixed $value): string|array
    {
        if ($field === 'content') {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (! is_array($decoded)) {
                    throw ValidationException::withMessages([
                        'value' => 'Field content harus berupa array JSON yang valid.',
                    ]);
                }
                $value = $decoded;
            }

            if (! is_array($value)) {
                throw ValidationException::withMessages([
                    'value' => 'Field content harus berupa array.',
                ]);
            }

            // Sanitasi HTML di kunci html bila ada
            if (isset($value['html']) && is_string($value['html'])) {
                $value['html'] = $this->sanitizer->clean($value['html']);
            }

            return $value;
        }

        if (! is_scalar($value)) {
            throw ValidationException::withMessages([
                'value' => 'Nilai field harus berupa string.',
            ]);
        }

        $stringValue = (string) $value;

        // Field HTML kaya (body) dibersihkan; title/meta tetap plain text
        if ($field === 'body') {
            return $this->sanitizer->clean($stringValue);
        }

        return $stringValue;
    }
}
