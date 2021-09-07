<?php

namespace Tonysm\GlobalId\URI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class GID
{
    /**
     * Regex Pattern to allow only alphanum values.
     *
     * @var string
     */
    public const ALPHANUMS_ONLY = '#^[a-zA-Z0-9\-]*$#';

    /**
     * The scheme used for the Global Id URI.
     *
     * @var string
     */
    public const SCHEME = 'gid';

    /**
     * Parses a global ID URI string and creates an instance of the GID class.
     *
     * @param string|null $gid
     * @return GID
     * @throws GIDParsingException
     */
    public static function parse($gid): self
    {
        if ($gid === null) {
            throw GIDParsingException::cannotBeNull();
        }

        $parsed = parse_url($gid);

        if (false === $parsed || ! isset($parsed['scheme']) || $parsed['scheme'] !== static::SCHEME) {
            throw GIDParsingException::badUri();
        }

        if (! isset($parsed['host']) || ! preg_match(static::ALPHANUMS_ONLY, $parsed['host'])) {
            throw GIDParsingException::invalidHost();
        }

        $explodedPath = explode('/', trim($parsed['path'], '/'));

        $modelName = array_shift($explodedPath);

        if (empty($modelName)) {
            throw GIDParsingException::missingPath();
        }

        if (count($explodedPath) !== 1) {
            throw GIDParsingException::missingModelId();
        }

        $modelId = array_shift($explodedPath);

        $params = [];

        if ($parsed['query'] ?? false) {
            parse_str($parsed['query'], $params);
        }

        return new self(
            $parsed['host'],
            urldecode($modelName),
            urldecode($modelId),
            $params,
        );
    }

    /**
     * Checks if the app name is valid.
     *
     * @param string $app
     * @return string
     */
    public static function validateAppName(string $app): string
    {
        return static::parse("gid://{$app}/Model/1")->app;
    }

    /**
     * Creates an instance of the GID class for a specific model.
     *
     * @param string $app
     * @param Model $model
     * @param array $params
     * @return GID
     */
    public static function create(string $app, $model, array $params = []): self
    {
        $modelClass = $model instanceof Model
            ? $model->getMorphClass()
            : $model::class;

        return new self($app, $modelClass, (string) $model->getKey(), $params);
    }

    /**
     * Creates an instance of the GID class based on an array of arguments.
     *
     * @param array $args
     * @return self
     */
    public static function build($args): self
    {
        return new self(
            $args[0] ?? $args['app'],
            $args[1] ?? $args['model_name'],
            $args[2] ?? $args['model_id'],
            static::encodeWwwParams($args[3] ?? $args['params'] ?? []),
        );
    }

    /**
     * Encodes the query strings (params) to be added to the GID URI.
     * We're not supporting multi value params.
     *
     * @param array $params
     * @return array
     */
    protected static function encodeWwwParams(array $params = []): array
    {
        // Multi value params aren't supported. When any param is a
        // array, we'll return the last item. This is here so we
        // can get the samed feature parity as the Rails gem.

        foreach ($params as $key => $val) {
            $params[$key] = is_array($val) ? Arr::last($val) : $val;
        }

        return $params;
    }

    /**
     * Creates an instance of the GID class.
     *
     * @param string $app
     * @param string $modelName
     * @param string $modelId
     * @param array $params
     */
    public function __construct(
        public string $app,
        public string $modelName,
        public string $modelId,
        public array $params = [],
    ) {
    }

    /**
     * Determines if two instances of the GID class can be considered the same.
     *
     * @param GID $gid
     * @return bool
     */
    public function equalsTo(GID $gid): bool
    {
        return $this->toString() === $gid->toString();
    }

    /**
     * Gets a specific query string (param) from the URI.
     *
     * @param string $key
     * @return string|null
     */
    public function getParam($key)
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Converts the GID to a URI string.
     *
     * @return string
     */
    public function toString(): string
    {
        return trim(sprintf(
            'gid://%s/%s/%s?%s',
            $this->app,
            urlencode($this->modelName),
            urlencode($this->modelId),
            http_build_query($this->params),
        ), '?');
    }

    /**
     * Converts the GID to a URI string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
