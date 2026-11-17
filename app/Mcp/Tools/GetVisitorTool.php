<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Mcp\Support\EventPresenter;
use App\Models\Event;
use App\Models\TrackingSession;
use App\Models\Visitor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetVisitorTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'get-visitor';

    protected string $description = <<<'MARKDOWN'
        Full detail for one visitor (by numeric `id` or `visitor_uuid`): profile, notes, tags, sessions with device
        info, and their event journey in chronological order (most recent N events).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            'id' => ['nullable', 'integer', 'required_without:visitor_uuid'],
            'visitor_uuid' => ['nullable', 'string', 'required_without:id'],
            'events_limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $visitor = Visitor::query()
            ->with('tags:id,name')
            ->withCount(['events', 'trackingSessions'])
            ->when($request->get('id'), fn ($q, $id) => $q->whereKey($id))
            ->when($request->get('visitor_uuid'), fn ($q, $uuid) => $q->where('visitor_uuid', $uuid))
            ->first();

        if (! $visitor) {
            return Response::error('Visitor not found.');
        }

        $sessions = $visitor->trackingSessions()
            ->withCount('events')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (TrackingSession $s) => [
                'id' => $s->id,
                'session_uuid' => $s->session_uuid,
                'started_at' => ($s->started_at ?? $s->created_at)?->toDateTimeString(),
                'ended_at' => $s->ended_at?->toDateTimeString(),
                'events_count' => $s->events_count,
                'device' => $s->device,
                'browser' => $s->browser,
                'os' => $s->os,
                'ip_address' => $s->ip_address,
            ]);

        $limit = (int) $request->get('events_limit', 100);

        $events = $visitor->events()
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Event $e) => EventPresenter::present($e));

        return $this->json([
            'visitor' => [
                'id' => $visitor->id,
                'visitor_uuid' => $visitor->visitor_uuid,
                'name' => $visitor->name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'starred' => (bool) $visitor->starred,
                'notes' => $visitor->notes,
                'tags' => $visitor->tags->pluck('name')->all(),
                'first_seen_at' => $visitor->created_at?->toDateTimeString(),
                'events_count' => $visitor->events_count,
                'sessions_count' => $visitor->tracking_sessions_count,
            ],
            'sessions' => $sessions,
            'events_shown' => $events->count(),
            'events' => $events,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Visitor numeric ID.'),
            'visitor_uuid' => $schema->string()->description('Visitor UUID (alternative to id).'),
            'events_limit' => $schema->integer()->min(1)->max(500)->description('Most recent N events to include. Default 100.'),
        ];
    }
}
