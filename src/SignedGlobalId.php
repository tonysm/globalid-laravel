<?php

namespace Tonysm\GlobalId;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Arr;
use Tonysm\GlobalId\Exceptions\SignedGlobalIdException;
use Tonysm\GlobalId\URI\GID;

class SignedGlobalId extends GlobalId
{
    public const DEFAULT_PURPOSE = 'default';
    public const ITERATIONS = 100;
    public const KEY_SIZE = 64;

    public static ?Closure $expiresInResolver = null;

    private Verifier $verifier;
    private string $purpose;
    private ?CarbonInterface $expiresAt = null;

    private string $cachedSgid;

    public static function useExpirationResolver(?Closure $expiresInResolver)
    {
        static::$expiresInResolver = $expiresInResolver;
    }

    public static function parse($gid, array $options = []): ?static
    {
        return parent::parse(static::verify($gid, $options), $options);
    }

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

    private static function expiresIn(): ?CarbonInterface
    {
        if (static::$expiresInResolver) {
            return call_user_func(static::$expiresInResolver);
        }

        return now()->addMonth();
    }

    private static function verify($sgid, array $options = [])
    {
        $metadata = static::pickVerifier($options)->verify($sgid);

        try {
            throw_if(static::hasExpired($metadata['expires_at']), SignedGlobalIdException::expired());
        } catch (SignedGlobalIdException) {
            return null;
        }

        if ($metadata['purpose'] !== static::pickPurpose($options)) {
            return null;
        }

        return $metadata['sgid'];
    }

    private static function hasExpired($expiredAt): bool
    {
        if (! $expiredAt) {
            return false;
        }

        return now()->parse($expiredAt)->lt(now());
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
        if (array_key_exists('expires_at', $options)) {
            return $options['expires_at'];
        }

        return static::expiresIn();
    }

    public function toString(): string
    {
        return $this->cachedSgid ??= $this->verifier->generate($this->toArray());
    }

    public function toParam(): string
    {
        return $this->toString();
    }

    public function equalsTo(GlobalId $globalId): bool
    {
        if (! $globalId instanceof SignedGlobalId) {
            return parent::equalsTo($globalId);
        }

        return $this->gid->equalsTo($globalId->gid) && $globalId->purpose === $this->purpose;
    }

    public function expiresAt(): ?CarbonInterface
    {
        return $this->expiresAt?->copy();
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
