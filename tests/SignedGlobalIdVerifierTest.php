<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Verifier;

class SignedGlobalIdVerifierTest extends TestCase
{
    private Person $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = Person::create(['name' => 'signed user']);
        $this->sgid = SignedGlobalId::create($this->model);
    }

    /** @test */
    public function create_accepts_a_verifier()
    {
        $sgid = SignedGlobalId::create($this->model, [
            'verifier' => new FakeVerifier(),
        ]);

        $this->assertEquals('mocked', $sgid->toString());
    }

    /** @test */
    public function new_accepts_a_verifier()
    {
        $sgid = new SignedGlobalId(GlobalId::create($this->model)->toString(), [
            'verifier' => new FakeVerifier(),
        ]);

        $this->assertEquals('mocked', $sgid->toString());
    }
}

class FakeVerifier extends Verifier
{
    public function __construct()
    {
    }

    public function verify($sgid): array
    {
        return [];
    }

    public function generate($data): string
    {
        return 'mocked';
    }
}
