<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum UserRole: string
{
    use HasLabel;

    case Admin = 'Admin';
    case Editor = 'Editor';
    case Author = 'Author';

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => ['access-admin', 'admin.use-page-code-mode', 'admin.access-system', 'admin.access-appearance'],
            self::Editor => ['access-admin'],
            self::Author => ['access-admin'],
        };
    }
}
