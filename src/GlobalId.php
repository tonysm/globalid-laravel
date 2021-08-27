<?php

namespace Tonysm\GlobalId;

use Tonysm\GlobalId\Facades\Locator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Tonysm\GlobalId\Exceptions\GlobalIdException;
use Tonysm\GlobalId\URI\GID;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalId
{
    public static $app;

    protected GID $gid;

    public static function useAppName(string $app): void
    {
        static::$app = GID::validateAppName($app);
    }

    /**
     * @param Model $model
     * @param array $options
     */
    public static function create($model, array $options = []): static
    {
        $app = Arr::get($options, 'app', static::$app);

        if (! $app) {
            throw GlobalIdException::missingApp();
        }

        return new static(
            GID::create($app, $model, Arr::except($options, ['app', 'verifier', 'for'])),
            $options,
        );
    }

    public static function parse($gid, array $options = []): ?static
    {
        try {
            return $gid instanceof static
                ? $gid
                : new static($gid, $options);
        } catch (GIDParsingException) {
            return static::parseEncoded($gid, $options);
        }
    }

    protected static function parseEncoded($gid, array $options = []): ?static
    {
        if ($gid === null) {
            return null;
        }

        try {
            return new static(base64_decode(static::repadGid($gid)), $options);
        } catch (GIDParsingException) {
            return null;
        }
    }

    protected static function repadGid(string $gid): string
    {
        // Adding back the removed == signs at the end of the base64 encoded string.
        $paddingCount = strlen($gid) % 4 == 0 ? 0 : 4 - (strlen($gid) % 4);

        return str_pad($gid, $paddingCount, '=', STR_PAD_RIGHT);
    }

    public static function find($gid, array $options = [])
    {
        return static::parse($gid, $options)?->locate($options);
    }

    public function __construct($gid, array $options = [])
    {
        $this->gid = $gid instanceof GID
            ? $gid
            : GID::parse($gid);
    }

    public function locate(array $options = [])
    {
        return Locator::locate($this, $options);
    }

    public function app(): string
    {
        return $this->gid->app;
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
