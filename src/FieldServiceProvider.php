<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField;

use Illuminate\Support\Facades\Route;
use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;
use Outl1ne\NovaTranslationsLoader\LoadsNovaTranslations;
use ReflectionClass;
use Exception;

class FieldServiceProvider extends ServiceProvider
{
    use LoadsNovaTranslations;

    public function boot(): void
    {
        Nova::serving(function (ServingNova $event): void {
            Nova::script('nova-icon-field', __DIR__.'/../dist/js/nova-icon-field.js');
        });

        $this->loadTranslations(__DIR__.'/../resources/lang', 'nova-icon-field');

        $this->registerConfigs();

        $this->registerRoutes();
    }

    public function register(): void
    {

    }

    protected function registerRoutes(): void
    {
        $dir = $this->getDir().'/routes/';
        if ( ! is_dir($dir)) {
            return;
        }

        $files = scandir($dir) ?: [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.php')) {
                $options = [
                    'as' => (config('nova-icon-field.route.name') ?? 'fontawesome').'.',
                    'prefix' => config('nova-icon-field.route.prefix') ?? 'fa',
                ];

                if($middleware = config('nova-icon-field.route.middleware')) {
                    $options['middleware'] = $middleware;
                }

                Route::group($options, function () use ($dir, $file): void {
                    $this->loadRoutesFrom($dir.$file);
                });
            }
        }
    }


    protected function registerConfigs(): void
    {
        $dir = $this->getDir().'/../config/';
        if ( ! is_dir($dir)) {
            return;
        }

        $files = scandir($dir) ?: [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.php')) {
                $namespace = preg_replace('/\.php$/', '', $file) ?: '';
                $this->mergeConfigFrom($dir.$file, $namespace);

                if ($this->app->runningInConsole()) {
                    $this->publishes([
                        $dir.$file => config_path($file),
                    ], 'config');
                }
            }
        }
    }

    protected function getDir(): string
    {
        $reflector = new ReflectionClass(get_class($this));
        $path = dirname($reflector->getFileName());

        if( ! $path) {
            throw new Exception('Unable to find current FieldServiceProvider directory.');
        }
        return $path;
    }
}
