<div wire:poll.5s="loadTorrents">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Downloads') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Manage your qBittorrent download queue.') }}</flux:text>
    </div>

    {{-- Toast --}}
    @if($toastMessage)
        <flux:callout
            variant="{{ $toastType }}"
            icon="{{ $toastType === 'success' ? 'check-circle' : 'x-circle' }}"
            class="mb-4"
        >
            <div class="flex items-center justify-between">
                <flux:callout.text>{{ $toastMessage }}</flux:callout.text>
                <flux:button variant="ghost" size="sm" inset wire:click="clearToast">
                    <flux:icon name="x-mark" class="size-4" />
                </flux:button>
            </div>
        </flux:callout>
    @endif

    {{-- Error state --}}
    @if($error)
        <flux:callout variant="error" icon="exclamation-triangle" class="mb-6">
            <div class="text-center">
                <flux:heading size="sm" class="mt-2">{{ __('Connection Error') }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ $error }}</flux:text>
                <div class="mt-4">
                    <flux:button variant="primary" icon="arrow-path" wire:click="loadTorrents">
                        {{ __('Retry') }}
                    </flux:button>
                </div>
            </div>
        </flux:callout>
    @endif

    {{-- Filter tabs --}}
    @if(! $error)
        <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex space-x-2">
                @foreach(['all' => __('All'), 'downloading' => __('Downloading'), 'completed' => __('Completed'), 'paused' => __('Paused')] as $key => $label)
                    <flux:button
                        variant="{{ $filter === $key ? 'filled' : 'ghost' }}"
                        size="sm"
                        wire:click="setFilter('{{ $key }}')"
                        class="rounded-b-none {{ $filter === $key ? 'border-b-2 border-accent' : '' }}"
                    >
                        {{ $label }}
                    </flux:button>
                @endforeach
            </nav>
        </div>
    @endif

    {{-- Loading --}}
    @if($loading)
        <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
            <flux:icon name="arrow-path" class="size-5 animate-spin" />
            <flux:text size="sm">{{ __('Loading torrents...') }}</flux:text>
        </div>
    @endif

    {{-- Empty state --}}
    @if(! $loading && ! $error && empty($torrents))
        <flux:card class="py-10 text-center">
            <flux:icon name="arrow-down-tray" class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="sm" class="mt-2">{{ __('No torrents') }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Your download queue is empty. Search for episodes from a series detail page to add torrents.') }}
            </flux:text>
        </flux:card>
    @endif

    {{-- Torrent list --}}
    @if(! $loading && ! empty($torrents))
        <div class="space-y-4">
            @foreach($torrents as $torrent)
                @php
                    $stateLabel = $this->getStateLabel($torrent['state']);
                    $stateColor = $this->getStateColor($torrent['state']);
                    $progress = $torrent['progress'] * 100;
                    $badgeColor = match($stateColor) {
                        'blue' => 'blue',
                        'amber' => 'amber',
                        'zinc' => 'zinc',
                        'green' => 'green',
                        'orange' => 'orange',
                        'purple' => 'purple',
                        'red' => 'red',
                        default => 'zinc',
                    };
                @endphp
                <flux:card>
                    {{-- Header: name + status badge --}}
                    <div class="mb-3 flex items-start justify-between">
                        <div class="mr-3 min-w-0 flex-1">
                            <flux:heading size="sm" class="truncate" title="{{ $torrent['name'] }}">
                                {{ $torrent['name'] }}
                            </flux:heading>
                            <flux:text size="xs" class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                                {{ $torrent['save_path'] }}
                            </flux:text>
                        </div>
                        <flux:badge size="sm" :color="$badgeColor">
                            {{ $stateLabel }}
                        </flux:badge>
                    </div>

                    {{-- Progress bar --}}
                    <div class="mb-3">
                        <div class="mb-1 flex justify-between text-xs">
                            <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                {{ $this->formatBytes($torrent['completed']) }} / {{ $this->formatBytes($torrent['size']) }}
                            </flux:text>
                            <flux:text size="xs" class="font-medium">{{ number_format($progress, 1) }}%</flux:text>
                        </div>
                        <flux:progress :value="$progress" :color="$badgeColor" />
                    </div>

                    {{-- Stats row --}}
                    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                        @if($this->isActive($torrent['state']))
                            <div class="flex items-center gap-1">
                                <flux:icon name="arrow-down-tray" class="size-3" />
                                <span>{{ $this->formatSpeed($torrent['dlspeed']) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:icon name="arrow-up-tray" class="size-3" />
                                <span>{{ $this->formatSpeed($torrent['upspeed']) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:icon name="clock" class="size-3" />
                                <span>{{ __('ETA:') }} {{ $this->formatEta($torrent['eta']) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1">
                            <flux:icon name="users" class="size-3" />
                            <span>{{ $torrent['num_seeds'] }} {{ __('seeders') }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($this->isPaused($torrent['state']))
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="play"
                                wire:click="resume('{{ $torrent['hash'] }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('Resume') }}
                            </flux:button>
                        @else
                            <flux:button
                                size="sm"
                                variant="outline"
                                icon="pause"
                                wire:click="pause('{{ $torrent['hash'] }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('Pause') }}
                            </flux:button>
                        @endif

                        <flux:button
                            size="sm"
                            variant="danger"
                            icon="trash"
                            wire:click="delete('{{ $torrent['hash'] }}')"
                            wire:confirm="{{ __('Remove this torrent from the queue?') }}"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</div>
