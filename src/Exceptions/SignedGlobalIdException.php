<?php

namespace Tonysm\GlobalId\Exceptions;

use RuntimeException;

class SignedGlobalIdException extends RuntimeException
{
    public static function expired(): static
    {
        return new static(
            'The SignedGlobalId has already expired.'
        );
    }
}
