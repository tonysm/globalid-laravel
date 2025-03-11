<?php

namespace Tonysm\GlobalId\Tests\URI;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\TestCase;
use Tonysm\GlobalId\URI\GID;

class GIDModelIdEncodingTest extends TestCase
{
    #[Test]
    public function encodes_alphanumeric()
    {
        $model = (new Person)->forceFill(['id' => 'John123']);
        $model->incrementing = false;

        $this->assertEquals('gid://laravel/'.urlencode(Person::class).'/John123', GID::create('laravel', $model)->toString());
    }

    #[Test]
    public function encodes_non_alphanumberic()
    {
        $model = (new Person)->forceFill(['id' => 'John Doe 123/Ipsum']);
        $model->incrementing = false;

        $this->assertEquals('gid://laravel/'.urlencode(Person::class).'/'.urlencode('John Doe 123/Ipsum'), GID::create('laravel', $model)->toString());
    }

    #[Test]
    public function decodes_alphanumeric()
    {
        $this->assertEquals('John123', GID::parse('gid://laravel/Person/John123')->modelId);
    }

    #[Test]
    public function decodes_non_alphanumerics()
    {
        $this->assertEquals('John Doe-Smith/Jones', GID::parse('gid://laravel/Person/'.urlencode('John Doe-Smith/Jones'))->modelId);
    }
}
