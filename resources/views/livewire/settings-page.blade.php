<div class="max-w-3xl space-y-10">
    <div>
        <flux:heading size="xl">{{ __('App Settings') }}</flux:heading>
        <flux:subheading>{{ __('Configure media paths, API integrations, and app appearance.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-10">
        {{-- Media Paths --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Media Library') }}</flux:heading>
                <flux:subheading>{{ __('Paths where your media files are stored.') }}</flux:subheading>
            </div>

            <flux:input wire:model="mediaAnimesPath" :label="__('Animes Path')" type="text" required />
            <flux:input wire:model="mediaPeliculasPath" :label="__('Peliculas Path')" type="text" required />
            <flux:input wire:model="videoExtensions" :label="__('Video Extensions')" type="text" required description="{{ __('Comma-separated, e.g. mkv,mp4,avi') }}" />
        </div>

        <flux:separator />

        {{-- TMDB --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('TMDB') }}</flux:heading>
                <flux:subheading>{{ __('The Movie Database API configuration.') }}</flux:subheading>
            </div>

            <flux:input wire:model="tmdbApiKey" :label="__('API Key')" type="text" />
            <flux:input wire:model="tmdbLanguage" :label="__('Language')" type="text" required description="{{ __('e.g. es-ES, en-US') }}" />
        </div>

        <flux:separator />

        {{-- qBittorrent --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('qBittorrent') }}</flux:heading>
                <flux:subheading>{{ __('Connection settings for qBittorrent Web UI.') }}</flux:subheading>
            </div>

            <flux:input wire:model="qbittorrentUrl" :label="__('URL')" type="url" required />
            <flux:input wire:model="qbittorrentUser" :label="__('Username')" type="text" required />
            <flux:input wire:model="qbittorrentPassword" :label="__('Password')" type="password" required />
        </div>

        <flux:separator />

        {{-- Nyaa.si --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Nyaa.si') }}</flux:heading>
                <flux:subheading>{{ __('Torrent search configuration.') }}</flux:subheading>
            </div>

            <flux:input wire:model="nyaaBaseUrl" :label="__('Base URL')" type="url" required />
            <flux:input wire:model="nyaaDefaultSubmitter" :label="__('Default Submitter')" type="text" required />
            <flux:input wire:model="nyaaDefaultQuality" :label="__('Default Quality')" type="text" required />
            <flux:input wire:model="nyaaConcurrency" :label="__('Concurrency')" type="number" required min="1" max="20" />
            <flux:input wire:model="nyaaCacheTtl" :label="__('Cache TTL (seconds)')" type="number" required min="0" />
        </div>

        <flux:separator />

        {{-- Theme --}}
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Theme') }}</flux:heading>
                <flux:subheading>{{ __('Choose your accent color. The theme updates instantly.') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-7 gap-2 sm:grid-cols-9">
                @foreach ($this->accentColors as $value => $label)
                    <button
                        type="button"
                        wire:click="selectColor('{{ $value }}')"
                        class="group relative flex size-8 items-center justify-center rounded-full ring-2 transition-all"
                        :class="$wire.themeAccentColor === '{{ $value }}' ? 'ring-accent scale-110' : 'ring-transparent hover:ring-zinc-300 dark:hover:ring-zinc-600'"
                        title="{{ $label }}"
                        aria-label="{{ $label }}"
                    >
                        <span class="block size-6 rounded-full bg-{{ $value }}-500 dark:bg-{{ $value }}-400"></span>
                        @if ($themeAccentColor === $value)
                            <span class="absolute inset-0 flex items-center justify-center">
                                <flux:icon name="check" class="size-3 text-white dark:text-zinc-900" />
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <flux:separator />

        <div class="flex items-center gap-4 pt-4">
            <flux:button variant="primary" type="submit" class="min-w-[140px]" data-test="save-settings-button">
                {{ __('Save Settings') }}
            </flux:button>

            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Changes are applied after saving.') }}
            </flux:text>
        </div>
    </form>
</div>
