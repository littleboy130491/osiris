<?php

namespace App\Mcp\Concerns;

use App\Models\Visitor;
use Laravel\Mcp\Request;

trait ResolvesVisitor
{
    protected function visitorRules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'required_without:visitor_uuid'],
            'visitor_uuid' => ['nullable', 'string', 'required_without:id'],
        ];
    }

    protected function resolveVisitor(Request $request): ?Visitor
    {
        return Visitor::query()
            ->when($request->get('id'), fn ($q, $id) => $q->whereKey($id))
            ->when($request->get('visitor_uuid'), fn ($q, $uuid) => $q->where('visitor_uuid', $uuid))
            ->first();
    }

    protected function presentVisitor(Visitor $visitor): array
    {
        $visitor->load('tags:id,name');

        return [
            'id' => $visitor->id,
            'visitor_uuid' => $visitor->visitor_uuid,
            'name' => $visitor->name,
            'email' => $visitor->email,
            'phone' => $visitor->phone,
            'starred' => (bool) $visitor->starred,
            'notes' => $visitor->notes,
            'tags' => $visitor->tags->pluck('name')->all(),
        ];
    }
}
