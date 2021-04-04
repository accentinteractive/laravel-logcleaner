<?php

namespace Accentinteractive\LaravelLogcleaner;

use Accentinteractive\LaravelLogcleaner\Commands\Logcleaner;
use Illuminate\Support\ServiceProvider;

class LaravelLogcleanerServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/logcleaner.php' => config_path('logcleaner.php'),
        ], 'config');

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-logcleaner'),
        ], 'lang');*/
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/logcleaner.php', 'logcleaner');

        $this->app->bind('command.logcleaner:run', Logcleaner::class);

        $this->commands([
            'command.logcleaner:run',
        ]);
    }
}
