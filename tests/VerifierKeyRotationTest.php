<?php

namespace Tonysm\GlobalId\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\Exceptions\InvalidSignatureException;
use Tonysm\GlobalId\Verifier;

class VerifierKeyRotationTest extends TestCase
{
    #[Test]
    public function verifies_with_current_key()
    {
        $verifier = new Verifier(
            fn () => 'current-key',
            salt: 'salty',
            previousKeysResolver: fn () => ['old-key'],
        );

        $sgid = $verifier->generate(['gid' => 'gid://laravel/Person/1']);

        $this->assertEquals(
            ['gid' => 'gid://laravel/Person/1'],
            $verifier->verify($sgid),
        );
    }

    #[Test]
    public function verifies_with_previous_key_after_rotation()
    {
        $oldVerifier = new Verifier(fn () => 'old-key', salt: 'salty');
        $sgid = $oldVerifier->generate(['gid' => 'gid://laravel/Person/1']);

        $newVerifier = new Verifier(
            fn () => 'new-key',
            salt: 'salty',
            previousKeysResolver: fn () => ['old-key'],
        );

        $this->assertEquals(
            ['gid' => 'gid://laravel/Person/1'],
            $newVerifier->verify($sgid),
        );
    }

    #[Test]
    public function verifies_with_second_previous_key()
    {
        $originalVerifier = new Verifier(fn () => 'original-key', salt: 'salty');
        $sgid = $originalVerifier->generate(['gid' => 'gid://laravel/Person/1']);

        $currentVerifier = new Verifier(
            fn () => 'newest-key',
            salt: 'salty',
            previousKeysResolver: fn () => ['old-key', 'original-key'],
        );

        $this->assertEquals(
            ['gid' => 'gid://laravel/Person/1'],
            $currentVerifier->verify($sgid),
        );
    }

    #[Test]
    public function fails_verification_when_key_is_not_in_current_or_previous()
    {
        $unknownVerifier = new Verifier(fn () => 'unknown-key', salt: 'salty');
        $sgid = $unknownVerifier->generate(['gid' => 'gid://laravel/Person/1']);

        $verifier = new Verifier(
            fn () => 'current-key',
            salt: 'salty',
            previousKeysResolver: fn () => ['old-key'],
        );

        $this->expectException(InvalidSignatureException::class);
        $verifier->verify($sgid);
    }

    #[Test]
    public function always_signs_with_current_key()
    {
        $currentVerifier = new Verifier(fn () => 'current-key', salt: 'salty');
        $rotatedVerifier = new Verifier(
            fn () => 'current-key',
            salt: 'salty',
            previousKeysResolver: fn () => ['old-key'],
        );

        $sgid1 = $currentVerifier->generate(['gid' => 'gid://laravel/Person/1']);
        $sgid2 = $rotatedVerifier->generate(['gid' => 'gid://laravel/Person/1']);

        $this->assertEquals($sgid1, $sgid2);
    }

    #[Test]
    public function previous_keys_resolver_is_not_called_when_current_key_works()
    {
        $called = false;

        $verifier = new Verifier(
            fn () => 'current-key',
            salt: 'salty',
            previousKeysResolver: function () use (&$called) {
                $called = true;

                return ['old-key'];
            },
        );

        $sgid = $verifier->generate(['gid' => 'gid://laravel/Person/1']);
        $verifier->verify($sgid);

        $this->assertFalse($called);
    }
}
