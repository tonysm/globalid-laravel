<?php

namespace Tonysm\GlobalId\Locators;

use Illuminate\Support\Collection;
use Tonysm\GlobalId\Exceptions\LocatorException;
use Tonysm\GlobalId\GlobalId;

class BaseLocator implements LocatorContract
{
    public function locate(GlobalId $globalId)
    {
        $model = $globalId->modelName();

        return $model::find($globalId->modelId());
    }

    public function locateMany(Collection $globalIds): Collection
    {
        $idsByModel = $globalIds->mapToGroups(fn (GlobalId $globalId) => [$globalId->modelName() => $globalId->modelId()]);

        $loadedByModel = $idsByModel->map(fn ($ids, $model) => $model::findMany($ids));

        return $globalIds->map(function (GlobalId $gid) use ($loadedByModel) {
            $found = $loadedByModel[$gid->modelName()]->find($gid->modelId());

            if (! $found) {
                throw LocatorException::modelNotFoundFromLocateMany();
            }

            return $found;
        });
    }
}
