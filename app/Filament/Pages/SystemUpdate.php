<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Services\Deployment\CoolifyDeployService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class SystemUpdate extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'System Update';

    protected string $view = 'filament.pages.system-update';

    public array $configuration = [];

    public ?string $lastMessage = null;

    public function mount(): void
    {
        $this->configuration = app(CoolifyDeployService::class)->configurationStatus();
    }

    public function refreshConfiguration(): void
    {
        $this->configuration = app(CoolifyDeployService::class)->configurationStatus();
    }

    public function triggerDeployment(): void
    {
        $result = app(CoolifyDeployService::class)->triggerDeployment(auth()->user());

        $this->lastMessage = $result['message'];
        $this->configuration = app(CoolifyDeployService::class)->configurationStatus();

        Notification::make()
            ->title($result['success'] ? 'Deployment requested' : 'Deployment rejected')
            ->body($result['message'])
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();
    }

    public function getLatestLogsProperty(): Collection
    {
        return AuditLog::query()
            ->where('subject_type', 'system_update')
            ->latest()
            ->limit(6)
            ->get();
    }
}
