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

    public static function unknownApp(string $app)
    {
        return new static(
            "No locator registered for app \"{$app}\". Register one with Locator::use('{$app}', ...)."
        );
    }
}
