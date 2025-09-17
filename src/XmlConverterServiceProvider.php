<?php

namespace Noki\XmlConverter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class XmlConverterServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('convert', function () {
            return new Convert();
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (Storage::exists(__DIR__ . '/../../vendor/autoload.php'))
        {
            include __DIR__ . '/../../vendor/autoload.php';
        }

    }


}
