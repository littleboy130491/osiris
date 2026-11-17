<?php

namespace App\Mcp\Support;

use App\Models\Event;

class EventPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Event $event): array
    {
        return array_filter([
            'id' => $event->id,
            'created_at' => $event->created_at?->toDateTimeString(),
            'event_name' => $event->event_name,
            'url' => $event->url,
            'referrer' => $event->referrer,
            'visitor_id' => $event->visitor_id,
            'visitor_uuid' => $event->relationLoaded('visitor') ? $event->visitor?->visitor_uuid : null,
            'tracking_session_id' => $event->tracking_session_id,
            'gclid' => $event->gclid,
            'fbclid' => $event->fbclid,
            'utm_source' => $event->utm_source,
            'utm_medium' => $event->utm_medium,
            'utm_campaign' => $event->utm_campaign,
            'query_strings' => $event->query_strings ?: null,
            'meta' => $event->meta ?: null,
            'device' => $event->device,
            'browser' => $event->browser,
            'os' => $event->os,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
