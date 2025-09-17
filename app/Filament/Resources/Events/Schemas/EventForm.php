<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use App\Models\Visitor;
use App\Models\TrackingSession;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('visitor_id')
                    ->label('Visitor')
                    ->relationship('visitor', 'visitor_uuid')
                    ->searchable()
                    ->nullable(),

                Select::make('tracking_session_id')
                    ->label('Tracking Session')
                    ->relationship('trackingSession', 'session_uuid')
                    ->searchable()
                    ->nullable(),

                TextInput::make('event_name')
                    ->required(),

                TextInput::make('url')
                    ->url(),

                TextInput::make('referrer'),

                TextInput::make('gclid'),
                TextInput::make('fbclid'),
                TextInput::make('utm_source'),
                TextInput::make('utm_medium'),
                TextInput::make('utm_campaign'),

                KeyValue::make('query_strings')
                    ->columnSpanFull(),

                TextInput::make('ip_address'),

                Textarea::make('user_agent')
                    ->columnSpanFull(),

                TextInput::make('device'),
                TextInput::make('browser'),
                TextInput::make('os'),

                KeyValue::make('meta')
                    ->columnSpanFull(),
            ]);
    }
}
