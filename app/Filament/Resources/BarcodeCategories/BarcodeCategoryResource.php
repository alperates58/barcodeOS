<?php

namespace App\Filament\Resources\BarcodeCategories;

use App\Filament\Resources\BarcodeCategories\Pages\ManageBarcodeCategories;
use App\Models\BarcodeCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BarcodeCategoryResource extends Resource
{
    protected static ?string $model = BarcodeCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Barcode Catalog';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                        TextInput::make('icon')->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        TextInput::make('seo_title')->maxLength(255),
                        Textarea::make('seo_description')->rows(3)->columnSpanFull(),
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBarcodeCategories::route('/'),
        ];
    }
}
