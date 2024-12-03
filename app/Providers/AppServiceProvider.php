<?php

namespace App\Providers;

use App\Scanner\Processors\Processor;
use App\Scanner\Scanner;
use App\Services\ImageHandlerService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageHandlerService::class, function (Application $app) {
            /** @var array<string> $config */
            $config = Config::get('media.mime_types');

            return new ImageHandlerService($config);
        });
        $this->app->singleton(Scanner::class, function (Application $app) {
            /**
             * @var array<class-string<Processor>> $processors
             */
            $processors = Config::get('media.processors');

            return new Scanner($processors, $this->app->make(ImageHandlerService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
