<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    
    use HasFactory;
    protected $fillable = [
        'user_id',
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
    ];
}
