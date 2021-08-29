<?php

namespace Tonysm\GlobalId;

use Closure;
use Tonysm\GlobalId\Exceptions\InvalidSignatureException;

class Verifier
{
    /**
     * The cached key. We cache it so subsequent signing calls don't have to recompute the key.
     *
     * @var string
     */
    private string $cachedKey;

    /**
     * Creates an instance of the Verifier class.
     *
     * @param Closure $keyResolver
     * @param string $salt
     */
    public function __construct(private Closure $keyResolver, private string $salt)
    {
    }

    /**
     * Verifies the Signed Global Id string matches with the signature.
     *
     * @param string $sgid
     * @return array Returns the signed global Id attributes when the verification works, otherwise it throws an exception.
     * @throws InvalidSignatureException
     */
    public function verify(string $sgid): array
    {
        $split = explode('--', $sgid);

        if (count($split) !== 2) {
            throw new InvalidSignatureException();
        }

        list($encoded, $signature) = $split;

        $rehased = $this->hash($encoded);

        if ($rehased !== $signature) {
            throw new InvalidSignatureException();
        }

        return json_decode(base64_decode($encoded), true);
    }

    /**
     * Encodes the Signed Global Id attributes and appends the signature to it.
     *
     * @param array $data
     * @return string
     */
    public function generate($data): string
    {
        $encoded = base64_encode(json_encode($data));

        $signature = $this->hash($encoded);

        return "{$encoded}--{$signature}";
    }

    /**
     * Generates the signature of an encoded string.
     *
     * @param string $encoded
     * @return string
     */
    private function hash(string $encoded): string
    {
        return hash_hmac('sha256', $encoded, $this->key() . $this->salt);
    }

    /**
     * Gets the key used to sign the data.
     *
     * @return string
     */
    private function key(): string
    {
        return $this->cachedKey ??= call_user_func($this->keyResolver);
    }
}
