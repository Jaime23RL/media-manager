<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Series') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Your anime collection with TMDB episode tracking.') }}</flux:text>
    </div>

    {{-- No scan data --}}
    @if(empty($series))
        <flux:card class="py-10 text-center">
            <flux:icon name="face-frown" class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="sm" class="mt-2">{{ __('No series found') }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Run a scan first to detect your series.') }}
            </flux:text>
            <div class="mt-4">
                <flux:button variant="primary" icon="arrow-right" href="{{ route('scan') }}" wire:navigate>
                    {{ __('Go to Scanner') }}
                </flux:button>
            </div>
        </flux:card>
    @else
        {{-- Action buttons --}}
        <div class="mb-6 flex items-center gap-4">
            <flux:button
                variant="primary"
                icon="magnifying-glass"
                wire:click="lookupTmdb"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
            >
                <span wire:loading.remove wire:target="lookupTmdb">{{ __('Lookup on TMDB') }}</span>
                <span wire:loading wire:target="lookupTmdb">{{ __('Searching...') }}</span>
            </flux:button>

            @if($lastScanTime)
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Last scan:') }} {{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }}
                </flux:text>
            @endif
        </div>

        {{-- Series list --}}
        <flux:card class="overflow-hidden p-0">
            <flux:table>
                <flux:table.rows>
                    @forelse($series as $index => $serie)
                        <flux:table.row :key="$index" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell>
                                <a href="{{ route('series.show', $index) }}" wire:navigate class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                                        @if(isset($serie['tmdb']) && $serie['tmdb'])
                                            @if($serie['missing_count'] === 0)
                                                <flux:icon name="check-circle" class="size-6 text-green-600 dark:text-green-400" />
                                            @else
                                                <flux:icon name="exclamation-triangle" class="size-6 text-amber-600 dark:text-amber-400" />
                                            @endif
                                        @else
                                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-300">
                                                {{ strtoupper(substr($serie['name'], 0, 2)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <flux:heading size="sm" class="truncate">{{ $serie['name'] }}</flux:heading>
                                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            @if($serie['file_count'] === 0)
                                                <flux:badge size="sm" color="amber">{{ __('Empty') }}</flux:badge>
                                            @else
                                                <span>{{ $serie['file_count'] }} {{ $serie['file_count'] === 1 ? __('file') : __('files') }}</span>
                                            @endif
                                            @if(isset($serie['tmdb']) && $serie['tmdb'])
                                                <span>·</span>
                                                <span class="{{ $serie['missing_count'] === 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ $serie['have_count'] }}/{{ $serie['total_episodes'] }} {{ __('episodes') }}
                                                </span>
                                                @if(isset($serie['upcoming_count']) && $serie['upcoming_count'] > 0)
                                                    <span>·</span>
                                                    <span class="text-blue-500 dark:text-blue-400">{{ $serie['upcoming_count'] }} {{ __('upcoming') }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <flux:icon name="chevron-right" class="size-5 shrink-0 text-zinc-400" />
                                </a>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No series in cache. Run a scan first.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
