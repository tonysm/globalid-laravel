<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class GlobalIdentificationTest extends TestCase
{
    private Person $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = Person::create(['name' => 'a model']);
    }

    /** @test */
    public function creates_a_global_id_from_model()
    {
    }

    /** @test */
    public function creates_a_global_id_with_custom_params()
    {
    }

    /** @test */
    public function creates_signed_global_id_from_model()
    {
    }

    /** @test */
    public function creates_signed_global_id_with_custom_params()
    {
    }

    /** @test */
    public function clone_should_clear_cached_global_id()
    {
    }
}
