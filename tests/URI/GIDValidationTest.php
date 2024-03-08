<?php

namespace Tonysm\GlobalId\URI;

use Tonysm\GlobalId\Tests\TestCase;

class GIDValidationTest extends TestCase
{
    public static function invalidGids()
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
            'empty' => [
                'gid' => 'gid:///',
                'expectedException' => GIDParsingException::class,
            ],
            'invalid schemes http' => [
                'gid' => 'http://laravel/Person/123',
                'expectedException' => GIDParsingException::class,
            ],
            'invalid schemes gyd' => [
                'gid' => 'gyd://laravel/Person/123',
                'expectedException' => GIDParsingException::class,
            ],
            'invalid schemes empty' => [
                'gid' => '//laravel/Person/123',
                'expectedException' => GIDParsingException::class,
            ],
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
}
