<?php

use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Widget;
use App\Models\WidgetPlacement;
use App\Models\WidgetPlacementTarget;
use App\Models\WidgetTranslation;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->langId = Language::idFor('id');
});

it('memfilter widget berdasarkan scope All/Only/Except sesuai konteks halaman', function () {
    $langId = $this->langId;
    $type = ContentType::where('slug', 'berita')->firstOrFail();

    $make = function (string $title, PlacementScope $scope, int $sort, array $targets = []) use ($langId): void {
        $widget = Widget::create(['type' => 'HtmlWidget', 'is_active' => true]);
        WidgetTranslation::create([
            'widget_id' => $widget->id,
            'language_id' => $langId,
            'title' => $title,
            'content' => '<p>'.$title.'</p>',
        ]);
        $placement = WidgetPlacement::create([
            'widget_id' => $widget->id,
            'position' => WidgetPosition::Sidebar,
            'scope' => $scope,
            'sort_order' => $sort,
        ]);
        foreach ($targets as [$targetType, $targetRef]) {
            WidgetPlacementTarget::create([
                'placement_id' => $placement->id,
                'target_type' => $targetType,
                'target_ref' => (string) $targetRef,
            ]);
        }
    };

    $make('A', PlacementScope::All, 1);
    $make('B', PlacementScope::Only, 2, [[WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE, $type->id]]);
    $make('C', PlacementScope::Except, 3, [[WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE, $type->id]]);

    // Arsip /berita: All(A) + Only yang match(B); Except yang match(C) tersembunyi.
    $this->get('/berita')->assertInertia(fn (Assert $page) => $page
        ->component('public/post-archive')
        ->has('region.widgets.sidebar', 2)
        ->where('region.widgets.sidebar.0.title', 'A')
        ->where('region.widgets.sidebar.1.title', 'B')
    );

    // Beranda /: All(A) + Except tanpa match(C); Only(B) tersembunyi.
    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->component('public/home')
        ->has('region.widgets.sidebar', 2)
        ->where('region.widgets.sidebar.0.title', 'A')
        ->where('region.widgets.sidebar.1.title', 'C')
    );
});
