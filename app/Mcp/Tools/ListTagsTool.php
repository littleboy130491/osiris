<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Models\Tag;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListTagsTool extends Tool
{
    use InteractsWithAnalytics;

    protected string $name = 'list-tags';

    protected string $description = 'List all visitor tags with how many visitors carry each one.';

    public function handle(Request $request): Response
    {
        return $this->json(
            Tag::withCount('visitors')
                ->orderBy('name')
                ->get()
                ->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'visitors_count' => $tag->visitors_count,
                ])
        );
    }
}
