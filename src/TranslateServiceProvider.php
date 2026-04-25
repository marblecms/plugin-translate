<?php

namespace MarbleCms\Translate;

use Illuminate\Support\ServiceProvider;
use Marble\Admin\Facades\MarbleAdmin;

class TranslateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/translate.php', 'translate');

        $this->app->singleton(Services\TranslationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'translate');

        $this->registerRoutes();
        $this->registerAdminNav();
        $this->registerPublishables();
        $this->registerCommands();
    }

    protected function registerRoutes(): void
    {
        $prefix = config('marble.route_prefix', 'admin');

        \Illuminate\Support\Facades\Route::middleware([
                'web',
                \Marble\Admin\Http\Middleware\MarbleAuthenticate::class,
                \Marble\Admin\Http\Middleware\SetMarbleGuard::class,
            ])
            ->prefix($prefix)
            ->as('marble.')
            ->group(__DIR__ . '/Http/admin_routes.php');
    }

    protected function registerAdminNav(): void
    {
        MarbleAdmin::addNavItem('structure', 'Translate', 'marble.translate.index', 'script_go', ['marble.translate.*']);
    }

    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/translate.php' => config_path('translate.php'),
            ], 'translate-config');

            $this->publishes([
                __DIR__ . '/Resources/views' => resource_path('views/vendor/translate'),
            ], 'translate-views');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }
}
