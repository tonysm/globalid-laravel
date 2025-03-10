<?php

namespace Tonysm\GlobalId\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\Stubs\Models\PersonWithAlias;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalIdTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Relation::morphMap([]);
    }

    /** @test */
    public function value_equality()
    {
        $this->assertTrue((new GlobalId('gid://app/model/id'))->equalsTo(new GlobalId('gid://app/model/id')));
    }

    public static function invalidAppNames()
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
    public function invalid_app_name($app_name, $expectedException)
    {
        $this->expectException($expectedException);

        GlobalId::useAppName($app_name);
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
        $gid = GlobalId::create(Person::create(['name' => 'custom']), ['hello' => 'world']);
        $this->assertEquals('world', $gid->getParam('hello'));
    }

    /** @test */
    public function custom_params_ignore_app()
    {
        $gid = GlobalId::create(Person::create(['name' => 'custom']), ['app' => 'test', 'hello' => 'world']);
        $this->assertEquals('world', $gid->getParam('hello'));
        $this->assertEquals(null, $gid->getParam('app'));
    }

    /** @test */
    public function parse_custom_param()
    {
        $gid = GlobalId::parse('gid://laravel/User/5?hello=world');
        $this->assertEquals('world', $gid->getParam('hello'));
    }

    /** @test */
    public function uses_relation_aliases()
    {
        Relation::morphMap([
            'person-with-alias' => PersonWithAlias::class,
        ]);

        $model = PersonWithAlias::create(['name' => 'uses model relation']);

        $gid = GlobalId::create($model, [
            'app' => 'test',
        ]);

        $this->assertEquals('gid://test/person-with-alias/1', $gid->toString());
    }
}
