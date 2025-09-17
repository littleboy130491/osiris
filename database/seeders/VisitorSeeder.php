<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Visitor;
use App\Models\Event;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        Visitor::factory()
            ->count(50)
            ->create()
            ->each(function ($visitor) {
                // Each visitor gets 3 sessions
                $sessions = $visitor->trackingSessions()->createMany(
                    \App\Models\TrackingSession::factory()->count(3)->make()->toArray()
                );

                // For each session, attach 10 events
                $sessions->each(function ($session) use ($visitor) {
                    $events = Event::factory()->count(10)->make([
                        'visitor_id' => $visitor->id,
                        'tracking_session_id' => $session->id,
                    ]);

                    $session->events()->createMany($events->toArray());
                });
            });
    }
}
