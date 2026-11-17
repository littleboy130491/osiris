<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Mcp\Concerns\ResolvesVisitor;
use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[IsDestructive(false)]
class TagVisitorTool extends Tool
{
    use InteractsWithAnalytics, ResolvesVisitor;

    protected string $name = 'tag-visitor';

    protected string $description = <<<'MARKDOWN'
        Add tags to or remove tags from a visitor (by `id` or `visitor_uuid`). Tag names are matched
        case-insensitively against existing tags (see `list-tags`); a tag in `add` that does not exist yet is
        created. Removing only detaches the tag from this visitor; the tag itself is never deleted.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            ...$this->visitorRules(),
            'add' => ['nullable', 'array', 'max:20'],
            'add.*' => ['string', 'min:1', 'max:255'],
            'remove' => ['nullable', 'array', 'max:20'],
            'remove.*' => ['string', 'min:1', 'max:255'],
        ]);

        $add = collect($request->get('add', []))->map(fn ($n) => trim($n))->filter()->unique()->values();
        $remove = collect($request->get('remove', []))->map(fn ($n) => trim($n))->filter()->unique()->values();

        if ($add->isEmpty() && $remove->isEmpty()) {
            return Response::error('Pass tag names in `add` and/or `remove`.');
        }

        $visitor = $this->resolveVisitor($request);

        if (! $visitor) {
            return Response::error('Visitor not found.');
        }

        $find = fn (string $name) => Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        $created = [];
        $addIds = $add->map(function (string $name) use ($find, &$created) {
            if ($tag = $find($name)) {
                return $tag->id;
            }
            $created[] = $name;

            return Tag::create(['name' => $name])->id;
        });

        $removeIds = $remove->map($find)->filter()->pluck('id');

        $visitor->tags()->syncWithoutDetaching($addIds->all());
        if ($removeIds->isNotEmpty()) {
            $visitor->tags()->detach($removeIds->all());
        }

        Log::info('MCP tag-visitor', [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'visitor_id' => $visitor->id,
            'add' => $add->all(),
            'remove' => $remove->all(),
        ]);

        return $this->json([
            'created_tags' => $created,
            'visitor' => $this->presentVisitor($visitor),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Visitor numeric ID.'),
            'visitor_uuid' => $schema->string()->description('Visitor UUID (alternative to id).'),
            'add' => $schema->array()->items($schema->string())->description('Tag names to attach, e.g. ["proposal"].'),
            'remove' => $schema->array()->items($schema->string())->description('Tag names to detach from this visitor.'),
        ];
    }
}
