<?php

namespace SiteSource\PolymorphicSettings\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use SiteSource\PolymorphicSettings\Concerns\HasSettings;

class CascadingTeam extends Model
{
    use HasSettings;

    public bool $cascadeDeleteSettings = true;

    protected $table = 'test_teams';

    protected $guarded = [];
}
