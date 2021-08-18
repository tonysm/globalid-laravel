<?php

namespace Tonysm\GlobalId;

use Tonysm\GlobalId\URI\GID;

class GlobalId
{
    private static $app;

    private GID $gid;

    public static function useAppName(string $app): void
    {
        static::$app = GID::validateAppName($app);
    }

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
