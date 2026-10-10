<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $guarded = [];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function areas() { return $this->hasMany(Area::class); }
}