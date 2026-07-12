<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Series Scanner') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Scan your anime folders to detect series and video files.') }}</flux:text>
    </div>

    {{-- Last scan info --}}
    @if($lastScanTime && !$scanned)
        <flux:callout variant="info" icon="clock" class="mb-6">
            <div class="flex items-center justify-between">
                <flux:callout.text>
                    {{ __('Last scan:') }} {{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }}
                </flux:callout.text>
                <flux:button variant="ghost" size="sm" wire:click="loadPreviousScan">
                    {{ __('Load previous scan') }}
                </flux:button>
            </div>
        </flux:callout>
    @endif

    {{-- Scan button --}}
    <div class="mb-6">
        <flux:button
            variant="primary"
            icon="arrow-path-rounded-square"
            wire:click="scan"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50"
        >
            <span wire:loading.remove wire:target="scan">{{ __('Scan Folders') }}</span>
            <span wire:loading wire:target="scan">{{ __('Scanning...') }}</span>
        </flux:button>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading wire:target="scan" class="mb-6 flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
        <flux:icon name="arrow-path" class="size-5 animate-spin" />
        <flux:text size="sm">{{ __('Scanning series folders...') }}</flux:text>
    </div>

    {{-- Cache loaded indicator --}}
    @if($loadedFromCache)
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            <flux:callout.text>
                {{ __('Loaded from cache') }} ({{ \Carbon\Carbon::parse($lastScanTime)->diffForHumans() }})
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Results --}}
    @if($scanned && count($series) > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                {{ __('Series Found') }} ({{ count($series) }})
            </flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Series') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Folder') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($series as $serie)
                        <flux:table.row :key="$serie['name']">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-300">
                                            {{ strtoupper(substr($serie['name'], 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <flux:heading size="sm">{{ $serie['name'] }}</flux:heading>
                                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                            {{ $serie['file_count'] }} {{ $serie['file_count'] === 1 ? __('file') : __('files') }}
                                        </flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="zinc" variant="outline">
                                    {{ $serie['type'] === 'anime' ? __('Anime') : __('Movie') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                    {{ class_basename($serie['path']) }}
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @elseif($scanned)
        <flux:card class="py-10 text-center">
            <flux:icon name="folder-open" class="mx-auto size-12 text-zinc-400" />
            <flux:heading size="sm" class="mt-2">{{ __('No series found') }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Make sure the configured folders exist and contain video files.') }}
            </flux:text>
        </flux:card>
    @endif
</div>
