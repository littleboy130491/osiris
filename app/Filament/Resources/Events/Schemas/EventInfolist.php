<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('visitor.visitor_uuid'),
                TextEntry::make('trackingSession.session_uuid'),
                TextEntry::make('event_name'),
                TextEntry::make('url')
                    ->placeholder('-'),
                TextEntry::make('referrer')
                    ->placeholder('-'),
                TextEntry::make('gclid')
                    ->placeholder('-'),
                TextEntry::make('fbclid')
                    ->placeholder('-'),
                TextEntry::make('utm_source')
                    ->placeholder('-'),
                TextEntry::make('utm_medium')
                    ->placeholder('-'),
                TextEntry::make('utm_campaign')
                    ->placeholder('-'),
                TextEntry::make('query_strings')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ip_address')
                    ->placeholder('-'),
                TextEntry::make('user_agent')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('device')
                    ->placeholder('-'),
                TextEntry::make('browser')
                    ->placeholder('-'),
                TextEntry::make('os')
                    ->placeholder('-'),
                TextEntry::make('meta')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
