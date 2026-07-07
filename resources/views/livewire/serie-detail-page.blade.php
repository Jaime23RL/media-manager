<div>
    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('series') }}" wire:navigate class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Series
        </a>
    </div>

    @if(!$serie)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Series not found</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                The series you're looking for doesn't exist or hasn't been scanned yet.
            </p>
        </div>
    @else
        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $serie['name'] }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $serie['file_count'] }} local files · {{ $serie['type'] === 'anime' ? 'Anime' : 'Movie' }}
                </p>
            </div>

            <div class="flex gap-2">
                @if($tmdb)
                    <button
                        wire:click="clearCache"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh from TMDB
                    </button>
                @endif

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
                    <span wire:loading.remove wire:target="lookupTmdb">{{ $tmdb ? 'Lookup on TMDB' : 'Lookup on TMDB' }}</span>
                </button>
            </div>
        </div>

        {{-- TMDB Info --}}
        @if($tmdb)
            <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-start gap-4">
                    @if($tmdb['poster_path'])
                        <img src="https://image.tmdb.org/t/p/w200{{ $tmdb['poster_path'] }}"
                             alt="{{ $tmdb['name'] }}"
                             class="w-24 h-36 object-cover rounded-lg shadow" />
                    @endif
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $tmdb['name'] }}</h2>
                        @if($tmdb['first_air_date'])
                            <p class="text-sm text-gray-500 dark:text-gray-400">First aired: {{ $tmdb['first_air_date'] }}</p>
                        @endif
                        @if($tmdb['overview'])
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-3">{{ $tmdb['overview'] }}</p>
                        @endif

                        {{-- Stats --}}
                        @if($comparison)
                            <div class="mt-4 flex gap-6">
                                <div>
                                    <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($comparison['have']) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">have</span>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ count($comparison['missing']) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">missing</span>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-blue-500 dark:text-blue-400">{{ count($comparison['upcoming']) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">upcoming</span>
                                </div>
                                @if(count($comparison['unparseable']) > 0)
                                    <div>
                                        <span class="text-2xl font-bold text-gray-400">{{ count($comparison['unparseable']) }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">unparsed</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Episodes by Season (TMDB comparison) --}}
        @if(count($episodesBySeason) > 0)
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Episodes</h3>
            @foreach($episodesBySeason as $season => $episodes)
                @php
                    $haveCount = collect($episodes)->where('status', 'have')->count();
                    $totalCount = count($episodes);
                @endphp
                <div class="mb-4" x-data="{ open: {{ $haveCount < $totalCount ? 'true' : 'false' }} }">
                    {{-- Season header --}}
                    <button
                        @click="open = !open"
                        type="button"
                        class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 shadow rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                class="h-5 w-5 text-gray-500 transition-transform duration-200"
                                :class="{ 'rotate-90': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-white">Season {{ $season }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                ({{ $haveCount }}/{{ $totalCount }} downloaded)
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($haveCount === $totalCount)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    Complete
                                </span>
                            @endif
                        </div>
                    </button>

                    {{-- Episodes list --}}
                    <div
                        x-show="open"
                        x-collapse
                        class="mt-2 bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg"
                    >
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($episodes as $ep)
                                <li class="px-4 py-3 {{ $ep['status'] === 'have' ? 'bg-green-50 dark:bg-green-900/10' : ($ep['status'] === 'upcoming' ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-amber-50 dark:bg-amber-900/10') }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            @if($ep['status'] === 'have')
                                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @elseif($ep['status'] === 'upcoming')
                                                <svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                            @endif
                                            <div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    E{{ str_pad($ep['episode'], 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                                @if($ep['name'])
                                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">— {{ $ep['name'] }}</span>
                                                @endif
                                                @if($ep['status'] === 'upcoming' && $ep['air_date'])
                                                    <span class="text-xs text-blue-500 dark:text-blue-400 ml-2">airs {{ $ep['air_date'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($ep['filename'])
                                            <span class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-[200px]" title="{{ $ep['filename'] }}">
                                                {{ $ep['filename'] }}
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Local files by Season (before TMDB lookup) --}}
        @if(!$tmdb && count($localFilesBySeason) > 0)
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Local Files by Season</h3>
            @foreach($localFilesBySeason as $season => $files)
                <div class="mb-4" x-data="{ open: {{ $season === 1 ? 'true' : 'false' }} }">
                    {{-- Season header --}}
                    <button
                        @click="open = !open"
                        type="button"
                        class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 shadow rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                class="h-5 w-5 text-gray-500 transition-transform duration-200"
                                :class="{ 'rotate-90': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $season === 0 ? 'Unparsed' : 'Season ' . $season }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                ({{ count($files) }} {{ count($files) === 1 ? 'file' : 'files' }})
                            </span>
                        </div>
                    </button>

                    {{-- Files list --}}
                    <div
                        x-show="open"
                        x-collapse
                        class="mt-2 bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg"
                    >
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($files as $file)
                                <li class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($file['parsed'])
                                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $file['filename'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        @endif
    @endif
</div>
