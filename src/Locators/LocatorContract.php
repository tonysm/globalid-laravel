<?php

namespace Tonysm\GlobalId\Locators;

use Illuminate\Support\Collection;
use Tonysm\GlobalId\GlobalId;

interface LocatorContract
{
    public function locate(GlobalId $globalId);
    public function locateMany(Collection $globalIds): Collection;
}
