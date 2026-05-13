<?php

namespace App\Filament\Resources\PaymentProviders;

use App\Filament\Resources\PaymentProviders\Pages\ManagePaymentProviders;
use App\Models\PaymentProvider;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentProviderResource extends Resource
{
    protected static ?string $model = PaymentProvider::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider details')
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                        Select::make('mode')
                            ->required()
                            ->default('sandbox')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'live' => 'Live',
                            ]),
                        Toggle::make('is_enabled')->default(false),
                        Placeholder::make('secret_notice')
                            ->label('Secret credentials')
                            ->content('Secret values are never edited here. They must be configured through .env or Coolify environment variables.'),
                        Placeholder::make('environment_status')
                            ->label('Environment status')
                            ->content(function (?PaymentProvider $record): string {
                                if (! $record) {
                                    return 'Save the provider first to see configured/not configured status.';
                                }

                                return $record->environment_configured
                                    ? 'Configured via environment variables.'
                                    : 'Not configured in environment variables yet.';
                            }),
                    ])
                    ->columns(2),
                Section::make('Public metadata')
                    ->schema([
                        KeyValue::make('public_config')->columnSpanFull(),
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
                IconColumn::make('is_enabled')->boolean()->label('DB enabled'),
                TextColumn::make('mode')->badge(),
                IconColumn::make('environment_enabled')->boolean()->label('Env enabled'),
                IconColumn::make('environment_configured')->boolean()->label('Env configured'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->options([
                        'sandbox' => 'Sandbox',
                        'live' => 'Live',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePaymentProviders::route('/'),
        ];
    }
}
