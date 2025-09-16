<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // how many dummy events you want
        $count = 500;

        Event::factory()
            ->count($count)
            ->create();
    }
}
