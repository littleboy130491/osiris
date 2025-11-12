<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
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
            ]);
    }
}
