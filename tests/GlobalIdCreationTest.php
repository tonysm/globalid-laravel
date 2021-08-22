<?php

namespace Tonysm\GlobalId\Tests;

use Illuminate\Database\Eloquent\Model;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\Exceptions\GlobalIdException;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\Stubs\Models\PersonUuid;
use Tonysm\GlobalId\Tests\Stubs\NonModelPerson;

class GlobalIdCreationTest extends TestCase
{
    private string $uuid;
    private GlobalId $personGid;
    private GlobalId $uuidPersonGid;
    private GlobalId $nonModelPersonGid;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid = '8d618861-964f-4fdd-a636-5af844fa92ee';
        $this->personGid = GlobalId::create(Person::create(['name' => 'testing']));
        $this->uuidPersonGid = GlobalId::create(PersonUuid::create(['id' => $this->uuid, 'name' => 'uuid']));
        $this->nonModelPersonGid = GlobalId::create(new NonModelPerson(1));
    }

    /** @test */
    public function find()
    {
        $this->assertTrue(Person::find($this->personGid->modelId())->is($this->personGid->locate()));
        $this->assertTrue(PersonUuid::find($this->uuidPersonGid->modelId())->is($this->uuidPersonGid->locate()));
        $this->assertTrue(NonModelPerson::find(1)->is($this->nonModelPersonGid->locate()));
    }

    /** @test */
    public function find_with_class()
    {
        $this->assertTrue(Person::find($this->personGid->modelId())->is($this->personGid->locate(['only' => Person::class])));
        $this->assertTrue(PersonUuid::find($this->uuidPersonGid->modelId())->is($this->uuidPersonGid->locate(['only' => PersonUuid::class])));
    }

    /** @test */
    public function find_with_class_no_match()
    {
        $this->assertNull($this->personGid->locate(['only' => PersonUuid::class]));
        $this->assertNull($this->uuidPersonGid->locate(['only' => Person::class]));
    }

    /** @test */
    public function find_with_subclass()
    {
        $this->assertTrue(Person::find($this->personGid->modelId())->is($this->personGid->locate(['only' => Model::class])));
        $this->assertTrue(PersonUuid::find($this->uuidPersonGid->modelId())->is($this->uuidPersonGid->locate(['only' => Model::class])));
    }

    /** @test */
    public function find_with_multiple_class()
    {
        $this->assertTrue(Person::find($this->personGid->modelId())->is($this->personGid->locate(['only' => [Person::class, PersonUuid::class]])));
        $this->assertTrue(PersonUuid::find($this->uuidPersonGid->modelId())->is($this->uuidPersonGid->locate(['only' => [Person::class, PersonUuid::class]])));
    }

    /** @test */
    public function find_with_multiple_class_no_match()
    {
        $this->assertNull($this->personGid->locate(['only' => [GlobalId::class, PersonUuid::class]]));
        $this->assertNull($this->uuidPersonGid->locate(['only' => [Person::class, GlobalId::class]]));
    }

    /** @test */
    public function to_string()
    {
        $this->assertEquals('gid://laravel/'.urlencode(Person::class). '/1', $this->personGid->toString());
        $this->assertEquals('gid://laravel/'.urlencode(Person::class). '/1', (string) $this->personGid->toString());
        $this->assertEquals('gid://laravel/'.urlencode(PersonUuid::class). '/' . $this->uuid, $this->uuidPersonGid->toString());
        $this->assertEquals('gid://laravel/'.urlencode(PersonUuid::class). '/' . $this->uuid, (string) $this->uuidPersonGid->toString());
    }

    /** @test */
    public function to_params()
    {
        $this->assertEquals('Z2lkOi8vbGFyYXZlbC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb24vMQ', $this->personGid->toParam());
        $this->assertEquals('Z2lkOi8vbGFyYXZlbC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb25VdWlkLzhkNjE4ODYxLTk2NGYtNGZkZC1hNjM2LTVhZjg0NGZhOTJlZQ', $this->uuidPersonGid->toParam());
    }

    /** @test */
    public function model_id()
    {
        $this->assertEquals('1', $this->personGid->modelId());
        $this->assertEquals($this->uuid, $this->uuidPersonGid->modelId());
    }

    /** @test */
    public function model_name()
    {
        $this->assertEquals(Person::class, $this->personGid->modelName());
        $this->assertEquals(PersonUuid::class, $this->uuidPersonGid->modelName());
        $this->assertEquals(NonModelPerson::class, $this->nonModelPersonGid->modelName());
    }

    /** @test */
    public function app_option()
    {
        $personGid = GlobalId::create($model = Person::create(['name' => 'lorem']));
        $this->assertEquals(
            sprintf('gid://laravel/%s/%s', urlencode($model::class), $model->getKey()),
            $personGid->toString()
        );

        $personGid = GlobalId::create($model = Person::create(['name' => 'lorem']), ['app' => 'foo']);
        $this->assertEquals(
            sprintf('gid://foo/%s/%s', urlencode($model::class), $model->getKey()),
            $personGid->toString()
        );
    }

    /** @test */
    public function fails_when_app_options_is_null()
    {
        $this->expectException(GlobalIdException::class);

        GlobalId::create(Person::create(['name' => 'lorem']), ['app' => null]);
    }

    /** @test */
    public function equality()
    {
        $p1 = Person::create(['name' => 'first']);
        $p2 = Person::find($p1->getKey());
        $p3 = Person::create(['name' => 'third']);

        $this->assertTrue($p1->is($p2));
        $this->assertFalse($p2->is($p3));

        $gid1 = GlobalId::create($p1);
        $gid2 = GlobalId::create($p2);
        $gid3 = GlobalId::create($p3);

        $this->assertTrue($gid1->equalsTo($gid2));
        $this->assertFalse($gid2->equalsTo($gid3));
    }
}
