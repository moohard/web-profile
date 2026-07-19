<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum AiTask: string
{
    use HasLabel;

    case Translation = 'Translation';
    case ContentRefinement = 'ContentRefinement';
    case MarkupConform = 'MarkupConform';
}
