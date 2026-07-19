<?php

use App\Enums\AiTask;
use App\Enums\ContactStatus;
use App\Enums\LinkType;
use App\Enums\MenuLocation;
use App\Enums\PageMode;
use App\Enums\PlacementScope;
use App\Enums\PostStatus;
use App\Enums\TestimonialStatus;
use App\Enums\UserRole;
use App\Enums\WidgetPosition;

it('PostStatus memiliki cases yang benar', function () {
    expect(PostStatus::cases())
        ->toHaveCount(2)
        ->and(PostStatus::Draft->value)->toBe('Draft')
        ->and(PostStatus::Published->value)->toBe('Published');
});

it('UserRole permissions terdefinisi', function () {
    expect(UserRole::Admin->permissions())->toContain('admin.use-page-code-mode')
        ->and(UserRole::Editor->permissions())->not->toContain('admin.use-page-code-mode');
});

it('semua enum adalah backed string', function () {
    $enums = [PostStatus::class, UserRole::class, AiTask::class, LinkType::class,
        WidgetPosition::class, PlacementScope::class, MenuLocation::class,
        PageMode::class, ContactStatus::class, TestimonialStatus::class];
    foreach ($enums as $enum) {
        $reflection = new ReflectionEnum($enum);
        expect($reflection->getBackingType()?->getName())->toBe('string');
    }
});

it('AiTask memiliki tiga task', function () {
    expect(AiTask::cases())->toHaveCount(3)
        ->and(array_map(fn ($e) => $e->value, AiTask::cases()))
        ->toBe(['Translation', 'ContentRefinement', 'MarkupConform']);
});
