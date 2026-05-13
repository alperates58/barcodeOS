<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use App\Models\Feature;
use App\Models\PlanFeature;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PlanFeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'planFeatures';

    protected static ?string $title = 'Plan Features';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feature assignment')
                    ->schema([
                        Select::make('feature_id')
                            ->label('Feature')
                            ->relationship(
                                name: 'feature',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Feature $record): string => "{$record->name} ({$record->key})")
                            ->searchable(['name', 'key'])
                            ->preload()
                            ->required()
                            ->disabledOn('edit')
                            ->unique(
                                table: PlanFeature::class,
                                column: 'feature_id',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('plan_id', $this->getOwnerRecord()->getKey()),
                            ),
                        Select::make('enabled')
                            ->required()
                            ->default(true)
                            ->options([
                                true => 'Enabled',
                                false => 'Disabled',
                            ])
                            ->native(false),
                        TextInput::make('value')
                            ->label('Value')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        TextInput::make('limit_value')
                            ->label('Limit Value')
                            ->numeric()
                            ->minValue(0),
                        KeyValue::make('metadata')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feature.key')->label('Key')->searchable()->sortable(),
                TextColumn::make('feature.name')->label('Feature')->searchable()->sortable(),
                TextColumn::make('feature.category')->badge(),
                TextColumn::make('feature.value_type')->label('Value Type')->badge(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('limit_value')->numeric()->label('Limit'),
                TextColumn::make('value')->limit(40),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
