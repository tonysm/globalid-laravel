<?php

namespace Tonysm\GlobalId\Tests\URI;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\TestCase;
use Tonysm\GlobalId\URI\GID;

class GIDParamsTest extends TestCase
{
    private GID $gid;

    protected function setUp(): void
    {
        parent::setUp();

        $model = (new Person)->forceFill(['id' => 5]);
        $this->gid = GID::create('laravel', $model, ['hello' => 'world']);
    }

    #[Test]
    public function can_get_params()
    {
        $this->assertEquals('world', $this->gid->getParam('hello'));
    }

    #[Test]
    public function multi_value()
    {
        $gid = GID::build(['laravel', 'Person', '5', ['multi' => ['one', 'two']]]);
        $expected = ['multi' => 'two'];

        $this->assertEquals($expected, $gid->params);
    }

    #[Test]
    public function to_string()
    {
        $this->assertEquals('gid://laravel/'.urlencode(Person::class).'/5?hello=world', $this->gid->toString());
    }
}
