<?php

namespace SiteSource\PolymorphicSettings\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestTeam extends Model
{
    protected $table = 'test_teams';

    protected $guarded = [];
}
