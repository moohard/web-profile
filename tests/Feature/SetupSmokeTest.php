<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\AiServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Svg;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Webp;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Sitemap\SitemapServiceProvider;
use Stevebauman\Purify\PurifyServiceProvider;

$readSetupEnv = function (string $path): Collection {
    return collect(file($path, FILE_IGNORE_NEW_LINES))
        ->filter(fn (string $line): bool => str_contains($line, '=') && ! str_starts_with(trim($line), '#'))
        ->mapWithKeys(function (string $line): array {
            [$key, $value] = explode('=', $line, 2);

            return [trim($key) => trim($value, " \t\n\r\0\x0B\"'")];
        });
};

$configureSetupPgsql = function (Collection $env): void {
    config([
        'database.connections.setup_pgsql' => [
            'driver' => 'pgsql',
            'url' => $env->get('DB_URL'),
            'host' => $env->get('DB_HOST', '127.0.0.1'),
            'port' => $env->get('DB_PORT', '5432'),
            'database' => $env->get('DB_DATABASE', 'db_development'),
            'username' => $env->get('DB_USERNAME'),
            'password' => $env->get('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ]);

    DB::purge('setup_pgsql');
};

it('uses the required local setup configuration', function () use ($configureSetupPgsql, $readSetupEnv) {
    $env = $readSetupEnv(base_path('.env'));

    expect($env->get('DB_CONNECTION'))->toBe('pgsql')
        ->and($env->get('DB_DATABASE'))->toBe('db_development')
        ->and($env->get('APP_LOCALE'))->toBe('id')
        ->and($env->get('APP_FAKER_LOCALE'))->toBe('id_ID')
        ->and($env->get('ADMIN_EMAIL'))->toBe('admin@papenajam.test')
        ->and($env->get('ADMIN_PASSWORD'))->toBe('password')
        ->and($env->get('FILESYSTEM_DISK'))->toBe('public')
        ->and(config('app.locale'))->toBe('id')
        ->and(config('app.faker_locale'))->toBe('id_ID')
        ->and(config('filesystems.default'))->toBe('public');

    $appConfig = file_get_contents(config_path('app.php'));

    expect($appConfig)->toContain("'locale' => env('APP_LOCALE', 'id')")
        ->and($appConfig)->toContain("'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID')");

    $configureSetupPgsql($env);

    expect(DB::connection('setup_pgsql')->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME))->toBe('pgsql');
});

it('documents safe setup values in the environment example', function () use ($readSetupEnv) {
    $envExample = $readSetupEnv(base_path('.env.example'));

    expect($envExample->get('DB_CONNECTION'))->toBe('pgsql')
        ->and($envExample->get('DB_HOST'))->toBe('127.0.0.1')
        ->and($envExample->get('DB_PORT'))->toBe('5432')
        ->and($envExample->get('DB_DATABASE'))->toBe('db_development')
        ->and($envExample->get('DB_USERNAME'))->toBe('')
        ->and($envExample->get('DB_PASSWORD'))->toBe('')
        ->and($envExample->get('APP_LOCALE'))->toBe('id')
        ->and($envExample->get('APP_FAKER_LOCALE'))->toBe('id_ID')
        ->and($envExample->get('ADMIN_EMAIL'))->toBe('admin@papenajam.test')
        ->and($envExample->get('ADMIN_PASSWORD'))->toBe('')
        ->and($envExample->get('FILESYSTEM_DISK'))->toBe('public');
});

it('has the required foundation packages installed', function () {
    expect(class_exists(PermissionServiceProvider::class))->toBeTrue()
        ->and(class_exists(MediaLibraryServiceProvider::class))->toBeTrue()
        ->and(class_exists(SitemapServiceProvider::class))->toBeTrue()
        ->and(class_exists(LaravelSettingsServiceProvider::class))->toBeTrue()
        ->and(class_exists(AiServiceProvider::class))->toBeTrue()
        ->and(class_exists(PurifyServiceProvider::class))->toBeTrue();
});

it('publishes package configuration with the expected media library defaults', function () {
    expect(config('media-library.disk_name'))->toBe('public')
        ->and(config('media-library.queue_name'))->toBe('default')
        ->and(config('media-library.image_generators'))->toContain(Image::class)
        ->and(config('media-library.image_generators'))->toContain(Webp::class)
        ->and(config('media-library.image_generators'))->toContain(Svg::class)
        ->and(config('permission.models.role'))->toBe(Role::class)
        ->and(config('permission.models.permission'))->toBe(Permission::class);
});

it('has package migration tables available in PostgreSQL', function () use ($configureSetupPgsql, $readSetupEnv) {
    $configureSetupPgsql($readSetupEnv(base_path('.env')));

    expect(Schema::connection('setup_pgsql')->hasTable('roles'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('permissions'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('model_has_roles'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('model_has_permissions'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('role_has_permissions'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('media'))->toBeTrue()
        ->and(Schema::connection('setup_pgsql')->hasTable('settings'))->toBeTrue();
});

it('has an Inertia React SSR entry configured for the Vite build', function () {
    expect(file_exists(resource_path('js/ssr.tsx')))->toBeTrue();

    $viteConfig = file_get_contents(base_path('vite.config.ts'));
    $ssrEntry = file_get_contents(resource_path('js/ssr.tsx'));

    expect($viteConfig)->toContain("ssr: 'resources/js/ssr.tsx'")
        ->and($viteConfig)->toContain("entry: 'resources/js/ssr.tsx'")
        ->and($ssrEntry)->toContain('@inertiajs/react/server')
        ->and($ssrEntry)->toContain('ReactDOMServer.renderToString')
        ->and($ssrEntry)->not->toContain('initializeTheme');
});
