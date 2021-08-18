<?php

namespace Tonysm\GlobalId;

use Tonysm\GlobalId\URI\GID;

class GlobalId
{
    private GID $gid;

    public function __construct($gid)
    {
        $this->gid = $gid instanceof GID
            ? $gid
            : GID::parse($gid);
    }

    public function equalsTo(GlobalId $globalId): bool
    {
        return $this->gid->equalsTo($globalId->gid);
    }
}
