@if ($fotos->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-700 px-4 py-10 text-center">
        <p class="text-sm text-gray-500">Sin evidencias todavía.</p>
        @if ($ordenActiva)
            <a
                href="{{ route('filament.admin.resources.ordenes-trabajo.edit', $ordenActiva) }}"
                class="mt-2 inline-block text-sm font-medium text-lime-400 hover:underline"
            >
                Ver orden activa
            </a>
        @endif
    </div>
@else
    <div x-data="{ open: false, src: null }" x-on:keydown.escape.window="open = false">
        <div class="grid gap-3.5" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
            @foreach ($fotos as $foto)
                <div>
                    <div class="group relative overflow-hidden rounded-xl border border-gray-800 transition-colors hover:border-lime-500" style="aspect-ratio: 4 / 3;">
                        <button
                            type="button"
                            x-on:click="open = true; src = @js($foto->url)"
                            class="absolute inset-0 h-full w-full cursor-zoom-in"
                        >
                            <img src="{{ $foto->url }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                        </button>

                        @if ($foto->ordenTrabajo)
                            <a
                                href="{{ route('filament.admin.resources.ordenes-trabajo.edit', $foto->ordenTrabajo) }}"
                                class="absolute left-2 top-2 rounded px-1.5 py-0.5 font-mono text-[10.5px] text-lime-300"
                                style="background: rgba(11,18,32,.82);"
                            >
                                {{ $foto->ordenTrabajo->codigo }}
                            </a>
                        @endif
                    </div>

                    <div class="mt-1 flex items-center justify-between text-[10px] tabular-nums text-gray-500">
                        <span>{{ $foto->tomada_at?->format('d/m/Y') }}</span>
                        <span>{{ $foto->tomada_at?->format('H:i') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            x-show="open"
            x-cloak
            x-on:click.self="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-6"
            style="background: rgba(6,8,10,.85);"
        >
            <img :src="src" class="max-h-full max-w-full rounded-lg object-contain" />
            <button
                type="button"
                x-on:click="open = false"
                class="absolute right-5 top-5 flex size-9 items-center justify-center rounded-full bg-gray-900 text-gray-300 hover:text-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
@endif
