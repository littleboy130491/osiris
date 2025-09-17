<?php

namespace App\Filament\Resources\TrackingSessions\Pages;

use App\Filament\Resources\TrackingSessions\TrackingSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackingSessions extends ListRecords
{
    protected static string $resource = TrackingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
