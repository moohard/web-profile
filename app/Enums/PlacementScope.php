<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PlacementScope: string
{
    use HasLabel;

    case All = 'All';
    case Only = 'Only';
    case Except = 'Except';
}
