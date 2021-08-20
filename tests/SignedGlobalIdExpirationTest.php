<?php

namespace Tonysm\GlobalId\Tests;

class SignedGlobalIdExpirationTest extends TestCase
{
    /** @test */
    public function expires_in_defaults_to_class_level_expiration()
    {
    }

    /** @test */
    public function passing_in_expires_in_overrides_class_level_expiration()
    {
    }

    /** @test */
    public function passing_expires_in_less_than_a_second_is_not_expired()
    {
    }

    /** @test */
    public function passing_expires_in_null_turns_off_expiration_checking()
    {
    }

    /** @test */
    public function passing_expires_in_null_off_expiration_checking()
    {
    }

    /** @test */
    public function passing_expires_at_sets_expiration_date()
    {
    }

    /** @test */
    public function passing_null_expires_at_turns_off_expiration_checking()
    {
    }

    /** @test */
    public function passing_expires_at_overrides_class_level_expires_in()
    {
    }

    /** @test */
    public function favor_expires_at_over_expires_in()
    {
    }
}
