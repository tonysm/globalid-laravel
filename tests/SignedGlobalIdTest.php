<?php

namespace Tonysm\GlobalId\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdTest extends TestCase
{
    private SignedGlobalId $personSgid;

    private Person $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->parse('2021-09-21T18:07:45Z'));

        $this->model = Person::create(['name' => 'signed user']);
        $this->personSgid = SignedGlobalId::create($this->model);
    }

    #[Test]
    public function to_string()
    {
        $this->assertEquals('eyJzZ2lkIjoiZ2lkOlwvXC9sYXJhdmVsXC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb25cLzEiLCJwdXJwb3NlIjoiZGVmYXVsdCIsImV4cGlyZXNfYXQiOiIyMDIxLTEwLTIxVDE4OjA3OjQ1WiJ9--03906479811fcd37fc53deff414f525cd7cd94d844b0cb6d6e547e5a2d912740', $this->personSgid->toString());
    }

    #[Test]
    public function model_id()
    {
        $this->assertEquals($this->model->id, $this->personSgid->modelId());
    }

    #[Test]
    public function model_name()
    {
        $this->assertEquals($this->model::class, $this->personSgid->modelName());
    }

    #[Test]
    public function value_equality()
    {
        $this->assertTrue($this->personSgid->equalsTo(SignedGlobalId::create($this->model)));
    }

    #[Test]
    public function value_equality_with_an_unsigned_id()
    {
        $this->assertTrue($this->personSgid->equalsTo(GlobalId::create($this->model)));
    }

    #[Test]
    public function to_param()
    {
        $this->assertEquals($this->personSgid->toParam(), $this->personSgid->toString());
    }

    #[Test]
    public function parses_signed_gids()
    {
        $this->assertTrue($this->personSgid->equalsTo(SignedGlobalId::parse($this->personSgid->toString())));
    }
}
