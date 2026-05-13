<?php

namespace App\Filament\Resources\Features;

use App\Filament\Resources\Features\Pages\ManageFeatures;
use App\Models\Feature;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
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

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feature details')
                    ->schema([
                        TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(255),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('category')->maxLength(255),
                        Select::make('value_type')
                            ->required()
                            ->default('boolean')
                            ->options([
                                'boolean' => 'Boolean',
                                'limit' => 'Limit',
                                'string' => 'String',
                                'integer' => 'Integer',
                                'json' => 'JSON',
                            ]),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        TextInput::make('sort_order')->numeric()->default(0)->required(),
                        Toggle::make('is_active')->default(true),
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
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->searchable()->sortable(),
                TextColumn::make('value_type')->badge(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                SelectFilter::make('value_type')
                    ->options([
                        'boolean' => 'Boolean',
                        'limit' => 'Limit',
                        'string' => 'String',
                        'integer' => 'Integer',
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
            'index' => ManageFeatures::route('/'),
        ];
    }
}
