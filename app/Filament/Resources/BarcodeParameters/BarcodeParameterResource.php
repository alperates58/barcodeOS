<?php

namespace App\Filament\Resources\BarcodeParameters;

use App\Filament\Resources\BarcodeParameters\Pages\ManageBarcodeParameters;
use App\Models\BarcodeParameter;
use App\Models\Feature;
use BackedEnum;
use Filament\Actions\EditAction;
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
use Illuminate\Database\Eloquent\Model;

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
                            ->helperText('Choose the barcode type whose future generator form and validation metadata will be driven by this parameter.')
                            ->required(),
                        TextInput::make('label')->required()->maxLength(255),
                        TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Stable internal key used by future generator form payloads and validation. Avoid changing this after creation.')
                            ->readOnly(fn (?BarcodeParameter $record): bool => $record !== null),
                        Select::make('type')
                            ->required()
                            ->helperText('Supported parameter types for future generator form fields and request validation.')
                            ->options([
                                'text' => 'Text',
                                'number' => 'Number',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'select' => 'Select',
                                'multi_select' => 'Multi select',
                                'color' => 'Color',
                            ]),
                        TextInput::make('default_value')
                            ->maxLength(255)
                            ->helperText('Optional default that future generator screens can prefill before any rendering exists.'),
                        TextInput::make('min_value')
                            ->helperText('Optional lower bound for number/integer validation.'),
                        TextInput::make('max_value')
                            ->helperText('Optional upper bound for number/integer validation.'),
                        Toggle::make('is_required')
                            ->default(false)
                            ->helperText('Marks the parameter as required in future generator requests.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive parameters stay in admin history but are excluded from resolved generator schema.'),
                        Textarea::make('help_text')
                            ->rows(3)
                            ->helperText('Helper copy for the future generator form. This does not create previews or rendering support.')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Options and metadata')
                    ->schema([
                        TagsInput::make('options')
                            ->separator(',')
                            ->helperText('List selectable option values as an array. Used only for select and multi_select parameter types.')
                            ->visible(fn (callable $get): bool => in_array($get('type'), ['select', 'multi_select'], true))
                            ->columnSpanFull(),
                        Select::make('available_features')
                            ->multiple()
                            ->options(fn (): array => Feature::query()->orderBy('sort_order')->pluck('name', 'key')->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Optional feature keys that future generator UI can use to conditionally show or lock this parameter.')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Controls parameter display order in future generator forms.'),
                        Textarea::make('metadata')
                            ->rows(5)
                            ->helperText('Optional JSON metadata for future form behavior. Keep this admin-safe and non-rendering.')
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->dehydrateStateUsing(function (?string $state): array {
                                if ($state === null || trim($state) === '') {
                                    return [];
                                }

                                $decoded = json_decode($state, true);

                                return is_array($decoded) ? $decoded : [];
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barcodeType.name')->label('Barcode type')->searchable()->sortable(),
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('label')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
                        'color' => 'Color',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBarcodeParameters::route('/'),
        ];
    }
}
