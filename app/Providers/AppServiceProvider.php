<?php

namespace App\Providers;

use App\Media\Matcher\Matcher;
use App\Media\Matcher\Sources\Source;
use App\Media\Scanner\Processors\Processor;
use App\Media\Scanner\Scanner;
use App\Media\Storage\Handlers\Handler;
use App\Media\Storage\Storage;
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
        $this->app->singleton(ImageHandlerService::class, function (Application $app): \App\Services\ImageHandlerService {
            /** @var string[] $config */
            $config = Config::get('media.mime_types');

            return new ImageHandlerService($config);
        });
        $this->app->singleton(Storage::class, function (Application $app): \App\Media\Storage\Storage {
            /** @var class-string<Handler>[] $config */
            $config = Config::get('media.handlers');

            return new Storage($config, $app);
        });
        $this->app->singleton(Matcher::class, function (Application $app): \App\Media\Matcher\Matcher {
            /** @var class-string<Source>[] $config */
            $config = Config::get('media.matchers');

            return new Matcher($config, $app);
        });
        $this->app->singleton(Scanner::class, function (Application $app): \App\Media\Scanner\Scanner {
            /**
             * @var class-string<Processor>[] $processors
             */
            $processors = Config::get('media.processors');

            return new Scanner(
                $processors,
                $app->make(Storage::class),
                $app
            );
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
