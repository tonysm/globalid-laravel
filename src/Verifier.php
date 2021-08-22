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
        [$encoded] = explode('--', $sgid);

        $data = json_decode(base64_decode($encoded), true);

        // Re-hashing it again should generate the name string.

        if ($sgid !== $this->generate($data)) {
            throw new InvalidSignatureException();
        }

        return $data;
    }

    public function generate($data): string
    {
        $parsed = base64_encode(json_encode($data));

        $signature = hash_hmac('sha256', $parsed, $this->key() . $this->salt);

        return "{$parsed}--{$signature}";
    }

    private function key(): string
    {
        return $this->cachedKey ??= call_user_func($this->keyResolver);
    }
}
