<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum WidgetPosition: string
{
    use HasLabel;

    case BeforeContent = 'BeforeContent';
    case AfterContent = 'AfterContent';
    case Sidebar = 'Sidebar';
    case Footer = 'Footer';
}
