<?php

namespace Tonysm\GlobalId;

class Locator
{
    public function locate(GlobalId $globalId, $only = null)
    {
        $model = $globalId->modelName();

        return $model::find($globalId->modelId());
    }
}
