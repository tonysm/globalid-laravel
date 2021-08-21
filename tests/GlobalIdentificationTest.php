<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
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
        $this->assertTrue(GlobalId::create($this->model)->equalsTo($this->model->toGlobalId()));
        $this->assertTrue(GlobalId::create($this->model)->equalsTo($this->model->toGid()));
    }

    /** @test */
    public function creates_a_global_id_with_custom_params()
    {
        $this->assertTrue(GlobalId::create($this->model, ['some' => 'param'])->equalsTo($this->model->toGlobalId(['some' => 'param'])));
        $this->assertFalse(GlobalId::create($this->model)->equalsTo($this->model->toGlobalId(['some' => 'param'])));

        $this->assertTrue(GlobalId::create($this->model, ['some' => 'param'])->equalsTo($this->model->toGid(['some' => 'param'])));
        $this->assertFalse(GlobalId::create($this->model)->equalsTo($this->model->toGid(['some' => 'param'])));
    }

    /** @test */
    public function creates_signed_global_id_from_model()
    {
        $this->assertTrue(SignedGlobalId::create($this->model)->equalsTo($this->model->toSignedGlobalId()));
        $this->assertTrue(SignedGlobalId::create($this->model)->equalsTo($this->model->toSgid()));
    }

    /** @test */
    public function creates_signed_global_id_with_custom_params()
    {
        $this->assertTrue(SignedGlobalId::create($this->model, ['some' => 'param'])->equalsTo($this->model->toSignedGlobalId(['some' => 'param'])));
        $this->assertFalse(SignedGlobalId::create($this->model)->equalsTo($this->model->toSignedGlobalId(['some' => 'param'])));

        $this->assertTrue(SignedGlobalId::create($this->model, ['some' => 'param'])->equalsTo($this->model->toSgid(['some' => 'param'])));
        $this->assertFalse(SignedGlobalId::create($this->model)->equalsTo($this->model->toSgid(['some' => 'param'])));
    }
}
