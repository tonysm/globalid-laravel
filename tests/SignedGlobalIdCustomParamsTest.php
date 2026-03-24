<?php

namespace Tonysm\GlobalId\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdCustomParamsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->parse('2021-08-21T18:51:20Z'));
    }

    #[Test]
    public function create_custom_params()
    {
        $sgid = SignedGlobalId::create(Person::create(['name' => 'a person']), ['hello' => 'world']);
        $this->assertEquals('world', $sgid->getParam('hello'));
    }

    #[Test]
    public function parse_custom_params()
    {
        $sgid = SignedGlobalId::parse('eyJzZ2lkIjoiZ2lkOlwvXC9sYXJhdmVsXC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb25cLzE/aGVsbG89d29ybGQiLCJwdXJwb3NlIjoiZGVmYXVsdCIsImV4cGlyZXNfYXQiOiIyMDIxLTA5LTIxVDE4OjUxOjIwWiJ9--692ce83526b900f93c5a50119919c28893a437327f7cb914a31eee6d9a025e0a');

        $this->assertEquals('world', $sgid->getParam('hello'));
    }
}
