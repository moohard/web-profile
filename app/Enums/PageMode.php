<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PageMode: string
{
    use HasLabel;

    case Code = 'Code';
    case Template = 'Template';
}
