<?php

namespace Tonysm\GlobalId\Exceptions;

use RuntimeException;

class MissingVerifierException extends RuntimeException
{
    public function __construct(string $purpose)
    {
        parent::__construct(sprintf(
            'Missing verifier for "" purpose.',
            $purpose,
        ));
    }
}
