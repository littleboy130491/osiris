<?php

namespace App\Filament\Resources\TrackingSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TrackingSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_uuid')
                    ->searchable(),
                TextColumn::make('visitor.visitor_uuid')
                    ->url(fn($record) => route('filament.admin.resources.visitors.view', $record->visitor_id))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : null),
                TextColumn::make('ended_at')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : null),
                TextColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : null),
                TextColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') : null),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }
}
