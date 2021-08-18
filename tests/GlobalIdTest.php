<?php

namespace Tonysm\GlobalId\Tests;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\URI\GIDParsingException;

class GlobalIdTest extends TestCase
{
    /** @test */
    public function value_equality()
    {
        $this->assertTrue((new GlobalId('gid://app/model/id'))->equalsTo(new GlobalId('gid://app/model/id')));
    }

    public function invalidAppNames()
    {
        return [
            'empty name' => [
                'app_name' => '',
                'expectedException' => GIDParsingException::class,
            ],
            'underscore is invalid' => [
                'app_name' => 'blog_app',
                'expectedException' => GIDParsingException::class,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAppNames
     */
    public function invalid_app_name($app, $expectedException)
    {
        $this->expectException($expectedException);

        GlobalId::useAppName($app);
    }

    /** @test */
    public function param_parsing()
    {
    }

    /** @test */
    public function find_with_param()
    {
    }

    /** @test */
    public function find()
    {
    }

    /** @test */
    public function find_with_class()
    {
    }

    /** @test */
    public function find_with_class_no_match()
    {
    }

    /** @test */
    public function find_with_subclass()
    {
    }

    /** @test */
    public function find_with_subclass_no_match()
    {
    }

    /** @test */
    public function find_with_module()
    {
    }

    /** @test */
    public function test_with_module_no_match()
    {
    }

    /** @test */
    public function find_with_multiple_class()
    {
    }

    /** @test */
    public function find_with_multiple_class_no_match()
    {
    }

    /** @test */
    public function find_with_multiple_module()
    {
    }

    /** @test */
    public function find_with_multiple_module_no_match()
    {
    }

    /** @test */
    public function to_string()
    {
    }

    /** @test */
    public function to_params()
    {
    }

    /** @test */
    public function to_uri()
    {
    }

    /** @test */
    public function model_id()
    {
    }

    /** @test */
    public function model_name()
    {
    }

    /** @test */
    public function model_class()
    {
    }

    /** @test */
    public function app_option()
    {
    }

    /** @test */
    public function equality()
    {
    }

    /** @test */
    public function create_custom_params()
    {
    }

    /** @test */
    public function parse_custom_param()
    {
    }
}
