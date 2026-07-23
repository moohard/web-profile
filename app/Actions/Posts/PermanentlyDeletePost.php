<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Enums\LinkType;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\WidgetPlacementTarget;
use App\Support\PublicLayoutProps;
use Illuminate\Support\Facades\DB;

class PermanentlyDeletePost
{
    public function __invoke(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            MenuItem::query()
                ->where('link_type', LinkType::ContentSingle->value)
                ->where('link_ref', (string) $post->id)
                ->delete();

            WidgetPlacementTarget::query()
                ->where('target_type', WidgetPlacementTarget::TYPE_CONTENT_SINGLE)
                ->where('target_ref', (string) $post->id)
                ->delete();

            $post->forceDelete();
        });

        PublicLayoutProps::flushCache();
    }
}
