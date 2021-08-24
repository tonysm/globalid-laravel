<?php

namespace Tonysm\GlobalId\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void use(string $app, ?\Tonysm\GlobalId\Locators\LocatorContract $locator = null)
 * @method static mixed locate(string|\Tonysm\GlobalId\GlobalId $gid, array $options = [])
 * @method static \Illuminate\Support\Collection locateMany(array $gids, array $options = [])
 * @method static mixed locateSigned(string|\Tonysm\GlobalId\SignedGlobalId $sgid, array $options = [])
 * @method static \Illuminate\Support\Collection locateManySigned(array $sgids, array $options = [])
 *
 * @see \Tonysm\GlobalId\Locator
 */
class Locator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Tonysm\GlobalId\Locator::class;
    }
}
