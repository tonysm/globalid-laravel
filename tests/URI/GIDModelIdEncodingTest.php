<?php

namespace Tonysm\GlobalId\Tests\URI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tonysm\GlobalId\Tests\TestCase;
use Tonysm\GlobalId\URI\GID;

class GIDModelIdEncodingTest extends TestCase
{
    use RefreshDatabase;

    private string $gidString;
    private GID $gid;

    public function setUp(): void
    {
        parent::setUp();

        $this->gidString = 'gid://laravel/App-Models-Person/5';
        $this->gid = GID::parse($this->gidString);
    }

    /** @test */
    public function encodes_alphanumeric()
    {
    }

    /** @test */
    public function encodes_non_alphanumberic()
    {
    }

    /** @test */
    public function decodes_alphanumeric()
    {
    }

    /** @test */
    public function decodes_non_alphanumerics()
    {
    }
}
