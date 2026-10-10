<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $guarded = [];

    protected $casts = ['scheduled_for' => 'datetime'];

    public function visitor() { return $this->belongsTo(Visitor::class); }
    public function host() { return $this->belongsTo(User::class, 'host_id'); }
    public function area() { return $this->belongsTo(Area::class); }
    public function plant() { return $this->belongsTo(Plant::class); }
    public function accessLogs() { return $this->hasMany(AccessLog::class); }
}