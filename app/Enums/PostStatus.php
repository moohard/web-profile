<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PostStatus: string
{
    use HasLabel;

    case Draft = 'Draft';
    case Published = 'Published';
}
