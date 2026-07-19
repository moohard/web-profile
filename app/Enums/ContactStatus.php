<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ContactStatus: string
{
    use HasLabel;

    case New = 'New';
    case Read = 'Read';
    case Archived = 'Archived';
}
