<?php

namespace Tonysm\GlobalId\URI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GID
{
    public static function parse(string $gid): self
    {
        $parsed = parse_url(urldecode($gid));

        $str = Str::of($parsed['path']);

        $modelName = (string) $str->after('/')->beforeLast('/');
        $modelId = (string) $str->afterLast('/');

        return new self(
            $parsed['host'],
            $modelName,
            $modelId,
        );
    }

    public static function create(string $app, Model $model): self
    {
        return new self($app, $model::class, (string) $model->getKey());
    }

    public static function build($args): self
    {
        return new self(
            $args[0] ?? $args['app'],
            $args[1] ?? $args['model_name'],
            $args[2] ?? $args['model_id'],
            $args[3] ?? $args['params'] ?? [],
        );
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

    public function toString(): string
    {
        return urlencode(sprintf(
            'gid://%s/%s/%s',
            $this->app,
            $this->modelName,
            $this->modelId,
        ));
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
