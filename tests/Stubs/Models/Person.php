<?php

namespace Tonysm\GlobalId\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Tonysm\GlobalId\Models\GlobalIdentification;

class Person extends Model
{
    use GlobalIdentification;

    protected $table = "people";

    protected $guarded = [];
}
