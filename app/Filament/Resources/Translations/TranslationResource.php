<?php

namespace App\Filament\Resources\Translations;

use App\Filament\Resources\Translations\Pages\ManageTranslations;
use App\Models\Translation;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Localization';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Translation entry')
                    ->schema([
                        Select::make('language_id')
                            ->relationship('language', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('group')->required()->maxLength(255),
                        TextInput::make('key')->required()->maxLength(255),
                        Textarea::make('value')->rows(4)->required()->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Metadata')
                    ->schema([
                        KeyValue::make('metadata')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('language.code')->label('Language')->searchable()->sortable(),
                TextColumn::make('group')->searchable()->sortable(),
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('value')->limit(60)->wrap(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTranslations::route('/'),
        ];
    }
}
