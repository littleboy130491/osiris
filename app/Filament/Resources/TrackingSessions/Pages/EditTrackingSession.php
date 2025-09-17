<?php

namespace App\Filament\Resources\TrackingSessions\Pages;

use App\Filament\Resources\TrackingSessions\TrackingSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTrackingSession extends EditRecord
{
    protected static string $resource = TrackingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
