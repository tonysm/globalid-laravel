<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdTest extends TestCase
{
    private SignedGlobalId $personSgid;
    private Person $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = Person::create(['name' => 'signed user']);
        $this->personSgid = SignedGlobalId::create($this->model);
    }
    /** @test */
    public function to_string()
    {
        $this->assertEquals('ImdpZDpcL1wvbGFyYXZlbFwvVG9ueXNtJTVDR2xvYmFsSWQlNUNUZXN0cyU1Q1N0dWJzJTVDTW9kZWxzJTVDUGVyc29uXC8xIg==--d0846546b6776dd5784941438a98db36cc4b5dcb55188a25789f9c997d432035', $this->personSgid->toString());
    }

    /** @test */
    public function model_id()
    {
        $this->assertEquals($this->model->id, $this->personSgid->modelId());
    }

    /** @test */
    public function model_name()
    {
        $this->assertEquals($this->model::class, $this->personSgid->modelName());
    }

    /** @test */
    public function value_equality()
    {
        $this->assertTrue($this->personSgid->equalsTo(SignedGlobalId::create($this->model)));
    }

    /** @test */
    public function value_equality_with_an_unsigned_id()
    {
        $this->assertTrue($this->personSgid->equalsTo(GlobalId::create($this->model)));
    }

    /** @test */
    public function to_param()
    {
        $this->assertEquals($this->personSgid->toParam(), $this->personSgid->toString());
    }
}
