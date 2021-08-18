<?php

namespace Tonysm\GlobalId;

use RuntimeException;

class GlobalIdException extends RuntimeException
{
    public static function missingApp(): self
    {
        return new static('An app is required to create a GlobalId.');
    }
}
