<?php

namespace App\Filament\Resources\Visitors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class VisitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('visitor_uuid')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone'),
                Toggle::make('starred')
                    ->required(),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->unique(),
                        Select::make('color')
                            ->required()
                            ->default('primary')
                            ->options([
                                'primary' => 'Primary (Amber)',
                                'success' => 'Success (Green)',
                                'warning' => 'Warning (Amber)',
                                'danger' => 'Danger (Red)',
                                'info' => 'Info (Blue)',
                                'gray' => 'Gray (Zinc)',
                            ]),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $tag = \App\Models\Tag::create($data);
                        return $tag->id;
                    }),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
