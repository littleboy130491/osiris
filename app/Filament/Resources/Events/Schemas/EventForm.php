<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required(),
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
                Textarea::make('query_strings')
                    ->columnSpanFull(),
                TextInput::make('ip_address'),
                Textarea::make('user_agent')
                    ->columnSpanFull(),
                TextInput::make('device'),
                TextInput::make('browser'),
                TextInput::make('os'),
                Textarea::make('meta')
                    ->columnSpanFull(),
            ]);
    }
}
