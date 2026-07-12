<div wire:poll.5s="loadTorrents">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Downloads</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Manage your qBittorrent download queue.
        </p>
    </div>

    {{-- Toast --}}
    @if($toastMessage)
        <div class="mb-4 {{ $toastType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} border rounded-lg px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 {{ $toastType === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($toastType === 'success')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    @endif
                </svg>
                <span class="text-sm {{ $toastType === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">{{ $toastMessage }}</span>
            </div>
            <button wire:click="clearToast" type="button" class="{{ $toastType === 'success' ? 'text-green-600 dark:text-green-400 hover:text-green-800' : 'text-red-600 dark:text-red-400 hover:text-red-800' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Error state --}}
    @if($error)
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-red-900 dark:text-red-300">Connection Error</h3>
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
            <div class="mt-4">
                <button wire:click="loadTorrents" type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">
                    Retry
                </button>
            </div>
        </div>
    @endif

    {{-- Filter tabs --}}
    @if(! $error)
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                @foreach(['all' => 'All', 'downloading' => 'Downloading', 'completed' => 'Completed', 'paused' => 'Paused'] as $key => $label)
                    <button
                        wire:click="setFilter('{{ $key }}')"
                        type="button"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filter === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>
    @endif

    {{-- Loading --}}
    @if($loading)
        <div class="flex items-center text-gray-500 dark:text-gray-400">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading torrents...</span>
        </div>
    @endif

    {{-- Empty state --}}
    @if(! $loading && ! $error && empty($torrents))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No torrents</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Your download queue is empty. Search for episodes from a series detail page to add torrents.
            </p>
        </div>
    @endif

    {{-- Torrent list --}}
    @if(! $loading && ! empty($torrents))
        <div class="space-y-4">
            @foreach($torrents as $torrent)
                @php
                    $stateLabel = $this->getStateLabel($torrent['state']);
                    $stateColor = $this->getStateColor($torrent['state']);
                    $progress = $torrent['progress'] * 100;
                    $colorClasses = match($stateColor) {
                        'blue' => 'bg-blue-500',
                        'amber' => 'bg-amber-500',
                        'zinc' => 'bg-zinc-500',
                        'green' => 'bg-green-500',
                        'orange' => 'bg-orange-500',
                        'purple' => 'bg-purple-500',
                        'red' => 'bg-red-500',
                        default => 'bg-gray-500',
                    };
                    $badgeClasses = match($stateColor) {
                        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                        'zinc' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
                        'green' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                    };
                @endphp
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 sm:p-6">
                    {{-- Header: name + status badge --}}
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0 flex-1 mr-3">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate" title="{{ $torrent['name'] }}">
                                {{ $torrent['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $torrent['save_path'] }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                            {{ $stateLabel }}
                        </span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $this->formatBytes($torrent['completed']) }} / {{ $this->formatBytes($torrent['size']) }}
                            </span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ number_format($progress, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $colorClasses }} h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    {{-- Stats row --}}
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                        @if($this->isActive($torrent['state']))
                            <div class="flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>{{ $this->formatSpeed($torrent['dlspeed']) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>{{ $this->formatSpeed($torrent['upspeed']) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>ETA: {{ $this->formatEta($torrent['eta']) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>{{ $torrent['num_seeds'] }} seeders</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($this->isPaused($torrent['state']))
                            <button
                                wire:click="resume('{{ $torrent['hash'] }}')"
                                wire:loading.attr="disabled"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent rounded text-xs font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors"
                            >
                                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Resume
                            </button>
                        @else
                            <button
                                wire:click="pause('{{ $torrent['hash'] }}')"
                                wire:loading.attr="disabled"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors"
                            >
                                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pause
                            </button>
                        @endif

                        <button
                            wire:click="delete('{{ $torrent['hash'] }}')"
                            wire:confirm="Remove this torrent from the queue?"
                            wire:loading.attr="disabled"
                            type="button"
                            class="inline-flex items-center px-3 py-1.5 border border-transparent rounded text-xs font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors"
                        >
                            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Remove
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
