<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            RolePermissionSeeder::class,
            WritingStyleSeeder::class,
            ContentTypeSeeder::class,
            RatingCriteriaSeeder::class,
            AdminUserSeeder::class,
            DemoPostSeeder::class,
        ]);
    }
}
