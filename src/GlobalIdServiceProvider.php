<?php

namespace Tonysm\GlobalId;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tonysm\GlobalId\Commands\GlobalIdCommand;

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
            ->name('globalid-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_globalid-laravel_table')
            ->hasCommand(GlobalIdCommand::class);
    }
}
