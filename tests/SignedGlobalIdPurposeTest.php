<?php

namespace Tonysm\GlobalId\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdPurposeTest extends TestCase
{
    private Person $model;

    private SignedGlobalId $loginSgid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->parse('2021-09-21T18:07:45Z'));

        $this->model = Person::create(['name' => 'signed']);
        $this->loginSgid = SignedGlobalId::create($this->model, ['for' => 'login']);
    }

    #[Test]
    public function sign_with_purpose_when_for_is_provided()
    {
        $this->assertEquals('eyJzZ2lkIjoiZ2lkOlwvXC9sYXJhdmVsXC9Ub255c20lNUNHbG9iYWxJZCU1Q1Rlc3RzJTVDU3R1YnMlNUNNb2RlbHMlNUNQZXJzb25cLzEiLCJwdXJwb3NlIjoibG9naW4iLCJleHBpcmVzX2F0IjoiMjAyMS0xMC0yMVQxODowNzo0NVoifQ==--2608dcd66dd87acc6f20b44a399ab07672ea2a0be804ab6313ba8bb8c2a4bf0c', $this->loginSgid->toString());
    }

    #[Test]
    public function sign_with_default_purpose_when_no_for_is_provided()
    {
        $sgid = SignedGlobalId::create($this->model);
        $defaultSigned = SignedGlobalId::create($this->model, ['for' => 'default']);

        $this->assertTrue($sgid->equalsTo($defaultSigned));
    }

    #[Test]
    public function create_accepts_a_for()
    {
        $expected = SignedGlobalId::create($this->model, ['for' => 'login']);

        $this->assertTrue($this->loginSgid->equalsTo($expected));
    }

    #[Test]
    public function new_accepts_a_for()
    {
        $expected = new SignedGlobalId(GlobalId::create($this->model)->toString(), ['for' => 'login']);

        $this->assertTrue($this->loginSgid->equalsTo($expected));
    }

    #[Test]
    public function parse_returns_null_when_purpose_mismatch()
    {
        $sgid = $this->loginSgid->toString();

        $this->assertNull(SignedGlobalID::parse($sgid));
        $this->assertNull(SignedGlobalID::parse($sgid, ['for' => 'like_button']));
    }

    #[Test]
    public function equal_only_with_same_purpose()
    {
        $expected = SignedGlobalId::create($this->model, ['for' => 'login']);
        $likeSgid = SignedGlobalId::create($this->model, ['for' => 'like_button']);
        $noPurposeSgid = SignedGlobalId::create($this->model);

        $this->assertTrue($this->loginSgid->equalsTo($expected));
        $this->assertFalse($this->loginSgid->equalsTo($likeSgid));
        $this->assertFalse($this->loginSgid->equalsTo($noPurposeSgid));
    }
}
