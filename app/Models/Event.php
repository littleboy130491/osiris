<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    
    use HasFactory;
    protected $fillable = [
        'visitor_id',
        'tracking_session_id',
        'event_name',
        'url',
        'referrer',
        'gclid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'query_strings',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'os',
        'meta',
    ];

    protected $casts = [
        'query_strings' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function session()
    {
        return $this->belongsTo(TrackingSession::class);
    }

    public function scopeFromVisitor($query, $visitorId)
    {
        return $query->where('visitor_id', $visitorId);
    }

    public function scopeFromSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByEventName($query, $name)
    {
        return $query->where('event_name', $name);
    }

    public function trackingSession()
    {
        return $this->belongsTo(TrackingSession::class);
    }


}
