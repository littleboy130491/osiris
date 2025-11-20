<?php

namespace App\Filament\Resources\Visitors\Tables;

use App\Models\Visitor;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class VisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if ($query->getModel() instanceof Visitor) {
                    $query->with('firstEvent', 'firstGclidEvent', 'firstFbclidEvent', 'tags');
                }

                return $query;
            })
            ->columns([
                TextColumn::make('visitor_uuid')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('starred')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->color(function ($state, $record) {
                        if (!$record) {
                            return 'gray';
                        }
                        $tag = $record->tags->where('name', $state)->first();
                        return $tag?->color ?? 'gray';
                    }),
                TextColumn::make('firstEvent.referrer')
                    ->label('Referrer')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstGclidEvent.gclid')
                    ->label('GCLID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstFbclidEvent.fbclid')
                    ->label('FBCLID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.utm_source')
                    ->label('UTM Source')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.utm_medium')
                    ->label('UTM Medium')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.utm_campaign')
                    ->label('UTM Campaign')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.device')
                    ->label('Device')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.browser')
                    ->label('Browser')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firstEvent.os')
                    ->label('OS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i:s') : null),
                TextColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state ? $state->format('Y-m-d H:i:s') : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('starred'),
                TernaryFilter::make('has_gclid')
                    ->label('Has GCLID')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('firstGclidEvent', fn(Builder $query) => $query->whereNotNull('gclid')->where('gclid', '!=', '')),
                        false: fn(Builder $query) => $query->whereDoesntHave('firstGclidEvent')
                            ->orWhereHas('firstGclidEvent', fn(Builder $query) => $query->whereNull('gclid')->orWhere('gclid', '')),
                        blank: fn(Builder $query) => $query,
                    ),
                TernaryFilter::make('has_fbclid')
                    ->label('Has FBCLID')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('firstFbclidEvent', fn(Builder $query) => $query->whereNotNull('fbclid')->where('fbclid', '!=', '')),
                        false: fn(Builder $query) => $query->whereDoesntHave('firstFbclidEvent')
                            ->orWhereHas('firstFbclidEvent', fn(Builder $query) => $query->whereNull('fbclid')->orWhere('fbclid', '')),
                        blank: fn(Builder $query) => $query,
                    ),
                SelectFilter::make('tag')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->bulkActions([
                ExportBulkAction::make()
            ]);
    }
}