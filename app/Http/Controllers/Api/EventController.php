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
use Illuminate\Support\Facades\DB;

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

        $validator = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'url' => 'nullable|url',
            'referrer' => 'nullable|string|max:2000',
            'visitor_uuid' => 'nullable|uuid|string|max:255',
            'session_uuid' => 'nullable|uuid|string|max:255',
            'visitor_name' => 'nullable|string|max:255',
            'visitor_email' => 'nullable|email|max:255',
            'visitor_phone' => 'nullable|string|max:50',
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
     * Find or create a visitor (FIXED - prevents race condition)
     */
    private function findOrCreateVisitor(Request $request): Visitor
    {
        $visitorUuid = $request->input('visitor_uuid') ?: (string) Str::uuid();

        // Use firstOrCreate to prevent race conditions
        $visitor = Visitor::firstOrCreate(
            ['visitor_uuid' => $visitorUuid],
            [
                'name' => $request->input('visitor_name'),
                'email' => $request->input('visitor_email'),
                'phone' => $request->input('visitor_phone'),
            ]
        );

        // Update visitor info if they already existed and new data is provided
        if (!$visitor->wasRecentlyCreated) {
            $updateData = [];

            if ($request->filled('visitor_name') && $visitor->name !== $request->input('visitor_name')) {
                $updateData['name'] = $request->input('visitor_name');
            }
            if ($request->filled('visitor_email') && $visitor->email !== $request->input('visitor_email')) {
                $updateData['email'] = $request->input('visitor_email');
            }
            if ($request->filled('visitor_phone') && $visitor->phone !== $request->input('visitor_phone')) {
                $updateData['phone'] = $request->input('visitor_phone');
            }

            if (!empty($updateData)) {
                $visitor->update($updateData);
            }
        }

        return $visitor;
    }

    /**
     * Find or create a session (FIXED - prevents race condition)
     */
    private function findOrCreateSession(Request $request, Visitor $visitor, Agent $agent): TrackingSession
    {
        $sessionUuid = $request->input('session_uuid') ?: (string) Str::uuid();

        // Use firstOrCreate to prevent race conditions
        $session = TrackingSession::firstOrCreate(
            [
                'session_uuid' => $sessionUuid,
                'visitor_id' => $visitor->id
            ],
            [
                'device' => $agent->device(),
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'started_at' => now(),
            ]
        );

        return $session;
    }

    /**
     * Create the tracking event
     */
    private function createEvent(Request $request, Visitor $visitor, TrackingSession $session, Agent $agent): Event
    {
        // Get the URL from request & referrer
        $url = $request->input('url');
        $referrer = $request->input('referrer');

        // Truncate if too long
        if ($url && strlen($url) > 255) {
            $url = substr($url, 0, 255);
        }

        if ($referrer && strlen($referrer) > 255) {
            $referrer = substr($referrer, 0, 255);
        }

        $queryStrings = $request->input('query_strings', []);

        return Event::create([
            'visitor_id' => $visitor->id,
            'tracking_session_id' => $session->id,
            'event_name' => $request->input('event'),
            'url' => $url,
            'referrer' => $referrer,

            // Attribution (auto-extracted)
            'gclid' => $queryStrings['gclid'] ?? null,
            'fbclid' => $queryStrings['fbclid'] ?? null,
            'utm_source' => $queryStrings['utm_source'] ?? null,
            'utm_medium' => $queryStrings['utm_medium'] ?? null,
            'utm_campaign' => $queryStrings['utm_campaign'] ?? null,
            'utm_content' => $queryStrings['utm_content'] ?? null,
            'utm_term' => $queryStrings['utm_term'] ?? null,

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