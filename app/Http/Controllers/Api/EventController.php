<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Jenssegers\Agent\Agent;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $agent = new Agent();

        // Extract known attribution params
        $gclid = $request->input('gclid');
        $fbclid = $request->input('fbclid');
        $utm_source = $request->input('utm_source');
        $utm_medium = $request->input('utm_medium');
        $utm_campaign = $request->input('utm_campaign');

        // Collect all query strings (future proof)
        $queryStrings = $request->input('query_strings', []);

        // Device info
        $agent->setUserAgent($request->userAgent());

        $event = Event::create([
            'user_id' => $request->input('user_id'),
            'event_name' => $request->input('event'),
            'url' => $request->input('url'),
            'referrer' => $request->input('referrer'),

            // Indexed attribution
            'gclid' => $gclid,
            'fbclid' => $fbclid,
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,

            // Raw dump
            'query_strings' => $queryStrings,

            // Visitor info
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),

            // Any custom payload
            'meta' => $request->input('data', []),
        ]);

        return response()->json(['success' => true, 'id' => $event->id]);
    }
}
