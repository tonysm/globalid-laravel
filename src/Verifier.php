<?php

namespace Tonysm\GlobalId;

use Closure;

class Verifier
{
    private string $cachedKey;

    public function __construct(private Closure $keyResolver, private string $salt)
    {
    }

    public function verify(SignedGlobalId $sgid)
    {
    }

    public function generate(array $data): string
    {
        $parsed = base64_encode(json_encode($data['sgid']));

        $signature = hash_hmac('sha256', $parsed, $this->key() . $this->salt);

        return "{$parsed}--{$signature}";
    }

    private function key(): string
    {
        return $this->cachedKey ??= call_user_func($this->keyResolver);
    }
}
