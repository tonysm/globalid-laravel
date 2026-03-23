<?php

namespace Tonysm\GlobalId;

use Closure;
use Tonysm\GlobalId\Exceptions\InvalidSignatureException;

class Verifier
{
    /**
     * The cached key. We cache it so subsequent signing calls don't have to recompute the key.
     */
    private string $cachedKey;

    /**
     * The cached previous keys. Resolved lazily only when the current key fails verification.
     */
    private ?array $cachedPreviousKeys = null;

    /**
     * Creates an instance of the Verifier class.
     */
    public function __construct(private Closure $keyResolver, private string $salt, private ?Closure $previousKeysResolver = null) {}

    /**
     * Verifies the Signed Global Id string matches with the signature.
     *
     * @return array Returns the signed global Id attributes when the verification works, otherwise it throws an exception.
     *
     * @throws InvalidSignatureException
     */
    public function verify(string $sgid): array
    {
        $split = explode('--', $sgid);

        if (count($split) !== 2) {
            throw new InvalidSignatureException;
        }

        [$encoded, $signature] = $split;

        if ($this->hashWithKey($encoded, $this->key()) === $signature) {
            return json_decode(base64_decode($encoded), true);
        }

        foreach ($this->previousKeys() as $previousKey) {
            if ($this->hashWithKey($encoded, $previousKey) === $signature) {
                return json_decode(base64_decode($encoded), true);
            }
        }

        throw new InvalidSignatureException;
    }

    /**
     * Encodes the Signed Global Id attributes and appends the signature to it.
     *
     * @param  array  $data
     */
    public function generate($data): string
    {
        $encoded = base64_encode(json_encode($data));

        $signature = $this->hash($encoded);

        return "{$encoded}--{$signature}";
    }

    /**
     * Generates the signature of an encoded string using a specific key.
     */
    private function hashWithKey(string $encoded, string $key): string
    {
        return hash_hmac('sha256', $encoded, $key.$this->salt);
    }

    /**
     * Generates the signature of an encoded string using the current key.
     */
    private function hash(string $encoded): string
    {
        return $this->hashWithKey($encoded, $this->key());
    }

    /**
     * Gets the key used to sign the data.
     */
    private function key(): string
    {
        return $this->cachedKey ??= call_user_func($this->keyResolver);
    }

    /**
     * Gets the previous keys used for verification fallback.
     *
     * @return string[]
     */
    private function previousKeys(): array
    {
        if ($this->previousKeysResolver === null) {
            return [];
        }

        return $this->cachedPreviousKeys ??= call_user_func($this->previousKeysResolver);
    }
}
