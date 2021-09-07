<?php

namespace Tonysm\GlobalId\Locators;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tonysm\GlobalId\Exceptions\LocatorException;
use Tonysm\GlobalId\GlobalId;

class BaseLocator implements LocatorContract
{
    /**
     * Locates the entity the GlobalId refers to.
     *
     * @param GlobalId $globalId
     * @return mixed
     */
    public function locate(GlobalId $globalId)
    {
        $model = $globalId->modelClass();

        return $model::find($globalId->modelId());
    }

    /**
     * Locates the intances of the given GlobalIds refers to.
     *
     * The options can be:
     *  - `ignore_missing` Which should be a boolean that indicates if missing references are allowed or if it should throw an exception.
     *
     * @param Collection $globalIds
     * @param array $options
     * @return Collection
     * @throws LocatorException
     */
    public function locateMany(Collection $globalIds, array $options = []): Collection
    {
        $idsByModel = $globalIds->mapToGroups(fn (GlobalId $globalId) => [$globalId->modelClass() => $globalId->modelId()]);

        $loadedByModel = $idsByModel->map(fn ($ids, $model) => $model::findMany($ids));

        return $globalIds->map(function (GlobalId $gid) use ($loadedByModel, $options) {
            $found = $loadedByModel[$gid->modelClass()]->find($gid->modelId());

            if (! $found && ! Arr::get($options, 'ignore_missing', false)) {
                throw LocatorException::modelNotFoundFromLocateMany();
            }

            return $found;
        });
    }
}
