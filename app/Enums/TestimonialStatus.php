<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum TestimonialStatus: string
{
    use HasLabel;

    case Pending = 'Pending';
    case Approved = 'Approved';
}
