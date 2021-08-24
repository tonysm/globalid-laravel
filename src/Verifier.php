<?php

namespace Tonysm\GlobalId;

use Closure;
use Tonysm\GlobalId\Exceptions\InvalidSignatureException;

class Verifier
{
    private string $cachedKey;

    public function __construct(private Closure $keyResolver, private string $salt)
    {
    }

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

    public function generate($data): string
    {
        $encoded = base64_encode(json_encode($data));

        $signature = $this->hash($encoded);

        return "{$encoded}--{$signature}";
    }

    private function hash(string $encoded): string
    {
        return hash_hmac('sha256', $encoded, $this->key() . $this->salt);
    }

    private function key(): string
    {
        return $this->cachedKey ??= call_user_func($this->keyResolver);
    }
}
