<?php

namespace App\Filament\Resources\BarcodeParameters;

use App\Filament\Resources\BarcodeParameters\Pages\ManageBarcodeParameters;
use App\Models\BarcodeParameter;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BarcodeParameterResource extends Resource
{
    protected static ?string $model = BarcodeParameter::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Barcode Catalog';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Parameter details')
                    ->schema([
                        Select::make('barcode_type_id')
                            ->relationship('barcodeType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('label')->required()->maxLength(255),
                        TextInput::make('key')->required()->maxLength(255),
                        Select::make('type')
                            ->required()
                            ->options([
                                'text' => 'Text',
                                'number' => 'Number',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'select' => 'Select',
                                'multi_select' => 'Multi select',
                                'json' => 'JSON',
                            ]),
                        TextInput::make('default_value')->maxLength(255),
                        TextInput::make('min_value')->numeric(),
                        TextInput::make('max_value')->numeric(),
                        Toggle::make('is_required')->default(false),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('help_text')->rows(3)->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Options and metadata')
                    ->schema([
                        KeyValue::make('options')->columnSpanFull(),
                        TagsInput::make('available_features')->separator(',')->columnSpanFull(),
                        TextInput::make('sort_order')->numeric()->default(0)->required(),
                        KeyValue::make('metadata')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('barcodeType.name')->label('Barcode type')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_required')->boolean()->label('Required'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'integer' => 'Integer',
                        'boolean' => 'Boolean',
                        'select' => 'Select',
                        'multi_select' => 'Multi select',
                        'json' => 'JSON',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBarcodeParameters::route('/'),
        ];
    }
}
