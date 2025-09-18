<?php

namespace App\Filament\Resources\TrackingSessions\RelationManagers;

use App\Filament\Resources\Visitors\VisitorResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use App\Filament\Resources\Events\Tables\EventsRelationTable;
class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $relatedResource = VisitorResource::class;

    public function table(Table $table): Table
    {
        return EventsRelationTable::configure($table);
    }
}
