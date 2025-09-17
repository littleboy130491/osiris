<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor.visitor_uuid')
                    ->label('Visitor')
                    ->url(fn($record) => route('filament.admin.resources.visitors.view', $record->visitor_id))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trackingSession.session_uuid')
                    ->label('Session')
                    ->url(fn($record) => route('filament.admin.resources.tracking-sessions.view', $record->tracking_session_id))
                    ->searchable()
                    ->sortable(),

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
            ])
            ->filters([
                // You can add filters later, e.g. filter by event type or by UTM source
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
