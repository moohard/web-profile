<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::query()->where('code', '!=', 'id')->update(['is_default' => false]);

        Language::query()->updateOrCreate(
            ['code' => 'id'],
            ['name' => 'Bahasa Indonesia', 'is_default' => true, 'is_active' => true, 'sort_order' => 1],
        );
        Language::query()->updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_default' => false, 'is_active' => true, 'sort_order' => 2],
        );

        Language::flushCache();
    }
}
