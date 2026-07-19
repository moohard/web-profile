<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use RuntimeException;

class MarkupConformTask
{
    /**
     * Saran penyesuaian markup HTML ke referensi komponen.
     * Skeleton pondasi — diimplementasikan di fase fitur.
     */
    public function suggest(string $html, string $componentReference): string
    {
        throw new RuntimeException('MarkupConformTask belum diimplementasikan di pondasi.');
    }
}
