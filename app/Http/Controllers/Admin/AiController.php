<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\Tasks\TranslationTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private TranslationTask $translation) {}

    /**
     * Hasilkan saran terjemahan AI tanpa menyimpan ke database.
     */
    public function translate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_locale' => ['required', 'string', 'size:2'],
            'target_locale' => ['required', 'string', 'size:2'],
            'entity_type' => ['required', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required', 'integer'],
            'field' => ['required', 'in:title,body,meta_title,meta_description,content'],
        ]);

        $class = 'App\\Models\\'.$validated['entity_type'];
        $source = $class::query()->where('id', $validated['entity_id'])->firstOrFail();
        $sourceText = $source->{$validated['field']} ?? '';

        if (empty($sourceText)) {
            return response()->json(['suggestion' => '', 'error' => 'Source kosong.'], 422);
        }

        $suggestion = $this->translation->translate(
            text: (string) $sourceText,
            sourceLocale: $validated['source_locale'],
            targetLocale: $validated['target_locale'],
        );

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
            'value' => ['required', 'string'],
        ]);

        $class = 'App\\Models\\'.$validated['entity_type'];
        $entity = $class::query()->findOrFail($validated['entity_id']);
        $entity->update([$validated['field'] => $validated['value']]);

        return response()->json(['ok' => true]);
    }
}
