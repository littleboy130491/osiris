<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

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

    public function firstEvent(): HasOne
    {
        return $this->hasOne(Event::class)->oldestOfMany();
    }

    public function firstGclidEvent(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereNotNull('gclid')
            ->orderBy('created_at', 'asc')
            ->limit(1);
    }

    public function firstFbclidEvent(): HasOne
    {
        return $this->hasOne(Event::class)
            ->whereNotNull('fbclid')
            ->orderBy('created_at', 'asc')
            ->limit(1);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
