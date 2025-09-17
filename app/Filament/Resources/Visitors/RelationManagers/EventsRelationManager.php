<?php

namespace App\Filament\Resources\Visitors\RelationManagers;

use App\Filament\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $relatedResource = EventResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('event_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('url')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('referrer')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('gclid')
                    ->searchable(),

                TextColumn::make('fbclid')
                    ->searchable(),

                TextColumn::make('utm_source')
                    ->searchable(),

                TextColumn::make('utm_medium')
                    ->searchable(),

                TextColumn::make('utm_campaign')
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->searchable(),

                TextColumn::make('device')
                    ->searchable(),

                TextColumn::make('browser')
                    ->searchable(),

                TextColumn::make('os')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
