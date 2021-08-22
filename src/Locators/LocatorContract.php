<?php

namespace Tonysm\GlobalId\Locators;

use Illuminate\Support\Collection;
use Tonysm\GlobalId\GlobalId;

interface LocatorContract
{
    /**
     * Returns the entry corresponding to that GID or null when it's gone.
     *
     * @param GlobalId $globalId
     * @return mixed
     */
    public function locate(GlobalId $globalId);

    /**
     * Get a list of global IDs and perform your find manys. In the `$options` array
     * you may receive a `ignore_missing` boolean flag which you can use to decide
     * if you should fail or return null when a GID cannot be found anymore.
     *
     * @param Collection<GlobalId> $globalIds
     * @param array $options
     * @return Collection The entries for the given GlobalIds. Make sure the Collection has a `find($id)` method on it.
     */
    public function locateMany(Collection $globalIds, array $options = []): Collection;
}
