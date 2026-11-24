<?php

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Avatar con las iniciales, generado acá mismo como SVG embebido. El de Filament por defecto
 * pide la imagen a ui-avatars.com, y eso le manda a un tercero el nombre de cada usuario.
 */
class AvatarIniciales implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $iniciales = collect(preg_split('/\s+/', trim((string) Filament::getNameForDefaultAvatar($record))))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        $iniciales = htmlspecialchars($iniciales !== '' ? $iniciales : '?', ENT_QUOTES);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#2A313A"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" fill="#ECEEF1" '
            ."font-family=\"Inter, Arial, sans-serif\" font-size=\"26\" font-weight=\"700\">{$iniciales}</text>"
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
