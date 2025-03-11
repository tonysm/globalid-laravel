<?php

namespace Tonysm\GlobalId\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use Tonysm\GlobalId\Models\HasGlobalIdentification;

class PersonWithAlias extends Model
{
    use HasGlobalIdentification;

    protected $table = 'people';

    protected $guarded = [];
}
