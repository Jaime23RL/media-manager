<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Series</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Your anime collection with TMDB episode tracking.
        </p>
    </div>

    {{-- No scan data --}}
    @if(empty($series))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No series found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Run a scan first to detect your series.
            </p>
            <div class="mt-4">
                <a href="{{ route('scan') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Go to Scanner
                </a>
            </div>
        </div>
    @else
        {{-- Action buttons --}}
        <div class="mb-6 flex items-center gap-4">
            <button
                wire:click="lookupTmdb"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                type="button"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="lookupTmdb">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <span wire:loading wire:target="lookupTmdb">Searching...</span>
                <span wire:loading.remove wire:target="lookupTmdb">Lookup on TMDB</span>
            </button>

            @if($lastScanTime)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Last scan: {{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }}
                </span>
            @endif
        </div>

        {{-- Series list --}}
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($series as $index => $serie)
                    <li class="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <a href="{{ route('series.show', $index) }}" wire:navigate class="block">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center min-w-0">
                                    <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                                        @if(isset($serie['tmdb']) && $serie['tmdb'])
                                            @if($serie['missing_count'] === 0)
                                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @else
                                                <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                            @endif
                                        @else
                                            <span class="text-indigo-600 dark:text-indigo-300 font-medium text-sm">
                                                {{ strtoupper(substr($serie['name'], 0, 2)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ml-4 min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $serie['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $serie['file_count'] }} files
                                            @if(isset($serie['tmdb']) && $serie['tmdb'])
                                                <span class="mx-1">·</span>
                                                <span class="{{ $serie['missing_count'] === 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ $serie['have_count'] }}/{{ $serie['total_episodes'] }} episodes
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        No series in cache. Run a scan first.
                    </li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
