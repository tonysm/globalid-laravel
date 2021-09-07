<?php

namespace Tonysm\GlobalId;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tonysm\GlobalId\Exceptions\GlobalIdException;
use Tonysm\GlobalId\Facades\Locator;
use Tonysm\GlobalId\URI\GID;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalId
{
    /**
     * The app name that should be used as the default app name when creating
     * new GlobalIds. This can be overwritten on each specific GlobalId
     * creation, or globally using the `useAppName` static method.
     *
     * @var string
     */
    protected static $app;

    /**
     * The GID instance for a GlobalId.
     *
     * @var GID
     */
    protected GID $gid;

    /**
     * Sets the default app name to be used when creating new
     * GlobalIds without specific app names.
     *
     * @param string $app
     * @return void
     */
    public static function useAppName(string $app): void
    {
        static::$app = GID::validateAppName($app);
    }

    /**
     * Returns the default app name. When the default app name
     * is not set by package consumers, it will default to
     * the app name defined in the Laravel application.
     *
     * @return string
     */
    public static function appName(): string
    {
        if (! static::$app ?? false) {
            static::useAppName(Str::slug(config('globalid.app_name')));
        }

        return static::$app;
    }

    /**
     * Creates a new GlobalId instance for a specific model.
     *
     * The options can be:
     *  - `app`: To force a specific app name.
     *  - `verifier`: To set a custom verifier.
     *  - `for`:  To define the purpose of the Signed Global ID.
     *  - `expires_at`: To define the expiration date for the Signed Global ID.
     *  - Anything else will be kept as param (query strings) in the GlobalId URI.
     *
     * @param Model $model
     * @param array $options
     */
    public static function create($model, array $options = []): static
    {
        $app = Arr::get($options, 'app', static::appName());

        if (! $app) {
            throw GlobalIdException::missingApp();
        }

        return new static(
            GID::create($app, $model, Arr::except($options, ['app', 'verifier', 'for'])),
            $options,
        );
    }

    /**
     * Parses a GlobalId URI string or base64 encoded version of
     * it into an instance of the GlobalId class, which can be
     * used to locate the entity this GlobalId refers to.
     *
     * @param GlobalId|string $gid
     * @param array $options
     * @return GlobalId|null
     */
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

    /**
     * Parses a Base64 encoded version of the Global ID.
     *
     * @param string $gid
     * @param array $options
     * @return GlobalId|null
     */
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

    /**
     * We remove the equal signs `=` at the end of the Base64 string for
     * the GlobalId. We're adding them back here so we can decode it.
     *
     * @param string $gid
     * @return string
     */
    protected static function repadGid(string $gid): string
    {
        // Adding back the removed == signs at the end of the base64 encoded string.
        $paddingCount = strlen($gid) % 4 == 0 ? 0 : 4 - (strlen($gid) % 4);

        return str_pad($gid, $paddingCount, '=', STR_PAD_RIGHT);
    }

    /**
     * Parses and locates the entity a GlobalId refers to.
     *
     * @param GlobalId|string $gid
     * @param array $options
     * @return mixed The entity the Global ID refers to
     */
    public static function find($gid, array $options = [])
    {
        return static::parse($gid, $options)?->locate($options);
    }

    /**
     * Creates a new instance.
     *
     * @param GID|string $gid
     * @param array $options
     */
    public function __construct($gid, array $options = [])
    {
        $this->gid = $gid instanceof GID
            ? $gid
            : GID::parse($gid);
    }

    /**
     * Locates the entity this GlobalId refers to.
     *
     * @param array $options
     * @return mixed
     */
    public function locate(array $options = [])
    {
        return Locator::locate($this, $options);
    }

    /**
     * The app name on the Global ID URI.
     *
     * @return string
     */
    public function app(): string
    {
        return $this->gid->app;
    }

    /**
     * The model name encoded in the Global ID.
     *
     * @return string
     */
    public function modelName(): string
    {
        return $this->gid->modelName;
    }

    /**
     * The model class name. It handles classes using custom polymorphic types.
     *
     * @return string
     */
    public function modelClass(): string
    {
        $modelName = $this->modelName();

        return Relation::getMorphedModel($modelName) ?: $modelName;
    }

    /**
     * The model ID encoded in the Global ID.
     *
     * @return string
     */
    public function modelId(): string
    {
        return $this->gid->modelId;
    }

    /**
     * Checks if two GlobalIds can be considered equal.
     *
     * @return bool
     */
    public function equalsTo(GlobalId $globalId): bool
    {
        return $this->gid->equalsTo($globalId->gid);
    }

    /**
     * Gets a param (query string) from the Global ID URI.
     *
     * @param string $key
     * @return string|null
     */
    public function getParam($key)
    {
        return $this->gid->getParam($key);
    }

    /**
     * Converts the Global ID to the URI string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->gid->toString();
    }

    /**
     * Converts the Global ID URI string to base64 encoded (without the
     * ending equal signs - which will be added later when parsing).
     *
     * @return string
     */
    public function toParam(): string
    {
        // Remove any = sign at the end of the base64 string. We'll remove it back when parsing.
        return preg_replace('/=+$/', '', base64_encode($this->toString()));
    }
}
