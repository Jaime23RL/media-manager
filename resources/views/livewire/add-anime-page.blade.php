<div>
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Add Anime') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Search TMDB and create folder structure for a new anime.') }}</flux:text>
    </div>

    {{-- Success state --}}
    @if($created)
        <flux:card>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <flux:icon name="check-circle" class="size-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:heading size="sm">{{ __('Folders created successfully') }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $createdPath }}</flux:text>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <flux:button variant="primary" icon="tv" href="{{ route('series') }}" wire:navigate>
                    {{ __('Go to Series') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="resetSearch">
                    {{ __('Add another anime') }}
                </flux:button>
            </div>
        </flux:card>

    {{-- Configuration step --}}
    @elseif($selectedSerie)
        <div class="mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="backToResults">
                {{ __('Back to results') }}
            </flux:button>
        </div>

        <flux:card class="space-y-6">
            <div class="flex items-start gap-4">
                @if($selectedSerie['poster_path'] ?? null)
                    <img src="https://image.tmdb.org/t/p/w200{{ $selectedSerie['poster_path'] }}"
                         alt="{{ $selectedSerie['name'] }}"
                         class="h-36 w-24 shrink-0 rounded-lg object-cover shadow" />
                @endif
                <div class="min-w-0 flex-1">
                    <flux:heading size="lg">{{ $selectedSerie['name'] }}</flux:heading>
                    @if($selectedSerie['first_air_date'] ?? null)
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            {{ __('First aired:') }} {{ $selectedSerie['first_air_date'] }}
                        </flux:text>
                    @endif
                    @if($selectedSerie['overview'] ?? null)
                        <flux:text size="sm" class="mt-2 line-clamp-3">
                            {{ $selectedSerie['overview'] }}
                        </flux:text>
                    @endif
                </div>
            </div>

            {{-- Folder name --}}
            <div class="space-y-3">
                <flux:heading size="sm">{{ __('Folder name') }}</flux:heading>

                @if(count($nameSuggestions) > 0)
                    <flux:radio.group wire:model.live="selectedNameOption">
                        @foreach($nameSuggestions as $suggestion)
                            <flux:radio value="{{ $suggestion['name'] }}">
                                <flux:radio.indicator />
                                <div class="flex flex-1 items-center justify-between">
                                    <flux:heading size="sm">{{ $suggestion['name'] }}</flux:heading>
                                    <flux:text size="sm" class="text-zinc-400 dark:text-zinc-500">{{ $suggestion['label'] }}</flux:text>
                                </div>
                            </flux:radio>
                        @endforeach
                        <flux:radio value="custom">
                            <flux:radio.indicator />
                            <flux:heading size="sm">{{ __('Custom name') }}</flux:heading>
                        </flux:radio>
                    </flux:radio.group>
                @endif

                @if($selectedNameOption === 'custom' || count($nameSuggestions) === 0)
                    <flux:input wire:model="folderName" placeholder="{{ __('Enter custom folder name...') }}" />
                @endif

                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                    {{ config('media.paths.animes') }}/{{ $folderName }}
                </flux:text>
            </div>

            {{-- Seasons --}}
            @if(count($seasons) > 0)
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Seasons to create') }}</flux:heading>
                    <flux:checkbox.group wire:model="selectedSeasons">
                        @foreach($seasons as $season)
                            <flux:checkbox value="{{ $season['number'] }}">
                                <flux:checkbox.indicator />
                                <div class="flex flex-1 items-center justify-between">
                                    <div>
                                        <flux:heading size="sm">{{ __('Season') }} {{ $season['number'] }}</flux:heading>
                                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                            {{ $season['episode_count'] }} {{ $season['episode_count'] === 1 ? __('episode') : __('episodes') }}
                                        </flux:text>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($season['is_aired'])
                                            <flux:badge size="sm" color="green">{{ __('Aired') }}</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="blue">{{ __('Upcoming') }}</flux:badge>
                                        @endif
                                        @if($season['air_date'])
                                            <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">{{ $season['air_date'] }}</flux:text>
                                        @endif
                                    </div>
                                </div>
                            </flux:checkbox>
                        @endforeach
                    </flux:checkbox.group>
                </div>
            @endif

            {{-- Create button --}}
            <div class="flex justify-end">
                <flux:button
                    variant="primary"
                    icon="plus"
                    wire:click="createFolders"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                >
                    <span wire:loading.remove wire:target="createFolders">
                        {{ __('Create') }} {{ count($selectedSeasons) }} {{ count($selectedSeasons) === 1 ? __('folder') : __('folders') }}
                    </span>
                    <span wire:loading wire:target="createFolders">{{ __('Creating...') }}</span>
                </flux:button>
            </div>
        </flux:card>

    {{-- Search results --}}
    @elseif(count($results) > 0)
        <div class="mb-4">
            <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="resetSearch">
                {{ __('New search') }}
            </flux:button>
        </div>

        <flux:card class="overflow-hidden p-0">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:text size="sm">
                    {{ count($results) }} {{ count($results) === 1 ? __('result') : __('results') }} {{ __('for') }} "<span class="font-medium">{{ $searchQuery }}</span>"
                </flux:text>
            </div>

            <flux:table>
                <flux:table.rows>
                    @foreach($results as $result)
                        <flux:table.row :key="$result['id']">
                            <flux:table.cell>
                                <div class="flex items-start gap-4">
                                    @if($result['poster_path'] ?? null)
                                        <img src="https://image.tmdb.org/t/p/w200{{ $result['poster_path'] }}"
                                             alt="{{ $result['name'] ?? $result['original_name'] }}"
                                             class="h-24 w-16 shrink-0 rounded object-cover shadow" />
                                    @else
                                        <div class="flex h-24 w-16 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700">
                                            <flux:icon name="tv" class="size-8 text-zinc-400 dark:text-zinc-500" />
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <flux:heading size="sm">{{ $result['name'] ?? $result['original_name'] ?? __('Unknown') }}</flux:heading>
                                        @if(isset($result['original_name']) && ($result['original_name'] ?? '') !== ($result['name'] ?? ''))
                                            <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500">{{ $result['original_name'] }}</flux:text>
                                        @endif
                                        <div class="mt-1 flex items-center gap-2">
                                            @if($result['first_air_date'] ?? null)
                                                <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">{{ substr($result['first_air_date'], 0, 4) }}</flux:text>
                                            @endif
                                            @if($result['vote_average'] ?? null)
                                                <flux:text size="xs" class="text-yellow-600 dark:text-yellow-400">★ {{ number_format($result['vote_average'], 1) }}</flux:text>
                                            @endif
                                        </div>
                                        @if($result['overview'] ?? null)
                                            <flux:text size="xs" class="mt-1 line-clamp-2 text-zinc-500 dark:text-zinc-400">
                                                {{ $result['overview'] }}
                                            </flux:text>
                                        @endif
                                    </div>
                                    <flux:button
                                        size="sm"
                                        variant="outline"
                                        icon="chevron-right"
                                        wire:click="select({{ $result['id'] }})"
                                    >
                                        {{ __('Select') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

    {{-- Search form --}}
    @else
        <flux:card>
            <form wire:submit="search" class="flex gap-3">
                <flux:input
                    wire:model="searchQuery"
                    placeholder="{{ __('Search anime name...') }}"
                    class="flex-1"
                />
                <flux:button
                    variant="primary"
                    icon="magnifying-glass"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                >
                    <span wire:loading.remove wire:target="search">{{ __('Search') }}</span>
                    <span wire:loading wire:target="search">{{ __('Searching...') }}</span>
                </flux:button>
            </form>

            @if($searchError)
                <flux:callout variant="error" icon="exclamation-circle" class="mt-4">
                    <flux:callout.text>{{ $searchError }}</flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>
    @endif
</div>
