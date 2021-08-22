<?php

namespace Tonysm\GlobalId;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tonysm\GlobalId\Locators\BaseLocator;
use Tonysm\GlobalId\Locators\LocatorContract;
use Tonysm\GlobalId\URI\GID;
use Tonysm\GlobalId\URI\GIDParsingException;

class Locator
{
    private array $locators = [];

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

    public function locate($gid, array $options = [])
    {
        if (($gid = GlobalId::parse($gid, $options)) && $this->canFind($gid->modelName(), $options)) {
            return $this->locatorFor($gid)->locate($gid);
        }

        return null;
    }

    public function locateMany($gids, array $options = []): Collection
    {
        if ($allowedGlobalIds = $this->parseAllowed($gids, $options)) {
            return $this->locatorFor($allowedGlobalIds->first())->locateMany($allowedGlobalIds);
        }

        return collect();
    }

    public function locateSigned($sgid, array $options = [])
    {
        return SignedGlobalId::find($sgid, $options);
    }

    public function locateManySigned($sgids, array $options = []): Collection
    {
        return $this->locateMany(
            collect($sgids)->map(fn ($sgid) => (
                SignedGlobalId::parse($sgid, Arr::only($options, 'for'))
            ))->filter()->values(),
            $options
        );
    }

    private function parseAllowed($globalIds, array $options = []): Collection
    {
        return collect($globalIds)
            ->map(fn ($globalId) => GlobalId::parse($globalId))
            ->filter(fn (GlobalId $globalId) => $this->canFind($globalId->modelName(), $options))
            ->values();
    }

    private function locatorFor(GlobalId $globalId): LocatorContract
    {
        return $this->locators[$this->normalizeApp($globalId->app())] ?? new BaseLocator();
    }

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

    private function normalizeApp(string $app): string
    {
        return strtolower($app);
    }

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
