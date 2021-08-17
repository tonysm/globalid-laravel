<?php

namespace Tonysm\GlobalId\URI;

use RuntimeException;

class GIDParsingException extends RuntimeException
{
    public static function badUri(): self
    {
        return new static('Bad URI');
    }

    public static function missingPath(): self
    {
        return new static('Missing PATH');
    }

    public static function missingModelId(): self
    {
        return new static('Missing model ID');
    }
}
