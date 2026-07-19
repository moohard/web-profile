<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum LinkType: string
{
    use HasLabel;

    case Page = 'Page';
    case ContentArchive = 'ContentArchive';
    case ContentSingle = 'ContentSingle';
    case Url = 'Url';
}
