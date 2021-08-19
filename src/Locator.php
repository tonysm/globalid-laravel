<?php

namespace Tonysm\GlobalId;

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
            throw GlobalIdException::invalidApp($app ,$e);
        }

        $this->locators[$this->normalizeApp($app)] = $this->resolve($locator);

        return $this;
    }

    public function locate($globalId, $only = null)
    {
        if (($globalId = GlobalId::parse($globalId)) && $this->canFind($globalId->modelName(), only: $only)) {
            return $this->locatorFor($globalId)->locate($globalId);
        }

        return null;
    }

    public function locateMany($globalIds, $only = null): Collection
    {
        if ($allowedGlobalIds = $this->parseAllowed($globalIds, $only)) {
            return $this->locatorFor($allowedGlobalIds->first())->locateMany($allowedGlobalIds);
        }

        return collect();
    }

    private function parseAllowed($globalIds, $only = null): Collection
    {
        return collect($globalIds)
            ->map(fn ($globalId) => GlobalId::parse($globalId))
            ->filter(fn (GlobalId $globalId) => $this->canFind($globalId->modelName(), $only))
            ->values();
    }

    private function locatorFor(GlobalId $globalId): LocatorContract
    {
        return $this->locators[$this->normalizeApp($globalId->app())] ?? new BaseLocator;
    }

    private function canFind($modelClass, $only = null): bool
    {
        if (! $only) {
            return true;
        }

        return ! is_null(collect($only)->first(fn ($onlyClass) => (
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
            return new BaseLocator;
        }

        $locator = value($locator);

        if (is_string($locator)) {
            return resolve($locator);
        }

        return $locator;
    }
}
