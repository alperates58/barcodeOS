<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Plan;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('plan.name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => static::statusColor($state))
                    ->sortable(),
                TextColumn::make('provider')->badge()->sortable(),
                TextColumn::make('provider_customer_id')
                    ->label('Customer ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => static::maskIdentifier($state)),
                TextColumn::make('provider_subscription_id')
                    ->label('Subscription ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => static::maskIdentifier($state)),
                TextColumn::make('billing_cycle')->badge()->sortable(),
                TextColumn::make('trial_ends_at')->dateTime()->sortable(),
                TextColumn::make('current_period_starts_at')->dateTime()->sortable(),
                TextColumn::make('current_period_ends_at')->dateTime()->sortable(),
                TextColumn::make('cancelled_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(static::statusOptions()),
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn (): array => Plan::query()->orderBy('sort_order')->pluck('name', 'id')->all()),
                SelectFilter::make('provider')
                    ->options(fn (): array => Subscription::query()
                        ->whereNotNull('provider')
                        ->distinct()
                        ->orderBy('provider')
                        ->pluck('provider', 'provider')
                        ->all()),
                SelectFilter::make('billing_cycle')
                    ->options(fn (): array => Subscription::query()
                        ->whereNotNull('billing_cycle')
                        ->distinct()
                        ->orderBy('billing_cycle')
                        ->pluck('billing_cycle', 'billing_cycle')
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    protected static function maskIdentifier(?string $value): string
    {
        if (blank($value)) {
            return '—';
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2).str_repeat('*', max($length - 4, 2)).substr($value, -2);
    }

    protected static function statusColor(string $status): string
    {
        return match ($status) {
            Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING => 'success',
            Subscription::STATUS_PAST_DUE => 'warning',
            Subscription::STATUS_UNPAID,
            Subscription::STATUS_CANCELED,
            Subscription::STATUS_EXPIRED,
            Subscription::STATUS_INCOMPLETE,
            Subscription::STATUS_INCOMPLETE_EXPIRED => 'danger',
            default => 'gray',
        };
    }

    protected static function statusOptions(): array
    {
        return collect(Subscription::knownStatuses())
            ->mapWithKeys(fn (string $status): array => [
                $status => str($status)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }
}
