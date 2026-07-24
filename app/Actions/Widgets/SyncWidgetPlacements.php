<?php

declare(strict_types=1);

namespace App\Actions\Widgets;

use App\Models\Widget;
use Illuminate\Support\Facades\DB;

class SyncWidgetPlacements
{
    /**
     * @param  list<array{position: string, scope: string, sort_order: int, targets: list<array{target_type: string, target_ref: ?string}>}>  $placements
     */
    public function __invoke(Widget $widget, array $placements): void
    {
        DB::transaction(function () use ($widget, $placements): void {
            $widget->placements()->delete();

            foreach ($placements as $placementData) {
                $targets = $placementData['targets'];
                unset($placementData['targets']);

                $placement = $widget->placements()->create($placementData);
                $placement->targets()->createMany($targets);
            }
        });
    }
}
