<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MenuLocation: string
{
    use HasLabel;

    case Header = 'Header';
    case Footer = 'Footer';
}
