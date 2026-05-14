<?php

namespace App\Filament\Resources\BarcodeTypes;

use App\Filament\Resources\BarcodeTypes\Pages\ManageBarcodeTypes;
use App\Models\Feature;
use App\Models\BarcodeType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BarcodeTypeResource extends Resource
{
    protected static ?string $model = BarcodeType::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Barcode Catalog';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Barcode type details')
                    ->schema([
                        Select::make('barcode_category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                        Select::make('status')
                            ->required()
                            ->default('active')
                            ->options([
                                'active' => 'Active',
                                'beta' => 'Beta',
                                'draft' => 'Draft',
                                'archived' => 'Archived',
                            ]),
                        TextInput::make('icon')->maxLength(255),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        TextInput::make('example_value')->maxLength(255)->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Defaults and exports')
                    ->schema([
                        TextInput::make('default_width')->numeric()->default(300)->minValue(1),
                        TextInput::make('default_height')->numeric()->default(120)->minValue(1),
                        TextInput::make('default_margin')->numeric()->default(10)->minValue(0),
                        Select::make('default_format')
                            ->default('png')
                            ->required()
                            ->options([
                                'png' => 'PNG',
                                'svg' => 'SVG',
                                'pdf' => 'PDF',
                                'eps' => 'EPS',
                                'zip' => 'ZIP',
                            ]),
                        Select::make('supported_export_formats')
                            ->multiple()
                            ->options(static::exportFormatOptions())
                            ->searchable()
                            ->preload()
                            ->helperText('Catalog metadata only. Supported formats here do not mean rendering or file generation is implemented yet.')
                            ->columnSpanFull(),
                        Select::make('required_features')
                            ->multiple()
                            ->options(fn (): array => Feature::query()->orderBy('sort_order')->pluck('name', 'key')->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Feature keys are enforced through entitlement checks. Empty still requires the base barcode.generate entitlement.')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make('Rules and documentation')
                    ->schema([
                        KeyValue::make('validation_rules')
                            ->keyLabel('Rule key')
                            ->valueLabel('Rule value')
                            ->helperText('Use key/value validation metadata such as required=true or gs1_datamatrix=true. GS1 parsing is only activated by explicit slug/rule signals and does not imply rendering support.')
                            ->columnSpanFull(),
                        Repeater::make('parameter_schema')
                            ->schema([
                                TextInput::make('key')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Stable parameter key consumed by future generator forms and validation.'),
                                TextInput::make('label')
                                    ->maxLength(255),
                                Select::make('type')
                                    ->required()
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'integer' => 'Integer',
                                        'boolean' => 'Boolean',
                                        'select' => 'Select',
                                        'multi_select' => 'Multi select',
                                        'color' => 'Color',
                                    ]),
                                Toggle::make('required')->default(false),
                                TextInput::make('default')->maxLength(255),
                                TextInput::make('min'),
                                TextInput::make('max'),
                                TagsInput::make('options')
                                    ->separator(',')
                                    ->visible(fn (callable $get): bool => in_array($get('type'), ['select', 'multi_select'], true))
                                    ->helperText('Option values for select-based inputs.'),
                                Select::make('available_features')
                                    ->multiple()
                                    ->options(fn (): array => Feature::query()->orderBy('sort_order')->pluck('name', 'key')->all())
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('help_text')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsed()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? null)
                            ->helperText('Structured parameter definitions for future generator form and validation contracts. This metadata does not render or preview barcodes.')
                            ->columnSpanFull(),
                        Textarea::make('documentation')->rows(4)->columnSpanFull(),
                        TextInput::make('seo_title')->maxLength(255),
                        Textarea::make('seo_description')->rows(3)->columnSpanFull(),
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->searchable()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('default_format')->badge()->label('Default format'),
                TextColumn::make('supported_export_formats')
                    ->label('Exports')
                    ->formatStateUsing(fn ($state): string => implode(', ', is_array($state) ? $state : [])),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'beta' => 'Beta',
                        'draft' => 'Draft',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('barcode_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBarcodeTypes::route('/'),
        ];
    }

    protected static function exportFormatOptions(): array
    {
        return [
            'png' => 'PNG',
            'svg' => 'SVG',
            'pdf' => 'PDF',
            'eps' => 'EPS',
            'zip' => 'ZIP',
        ];
    }
}
