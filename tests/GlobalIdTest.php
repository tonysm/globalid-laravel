<?php

namespace Tonysm\GlobalId\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\Stubs\Models\PersonWithAlias;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalIdTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Relation::morphMap([]);
    }

    #[Test]
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

    #[DataProvider('invalidAppNames')]
    #[Test]
    public function invalid_app_name($app_name, $expectedException)
    {
        $this->expectException($expectedException);

        GlobalId::useAppName($app_name);
    }

    #[Test]
    public function param_parsing()
    {
        $model = Person::make()->forceFill(['id' => 5]);
        $gid = GlobalId::create($model);

        $this->assertTrue(GlobalId::parse($gid->toParam())->equalsTo($gid));
    }

    #[Test]
    public function find_with_param()
    {
        $model = Person::create(['name' => 'Test']);
        $gid = GlobalId::create($model);

        $found = GlobalId::find($gid->toParam());

        $this->assertTrue($model->is($found));
        $this->assertEquals($gid->modelId(), $found->id);
    }

    #[Test]
    public function create_custom_params()
    {
        $gid = GlobalId::create(Person::create(['name' => 'custom']), ['hello' => 'world']);
        $this->assertEquals('world', $gid->getParam('hello'));
    }

    #[Test]
    public function custom_params_ignore_app()
    {
        $gid = GlobalId::create(Person::create(['name' => 'custom']), ['app' => 'test', 'hello' => 'world']);
        $this->assertEquals('world', $gid->getParam('hello'));
        $this->assertEquals(null, $gid->getParam('app'));
    }

    #[Test]
    public function parse_custom_param()
    {
        $gid = GlobalId::parse('gid://laravel/User/5?hello=world');
        $this->assertEquals('world', $gid->getParam('hello'));
    }

    #[Test]
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
