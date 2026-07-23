<?php

declare(strict_types=1);

namespace App\Actions\Languages;

use App\Models\Language;
use App\Support\PublicLayoutProps;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveLanguage
{
    /**
     * @param  array{code: string, name: string, is_active: bool, is_default: bool, sort_order: int}  $data
     */
    public function handle(array $data, ?Language $language = null): Language
    {
        $previousCode = $language?->code;

        $savedLanguage = DB::transaction(function () use ($data, $language): Language {
            $lockedLanguages = Language::query()->lockForUpdate()->get();
            $lockedLanguage = $language === null
                ? null
                : $lockedLanguages->firstWhere('id', $language->id);

            if ($lockedLanguages->isEmpty()) {
                $data['is_default'] = true;
                $data['is_active'] = true;
            }

            if (
                $lockedLanguage instanceof Language
                && $lockedLanguage->code !== $data['code']
                && $lockedLanguage->isInUse()
            ) {
                throw ValidationException::withMessages([
                    'code' => 'Kode bahasa tidak dapat diubah karena sudah dipakai.',
                ]);
            }

            if ($lockedLanguage?->is_default && ! $data['is_default']) {
                throw ValidationException::withMessages([
                    'is_default' => 'Bahasa default hanya dapat diganti dengan memilih bahasa default baru.',
                ]);
            }

            if ($data['is_default'] && ! $data['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => 'Bahasa default wajib aktif.',
                ]);
            }

            if ($data['is_default']) {
                Language::query()
                    ->when(
                        $lockedLanguage instanceof Language,
                        fn ($query) => $query->whereKeyNot($lockedLanguage->id),
                    )
                    ->update(['is_default' => false]);
            }

            $lockedLanguage ??= new Language;
            $lockedLanguage->fill($data);
            $lockedLanguage->save();

            return $lockedLanguage->refresh();
        });

        Language::flushCache($previousCode);
        PublicLayoutProps::flushCache();

        return $savedLanguage;
    }
}
