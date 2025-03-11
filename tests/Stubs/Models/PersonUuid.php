<?php

namespace Tonysm\GlobalId\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class PersonUuid extends Model
{
    protected $table = 'uuid_people';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';
}
