<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdCustomParamsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->parse('2021-08-21T18:51:20Z'));
    }

    /** @test */
    public function create_custom_params()
    {
        $sgid = SignedGlobalId::create(Person::create(['name' => 'a person']), ['hello' => 'world']);
        $this->assertEquals('world', $sgid->getParam('hello'));
    }

    /** @test */
    public function parse_custom_params()
    {
        $sgid = SignedGlobalId::parse('eyJzZ2lkIjoiZ2lkOlwvXC9sYXJhdmVsXC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb25cLzE/aGVsbG89d29ybGQiLCJwdXJwb3NlIjoiZGVmYXVsdCIsImV4cGlyZXNfYXQiOiIyMDIxLTA5LTIxVDE4OjUxOjIwWiJ9--131a0c93a84f48a315c1bab0e310440bb9c2c24fe3bf285bf2fa92303d18bd49');
        $this->assertEquals('world', $sgid->getParam('hello'));
    }
}
