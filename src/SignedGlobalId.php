<?php

namespace Tonysm\GlobalId;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Arr;
use Tonysm\GlobalId\URI\GID;

class SignedGlobalId extends GlobalId
{
    const DEFAULT_PURPOSE = 'default';
    const ITERATIONS = 100;
    const KEY_SIZE = 64;

    public static ?CarbonInterface $expiresIn;

    private Verifier $verifier;
    private string $purpose;
    private ?CarbonInterface $expiresAt = null;

    private string $cachedSgid;

    public static function create($model, array $options = []): static
    {
        if ($app = $options['app'] ?? GlobalId::$app) {
            $params = Arr::except($options, ['app', 'verifier', 'for']);
            return new static(GID::create($app, $model, $params), $options);
        }

        throw GlobalIdException::missingApp();
    }

    public static function pickVerifier(array $options = []): Verifier
    {
        if ($verifier = $options['verifier'] ?? false) {
            return $verifier;
        }

        return static::configuredVerifier();
    }

    private static function configuredVerifier()
    {
        return new Verifier(function () {
            $appKey = config('app.key');

            if (str_starts_with($appKey, 'base64:')) {
                $appKey = base64_decode(substr($appKey, 7));
            }

            return hash_pbkdf2(
                'sha256',
                $appKey,
                'signed_global_ids',
                static::ITERATIONS,
                static::KEY_SIZE,
            );
        }, salt: 'signed_global_ids');
    }

    public static function pickPurpose(array $options = [])
    {
        return $options['for'] ?? static::DEFAULT_PURPOSE;
    }

    public static function expiresIn(): ?CarbonInterface
    {
        // @TODO: allow consumers to specific the desired expiresIn globally.
        return now()->addMonth();
    }

    public function __construct($gid, array $options = [])
    {
        parent::__construct($gid, $options);

        $this->verifier = static::pickVerifier($options);
        $this->purpose = static::pickPurpose($options);
        $this->expiresAt = $this->pickExpiration($options);
    }

    public function pickExpiration(array $options = []): ?CarbonInterface
    {
        if ($options['expires_at'] ?? false) {
            return $options['expires_at'];
        }

        if ($expiresIn = $options['expires_in'] ?? static::expiresIn()) {
            return $expiresIn;
        }
    }

    public function toString(): string
    {
        return $this->cachedSgid ??= $this->verifier->generate($this->toArray());
    }

    public function toParam(): string
    {
        return $this->toString();
    }

    private function toArray(): array
    {
        return [
            'sgid' => $this->gid->toString(),
            'purpose' => $this->purpose,
            'expires_at' => $this->encodedExpiration(),
        ];
    }

    private function encodedExpiration(): ?string
    {
        if ($this->expiresAt ?? false) {
            return $this->expiresAt->copy()->utc()->toIso8601ZuluString();
        }

        return null;
    }
}
