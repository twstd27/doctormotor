# Sistema de Diseño — Doctor Motor

Fuente: [`design-reference/UI Mockups sistema facturacion (paleta y tokens).pdf`](design-reference/UI%20Mockups%20sistema%20facturacion%20%28paleta%20y%20tokens%29.pdf)
(documento de tokens ya cerrado) + [`design-reference/`](design-reference/) (3 capturas de pantallas
de ejemplo). Esta es la fuente única de verdad de color/tipografía — tanto Filament (panel interno)
como Tailwind en React (PWA) se configuran a partir de estos mismos tokens.

Origen de la marca: verde lima sobre grafito, sacado del logo (`#8FD62E` sobre `#0D0F12`).

## 01 · Paleta — escalas 50–950

Mismos números en los dos proyectos: Filament los consume como rampa de color, Tailwind como
`lime-500`, `graphite-900`, etc. Guardar estas rampas en un `colors.json` compartido en el repo y
generar desde ahí tanto el arreglo de Filament como el bloque `@theme` de Tailwind — evita que los
dos productos se desalineen con el tiempo.

**Lima — marca** (acción principal, progreso, estados en verde)
`50` #F4FCE4 · `100` #E7F9C6 · `200` #D0F395 · `300` #B6EC5E · `400` #9FE03A · `500` **#8FD62E** ·
`600` #74B31F · `700` #588718 · `800` #446618 · `900` #394F1A · `950` #1D2B08

**Grafito — neutro** (fondos, superficies, bordes, texto)
`50` #F4F6F7 · `100` #E9EDF0 · `200` #CDD4DA · `300` #A8B1BA · `400` #7A838E · `500` #5C6673 ·
`600` #444C57 · `700` #333A44 · `800` #232830 · `900` #14171C · `950` **#0D0F12**

**Ámbar — atención** (pendiente de pago, stock al límite)
`500` **#E8B23A** (resto de la rampa en el PDF de origen)

**Coral — alerta** (vencido, sin stock, error)
`500` **#E8654B** (resto de la rampa en el PDF de origen)

**Cian — informativo** (car wash, avisos neutros)
`500` **#4FA9C9** (resto de la rampa en el PDF de origen)

Los semánticos (ámbar/coral/cian) son independientes del acento — nunca se usan para CTAs de
marca, solo para estado.

## 02 · Roles por superficie

**Panel admin · Filament — oscuro fijo, sin toggle** (denso, mucha tabla, sesiones largas)

| Rol | Token | Valor |
|---|---|---|
| Lienzo de la app | graphite-950 | `#0D0F12` |
| Tarjeta / sección | graphite-900 | `#14171C` |
| Fila, input, chip | graphite-800 | `#1B1F26` |
| Borde y divisor | graphite-800 | `#232830` |
| Texto principal | graphite-100 | `#E9EDF0` |
| Texto secundario | graphite-400 | `#7A838E` |
| Primary / activo | lime-500 | `#8FD62E` |
| Texto sobre lima | graphite-950 | `#10130A` |

**App del cliente · React PWA — oscura fija, sin conmutador** (revisado 2026-09-01: el handoff de
Claude Design fijó la PWA en modo oscuro, igual que el panel Filament — se descarta el "clara por
defecto, oscuro automático" de la versión anterior de este documento)

| Rol | Token | Valor |
|---|---|---|
| Fondo | `app-bg` | `#0B0D10` |
| Tarjeta / superficie | `app-surface` | `#14171C` |
| Superficie hundida (inputs, columnas Kanban) | `app-surface-2` | `#1B1F26` |
| Superficie elevada (avatares, chips) | `app-surface-3` | `#22272F` |
| Borde | `app-line` | `rgba(233,237,240,.10)` |
| Divisor sutil | `app-line-2` | `rgba(233,237,240,.06)` |
| Texto principal | `app-text` | `#E9EDF0` |
| Texto secundario | `app-muted` | `rgba(233,237,240,.56)` |
| Texto terciario / metadatos | `app-faint` | `rgba(233,237,240,.40)` |
| CTA y progreso | — | lima `#8FD62E` |
| Texto lima sobre oscuro | — | `#B6EC5E` |

Ver `design_handoff_doctor_motor/README.md` (en `UI Mockups sistema facturación/`) para la
paleta semántica completa (ámbar/coral/cian con variantes de texto y fondo), tipografía por rol de
componente, espaciado y el detalle pantalla por pantalla — es la fuente de verdad vigente para la
PWA, este documento queda como resumen.

## 03 · Reglas de contraste (no romper)

| Combinación | ¿Ok? | Ratio | Regla |
|---|---|---|---|
| Lima 500 + tinta graphite-950 | Sí | 11.9:1 | Botón principal en los dos productos |
| Texto lima sobre graphite-950 | Sí | 9.7:1 | Texto, íconos y bordes en fondo oscuro |
| Texto lima-500 sobre fondo claro | **No** | 1.8:1 | En modo claro de la PWA usar lime-700 (`#588718`) para texto — el 500 queda solo para fondos |
| Texto blanco sobre lima | **No** | 1.7:1 | Nunca. Filament lo hace por defecto — hay que overridear con CSS (ver snippet abajo) |

## 04 · Código listo para pegar

> **Nota (Fase 0, verificado contra la instalación real):** el PDF de origen trae sintaxis de
> Filament 3. El proyecto instaló **Filament 5.7** (última estable a la fecha) — la API de
> `->darkMode()` cambió, y el registro del tema custom ahora es vía `->viteTheme()` + el comando
> `php artisan make:filament-theme admin`, no un `@import` manual de un archivo de vendor. El
> snippet de abajo ya está actualizado y probado (`php artisan route:list` confirma que el panel
> arranca sin errores).

**Filament — `app/Providers/Filament/AdminPanelProvider.php`**
```php
use Filament\Support\Colors\Color;

$panel
    ->viteTheme('resources/css/filament/admin/theme.css') // agregado automáticamente por make:filament-theme
    ->darkMode(isForced: true) // v5: named param, no un bool posicional
    ->font('Inter')
    ->colors([
        'primary' => [
            50 => '244, 252, 228',   100 => '231, 249, 198',
            200 => '208, 243, 149',  300 => '182, 236, 94',
            400 => '159, 224, 58',   500 => '143, 214, 46',
            600 => '116, 179, 31',   700 => '88, 135, 24',
            800 => '68, 102, 24',    900 => '57, 79, 26',
            950 => '29, 43, 8',
        ],
        'gray'    => Color::Slate,
        'success' => Color::hex('#8FD62E'),
        'warning' => Color::hex('#E8B23A'),
        'danger'  => Color::hex('#E8654B'),
        'info'    => Color::hex('#4FA9C9'),
    ]);
    // ->brandLogo(asset('img/doctor-motor.svg'))->brandLogoHeight('2rem');
    // pendiente: falta el SVG del logo como asset del proyecto
```

**Filament — `resources/css/filament/admin/theme.css`** (generado por `make:filament-theme admin`,
no se edita a mano por defecto — Filament 5 ya compila su propio tema oscuro con Tailwind v4 a
partir de los colores de arriba)
```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
```
Si al ver el panel en Fase 1 el fondo oscuro por defecto de Filament no queda suficientemente
cerca de graphite-950 (`#0D0F12`), overridear ahí mismo — pero primero verificar visualmente,
no asumir que hacen falta los mismos selectores CSS que en Filament 3.

**PWA — `src/index.css` (Tailwind v4, sin plugins)**
```css
@import "tailwindcss";

@theme {
  --color-lime-50: #F4FCE4;   --color-lime-500: #8FD62E;
  --color-lime-100: #E7F9C6;  --color-lime-600: #74B31F;
  --color-lime-200: #D0F395;  --color-lime-700: #588718;
  --color-lime-300: #B6EC5E;  --color-lime-800: #446618;
  --color-lime-400: #9FE03A;  --color-lime-900: #394F1A;
  --color-lime-950: #1D2B08;

  --color-graphite-50: #F4F6F7;   --color-graphite-500: #5C6673;
  --color-graphite-100: #E9EDF0;  --color-graphite-600: #444C57;
  --color-graphite-200: #CDD4DA;  --color-graphite-700: #333A44;
  --color-graphite-300: #A8B1BA;  --color-graphite-800: #232830;
  --color-graphite-400: #7A838E;  --color-graphite-900: #14171C;
  --color-graphite-950: #0D0F12;

  --color-amber-brand: #E8B23A;
  --color-alert: #E8654B;
  --color-info: #4FA9C9;

  --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
  --radius-card: 0.875rem; /* 14px, tarjetas del móvil */
}

/* Roles semánticos: cambian con el tema, las clases no */
:root {
  --app-bg: var(--color-graphite-950);
  --app-surface: var(--color-graphite-900);
  --app-line: #232830;
  --app-text: var(--color-graphite-100);
  --app-muted: #8A939C;
}
:root[data-theme="light"] {
  --app-bg: #F4F5F3;
  --app-surface: #FFFFFF;
  --app-line: rgba(20,23,26,.08);
  --app-text: #14171A;
  --app-muted: #6A7076;
}
@theme inline {
  --color-app-bg: var(--app-bg);
  --color-app-surface: var(--app-surface);
  --color-app-line: var(--app-line);
  --color-app-text: var(--app-text);
  --color-app-muted: var(--app-muted);
}
```

**PWA — uso en componentes React**
```tsx
{/* Botón principal: lima sólido con tinta oscura */}
<button className="w-full rounded-xl bg-lime-500 py-4 font-semibold
  text-graphite-950 active:bg-lime-600
  focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime-400">
  Pagar con QR
</button>

{/* Botón secundario: contorno lima sobre fondo del tema */}
<button className="w-full rounded-xl border border-lime-500/60 bg-lime-500/10
  py-4 font-medium text-lime-400 active:bg-lime-500/20">
  Aprobar presupuesto
</button>

{/* Tarjeta */}
<article className="rounded-card border border-app-line bg-app-surface p-4 text-app-text">
  <p className="text-sm text-app-muted">Avance del servicio</p>
</article>

{/* Estados de la OT */}
<span className="rounded-full bg-lime-500/15 px-2.5 py-1 text-xs text-lime-400">En reparación</span>
<span className="rounded-full bg-[#E8B23A]/15 px-2.5 py-1 text-xs text-[#E8B23A]">Por pagar</span>
<span className="rounded-full bg-[#E8654B]/15 px-2.5 py-1 text-xs text-[#E8654B]">Espera repuesto</span>
```

**PWA — `public/manifest.webmanifest` + meta de barra de estado**
```json
{
  "name": "Doctor Motor · Mustang's Garage",
  "short_name": "Doctor Motor",
  "display": "standalone",
  "orientation": "portrait",
  "background_color": "#0D0F12",
  "theme_color": "#0D0F12",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```
```html
<meta name="theme-color" content="#0D0F12" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#F4F5F3" media="(prefers-color-scheme: light)">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
```

## 05 · Notas de implementación

- **Un solo origen**: rampas en un `colors.json` compartido en el repo; generar desde ahí el
  arreglo de Filament y el bloque `@theme` de Tailwind. No copiar/pegar valores sueltos en cada
  lado.
- **Tipografía**: Inter, pesos **400/500/600 únicamente** — nada más. En Filament vía
  `->font('Inter')`; en la PWA autoalojada (no CDN) para que funcione offline.
- **Radios**: admin 8–10px (denso, tabular). App del cliente 12–18px en tarjetas, 12px en
  botones — más suave, más amigable en pantalla táctil.
- **Área táctil**: mínimo 44px de alto en todo elemento tocable de la PWA, con
  `safe-area-inset-bottom` en la barra de tabs inferior.
- **Modo oscuro**: el admin es oscuro fijo (sin toggle). La PWA sigue `prefers-color-scheme` con
  override manual por `data-theme` — el cliente la abre tanto en el taller como en la calle, así
  que necesita adaptarse a la luz ambiente.
- **El lima se usa poco**: regla práctica del propio sistema — un elemento lima por pantalla como
  protagonista, el resto viene del grafito. Si todo brilla, nada guía.

## Patrones de componentes (de las capturas de ejemplo)

- **Tarjetas de KPI**: label en mayúscula pequeña + cifra grande + línea de contexto secundaria.
- **Barras de progreso por etapa**: mini-tarjeta con ícono + cifra + barra, una por etapa del
  taller — mapea directo al Kanban de Módulo 2.
- **Feed de alertas/decisiones**: tarjetas apiladas, ícono de color semántico + texto corto +
  acción.
- **Timeline vertical**: punto lleno lima = completado, punto vacío gris = pendiente — línea de
  tiempo interactiva del Módulo 5.
- **Aprobación**: par Aprobar (relleno lima) / Rechazar (outline neutro).
- **Login del cliente**: Google (botón blanco) + WhatsApp (botón lima) + formulario manual.
