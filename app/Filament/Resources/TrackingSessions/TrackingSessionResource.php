<?php

namespace App\Filament\Resources\TrackingSessions;

use App\Filament\Resources\TrackingSessions\Pages\CreateTrackingSession;
use App\Filament\Resources\TrackingSessions\Pages\EditTrackingSession;
use App\Filament\Resources\TrackingSessions\Pages\ListTrackingSessions;
use App\Filament\Resources\TrackingSessions\Pages\ViewTrackingSession;
use App\Filament\Resources\TrackingSessions\Schemas\TrackingSessionForm;
use App\Filament\Resources\TrackingSessions\Schemas\TrackingSessionInfolist;
use App\Filament\Resources\TrackingSessions\Tables\TrackingSessionsTable;
use App\Models\TrackingSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrackingSessionResource extends Resource
{
    protected static ?string $model = TrackingSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TrackingSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TrackingSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrackingSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackingSessions::route('/'),
            'create' => CreateTrackingSession::route('/create'),
            'view' => ViewTrackingSession::route('/{record}'),
            'edit' => EditTrackingSession::route('/{record}/edit'),
        ];
    }
}
