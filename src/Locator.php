<?php

namespace Tonysm\GlobalId;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tonysm\GlobalId\Exceptions\GlobalIdException;
use Tonysm\GlobalId\Locators\BaseLocator;
use Tonysm\GlobalId\Locators\LocatorContract;
use Tonysm\GlobalId\URI\GID;
use Tonysm\GlobalId\URI\GIDParsingException;

class Locator
{
    /**
     * The list of locators for each app (the host in the Global ID URI string).
     *
     * @var array<string, LocatorContract>
     */
    private array $locators = [];

    /**
     * Configures a Locator for a specific app name.
     *
     * @param string $app
     * @param LocatorContract|Closure|string $locator Can be an instance of the LocatorContract, a closure or a string which can be the class name.
     * @return static
     */
    public function use($app, $locator = null): static
    {
        try {
            GID::validateAppName($app);
        } catch (GIDParsingException $e) {
            throw GlobalIdException::invalidApp($app, $e);
        }

        $this->locators[$this->normalizeApp($app)] = $this->resolve($locator);

        return $this;
    }

    /**
     * Locates the instance the Global ID refers to.
     *
     * @param GlobalID|string $gid
     * @param array $options
     * @return mixed
     */
    public function locate($gid, array $options = [])
    {
        if (($gid = GlobalId::parse($gid, $options)) && $this->canFind($gid->modelClass(), $options)) {
            return $this->locatorFor($gid)->locate($gid);
        }

        return null;
    }

    /**
     * Locates multiple Global ID instances at the same time.
     *
     * @param array<GlobalId|string>|Collection $gids
     * @param array $options
     * @return Collection
     */
    public function locateMany($gids, array $options = []): Collection
    {
        if ($allowedGlobalIds = $this->parseAllowed($gids, $options)) {
            return $this->locatorFor($allowedGlobalIds->first())->locateMany($allowedGlobalIds, $options);
        }

        return collect();
    }

    /**
     * Locates a Signed Global ID.
     *
     * @param SignedGlobalId|string $sgid
     * @param array $options
     * @return mixed
     */
    public function locateSigned($sgid, array $options = [])
    {
        return SignedGlobalId::find($sgid, $options);
    }

    /**
     * Locates multiple Signed Global IDs at the same time.
     *
     * @param array<SignedGlobalId|string>|Collection $sgids
     * @param array $options
     * @return Collection
     */
    public function locateManySigned($sgids, array $options = []): Collection
    {
        return $this->locateMany(
            collect($sgids)->map(fn ($sgid) => (
                SignedGlobalId::parse($sgid, Arr::only($options, 'for'))
            ))->filter()->values(),
            $options
        );
    }

    /**
     * Returns only the allowed Global ID instances.
     *
     * @param array<GlobalId|string>|Collection $globalIds
     * @param array $options
     * @return Collection
     */
    private function parseAllowed($globalIds, array $options = []): Collection
    {
        return collect($globalIds)
            ->map(fn ($globalId) => GlobalId::parse($globalId))
            ->filter(fn (GlobalId $globalId) => $this->canFind($globalId->modelClass(), $options))
            ->values();
    }

    /**
     * Gets the Locator based on the app name defined in the GlobalId.
     *
     * @param GlobalId $globalId
     * @return LocatorContract
     */
    private function locatorFor(GlobalId $globalId): LocatorContract
    {
        return $this->locators[$this->normalizeApp($globalId->app())] ?? new BaseLocator();
    }

    /**
     * Determines if the model class can be located (based on the `only` option).
     *
     * @param string $modelClass
     * @param array $options
     * @return bool
     */
    private function canFind($modelClass, array $options = []): bool
    {
        if (! array_key_exists('only', $options)) {
            return true;
        }

        return ! is_null(collect($options['only'])->first(fn ($onlyClass) => (
            $modelClass === $onlyClass
            || is_subclass_of($modelClass, $onlyClass)
        )));
    }

    /**
     * Normalizes the app name.
     *
     * @param string $app
     * @return string
     */
    private function normalizeApp(string $app): string
    {
        return strtolower($app);
    }

    /**
     * Resolves the Locator instance.
     *
     * @param LocatorContract|Closure|string $locator
     * @return LocatorContract
     */
    private function resolve($locator)
    {
        if (is_null($locator)) {
            return new BaseLocator();
        }

        $locator = value($locator);

        if (is_string($locator)) {
            return resolve($locator);
        }

        return $locator;
    }
}
