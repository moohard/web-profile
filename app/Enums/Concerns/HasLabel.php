<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasLabel
{
    /**
     * Label manusia-baca untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            default => ucfirst(strtolower(str_replace('_', ' ', $this->value))),
        };
    }
}
