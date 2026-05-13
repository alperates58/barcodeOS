<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
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

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        TextInput::make('sort_order')->numeric()->default(0)->required(),
                        Toggle::make('is_public')->default(true),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
                Section::make('Pricing')
                    ->schema([
                        TextInput::make('monthly_price')->numeric()->default(0)->required(),
                        TextInput::make('yearly_price')->numeric()->default(0)->required(),
                        TextInput::make('currency')->default('USD')->required()->maxLength(3),
                        TextInput::make('trial_days')->numeric()->default(0)->required(),
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
                TextColumn::make('monthly_price')
                    ->label('Monthly')
                    ->formatStateUsing(fn ($state, Plan $record) => number_format((float) $state, 2).' '.$record->currency)
                    ->sortable(),
                TextColumn::make('trial_days')->sortable(),
                IconColumn::make('is_public')->boolean()->label('Public'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_public'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
