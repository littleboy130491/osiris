<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'follow-up', 'color' => '#F59E0B'],
            ['name' => 'proposal', 'color' => '#3B82F6'],
            ['name' => 'hot-lead', 'color' => '#EF4444'],
            ['name' => 'cold-lead', 'color' => '#6B7280'],
            ['name' => 'customer', 'color' => '#10B981'],
            ['name' => 'interested', 'color' => '#8B5CF6'],
            ['name' => 'not-interested', 'color' => '#F97316'],
            ['name' => 'contacted', 'color' => '#06B6D4'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate($tag);
        }
    }
}
