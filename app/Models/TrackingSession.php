<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_uuid',
        'visitor_id',
        'device',
        'browser',
        'os',
        'ip_address',
        'user_agent',
        'started_at',
        'ended_at',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
