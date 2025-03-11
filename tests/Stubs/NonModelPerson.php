<?php

namespace Tonysm\GlobalId\Tests\Stubs;

class NonModelPerson
{
    public const MISSING_PERSON_ID = 100;

    public function __construct(private $id) {}

    public function getKey()
    {
        return $this->id;
    }

    /**
     * This method is only for testing and is not part of the
     * required API for globally findable custom classes.
     */
    public function is($another)
    {
        return $another instanceof static
            && $this->id == $another->id;
    }

    public static function find($id)
    {
        if ($id == static::MISSING_PERSON_ID) {
            return null;
        }

        return new static($id);
    }

    public static function findMany($ids)
    {
        return CustomCollection::wrap($ids)
            ->map(fn ($id) => NonModelPerson::find($id));
    }
}
