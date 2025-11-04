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
                        TextInput::make('color')
                            ->default('#3B82F6')
                            ->label('Color (hex)'),
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
