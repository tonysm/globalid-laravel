<?php

namespace Tonysm\GlobalId\URI;

use Tonysm\GlobalId\Tests\TestCase;

class GIDValidationTest extends TestCase
{
    public function invalidGids()
    {
        return [
            'missing app' => [
                'gid' => 'gid:///Person/1',
                'expectedException' => GIDParsingException::class,
            ],
            'missing path' => [
                'gid' => 'gid://laravel/',
                'expectedException' => GIDParsingException::class,
            ],
            'missing model id' => [
                'gid' => 'gid://laravel/Person',
                'expectedException' => GIDParsingException::class,
            ],
            'too many model ids' => [
                'gid' => 'gid://laravel/Person/1/2',
                'expectedException' => GIDParsingException::class,
            ],
            // 'empty',
            // 'invalid schemes',
        ];
    }

    /**
     * @test
     * @dataProvider invalidGids
     */
    public function invalid_gids($gid, $expectedException)
    {
        $this->expectException($expectedException);

        GID::parse($gid);
    }

    /** @test */
    public function can_get_pieces()
    {
    }
}
