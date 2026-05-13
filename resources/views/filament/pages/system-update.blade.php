<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-950 dark:text-white">Update from GitHub via Coolify</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            This page only triggers a Coolify deploy webhook or API call. It never runs
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">git pull</code>,
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">shell_exec</code>,
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">exec</code> or
                            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800">proc_open</code>
                            inside the live container.
                        </p>
                    </div>

                    @if ($lastMessage)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            {{ $lastMessage }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-filament::button color="gray" wire:click="refreshConfiguration">
                        Refresh Status
                    </x-filament::button>
                    <x-filament::button wire:click="triggerDeployment">
                        Trigger Deploy
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Integration enabled', 'value' => $configuration['enabled'] ? 'Yes' : 'No'],
                ['label' => 'Webhook configured', 'value' => $configuration['webhook_configured'] ? 'Configured' : 'Missing'],
                ['label' => 'API token configured', 'value' => $configuration['api_token_configured'] ? 'Configured' : 'Missing'],
                ['label' => 'Webhook secret configured', 'value' => $configuration['webhook_secret_configured'] ? 'Configured' : 'Missing'],
            ] as $card)
                <x-filament::section compact>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                </x-filament::section>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <x-filament::section heading="Deploy configuration">
                <div class="grid gap-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                        <span class="font-medium">HTTP method</span>
                        <span>{{ $configuration['method'] }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                        <span class="font-medium">Deploy branch</span>
                        <span>{{ $configuration['branch'] }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">Timeout</span>
                        <span>{{ $configuration['timeout'] }}s</span>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Recent audit logs">
                <div class="space-y-3">
                    @forelse ($this->latestLogs as $log)
                        <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $log->action }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->created_at?->toDateTimeString() }}</p>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ data_get($log->metadata, 'message', 'No message') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-sm text-gray-500 dark:border-gray-700">
                            No system update audit logs yet.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
