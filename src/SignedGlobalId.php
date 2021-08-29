<?php

namespace Tonysm\GlobalId;

use Carbon\CarbonInterface;
use Closure;
use Tonysm\GlobalId\Exceptions\InvalidSignatureException;
use Tonysm\GlobalId\Exceptions\SignedGlobalIdException;

class SignedGlobalId extends GlobalId
{
    /**
     * The default purpose of Signed Global Ids.
     *
     * @var string
     */
    public const DEFAULT_PURPOSE = 'default';

    /**
     * How many iteractions will be used to generate the signing key.
     *
     * @var int
     */
    public const ITERATIONS = 100;

    /**
     * The length of the key.
     *
     * @var int
     */
    public const KEY_SIZE = 64;

    /**
     * The expiration time resolver. When this exists, the closure will
     * be invoked every time a Signed Global Id is created (at least
     * for those where the `expires_at` is not specified as null).
     *
     * @var Closure|null
     */
    public static ?Closure $expiresInResolver = null;

    /**
     * The Verifier used to, well, verify the Signed Global Ids.
     *
     * @var Verifier
     */
    private Verifier $verifier;

    /**
     * The purpose of the Signed Global Id.
     *
     * @var string
     */
    private string $purpose;

    /**
     * The expiration date for th Signed Global Id.
     *
     * @var CarbonInterface|null
     */
    private ?CarbonInterface $expiresAt = null;

    /**
     * Checks the Signed Global Id. We cache it so we don't
     * have to hash it again on subsequent calls to the
     * `toString()` method.
     *
     * @param string $cachedSgid
     */
    private string $cachedSgid;

    /**
     * Sets the expiration date resolver.
     *
     * @param Closure|null $expiresInResolver
     * @return void
     */
    public static function useExpirationResolver(?Closure $expiresInResolver): void
    {
        static::$expiresInResolver = $expiresInResolver;
    }

    /**
     * Parses a Signed Global Id string into an instance of the SignedGlobalId class.
     *
     * The options can be:
     *  - `app`: To force a specific app name.
     *  - `verifier`: To set a custom verifier.
     *  - `for`:  To define the purpose of the Signed Global ID.
     *  - `expires_at`: To define the expiration date for the Signed Global ID.
     *  - Anything else will be kept as param (query strings) in the GlobalId URI.
     *
     * @param SignedGlobalId|string $gid
     * @param array $options
     * @return SignedGlobalId|null
     */
    public static function parse($gid, array $options = []): ?static
    {
        return parent::parse(static::verify($gid, $options), $options);
    }

    /**
     * Picks the verifier from the options or returns the default one.
     *
     * @param array $options
     * @return Verifier
     */
    private static function pickVerifier(array $options = []): Verifier
    {
        if ($verifier = $options['verifier'] ?? false) {
            return $verifier;
        }

        return static::configuredVerifier();
    }

    /**
     * Returns the default Verifier.
     *
     * @return Verifier
     */
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

    /**
     * Picks the purpose from the options array or returns the default one.
     *
     * @param array $options
     * @return string
     */
    private static function pickPurpose(array $options = []): string
    {
        return $options['for'] ?? static::DEFAULT_PURPOSE;
    }

    /**
     * Returns the expires in instance. Either the default one or using the resolver.
     *
     * @return CarbonInterface|null
     */
    private static function expiresIn(): ?CarbonInterface
    {
        if (static::$expiresInResolver) {
            return call_user_func(static::$expiresInResolver);
        }

        return now()->addMonth();
    }

    /**
     * Verifies the Signed Global Id.
     *
     * @param SignedGlobalId|string $sgid
     * @param array $options
     * @return string|null Returns the GID URI when it's verified, or null when it cannot be verified.
     */
    private static function verify($sgid, array $options = [])
    {
        try {
            $metadata = static::pickVerifier($options)->verify(
                $sgid instanceof SignedGlobalId ? $sgid->toString() : $sgid
            );

            throw_if(static::hasExpired($metadata['expires_at']), SignedGlobalIdException::expired());
        } catch (SignedGlobalIdException | InvalidSignatureException) {
            return null;
        }

        if ($metadata['purpose'] !== static::pickPurpose($options)) {
            return null;
        }

        return $metadata['sgid'];
    }

    /**
     * Determines if the Signed Global Id has expired.
     *
     * @param string|CarbonInterface|null
     * @return bool
     */
    private static function hasExpired($expiredAt): bool
    {
        if (! $expiredAt) {
            return false;
        }

        return now()->parse($expiredAt)->lt(now());
    }

    /**
     * Creates an instance of the Signed Global Id.
     *
     * @param SignedGlobalId|string $gid
     * @param array $options
     */
    public function __construct($gid, array $options = [])
    {
        parent::__construct($gid, $options);

        $this->verifier = static::pickVerifier($options);
        $this->purpose = static::pickPurpose($options);
        $this->expiresAt = $this->pickExpiration($options);
    }

    /**
     * Picks the expiration out of the options array, or returns the default one.
     *
     * @param array $options
     * @return CarbonInterface|null
     */
    public function pickExpiration(array $options = []): ?CarbonInterface
    {
        if (array_key_exists('expires_at', $options)) {
            return $options['expires_at'];
        }

        return static::expiresIn();
    }

    /**
     * Returns the Signed Global Id string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->cachedSgid ??= $this->verifier->generate($this->toArray());
    }

    /**
     * Returns the Signed Global Id string.
     *
     * @return string
     */
    public function toParam(): string
    {
        return $this->toString();
    }

    /**
     * Checks if two Signed Global Ids can be considered equals. Can also receive an instance of the GlobalId class.
     *
     * @param GlobalId $globalId
     * @return bool
     */
    public function equalsTo(GlobalId $globalId): bool
    {
        if (! $globalId instanceof SignedGlobalId) {
            return parent::equalsTo($globalId);
        }

        return $this->gid->equalsTo($globalId->gid) && $globalId->purpose === $this->purpose;
    }

    /**
     * Returns the expiration date Carbon instance when set, or null.
     *
     * @return CarbonInterface|null
     */
    public function expiresAt(): ?CarbonInterface
    {
        return $this->expiresAt?->copy();
    }

    /**
     * Returns the array of params to be encoded in the SGID.
     *
     * @return array
     */
    private function toArray(): array
    {
        return [
            'sgid' => $this->gid->toString(),
            'purpose' => $this->purpose,
            'expires_at' => $this->encodedExpiration(),
        ];
    }

    /**
     * Encodes the expiration date to string.
     *
     * @return string|null
     */
    private function encodedExpiration(): ?string
    {
        if ($this->expiresAt ?? false) {
            return $this->expiresAt->copy()->utc()->toIso8601ZuluString();
        }

        return null;
    }
}
