<?php

namespace Tonysm\GlobalId\Tests\Stubs;

use Illuminate\Support\Collection;

class CustomCollection extends Collection
{
    public function find($id)
    {
        return $this->first(fn ($model) => $model && $model->getKey() == $id);
    }
}
