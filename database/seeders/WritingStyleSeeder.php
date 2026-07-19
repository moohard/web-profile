<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WritingStyle;
use Illuminate\Database\Seeder;

class WritingStyleSeeder extends Seeder
{
    public function run(): void
    {
        WritingStyle::query()->delete();
        WritingStyle::create([
            'name' => 'Formal Indonesia',
            'prompt' => 'Tulis dengan gaya formal-natural Bahasa Indonesia. Sapaan baku, kalimat ringkas, hindari jargon teknis kecuali perlu. Pertahankan markup HTML apa adanya.',
        ]);
    }
}
