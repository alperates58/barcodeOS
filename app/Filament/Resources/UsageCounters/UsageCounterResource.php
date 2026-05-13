<?php

namespace App\Filament\Resources\UsageCounters;

use App\Filament\Resources\UsageCounters\Pages\ListUsageCounters;
use App\Models\UsageCounter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsageCounterResource extends Resource
{
    protected static ?string $model = UsageCounter::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('plan.name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('feature_key')->searchable()->sortable(),
                TextColumn::make('period_type')->badge()->sortable(),
                TextColumn::make('period_start')->dateTime()->sortable(),
                TextColumn::make('period_end')->dateTime()->sortable(),
                TextColumn::make('used')->numeric()->sortable(),
                TextColumn::make('limit')
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Unlimited' : number_format((int) $state))
                    ->sortable(),
                TextColumn::make('source')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('period_type')
                    ->options([
                        'daily' => 'Daily',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'lifetime' => 'Lifetime',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                        'bulk' => 'Bulk',
                        'admin' => 'Admin',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsageCounters::route('/'),
        ];
    }
}
