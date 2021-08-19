<?php

namespace Tonysm\GlobalId;

use RuntimeException;
use Throwable;

class GlobalIdException extends RuntimeException
{
    public static function missingApp(): self
    {
        return new static('An app is required to create a GlobalId.');
    }

    public static function invalidApp($app, ?Throwable $previous): self
    {
        return new static(sprintf('Invalid name "%s" for app. Must be alphanumeric and not contain underscore.', $app), previous: $previous);
    }
}
