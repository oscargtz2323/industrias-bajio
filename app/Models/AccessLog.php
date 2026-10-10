<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function visit() { return $this->belongsTo(Visit::class); }
    public function area() { return $this->belongsTo(Area::class); }
    public function badge() { return $this->belongsTo(Badge::class); }
    public function guardIn() { return $this->belongsTo(User::class, 'guard_in_id'); }
}