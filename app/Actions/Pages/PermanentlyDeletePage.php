<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use App\Enums\LinkType;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\WidgetPlacementTarget;
use App\Support\PublicLayoutProps;
use Illuminate\Support\Facades\DB;

class PermanentlyDeletePage
{
    public function __invoke(Page $page): void
    {
        DB::transaction(function () use ($page): void {
            MenuItem::query()
                ->where('link_type', LinkType::Page->value)
                ->where('link_ref', (string) $page->id)
                ->delete();

            WidgetPlacementTarget::query()
                ->where('target_type', WidgetPlacementTarget::TYPE_PAGE)
                ->where('target_ref', (string) $page->id)
                ->delete();

            $page->forceDelete();
        });

        PublicLayoutProps::flushCache();
    }
}
