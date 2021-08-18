<?php

namespace Tonysm\GlobalId;

use Illuminate\Database\Eloquent\Model;
use Tonysm\GlobalId\URI\GID;
use Facades\Tonysm\GlobalId\Locator;
use Illuminate\Support\Arr;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalId
{
    private static $app;

    private GID $gid;

    public static function useAppName(string $app): void
    {
        static::$app = GID::validateAppName($app);
    }

    public static function create(Model $model, array $options = []): static
    {
        $app = Arr::get($options, 'app', static::$app);

        if (! $app) {
            throw GlobalIdException::missingApp();
        }

        return new static(GID::create($app, $model, Arr::except($options, ['app'])));
    }

    public static function parse($gid): static
    {
        try {
            return $gid instanceof static
                ? $gid
                : new static($gid);
        } catch (GIDParsingException) {
            return static::parseEncoded($gid);
        }
    }

    private static function parseEncoded(string $gid): static
    {
        return new static(base64_decode(static::repadGid($gid)));
    }

    private static function repadGid(string $gid): string
    {
        // Adding back the removed == signs at the end of the base64 encoded string.
        $paddingCount = strlen($gid) % 4 == 0 ? 0 : 4 - (strlen($gid) % 4);

        return str_pad($gid, $paddingCount, '=', STR_PAD_RIGHT);
    }

    public static function find($gid)
    {
        return static::parse($gid)->locate();
    }

    public function __construct($gid)
    {
        $this->gid = $gid instanceof GID
            ? $gid
            : GID::parse($gid);
    }

    public function locate($only = null)
    {
        if (! $this->canFind($only)) {
            return null;
        }

        $locator = fn (GlobalId $globalId, $only = null) => Locator::locate($globalId, only: $only);

        return $locator($this, $only);
    }

    private function canFind($only = null): bool
    {
        if (! $only) {
            return true;
        }

        return ! is_null(collect($only)->first(fn ($onlyClass) => (
            $this->modelName() === $onlyClass
            || is_subclass_of($this->modelName(), $onlyClass)
        )));
    }

    public function modelName(): string
    {
        return $this->gid->modelName;
    }

    public function modelId(): string
    {
        return $this->gid->modelId;
    }

    public function equalsTo(GlobalId $globalId): bool
    {
        return $this->gid->equalsTo($globalId->gid);
    }

    public function getParam($key)
    {
        return $this->gid->getParam($key);
    }

    public function toString(): string
    {
        return $this->gid->toString();
    }

    public function toParam(): string
    {
        // Remove any = sign at the end of the base64 string. We'll remove it back when parsing.

        return preg_replace('/=+$/', '', base64_encode($this->toString()));
    }
}
