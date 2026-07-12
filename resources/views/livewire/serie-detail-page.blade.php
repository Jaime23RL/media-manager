<div>
    {{-- Back link --}}
    <div class="mb-4">
        <flux:button variant="ghost" size="sm" icon="arrow-left" href="{{ route('series') }}" wire:navigate>
            {{ __('Back to Series') }}
        </flux:button>
    </div>

    @if(!$serie)
        <flux:card class="py-10 text-center">
            <flux:heading size="sm">{{ __('Series not found') }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('The series you are looking for does not exist or has not been scanned yet.') }}
            </flux:text>
        </flux:card>
    @else
        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between">
            <div>
                <flux:heading size="xl">{{ $serie['name'] }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                    {{ $serie['file_count'] }} {{ __('local files') }} · {{ $serie['type'] === 'anime' ? __('Anime') : __('Movie') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                @if($tmdb)
                    <flux:button
                        variant="outline"
                        size="sm"
                        icon="arrow-path"
                        wire:click="clearCache"
                    >
                        {{ __('Refresh from TMDB') }}
                    </flux:button>
                @endif

                <flux:button
                    variant="primary"
                    size="sm"
                    icon="magnifying-glass"
                    wire:click="lookupTmdb"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                >
                    <span wire:loading.remove wire:target="lookupTmdb">{{ __('Lookup on TMDB') }}</span>
                    <span wire:loading wire:target="lookupTmdb">{{ __('Searching...') }}</span>
                </flux:button>
            </div>
        </div>

        {{-- Flash messages --}}
        @if($seasonCreatedMessage)
            <flux:callout variant="success" icon="check-circle" class="mb-4">
                <div class="flex items-center justify-between">
                    <flux:callout.text>{{ $seasonCreatedMessage }}</flux:callout.text>
                    <flux:button variant="ghost" size="sm" inset wire:click="$set('seasonCreatedMessage', '')">
                        <flux:icon name="x-mark" class="size-4" />
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        @if($nyaaSearchMessage)
            <flux:callout variant="info" icon="magnifying-glass" class="mb-4">
                <div class="flex items-center justify-between">
                    <flux:callout.text>{{ $nyaaSearchMessage }}</flux:callout.text>
                    <flux:button variant="ghost" size="sm" inset wire:click="$set('nyaaSearchMessage', '')">
                        <flux:icon name="x-mark" class="size-4" />
                    </flux:button>
                </div>
            </flux:callout>

            @if($nyaaCustomSeason > 0)
                <flux:card class="mb-4">
                    <div class="flex items-center gap-2">
                        <flux:text size="sm">
                            @if($nyaaCustomEpisode > 0)
                                {{ __('Custom search for E:episode:', ['episode' => str_pad((string) $nyaaCustomEpisode, 2, '0', STR_PAD_LEFT)]) }}
                            @else
                                {{ __('Custom search for Season :season:', ['season' => $nyaaCustomSeason]) }}
                            @endif
                        </flux:text>
                        <flux:input
                            wire:model="nyaaCustomQuery"
                            placeholder="{{ $nyaaCustomEpisode > 0 ? __('e.g. Mushoku Tensei III - 01') : __('e.g. Mushoku Tensei III') }}"
                            size="sm"
                            class="flex-1"
                            wire:keydown.enter="searchNyaaCustom"
                        />
                        <flux:button
                            size="sm"
                            variant="primary"
                            wire:click="searchNyaaCustom"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Search') }}
                        </flux:button>
                    </div>
                </flux:card>
            @endif

            {{-- Debug panel --}}
            @if(count($nyaaDebugLog) > 0)
                <div class="mb-4">
                    <details open class="rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            {{ __('Debug: Nyaa Search') }} ({{ count($nyaaDebugLog) }} {{ __('entries') }})
                        </summary>
                        <div class="px-4 pb-4">
                            <pre class="max-h-96 overflow-y-auto overflow-x-auto whitespace-pre-wrap break-words text-xs text-zinc-600 dark:text-zinc-400">{{ json_encode($nyaaDebugLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </details>
                </div>
            @endif
        @endif

        {{-- qBittorrent flash message --}}
        @if($qbMessage)
            <flux:callout
                variant="{{ $qbMessageType }}"
                icon="{{ $qbMessageType === 'success' ? 'check-circle' : 'x-circle' }}"
                class="mb-4"
            >
                <div class="flex items-center justify-between">
                    <flux:callout.text>{{ $qbMessage }}</flux:callout.text>
                    <flux:button variant="ghost" size="sm" inset wire:click="clearQbMessage">
                        <flux:icon name="x-mark" class="size-4" />
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        {{-- Rename flash message --}}
        @if($renameMessage)
            <flux:callout
                variant="{{ $renameMessageType === 'success' ? 'success' : ($renameMessageType === 'error' ? 'error' : 'info') }}"
                icon="{{ $renameMessageType === 'success' ? 'check-circle' : ($renameMessageType === 'error' ? 'x-circle' : 'information-circle') }}"
                class="mb-4"
            >
                <div class="flex items-center justify-between">
                    <flux:callout.text>{{ $renameMessage }}</flux:callout.text>
                    <flux:button variant="ghost" size="sm" inset wire:click="clearRenameMessage">
                        <flux:icon name="x-mark" class="size-4" />
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        {{-- TMDB Info --}}
        @if($tmdb)
            <flux:card class="mb-6">
                <div class="flex items-start gap-4">
                    @if($tmdb['poster_path'])
                        <img src="https://image.tmdb.org/t/p/w200{{ $tmdb['poster_path'] }}"
                             alt="{{ $tmdb['name'] }}"
                             class="h-36 w-24 shrink-0 rounded-lg object-cover shadow" />
                    @endif
                    <div class="flex-1">
                        <flux:heading size="lg">{{ $tmdb['name'] }}</flux:heading>
                        @if($jikanName && $jikanName !== $tmdb['name'])
                            <flux:text size="sm" class="italic text-zinc-500 dark:text-zinc-400">{{ $jikanName }}</flux:text>
                        @endif
                        @if($tmdb['first_air_date'])
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                {{ __('First aired:') }} {{ $tmdb['first_air_date'] }}
                            </flux:text>
                        @endif
                        @if($tmdb['overview'])
                            <flux:text size="sm" class="mt-2 line-clamp-3">
                                {{ $tmdb['overview'] }}
                            </flux:text>
                        @endif

                        {{-- Stats --}}
                        @if($comparison)
                            <div class="mt-4 flex gap-6">
                                <div>
                                    <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($comparison['have']) }}</span>
                                    <flux:text size="sm" class="ml-1 text-zinc-500 dark:text-zinc-400">{{ __('have') }}</flux:text>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ count($comparison['missing']) }}</span>
                                    <flux:text size="sm" class="ml-1 text-zinc-500 dark:text-zinc-400">{{ __('missing') }}</flux:text>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-blue-500 dark:text-blue-400">{{ count($comparison['upcoming']) }}</span>
                                    <flux:text size="sm" class="ml-1 text-zinc-500 dark:text-zinc-400">{{ __('upcoming') }}</flux:text>
                                </div>
                                @if(count($comparison['unparseable']) > 0)
                                    <div>
                                        <span class="text-2xl font-bold text-zinc-400">{{ count($comparison['unparseable']) }}</span>
                                        <flux:text size="sm" class="ml-1 text-zinc-500 dark:text-zinc-400">{{ __('unparsed') }}</flux:text>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        @endif

        {{-- Episodes by Season (TMDB comparison) --}}
        @if(count($episodesBySeason) > 0)
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Episodes') }}</flux:heading>
                <div class="flex gap-2">
                    @if(count($this->getMissingSeasons()) > 0)
                        <flux:button
                            size="sm"
                            variant="outline"
                            icon="plus"
                            wire:click="createAllMissingSeasons"
                        >
                            {{ __('Create all missing seasons') }}
                        </flux:button>
                    @endif
                    @if(count($existingSeasonFolders) > 0)
                        <flux:button
                            size="sm"
                            variant="primary"
                            icon="pencil"
                            wire:click="renameSeries"
                            wire:loading.attr="disabled"
                        >
                            {{ __('Rename Files') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            @foreach($episodesBySeason as $season => $episodes)
                @php
                    $haveCount = collect($episodes)->where('status', 'have')->count();
                    $totalCount = count($episodes);
                    $folderExists = $this->seasonFolderExists($season);
                    $isOpen = in_array($season, $openSeasons);
                @endphp
                <div class="mb-4">
                    {{-- Season header --}}
                    <button
                        wire:click="toggleSeason({{ $season }})"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg bg-white px-4 py-3 shadow-sm transition-colors hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                    >
                        <div class="flex items-center gap-3">
                            <flux:icon
                                name="chevron-right"
                                class="size-5 shrink-0 text-zinc-500 transition-transform duration-200 {{ $isOpen ? 'rotate-90' : '' }}"
                            />
                            <flux:heading size="sm">{{ __('Season') }} {{ $season }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                ({{ $haveCount }}/{{ $totalCount }} {{ __('downloaded') }})
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(isset($customNames[$season]) && $customNames[$season])
                                <flux:badge size="sm" color="purple">
                                    {{ __('Using:') }} {{ $customNames[$season] }}
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        inset
                                        wire:click="resetCustomName({{ $season }})"
                                        title="{{ __('Reset to default names') }}"
                                    >
                                        <flux:icon name="x-mark" class="size-3" />
                                    </flux:button>
                                </flux:badge>
                            @endif
                            @if($haveCount === $totalCount)
                                <flux:badge size="sm" color="green">{{ __('Complete') }}</flux:badge>
                            @else
                                <flux:button
                                    size="xs"
                                    variant="outline"
                                    icon="magnifying-glass"
                                    wire:click="searchNyaaForSeason({{ $season }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    title="{{ __('Search all missing episodes on Nyaa') }}"
                                >
                                    {{ __('Search Missing') }}
                                </flux:button>
                            @endif
                        </div>
                    </button>

                    {{-- Episodes list --}}
                    @if($isOpen)
                        <flux:card class="mt-2 overflow-hidden p-0">
                            @if(! $folderExists)
                                <flux:callout variant="warning" icon="exclamation-triangle" class="border-b border-amber-200 dark:border-amber-800">
                                    <div class="flex items-center justify-between">
                                        <flux:callout.text>
                                            {{ __('Season :season: directory is missing', ['season' => $season]) }}
                                        </flux:callout.text>
                                        <flux:button
                                            size="sm"
                                            variant="outline"
                                            icon="plus"
                                            wire:click="createSeasonFolder({{ $season }})"
                                        >
                                            {{ __('Create folder') }}
                                        </flux:button>
                                    </div>
                                </flux:callout>
                            @endif

                            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($episodes as $ep)
                                    <div class="px-4 py-3 {{ $ep['status'] === 'have' ? 'bg-green-50 dark:bg-green-900/10' : ($ep['status'] === 'upcoming' ? 'bg-zinc-50 dark:bg-zinc-800/50' : 'bg-amber-50 dark:bg-amber-900/10') }}">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                @if($ep['status'] === 'have')
                                                    <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                                                @elseif($ep['status'] === 'upcoming')
                                                    <flux:icon name="calendar" class="size-5 text-blue-500 dark:text-blue-400" />
                                                @else
                                                    <flux:icon name="exclamation-triangle" class="size-5 text-amber-600 dark:text-amber-400" />
                                                @endif
                                                <div>
                                                    <flux:text size="sm" class="font-medium">
                                                        E{{ str_pad((string) $ep['episode'], 2, '0', STR_PAD_LEFT) }}
                                                    </flux:text>
                                                    @if($ep['name'])
                                                        <flux:text size="sm" class="ml-2 text-zinc-500 dark:text-zinc-400">
                                                            — {{ $ep['name'] }}
                                                        </flux:text>
                                                    @endif
                                                    @if($ep['status'] === 'upcoming' && $ep['air_date'])
                                                        <flux:text size="xs" class="ml-2 text-blue-500 dark:text-blue-400">
                                                            {{ __('airs') }} {{ $ep['air_date'] }}
                                                        </flux:text>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($ep['filename'])
                                                <flux:text size="xs" class="max-w-[200px] truncate text-zinc-400 dark:text-zinc-500" title="{{ $ep['filename'] }}">
                                                    {{ $ep['filename'] }}
                                                </flux:text>
                                            @elseif($ep['status'] === 'missing')
                                                <flux:button
                                                    size="xs"
                                                    variant="outline"
                                                    icon="magnifying-glass"
                                                    wire:click="searchNyaaForEpisode({{ $season }}, {{ $ep['episode'] }})"
                                                    wire:loading.attr="disabled"
                                                >
                                                    {{ __('Search') }}
                                                </flux:button>
                                            @endif
                                        </div>

                                        {{-- Nyaa results --}}
                                        @php
                                            $nyaaKey = "{$season}_{$ep['episode']}";
                                            $hasResults = isset($this->nyaaResults[$nyaaKey]) && count($this->nyaaResults[$nyaaKey]) > 0;
                                        @endphp
                                        @if($hasResults)
                                            <div class="mt-3 ml-8 space-y-2">
                                                @foreach($this->nyaaResults[$nyaaKey] as $torrent)
                                                    <div class="flex items-center justify-between rounded border border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-600 dark:bg-zinc-700/50">
                                                        <div class="mr-3 min-w-0 flex-1">
                                                            <flux:text size="xs" class="truncate font-medium" title="{{ $torrent['title'] }}">
                                                                {{ $torrent['title'] }}
                                                            </flux:text>
                                                            <div class="mt-0.5 flex items-center gap-2">
                                                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">{{ $torrent['size'] }}</flux:text>
                                                                <flux:text size="xs" class="text-green-600 dark:text-green-400">▲ {{ $torrent['seeders'] }}</flux:text>
                                                                <flux:text size="xs" class="text-red-500 dark:text-red-400">▼ {{ $torrent['leechers'] }}</flux:text>
                                                                @if($torrent['trusted'])
                                                                    <flux:badge size="xs" color="green">{{ __('Trusted') }}</flux:badge>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex shrink-0 gap-1">
                                                            <flux:button
                                                                size="xs"
                                                                variant="outline"
                                                                icon="link"
                                                                onclick="navigator.clipboard.writeText('{{ $torrent['magnet'] }}'); Livewire.dispatch('showToast', { message: '{{ __('Magnet link copied!') }}', type: 'success' })"
                                                                title="{{ __('Copy magnet link') }}"
                                                            >
                                                                {{ __('Magnet') }}
                                                            </flux:button>
                                                            <flux:button
                                                                size="xs"
                                                                variant="primary"
                                                                icon="arrow-down-tray"
                                                                wire:click="addToQbittorrent('{{ $torrent['magnet'] }}', {{ $season }})"
                                                                wire:loading.attr="disabled"
                                                                title="{{ __('Send to qBittorrent') }}"
                                                            >
                                                                {{ __('Download') }}
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </flux:card>
                    @endif
                </div>
            @endforeach
        @endif

        {{-- Local files by Season (before TMDB lookup) --}}
        @if(!$tmdb && count($localFilesBySeason) > 0)
            <flux:heading size="lg" class="mb-3">{{ __('Local Files by Season') }}</flux:heading>
            @foreach($localFilesBySeason as $season => $files)
                @php
                    $isOpenLocal = in_array($season, $openSeasons);
                @endphp
                <div class="mb-4">
                    {{-- Season header --}}
                    <button
                        wire:click="toggleSeason({{ $season }})"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg bg-white px-4 py-3 shadow-sm transition-colors hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                    >
                        <div class="flex items-center gap-3">
                            <flux:icon
                                name="chevron-right"
                                class="size-5 shrink-0 text-zinc-500 transition-transform duration-200 {{ $isOpenLocal ? 'rotate-90' : '' }}"
                            />
                            <flux:heading size="sm">
                                {{ $season === 0 ? __('Unparsed') : __('Season') . ' ' . $season }}
                            </flux:heading>
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                ({{ count($files) }} {{ count($files) === 1 ? __('file') : __('files') }})
                            </flux:text>
                        </div>
                    </button>

                    {{-- Files list --}}
                    @if($isOpenLocal)
                        <flux:card class="mt-2 overflow-hidden p-0">
                            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($files as $file)
                                    <div class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($file['parsed'])
                                                <flux:icon name="check-circle" class="size-5 text-green-600 dark:text-green-400" />
                                            @else
                                                <flux:icon name="question-mark-circle" class="size-5 text-zinc-400" />
                                            @endif
                                            <flux:text size="sm" class="text-zinc-700 dark:text-zinc-300">
                                                {{ $file['filename'] }}
                                            </flux:text>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </flux:card>
                    @endif
                </div>
            @endforeach
        @endif
    @endif
</div>
