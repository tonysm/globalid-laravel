<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\Verifier;

class VerifierTest extends TestCase
{
    private Verifier $verifier;

    public function setUp(): void
    {
        parent::setUp();

        $this->verifier = new Verifier(fn () => 'MuchSECRETsoHIDDEN', salt: 'salty');
    }

    /** @test */
    public function generates()
    {
        $this->assertEquals(
            'eyJnaWQiOiJnaWQ6XC9cL2xhcmF2ZWxcL1BlcnNvblwvMTIzMTIzP2V4cGlyZXNfaW4iLCJleHBpcmVzX2luIjpudWxsfQ==--8c2f7c2bbd6f43f4004144bbd24d4a49fdcaba63b6ba99f0c740537d9a8d810b',
            $this->verifier->generate(['gid' => 'gid://laravel/Person/123123?expires_in', 'expires_in' => null]),
        );
    }

    /** @test */
    public function verifies()
    {
        $this->assertEquals(
            ['gid' => 'gid://laravel/Person/123123?expires_in', 'expires_in' => null],
            $this->verifier->verify('eyJnaWQiOiJnaWQ6XC9cL2xhcmF2ZWxcL1BlcnNvblwvMTIzMTIzP2V4cGlyZXNfaW4iLCJleHBpcmVzX2luIjpudWxsfQ==--8c2f7c2bbd6f43f4004144bbd24d4a49fdcaba63b6ba99f0c740537d9a8d810b'),
        );
    }
}
