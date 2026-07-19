<?php

use App\Enums\LinkType;
use App\Enums\MenuLocation;
use App\Enums\PageMode;
use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Widget;
use App\Models\WidgetPlacement;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Page mode cast ke PageMode enum', function () {
    $p = Page::create(['mode' => 'Code']);
    expect($p->fresh()->mode)->toBe(PageMode::Code);
});

it('PageTranslation content disimpan sebagai array JSONB', function () {
    $p = Page::create(['mode' => 'Template']);
    $tr = PageTranslation::create([
        'page_id' => $p->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'about',
        'title' => 'Tentang',
        'content' => ['html' => '<p>halo</p>'],
    ]);
    expect($tr->fresh()->content)->toBe(['html' => '<p>halo</p>']);
});

it('Menu location cast ke MenuLocation', function () {
    $m = Menu::create(['name' => 'Utama', 'location' => 'Header']);
    expect($m->fresh()->location)->toBe(MenuLocation::Header);
});

it('MenuItem link_type cast ke LinkType', function () {
    $m = Menu::create(['name' => 'Utama', 'location' => 'Header']);
    $i = MenuItem::create(['menu_id' => $m->id, 'link_type' => 'Url', 'url' => '/x']);
    expect($i->fresh()->link_type)->toBe(LinkType::Url);
});

it('WidgetPlacement cast position dan scope', function () {
    $w = Widget::create(['type' => 'HtmlWidget']);
    $pl = WidgetPlacement::create([
        'widget_id' => $w->id,
        'position' => 'Sidebar',
        'scope' => 'All',
        'sort_order' => 1,
    ]);
    expect($pl->fresh()->position)->toBe(WidgetPosition::Sidebar)
        ->and($pl->fresh()->scope)->toBe(PlacementScope::All);
});
