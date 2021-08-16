<?php

namespace Tonysm\GlobalId;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tonysm\GlobalId\GlobalId
 */
class GlobalIdFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'globalid-laravel';
    }
}
