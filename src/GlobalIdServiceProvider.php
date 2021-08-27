<?php

namespace Tonysm\GlobalId;

use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GlobalIdServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->hasConfigFile('globalid')
            ->name('globalid-laravel');

        $this->app->scoped(Locator::class);
    }

    public function boot()
    {
        parent::boot();

        GlobalId::useAppName(Str::slug(config('app.name')));
    }
}
