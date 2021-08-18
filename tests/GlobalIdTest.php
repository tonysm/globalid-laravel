<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalIdTest extends TestCase
{
    /** @test */
    public function value_equality()
    {
        $this->assertTrue((new GlobalId('gid://app/model/id'))->equalsTo(new GlobalId('gid://app/model/id')));
    }

    public function invalidAppNames()
    {
        return [
            'empty name' => [
                'app_name' => '',
                'expectedException' => GIDParsingException::class,
            ],
            'underscore is invalid' => [
                'app_name' => 'blog_app',
                'expectedException' => GIDParsingException::class,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAppNames
     */
    public function invalid_app_name($app, $expectedException)
    {
        $this->expectException($expectedException);

        GlobalId::useAppName($app);
    }

    /** @test */
    public function param_parsing()
    {
        $model = Person::make()->forceFill(['id' => 5]);
        $gid = GlobalId::create($model);

        $this->assertTrue(GlobalId::parse($gid->toParam())->equalsTo($gid));
    }

    /** @test */
    public function find_with_param()
    {
        $model = Person::create(['name' => 'Test']);
        $gid = GlobalId::create($model);

        $found = GlobalId::find($gid->toParam());

        $this->assertTrue($model->is($found));
        $this->assertEquals($gid->modelId(), $found->id);
    }

    /** @test */
    public function create_custom_params()
    {
    }

    /** @test */
    public function parse_custom_param()
    {
    }
}
