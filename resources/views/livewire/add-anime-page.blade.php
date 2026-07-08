<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Anime</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Search TMDB and create folder structure for a new anime.
        </p>
    </div>

    {{-- Success state --}}
    @if($created)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Folders created successfully</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $createdPath }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('series') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700">
                    Go to Series
                </a>
                <button wire:click="resetSearch" type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Add another anime
                </button>
            </div>
        </div>

    {{-- Configuration step --}}
    @elseif($selectedSerie)
        <div class="mb-4">
            <button wire:click="backToResults" type="button"
                    class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to results
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-start gap-4 mb-6">
                @if($selectedSerie['poster_path'] ?? null)
                    <img src="https://image.tmdb.org/t/p/w200{{ $selectedSerie['poster_path'] }}"
                         alt="{{ $selectedSerie['name'] }}"
                         class="w-24 h-36 object-cover rounded-lg shadow flex-shrink-0" />
                @endif
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedSerie['name'] }}</h2>
                    @if($selectedSerie['first_air_date'] ?? null)
                        <p class="text-sm text-gray-500 dark:text-gray-400">First aired: {{ $selectedSerie['first_air_date'] }}</p>
                    @endif
                    @if($selectedSerie['overview'] ?? null)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-3">{{ $selectedSerie['overview'] }}</p>
                    @endif
                </div>
            </div>

            {{-- Folder name --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Folder name</label>

                {{-- Name suggestions --}}
                @if(count($nameSuggestions) > 0)
                    <div class="space-y-1.5 mb-3">
                        @foreach($nameSuggestions as $suggestion)
                            <label class="flex items-center gap-3 p-2.5 rounded-lg border {{ $selectedNameOption === $suggestion['name'] ? 'border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer transition-colors">
                                <input type="radio" wire:click="selectNameOption('{{ addslashes($suggestion['name']) }}')"
                                       {{ $selectedNameOption === $suggestion['name'] ? 'checked' : '' }}
                                       class="border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500" />
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $suggestion['name'] }}</span>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ $suggestion['label'] }}</span>
                            </label>
                        @endforeach

                        {{-- Custom name option --}}
                        <label class="flex items-center gap-3 p-2.5 rounded-lg border {{ $selectedNameOption === 'custom' ? 'border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer transition-colors">
                            <input type="radio" wire:click="selectNameOption('custom')"
                                   {{ $selectedNameOption === 'custom' ? 'checked' : '' }}
                                   class="border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Custom name</span>
                        </label>
                    </div>
                @endif

                {{-- Custom name input --}}
                @if($selectedNameOption === 'custom' || count($nameSuggestions) === 0)
                    <input type="text" wire:model="folderName"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm" />
                @endif

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ config('media.paths.animes') }}/{{ $folderName }}
                </p>
            </div>

            {{-- Seasons --}}
            @if(count($seasons) > 0)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seasons to create</label>
                    <div class="space-y-2">
                        @foreach($seasons as $season)
                            <label class="flex items-center gap-3 p-3 rounded-lg border {{ in_array($season['number'], $selectedSeasons) ? 'border-purple-300 dark:border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} hover:border-gray-300 dark:hover:border-gray-600 cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="selectedSeasons" value="{{ $season['number'] }}"
                                       class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500" />
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Season {{ $season['number'] }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                        {{ $season['episode_count'] }} {{ $season['episode_count'] === 1 ? 'episode' : 'episodes' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($season['is_aired'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Aired
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            Upcoming
                                        </span>
                                    @endif
                                    @if($season['air_date'])
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $season['air_date'] }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Create button --}}
            <div class="flex justify-end">
                <button wire:click="createFolders"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="createFolders">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create {{ count($selectedSeasons) }} {{ count($selectedSeasons) === 1 ? 'folder' : 'folders' }}
                    </span>
                    <span wire:loading wire:target="createFolders">Creating...</span>
                </button>
            </div>
        </div>

    {{-- Search results --}}
    @elseif(count($results) > 0)
        <div class="mb-4">
            <button wire:click="resetSearch" type="button"
                    class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                New search
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ count($results) }} {{ count($results) === 1 ? 'result' : 'results' }} for "<span class="font-medium">{{ $searchQuery }}</span>"
                </p>
            </div>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($results as $result)
                    <li class="px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            @if($result['poster_path'] ?? null)
                                <img src="https://image.tmdb.org/t/p/w200{{ $result['poster_path'] }}"
                                     alt="{{ $result['name'] ?? $result['original_name'] }}"
                                     class="w-16 h-24 object-cover rounded shadow flex-shrink-0" />
                            @else
                                <div class="w-16 h-24 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center flex-shrink-0">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $result['name'] ?? $result['original_name'] ?? 'Unknown' }}
                                </h3>
                                @if(isset($result['original_name']) && ($result['original_name'] ?? '') !== ($result['name'] ?? ''))
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $result['original_name'] }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    @if($result['first_air_date'] ?? null)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ substr($result['first_air_date'], 0, 4) }}</span>
                                    @endif
                                    @if($result['vote_average'] ?? null)
                                        <span class="text-xs text-yellow-600 dark:text-yellow-400">★ {{ number_format($result['vote_average'], 1) }}</span>
                                    @endif
                                </div>
                                @if($result['overview'] ?? null)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $result['overview'] }}</p>
                                @endif
                            </div>
                            <button wire:click="select({{ $result['id'] }})" type="button"
                                    class="flex-shrink-0 inline-flex items-center px-3 py-1.5 border border-purple-300 dark:border-purple-600 text-sm font-medium rounded-md text-purple-700 dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/30 transition-colors">
                                Select
                                <svg class="ml-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

    {{-- Search form --}}
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form wire:submit="search" class="flex gap-3">
                <input type="text" wire:model="searchQuery" placeholder="Search anime name..."
                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm" />
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="search">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search
                    </span>
                    <span wire:loading wire:target="search">Searching...</span>
                </button>
            </form>

            @if($searchError)
                <div class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $searchError }}
                </div>
            @endif
        </div>
    @endif
</div>
