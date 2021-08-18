<?php

namespace Tonysm\GlobalId\URI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class GID
{
    public const SCHEME = 'gid';

    public static function parse(string $gid): self
    {
        $parsed = parse_url($gid);

        if (false === $parsed || ! isset($parsed['scheme']) || $parsed['scheme'] !== static::SCHEME) {
            throw GIDParsingException::badUri();
        }

        if (! isset($parsed['host']) || ! ctype_alnum($parsed['host'])) {
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

    public static function validateAppName(string $app): string
    {
        return static::parse("gid://{$app}/Model/1")->app;
    }

    public static function create(string $app, Model $model, array $params = []): self
    {
        return new self($app, $model::class, (string) $model->getKey(), $params);
    }

    public static function build($args): self
    {
        return new self(
            $args[0] ?? $args['app'],
            $args[1] ?? $args['model_name'],
            $args[2] ?? $args['model_id'],
            static::encodeWwwParams($args[3] ?? $args['params'] ?? []),
        );
    }

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

    public function __construct(
        public string $app,
        public string $modelName,
        public string $modelId,
        public array $params = [],
    ) {
    }

    public function equalsTo(GID $gid): bool
    {
        return $this->toString() === $gid->toString();
    }

    public function getParam($key)
    {
        return $this->params[$key] ?? null;
    }

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

    public function __toString(): string
    {
        return $this->toString();
    }
}
