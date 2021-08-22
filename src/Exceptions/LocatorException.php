<?php

namespace Tonysm\GlobalId\Exceptions;

use RuntimeException;

class LocatorException extends RuntimeException
{
    public static function modelNotFoundFromLocateMany()
    {
        return new static(
            'One or many of the models passed to the locate many could not be found.'
        );
    }
}
