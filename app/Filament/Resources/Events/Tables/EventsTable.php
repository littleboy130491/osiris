<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;

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
    // Event Type
    SelectFilter::make('event_name')
        ->label('Event Type')
        ->options(
            \App\Models\Event::distinct()
                ->pluck('event_name', 'event_name')
                ->filter()
                ->sort()
                ->toArray()
        ),

    // Device
    SelectFilter::make('device')
        ->options(
            \App\Models\Event::distinct()
                ->pluck('device', 'device')
                ->filter()
                ->sort()
                ->toArray()
        ),

    // Browser
    SelectFilter::make('browser')
        ->options(
            \App\Models\Event::distinct()
                ->pluck('browser', 'browser')
                ->filter()
                ->sort()
                ->toArray()
        ),

    // UTM source
    SelectFilter::make('utm_source')
        ->options(
            \App\Models\Event::distinct()
                ->pluck('utm_source', 'utm_source')
                ->filter()
                ->sort()
                ->toArray()
        ),

    // Month & year filter
    Filter::make('period')
        ->schema([
            Select::make('month')
                ->options([
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December',
                ]),
            Select::make('year')
                ->options(
                    collect(range(now()->year, now()->year - 5))
                        ->mapWithKeys(fn($y) => [$y => (string) $y])
                        ->toArray()
                ),
            ])
        ->query(function (Builder $query, array $data): Builder {
            $month = $data['month'];
            $year = $data['year'];

            if ($month) {
                $query = $query->whereMonth('created_at', $month);
            }
            if ($year) {
                $query = $query->whereYear('created_at', $year);
            }
            return $query;
        
        }),

  
    // Date range filter (safe)
    Filter::make('created_at')
        ->form([
            \Filament\Forms\Components\DatePicker::make('from'),
            \Filament\Forms\Components\DatePicker::make('until'),
        ])
        ->query(function (Builder $query, array $data): Builder {
            $from = $data['from'] ?? null;
            $until = $data['until'] ?? null;

            return $query
                ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                ->when($until, fn($q) => $q->whereDate('created_at', '<=', $until));
        }),
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
