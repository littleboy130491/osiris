<?php

namespace App\Filament\Resources\TrackingSessions\Pages;

use App\Filament\Resources\TrackingSessions\TrackingSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrackingSession extends ViewRecord
{
    protected static string $resource = TrackingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
