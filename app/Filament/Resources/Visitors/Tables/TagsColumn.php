<?php

namespace App\Filament\Resources\Visitors\Tables;

use Filament\Tables\Columns\Column;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

class TagsColumn extends Column
{
    protected string $view = 'filament.tables.columns.tags';

    protected function resolveDefaultDisplayValueUsing($state): ?string
    {
        return $state;
    }

    public function getTags(Model $record): array
    {
        return $record->tags->pluck('name')->toArray();
    }

    public function getTagColors(Model $record): array
    {
        return $record->tags->pluck('color', 'name')->toArray();
    }
}
