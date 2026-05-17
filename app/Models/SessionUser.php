<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class SessionUser extends Authenticatable
{
    protected $guarded = [];
    public $exists = true;

    public function getAuthIdentifierName() { return 'id'; }
    public function getAuthIdentifier()     { return $this->id; }
    public function getAuthPassword()       { return null; }
}