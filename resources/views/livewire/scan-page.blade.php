<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Series Scanner</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Scan your anime folders to detect series and video files.
        </p>
    </div>

    {{-- Last scan info --}}
    @if($lastScanTime && !$scanned)
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-blue-700 dark:text-blue-300">
                        Last scan: {{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }}
                    </span>
                </div>
                <button
                    wire:click="loadPreviousScan"
                    type="button"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 font-medium"
                >
                    Load previous scan
                </button>
            </div>
        </div>
    @endif

    {{-- Scan button --}}
    <div class="mb-6">
        <button
            wire:click="scan"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove wire:target="scan">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </span>
            <span wire:loading wire:target="scan">Scanning...</span>
            <span wire:loading.remove wire:target="scan">Scan Folders</span>
        </button>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading wire:target="scan" class="mb-6">
        <div class="flex items-center text-indigo-600">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Scanning series folders...</span>
        </div>
    </div>

    {{-- Cache loaded indicator --}}
    @if($loadedFromCache)
        <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <span class="text-sm text-green-700 dark:text-green-300">
                Loaded from cache ({{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }})
            </span>
        </div>
    @endif

    {{-- Results --}}
    @if($scanned && count($series) > 0)
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    Series Found ({{ count($series) }})
                </h3>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($series as $serie)
                    <li class="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 dark:text-indigo-300 font-medium">
                                        {{ strtoupper(substr($serie['name'], 0, 2)) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $serie['name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $serie['file_count'] }} files · {{ $serie['type'] === 'anime' ? 'Anime' : 'Movie' }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ class_basename($serie['path']) }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif($scanned)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No series found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Make sure the configured folders exist and contain video files.
            </p>
        </div>
    @endif
</div>
