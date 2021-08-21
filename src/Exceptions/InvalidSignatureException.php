<?php

namespace Tonysm\GlobalId\Exceptions;

use RuntimeException;

class InvalidSignatureException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid signature in SignedGlobalId.');
    }
}
