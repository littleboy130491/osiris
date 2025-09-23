<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;

class EventsRelationTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor.visitor_uuid')
                    ->label('Visitor')
                    ->url(fn($record) => route('filament.admin.resources.visitors.view', $record->visitor_id))
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('trackingSession.session_uuid')
                    ->label('Session')
                    ->url(fn($record) => route('filament.admin.resources.tracking-sessions.view', $record->tracking_session_id))
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                ToggleColumn::make('visitor.starred')
                    ->label('Starred')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('event_name')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('url')
                    ->searchable()
                    ->toggleable()
                    ->url(fn($record) => $record->url)
                    ->limit(50),

                TextColumn::make('referrer')
                    ->searchable()
                    ->toggleable()
                    ->url(fn($record) => $record->referrer)
                    ->limit(50),

                TextColumn::make('gclid')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('fbclid')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('utm_source')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('utm_medium')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('utm_campaign')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('device')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('browser')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('os')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', direction: 'desc')
            ->filters([
                TernaryFilter::make('Starred')
                    ->relationship('visitor', 'starred'),
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

                // Domain & Page Path

                Filter::make('domain_path')
                    ->schema([
                        // Domain selector
                        Select::make('domain')
                            ->options(function ($livewire) {
                                 // Get the records currently in the table query
                                $records = $livewire->getTableRecords();
                                return $records
                                    ->pluck('url')
                                    ->map(function ($url) {
                                        if (empty($url)) {
                                            return null;
                                        }
                                        $host = parse_url($url, PHP_URL_HOST);
                                        // remove leading www.
                                        if ($host) {
                                            return Str::replaceFirst('www.', '', $host);
                                        }
                                        return null;
                                    })
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->mapWithKeys(fn($d) => [$d => $d])
                                    ->toArray();
                            }),

                        // Page path selector (without query string)
                        Select::make('page_path')
                            ->options(function ($livewire) {
                                 // Get the records currently in the table query
                                $records = $livewire->getTableRecords();
                                return $records
                                    ->pluck('url')
                                    ->map(function ($url) {
                                        if (empty($url)) {
                                            return null;
                                        }
                                        $path = parse_url($url, PHP_URL_PATH);
                                        return $path ?: '/';
                                    })
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->mapWithKeys(fn($p) => [$p => $p])
                                    ->toArray();
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['domain'])) {
                            $query->whereRaw("
                replace(
                    substr(
                        substr(url, instr(url, '//')+2),
                        1,
                        instr(substr(url, instr(url, '//')+2), '/')-1
                    ),
                'www.','') = ?
            ", [$data['domain']]);
                        }

                        if (!empty($data['page_path'])) {
                            $query->whereRaw("substr(url, instr(url, '://')+3) like ?", ['%' . $data['page_path'] . '%']);
                        }

                        return $query;
                    }),


                // Date range filter (safe)
                Filter::make('created_at')
                    ->schema([
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
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
