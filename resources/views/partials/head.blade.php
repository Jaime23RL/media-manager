<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@if (auth()->check())
    @php
        $accentColor = auth()->user()->setting('theme.accent_color', 'neutral');
    @endphp

    <style id="user-theme">
        html {
            --color-accent: var(--color-{{ $accentColor }}-500);
            --color-accent-content: var(--color-{{ $accentColor }}-600);
            --color-accent-foreground: var(--color-white);
        }
        html.dark {
            --color-accent: var(--color-{{ $accentColor }}-500);
            --color-accent-content: var(--color-{{ $accentColor }}-400);
            --color-accent-foreground: var(--color-zinc-800);
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('theme-changed', (event) => {
                const color = Array.isArray(event) ? event[0]?.color : event.color;
                if (!color) return;
                const style = document.getElementById('user-theme');
                if (style) {
                    style.textContent = `
                        html {
                            --color-accent: var(--color-${color}-500);
                            --color-accent-content: var(--color-${color}-600);
                            --color-accent-foreground: var(--color-white);
                        }
                        html.dark {
                            --color-accent: var(--color-${color}-500);
                            --color-accent-content: var(--color-${color}-400);
                            --color-accent-foreground: var(--color-zinc-800);
                        }
                    `;
                }
            });
        });
    </script>
@endif
