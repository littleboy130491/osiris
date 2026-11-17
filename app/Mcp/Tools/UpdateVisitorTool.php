<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithAnalytics;
use App\Mcp\Concerns\ResolvesVisitor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[IsDestructive(false)]
class UpdateVisitorTool extends Tool
{
    use InteractsWithAnalytics, ResolvesVisitor;

    protected string $name = 'update-visitor';

    protected string $description = <<<'MARKDOWN'
        Update a visitor's profile (by `id` or `visitor_uuid`): name, email, phone, notes and starred flag.
        Only the fields you pass are changed; pass an empty string to clear name/email/phone/notes.
        Use `append_notes` to add a line to existing notes instead of replacing them.
        Returns the visitor before and after the change. Visitors cannot be deleted through this server.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $request->validate([
            ...$this->visitorRules(),
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'append_notes' => ['nullable', 'string', 'max:10000'],
            'starred' => ['nullable', 'boolean'],
        ]);

        $visitor = $this->resolveVisitor($request);

        if (! $visitor) {
            return Response::error('Visitor not found.');
        }

        $arguments = $request->all();
        $changes = [];

        foreach (['name', 'email', 'phone', 'notes'] as $field) {
            if (array_key_exists($field, $arguments)) {
                $value = trim((string) $arguments[$field]);
                $changes[$field] = $value === '' ? null : $value;
            }
        }

        if (filled($arguments['append_notes'] ?? null)) {
            $current = $changes['notes'] ?? $visitor->notes;
            $changes['notes'] = trim(($current ? $current."\n" : '').trim($arguments['append_notes']));
        }

        if (array_key_exists('starred', $arguments) && $arguments['starred'] !== null) {
            $changes['starred'] = (bool) $arguments['starred'];
        }

        if ($changes === []) {
            return Response::error('Nothing to update. Pass at least one of: name, email, phone, notes, append_notes, starred.');
        }

        if (isset($changes['email']) && ! filter_var($changes['email'], FILTER_VALIDATE_EMAIL)) {
            return Response::error('Invalid email address.');
        }

        $before = $this->presentVisitor($visitor);
        $visitor->fill($changes)->save();

        Log::info('MCP update-visitor', [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'visitor_id' => $visitor->id,
            'changes' => array_keys($changes),
        ]);

        return $this->json([
            'updated_fields' => array_keys($changes),
            'before' => $before,
            'after' => $this->presentVisitor($visitor->refresh()),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Visitor numeric ID.'),
            'visitor_uuid' => $schema->string()->description('Visitor UUID (alternative to id).'),
            'name' => $schema->string()->description('New name. Empty string clears it.'),
            'email' => $schema->string()->description('New email. Empty string clears it.'),
            'phone' => $schema->string()->description('New phone. Empty string clears it.'),
            'notes' => $schema->string()->description('Replace notes entirely. Empty string clears them.'),
            'append_notes' => $schema->string()->description('Append a new line to the existing notes.'),
            'starred' => $schema->boolean()->description('Star (true) or unstar (false) the visitor.'),
        ];
    }
}
