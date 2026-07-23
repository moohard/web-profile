<?php

declare(strict_types=1);

namespace App\Actions\Languages;

use App\Models\Language;
use App\Support\PublicLayoutProps;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteLanguage
{
    public function handle(Language $language): void
    {
        $deletedCode = DB::transaction(function () use ($language): string {
            $lockedLanguages = Language::query()->lockForUpdate()->get();
            $lockedLanguage = $lockedLanguages->firstWhere('id', $language->id);

            if (! $lockedLanguage instanceof Language) {
                return $language->code;
            }

            if ($lockedLanguage->is_default) {
                throw ValidationException::withMessages([
                    'language' => 'Bahasa default tidak dapat dihapus.',
                ]);
            }

            if ($lockedLanguage->isInUse()) {
                throw ValidationException::withMessages([
                    'language' => 'Bahasa tidak dapat dihapus karena sudah dipakai.',
                ]);
            }

            if ($lockedLanguages->count() <= 1) {
                throw ValidationException::withMessages([
                    'language' => 'Minimal satu bahasa harus tersedia.',
                ]);
            }

            $code = $lockedLanguage->code;
            $lockedLanguage->delete();

            return $code;
        });

        Language::flushCache($deletedCode);
        PublicLayoutProps::flushCache();
    }
}
