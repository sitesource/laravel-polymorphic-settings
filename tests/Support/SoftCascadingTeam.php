<?php

namespace SiteSource\PolymorphicSettings\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SiteSource\PolymorphicSettings\Concerns\HasSettings;

class SoftCascadingTeam extends Model
{
    use HasSettings;
    use SoftDeletes;

    public bool $cascadeDeleteSettings = true;

    protected $table = 'test_teams';

    protected $guarded = [];
}
