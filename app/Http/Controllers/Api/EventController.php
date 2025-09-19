<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Visitor;
use App\Models\TrackingSession;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $restrictDomain = config('osiris.restrict-domain');

        if ($restrictDomain) {
            $allowedDomains = config('osiris.allowed-domains');

            if (!in_array($request->header('Origin'), $allowedDomains)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }
       
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'url' => 'nullable|url',
            'referrer' => 'nullable|url',
            'visitor_uuid' => 'nullable|uuid',
            'session_uuid' => 'nullable|uuid',
            'gclid' => 'nullable|string|max:255',
            'fbclid' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'query_strings' => 'nullable|array',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Initialize agent for device detection
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            // Step 1: Find or create visitor
            $visitor = $this->findOrCreateVisitor($request, $agent);

            // Step 2: Find or create session
            $session = $this->findOrCreateSession($request, $visitor, $agent);

            // Step 3: Create the event
            $event = $this->createEvent($request, $visitor, $session, $agent);

            return response()->json([
                'success' => true,
                'event_id' => $event->id,
                'visitor_uuid' => $visitor->visitor_uuid,
                'session_uuid' => $session->session_uuid,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to process tracking event',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find or create a visitor based on visitor_uuid or IP + user agent
     */
    private function findOrCreateVisitor(Request $request, Agent $agent): Visitor
    {
        $visitorUuid = $request->input('visitor_uuid');

        if ($visitorUuid) {
            // Try to find existing visitor by UUID
            $visitor = Visitor::where('visitor_uuid', $visitorUuid)->first();
            if ($visitor) {
                return $visitor;
            }
        }

        // Generate a new visitor UUID if not provided or not found
        if (!$visitorUuid) {
            $visitorUuid = (string) Str::uuid();
        }

        // Create new visitor
        return Visitor::create([
            'visitor_uuid' => $visitorUuid,
            'name' => $request->input('visitor_name'),
            'email' => $request->input('visitor_email'),
            'phone' => $request->input('visitor_phone'),
        ]);
    }

    /**
     * Find or create a session for the visitor
     */
    private function findOrCreateSession(Request $request, Visitor $visitor, Agent $agent): TrackingSession
    {
        $sessionUuid = $request->input('session_uuid');

        if ($sessionUuid) {
            // Try to find existing session by UUID
            $session = TrackingSession::where('session_uuid', $sessionUuid)
                ->where('visitor_id', $visitor->id)
                ->first();
            if ($session) {
                return $session;
            }
        }

        // Generate a new session UUID if not provided or not found
        if (!$sessionUuid) {
            $sessionUuid = (string) Str::uuid();
        }

        // Create new session
        return TrackingSession::create([
            'session_uuid' => $sessionUuid,
            'visitor_id' => $visitor->id,
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);
    }

    /**
     * Create the tracking event
     */
    private function createEvent(Request $request, Visitor $visitor, TrackingSession $session, Agent $agent): Event
    {
        return Event::create([
            'visitor_id' => $visitor->id,
            'tracking_session_id' => $session->id,
            'event_name' => $request->input('event'),
            'url' => $request->input('url'),
            'referrer' => $request->input('referrer'),

            // Attribution parameters
            'gclid' => $request->input('gclid'),
            'fbclid' => $request->input('fbclid'),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),

            // Query strings dump
            'query_strings' => $request->input('query_strings', []),

            // Visitor info (redundant but useful for analytics)
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),

            // Custom metadata
            'meta' => $request->input('data', []),
        ]);
    }
}
