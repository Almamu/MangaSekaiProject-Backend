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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ImageHandlerService::class,
            function (Application $app): \App\Services\ImageHandlerService {
                $configRepository = $app->make(\Illuminate\Config\Repository::class);
                /** @var string[] $config */
                $config = $configRepository->get('media.mime_types');

                return new ImageHandlerService($config);
            },
        );
        $this->app->singleton(Storage::class, function (Application $app): \App\Media\Storage\Storage {
            $configRepository = $app->make(\Illuminate\Config\Repository::class);
            /** @var class-string<Handler>[] $config */
            $config = $configRepository->get('media.handlers');

            return new Storage($config, $app, $app->make(\Illuminate\Filesystem\FilesystemManager::class));
        });
        $this->app->singleton(Matcher::class, function (Application $app): \App\Media\Matcher\Matcher {
            $configRepository = $app->make(\Illuminate\Config\Repository::class);
            /** @var class-string<Source>[] $config */
            $config = $configRepository->get('media.matchers');

            return new Matcher($config, $app, $app->make(\Illuminate\Log\LogManager::class));
        });
        $this->app->singleton(Scanner::class, function (Application $app): \App\Media\Scanner\Scanner {
            $configRepository = $app->make(\Illuminate\Config\Repository::class);
            /** @var class-string<Processor>[] $processors */
            $processors = $configRepository->get('media.processors');

            return new Scanner(
                $processors,
                $app->make(Storage::class),
                $app,
                $app->make(\Illuminate\Log\LogManager::class),
                $app->make(\Illuminate\Filesystem\FilesystemManager::class),
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
