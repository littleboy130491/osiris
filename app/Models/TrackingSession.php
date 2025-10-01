<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function firstEvent(): HasOne
    {
        return $this->hasOne(Event::class)->oldestOfMany();
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
