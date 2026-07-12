<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Overview of your media library.') }}</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Scan Card --}}
            <a href="{{ route('scan') }}" wire:navigate class="group">
                <flux:card class="flex items-center gap-4 p-5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                        <flux:icon name="arrow-path-rounded-square" class="size-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Scanner') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Scan folders') }}</flux:text>
                    </div>
                </flux:card>
            </a>

            {{-- Series Card --}}
            <a href="{{ route('series') }}" wire:navigate class="group">
                <flux:card class="flex items-center gap-4 p-5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                        <flux:icon name="tv" class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Series') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('View collection') }}</flux:text>
                    </div>
                </flux:card>
            </a>

            {{-- Downloads Card --}}
            <a href="{{ route('downloads') }}" wire:navigate class="group">
                <flux:card class="flex items-center gap-4 p-5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                        <flux:icon name="arrow-down-tray" class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Downloads') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Manage queue') }}</flux:text>
                    </div>
                </flux:card>
            </a>

            {{-- Settings Card --}}
            <a href="{{ route('settings.media') }}" wire:navigate class="group">
                <flux:card class="flex items-center gap-4 p-5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="cog-6-tooth" class="size-6 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    <div>
                        <flux:heading size="sm">{{ __('Settings') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Configuration') }}</flux:text>
                    </div>
                </flux:card>
            </a>
        </div>
    </div>
</x-layouts::app>
