<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PageTranslation;
use App\Models\PostTranslation;
use App\Models\WritingStyle;
use App\Services\Ai\Tasks\ContentRefinementTask;
use App\Services\Ai\Tasks\MarkupConformTask;
use App\Services\Ai\Tasks\TranslationTask;
use App\Services\Html\Sanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AiController extends Controller
{
    public function __construct(
        private TranslationTask $translation,
        private ContentRefinementTask $refinement,
        private MarkupConformTask $markup,
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
            'source_locale' => ['required', 'string', 'size:2', 'exists:languages,code'],
            'target_locale' => ['required', 'string', 'size:2', 'exists:languages,code'],
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
        $this->validateEntityField($entity, $validated['field']);
        $this->validateEntityLocale($entity, $validated['source_locale'], 'source_locale');

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
     * Hasilkan saran penyesuaian markup HTML ke referensi komponen design
     * system, tanpa menyimpan ke database. Khusus Admin (mode Code halaman).
     */
    public function markupConform(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('use-page-code-mode'), 403);

        $validated = $request->validate([
            'source_html' => ['required', 'string'],
        ]);

        $suggestion = $this->markup->suggest($validated['source_html']);

        return response()->json(['suggestion' => $this->sanitizer->clean($suggestion)]);
    }

    /**
     * Simpan nilai terjemahan yang sudah di-review ke field entity.
     */
    public function applyTranslation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required', 'integer'],
            'target_locale' => ['required', 'string', 'size:2', 'exists:languages,code'],
            'field' => ['required', 'in:title,body,meta_title,meta_description,content'],
            'value' => ['required'],
        ]);

        $entity = $this->resolveEntity($validated['entity_type'], (int) $validated['entity_id']);
        $this->authorizeParentUpdate($entity);
        $this->validateEntityField($entity, $validated['field']);
        $this->validateEntityLocale($entity, $validated['target_locale'], 'target_locale');

        $value = $this->normalizeApplyValue($entity, $validated['field'], $validated['value']);
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
     * Pastikan field sesuai dengan skema entity terjemahan.
     */
    private function validateEntityField(Model $entity, string $field): void
    {
        $allowedFields = match (true) {
            $entity instanceof PostTranslation => ['title', 'body', 'meta_title', 'meta_description'],
            $entity instanceof PageTranslation => ['title', 'content', 'meta_title', 'meta_description'],
            default => [],
        };

        if (! in_array($field, $allowedFields, true)) {
            throw ValidationException::withMessages([
                'field' => 'Field tidak didukung untuk tipe entity ini.',
            ]);
        }
    }

    /**
     * Pastikan locale request menunjuk bahasa milik entity terjemahan.
     */
    private function validateEntityLocale(Model $entity, string $locale, string $errorKey): void
    {
        $languageId = Language::query()->where('code', $locale)->value('id');

        if ((int) $entity->getAttribute('language_id') !== (int) $languageId) {
            throw ValidationException::withMessages([
                $errorKey => 'Locale tidak cocok dengan bahasa entity terjemahan.',
            ]);
        }
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
    private function normalizeApplyValue(Model $entity, string $field, mixed $value): string|array
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
            return $entity instanceof PostTranslation
                ? $this->sanitizer->cleanRichText($stringValue)
                : $this->sanitizer->cleanCmsPage($stringValue);
        }

        return $stringValue;
    }
}
