<?php

namespace Tonysm\GlobalId\Tests\URI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\TestCase;
use Tonysm\GlobalId\URI\GID;

class UriGidTest extends TestCase
{
    use RefreshDatabase;

    private string $gidString;
    private GID $gid;

    public function setUp(): void
    {
        parent::setUp();

        $this->gidString = 'gid://laravel/' . urlencode(\Tonysm\GlobalId\Tests\Stubs\Models\Person::class). '/5';
        $this->gid = GID::parse($this->gidString);
    }

    /** @test */
    public function parsed()
    {
        $this->assertEquals('laravel', $this->gid->app);
        $this->assertEquals(\Tonysm\GlobalId\Tests\Stubs\Models\Person::class, $this->gid->modelName);
        $this->assertEquals('5', $this->gid->modelId);
    }

    /** @test */
    public function allows_dash()
    {
        $gid = GID::parse('gid://rich-text-laravel/User/5');

        $this->assertEquals('rich-text-laravel', $gid->app);
        $this->assertEquals('User', $gid->modelName);
        $this->assertEquals('5', $gid->modelId);
    }

    /** @test */
    public function returns_invalid_gid_when_not_checking()
    {
        $this->assertNotNull(new GID('', '', '', []));
    }

    /** @test */
    public function create()
    {
        $model = (new Person())->forceFill(['id' => 5]);
        $this->assertEquals($this->gidString, GID::create('laravel', $model)->toString());
    }

    /** @test */
    public function build()
    {
        $array = GID::build(['laravel', \Tonysm\GlobalId\Tests\Stubs\Models\Person::class, '5', null]);
        $this->assertEquals($this->gidString, $array->toString());

        $associativeArray = GID::build(['app' => 'laravel', 'model_name' => \Tonysm\GlobalId\Tests\Stubs\Models\Person::class, 'model_id' => '5', 'params' => null]);
        $this->assertEquals($this->gidString, $associativeArray->toString());
    }

    /** @test */
    public function build_with_wrong_ordered_array_creates_wrong_ordered_gid()
    {
        $array = GID::build([\Tonysm\GlobalId\Tests\Stubs\Models\Person::class, '5', 'laravel', null]);
        $this->assertNotEquals($this->gidString, $array->toString());
    }

    /** @test */
    public function to_string()
    {
        $this->assertEquals($this->gidString, $this->gid->toString());
        $this->assertEquals($this->gidString, (string) $this->gid);
    }

    /** @test */
    public function equality()
    {
        $this->assertTrue($this->gid->equalsTo(GID::parse($this->gid->toString())));
        $this->assertFalse($this->gid->equalsTo(GID::parse('gid://anotherapp/Person/5')));
    }
}
