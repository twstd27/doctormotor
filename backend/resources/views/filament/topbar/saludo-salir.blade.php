@php
    $usuario = filament()->auth()->user();
    $nombre = filament()->getUserName($usuario);
    $inicial = mb_strtoupper(mb_substr($nombre, 0, 1));
@endphp

<div class="flex items-center gap-2.5">
    <div class="flex items-center gap-2.5 rounded-full bg-gray-800 py-1 pl-1 pr-3">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-lime-400 text-xs font-bold text-gray-900">
            {{ $inicial }}
        </span>
        <span class="hidden leading-tight sm:block">
            <span class="block text-[11px] text-gray-400">Bienvenida/o</span>
            <span class="block text-sm font-semibold text-white">{{ $nombre }}</span>
        </span>
    </div>

    <form action="{{ route('filament.admin.auth.logout') }}" method="post">
        @csrf
        <button
            type="submit"
            class="flex items-center gap-1.5 rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-800 hover:text-white"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span class="hidden sm:inline">Salir</span>
        </button>
    </form>
</div>
