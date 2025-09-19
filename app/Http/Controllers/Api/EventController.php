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

        // Updated validation to match what JS sends
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'url' => 'nullable|url',
            'referrer' => 'nullable|string|max:2000', // Changed from url to string (referrer can be invalid URL)
            'visitor_uuid' => 'nullable|uuid',
            'session_uuid' => 'nullable|uuid',
            'visitor_name' => 'nullable|string|max:255',    // Added these
            'visitor_email' => 'nullable|email|max:255',    // Added these  
            'visitor_phone' => 'nullable|string|max:50',    // Added these
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
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            // Step 1: Find or create visitor
            $visitor = $this->findOrCreateVisitor($request);

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
            \Log::error('Event tracking error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process tracking event',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find or create a visitor
     */
    private function findOrCreateVisitor(Request $request): Visitor
    {
        $visitorUuid = $request->input('visitor_uuid');

        if ($visitorUuid) {
            $visitor = Visitor::where('visitor_uuid', $visitorUuid)->first();
            if ($visitor) {
                // Update visitor info if provided
                if ($request->filled('visitor_name') || $request->filled('visitor_email') || $request->filled('visitor_phone')) {
                    $visitor->update([
                        'name' => $request->input('visitor_name', $visitor->name),
                        'email' => $request->input('visitor_email', $visitor->email),
                        'phone' => $request->input('visitor_phone', $visitor->phone),
                    ]);
                }
                return $visitor;
            }
        }

        if (!$visitorUuid) {
            $visitorUuid = (string) Str::uuid();
        }

        return Visitor::create([
            'visitor_uuid' => $visitorUuid,
            'name' => $request->input('visitor_name'),
            'email' => $request->input('visitor_email'),
            'phone' => $request->input('visitor_phone'),
        ]);
    }

    /**
     * Find or create a session
     */
    private function findOrCreateSession(Request $request, Visitor $visitor, Agent $agent): TrackingSession
    {
        $sessionUuid = $request->input('session_uuid');

        if ($sessionUuid) {
            $session = TrackingSession::where('session_uuid', $sessionUuid)
                ->where('visitor_id', $visitor->id)
                ->first();
            if ($session) {
                return $session;
            }
        }

        if (!$sessionUuid) {
            $sessionUuid = (string) Str::uuid();
        }

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
        $queryStrings = $request->input('query_strings', []);

        return Event::create([
            'visitor_id' => $visitor->id,
            'tracking_session_id' => $session->id,
            'event_name' => $request->input('event'),
            'url' => $request->input('url'),
            'referrer' => $request->input('referrer'),

            // Attribution (auto-extracted) - Added null coalescing for safety
            'gclid' => $queryStrings['gclid'] ?? null,
            'fbclid' => $queryStrings['fbclid'] ?? null,
            'utm_source' => $queryStrings['utm_source'] ?? null,
            'utm_medium' => $queryStrings['utm_medium'] ?? null,
            'utm_campaign' => $queryStrings['utm_campaign'] ?? null,
            'utm_content' => $queryStrings['utm_content'] ?? null,  // Added this
            'utm_term' => $queryStrings['utm_term'] ?? null,        // Added this

            // Raw query params dump
            'query_strings' => $queryStrings,

            // Visitor/device info
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),

            // Extra metadata from GA4 event params
            'meta' => $request->input('data', []),
        ]);
    }
}