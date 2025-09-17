<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_uuid',
        'name',
        'email',
        'phone',
        'starred',
        'notes',
    ];

    public function trackingSessions()
    {
        return $this->hasMany(TrackingSession::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
