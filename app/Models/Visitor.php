<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $guarded = [];

    protected $casts = ['is_blocked' => 'boolean'];

    public function visits() { return $this->hasMany(Visit::class); }
}