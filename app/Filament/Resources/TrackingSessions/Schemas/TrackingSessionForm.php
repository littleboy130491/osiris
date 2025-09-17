<?php

namespace App\Filament\Resources\TrackingSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TrackingSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('session_uuid')
                    ->required(),
                TextInput::make('visitor_id')
                    ->required()
                    ->numeric(),
                TextInput::make('device'),
                TextInput::make('browser'),
                TextInput::make('os'),
                TextInput::make('ip_address'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('ended_at'),
            ]);
    }
}
