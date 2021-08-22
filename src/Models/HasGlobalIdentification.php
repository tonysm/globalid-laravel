<?php

namespace Tonysm\GlobalId\Models;

use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;

trait HasGlobalIdentification
{
    public function toGlobalId(array $options = []): GlobalId
    {
        return GlobalId::create($this, $options);
    }

    public function toGid(array $options = []): GlobalId
    {
        return $this->toGlobalId($options);
    }

    public function toSignedGlobalId(array $options = []): SignedGlobalId
    {
        return SignedGlobalId::create($this, $options);
    }

    public function toSgid(array $options = []): SignedGlobalId
    {
        return $this->toSignedGlobalId($options);
    }
}
