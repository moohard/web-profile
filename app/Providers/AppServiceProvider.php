<?php

namespace App\Providers;

use App\Enums\AiTask;
use App\Enums\UserRole;
use App\Models\AiConfig;
use App\Models\User;
use App\Services\Ai\AiClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAiRuntime();

        // Mode code editor halaman hanya untuk Admin
        Gate::define('use-page-code-mode', function (User $user): bool {
            return $user->hasRole(UserRole::Admin->value);
        });
    }

    /**
     * Override config ai.php runtime dari AiConfig Translation yang aktif.
     * Multi-provider per-task paralel ditangani di fase fitur.
     */
    protected function configureAiRuntime(): void
    {
        try {
            if (! Schema::hasTable('ai_configs')) {
                return;
            }

            $translation = AiConfig::resolve(AiTask::Translation);

            if ($translation === null) {
                return;
            }

            // Key config SDK: `url` (bukan base_url).
            config([
                'ai.providers.openai.key' => $translation->api_key ?? config('ai.providers.openai.key'),
                'ai.providers.openai.url' => $translation->base_url ?? config('ai.providers.openai.url'),
                'ai.default' => 'openai',
            ]);
        } catch (Throwable) {
            // DB belum siap (migrate, install, package discovery).
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
