<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use App\Models\RatingCriterion;
use Illuminate\Database\Seeder;

class RatingCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        RatingCriterion::query()->delete();
        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        $criteria = [
            ['id' => 'Kemudahan penggunaan', 'en' => 'Ease of use'],
            ['id' => 'Kelengkapan informasi', 'en' => 'Information completeness'],
            ['id' => 'Kecepatan akses', 'en' => 'Access speed'],
            ['id' => 'Tampilan & kenyamanan', 'en' => 'Look & feel'],
            ['id' => 'Kepuasan keseluruhan', 'en' => 'Overall satisfaction'],
        ];

        foreach ($criteria as $i => $criterion) {
            $crit = RatingCriterion::create(['is_active' => true, 'sort_order' => $i + 1]);
            $crit->translations()->create(['language_id' => $langId, 'name' => $criterion['id']]);
            $crit->translations()->create(['language_id' => $langEn, 'name' => $criterion['en']]);
        }
    }
}
