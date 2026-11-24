# Sistema de Diseño — Doctor Motor

Esta es la fuente única de verdad de color y tipografía — tanto Filament (panel interno) como
Tailwind en React (PWA) se configuran a partir de estos mismos tokens.

**Paleta «Razer» (2026-11-24):** verde Razer `#44D62C` sobre un grafito casi neutro con un toque
verde. Reemplaza a la paleta lima anterior. **Los dos productos son solo modo oscuro**: no hay
tokens, toggle ni variantes de modo claro. La única superficie blanca es el PDF impreso
(`resources/views/pdf/layout.blade.php`), a propósito, porque se imprime en papel.

> Los nombres internos siguen diciendo `lima` (`--color-lime-*`, `--dm-lima`) para no tocar cientos
> de reglas; el valor es el verde Razer.

## 01 · Paleta

**Verde Razer — marca** (acción principal, progreso)
`100` #D3F9C4 · `200` #ADF695 · `300` #81F161 · `400` #5AE039 · `500` **#44D62C** ·
`600` #2EB21B · `700` #29891A · `800` #236718 · `900` #234F19 · `950` #0D2A06

**Grafito — neutro** (fondos, superficies, bordes, texto)
`950` **#0D100D** fondo · `900` #151815 superficie 1 · `850` #1D201D superficie 2 / cards ·
`800` #262926 bordes · `700` #373A38 · `600` #494C4A · `500` #636663 · `400` #808380 texto
atenuado · `300` #ADB1AE · `200` #D0D4D1 · `100` #EAEEEA texto principal

**Texto sobre verde** (botones primarios): `#0D140B`

**Estados** (texto / fondo de badge)

| Estado | Texto | Fondo |
|---|---|---|
| Éxito («Pagada», «Sincronizado») | `#81F161` | `#0D2A06` |
| Advertencia (ámbar) | `#FCAA2B` | `#3A2808` |
| Peligro (vencido, error) | `#EC485C` | `#3A1219` |
| Info | `#44A8E7` | `#0F2A3D` |

El éxito usa `#81F161` y no el verde de marca puro, para que un badge «Pagada» no se confunda con
un botón de acción. Violeta `#B9A2FF` para «Control de calidad». Los semánticos son independientes
del acento — nunca se usan para CTAs de marca, solo para estado.

## 02 · Roles por superficie

**Panel admin · Filament — oscuro fijo, sin toggle**

| Rol | Token | Valor |
|---|---|---|
| Lienzo de la app, menú lateral, inputs | graphite-950 | `#0D100D` |
| Tarjeta / sección | graphite-900 | `#151815` |
| Botón secundario, fila, chip | graphite-850 | `#1D201D` |
| Borde de tarjeta | graphite-800 | `#262926` |
| Borde fuerte (inputs, avatar) | graphite-700 | `#373A38` |
| Texto principal | graphite-100 | `#EAEEEA` |
| Texto secundario | graphite-300 | `#ADB1AE` |
| Texto atenuado / metadatos | graphite-400 | `#808380` |
| Primary / activo | lime-500 | `#44D62C` |
| Hover del botón primario | lime-600 | `#2EB21B` |
| Texto sobre verde | on-brand | `#0D140B` |

**App móvil · React PWA — oscura fija, sin conmutador**

| Rol | Token | Valor |
|---|---|---|
| Fondo | `app-bg` | `#0D100D` |
| Tarjeta / superficie | `app-surface` | `#151815` |
| Superficie hundida (inputs, columnas Kanban) | `app-surface-2` | `#0D100D` |
| Superficie elevada (avatares, chips) | `app-surface-3` | `#1D201D` |
| Borde | `app-line` | `#262926` |
| Divisor sutil | `app-line-2` | `#1D201D` |
| Texto principal | `app-text` | `#EAEEEA` |
| Texto secundario | `app-muted` | `#ADB1AE` |
| Texto terciario / metadatos | `app-faint` | `#808380` |
| CTA y progreso | `lime-500` | `#44D62C` (hover `lime-600`) |
| Texto sobre verde | `lime-ink` | `#0D140B` |
| Texto verde sobre oscuro | `lime-txt` | `#44D62C` |
| Badge de éxito | `ok-txt` / `ok-bg` | `#81F161` / `#0D2A06` |

Ver `design_handoff_doctor_motor/README.md` (en `UI Mockups sistema facturación/`) para la
tipografía por rol de componente, espaciado y el detalle pantalla por pantalla.

## 03 · Reglas de contraste (no romper)

| Combinación | ¿Ok? | Ratio aprox. | Regla |
|---|---|---|---|
| Verde 500 + texto `#0D140B` | Sí | 9.7:1 | Botón principal en los dos productos |
| Texto verde sobre graphite-950 | Sí | 9:1 | Texto, íconos y bordes en fondo oscuro |
| `#808380` sobre graphite-900 | Justo | 4.6:1 | Solo texto terciario (metadatos); lo secundario usa `#ADB1AE` |
| Texto blanco sobre verde | **No** | 1.7:1 | Nunca. Filament lo hace por defecto — se overridea con CSS (`--dm-on-brand`) |

## 04 · Dónde vive cada cosa

- **Filament:** `AdminPanelProvider::colors()` (escalas `primary`, `gray`, `danger` y semánticos) y
  `resources/css/filament/admin/theme.css` (tokens `--dm-*`: fondo, superficies, bordes, texto,
  `--dm-on-brand`, `--dm-ok-*`) + `componentes.css` + `login.css`.
- **PWA:** bloque `@theme` de `frontend/src/index.css` (escala `lime`, `ok-*`, `amb-*`, `cor-*`,
  `cya-*`, `app-*`). Tipografías autoalojadas, nada de CDN.
- **Un solo origen:** si se cambia un valor, cambiarlo en los dos lados y en este documento.

```css
/* frontend/src/index.css — extracto */
@theme {
  --color-lime-500: #44D62C;  --color-lime-600: #2EB21B;
  --color-lime-ink: #0D140B;
  --color-ok-txt: #81F161;    --color-ok-bg: #0D2A06;
  --color-amb: #FCAA2B;       --color-amb-bg: #3A2808;
  --color-cor: #EC485C;       --color-cor-bg: #3A1219;
  --color-cya: #44A8E7;       --color-cya-bg: #0F2A3D;
  --color-app-bg: #0D100D;    --color-app-surface: #151815;
  --color-app-text: #EAEEEA;  --color-app-muted: #ADB1AE;
}
:root { color-scheme: dark; }
```

```tsx
{/* Botón principal: verde sólido con tinta oscura, hover más oscuro */}
<button className="w-full rounded-xl bg-lime-500 py-4 font-semibold text-lime-ink hover:bg-lime-600">
  Pagar con QR
</button>

{/* Badge de éxito: no usa el verde de los CTA */}
<span className="rounded-full bg-ok-bg px-2.5 py-1 text-xs text-ok-txt">Pagada</span>
```

## 05 · Notas de implementación

- **Tipografía**: Inter, JetBrains Mono (placas, códigos, montos) y Archivo 700 (títulos). En el
  panel y en la PWA autoalojadas con `@fontsource`, para que funcione offline.
- **Radios**: admin 8–10px (denso, tabular). App móvil 12–18px en tarjetas, 12px en botones.
- **Área táctil**: mínimo 44px de alto en todo elemento tocable de la PWA, con
  `safe-area-inset-bottom` en la barra de tabs inferior.
- **Solo modo oscuro**: ni el panel ni la PWA tienen selector de tema.
- **El verde se usa poco**: un elemento verde por pantalla como protagonista, el resto viene del
  grafito. Si todo brilla, nada guía.

## Patrones de componentes

- **Tarjetas de KPI**: label en mayúscula pequeña + cifra grande + línea de contexto secundaria.
- **Barras de progreso por etapa**: mini-tarjeta con ícono + cifra + barra, una por etapa del taller.
- **Feed de alertas/decisiones**: tarjetas apiladas, ícono de color semántico + texto corto + acción.
- **Timeline vertical**: punto lleno verde = completado, punto vacío gris = pendiente.
- **Aprobación**: par Aprobar (relleno verde) / Rechazar (outline neutro).
- **Login del cliente**: Google (botón blanco) + WhatsApp (botón verde) + formulario manual.

---

## Rediseño del panel admin (v2, noviembre 2026)

Fuente: handoff de Claude Design (`HANDOFF.md`, §3). Lo implementado vive en
`backend/resources/css/filament/admin/theme.css` y `AdminPanelProvider`.

- **Acento verde Razer `#44D62C`** con texto oscuro `#0D140B` encima (botones primarios). Sobre
  fondos grandes, nunca.
- **Superficies:** página y menú lateral `#0D100D` · tarjetas `#151815` · botón secundario
  `#1D201D` · inputs `#0D100D`. Bordes `#262926` (tarjetas) y `#373A38` (inputs).
- **Semánticos:** ámbar `#FCAA2B` (atención, saldo por cobrar), rojo `#EC485C` (vencido, error),
  azul `#44A8E7` (diagnóstico, info), violeta `#B9A2FF` (control de calidad).
- **Estados de la orden** (`App\Support\EstadoOrden`, un solo lugar): Recepcionado gris ·
  En diagnóstico azul · Esperando aprobación ámbar · En reparación verde · Control de calidad
  violeta · Listo para entrega verde · Entregado gris · Cancelado rojo.
- **Tipografía** (empaquetada con `@fontsource`, sin CDN): Archivo 700 (títulos de página y de
  sección), Inter (interfaz), JetBrains Mono (códigos de OT, placas, montos, contadores).
- **Textos en minúscula de oración:** "Órdenes de trabajo", no "Órdenes De Trabajo"
  (`App\Filament\Concerns\TituloEnOracion`).
- **Medidas:** inputs y botones 44 px y radio 10 px · tarjetas radio 16 px · menú lateral 248 px.
- **Oscuro fijo:** no hay selector de tema claro ni "Sistema" (decisión de producto).
- **Menú lateral:** Taller (Nueva recepción, Órdenes, Clientes, Vehículos) · Caja (Caja del día,
  Cobros, Gastos) · Inventario · Reportes · Administración. Contadores: órdenes activas (gris),
  productos bajo el mínimo (ámbar), mensajes por enviar (ámbar) o fallidos (rojo).
- **Barra superior:** búsqueda global (`Ctrl+K`: placa, cliente, orden), estado de la caja
  ("Caja abierta · Bs X" o "Caja cerrada · Abrir"; el efectivo sale de
  `CajaCierre::efectivoEsperado()`), campana y menú de usuario.
- **Avatares:** iniciales en SVG generado localmente; no se usa `ui-avatars.com`.

## Rediseño de la PWA móvil (v2, noviembre 2026)

La PWA es **solo móvil** (columna de hasta 520 px centrada) y oscura fija, con los mismos colores
que el panel admin (ver §01). Tipografías autoalojadas: Inter (texto), Archivo (títulos, clase
`.dm-display`) y JetBrains Mono (placas, códigos y montos).

- **Componentes** (en `frontend/src/index.css`): `.dm-card`, `.dm-lab`, `.dm-plate`, `.dm-prog` (progreso de 7 pasos), `.dm-btn` (`.p` verde / `.s` secundario / `.sm`), `.dm-sticky` (barra de acción fija) y `.dm-li` (fila de lista).
- **Menú inferior**: técnico = Mi día · Tablero · Perfil; cliente = Inicio · Mis vehículos · Perfil. Las pantallas de detalle (orden, inspección, fotos, presupuesto, seguimiento) no llevan menú: llevan «‹ atrás» y, si corresponde, un botón fijo abajo.
- **Técnico**: Mi día (`/`), Tablero agrupado por etapa (`/ordenes-trabajo`, solo sus órdenes asignadas), Orden como centro de trabajo (`/ordenes-trabajo/:id`, lista de tareas + botón fijo «pasar a la siguiente etapa»), Inspección, Fotos y videos.
- **Cliente**: Inicio (`/`, vehículo en el taller + acción pendiente), Presupuesto con aprobación por trabajo (`/presupuestos/:id`), Seguimiento con línea de tiempo (`/seguimiento/:id`), Mis vehículos (`/vehiculos`, reemplaza a «Mi garaje»; `/garaje` redirige) e Historial público por QR (`/qr/:token`).
- **Contacto con el taller**: los botones de WhatsApp / llamar aparecen solo si `VITE_TALLER_WHATSAPP` está definido en el `.env` del frontend (dígitos con código de país).
- Fase 2 (no está en pantalla): clasificar el presupuesto en «Necesario / Recomendado», notas del técnico al cliente, etapas con fotos en «Fotos y videos».

### Historial de cambios de acento

- 2026-11-24 · Paleta «Razer»: el lima `#C5F82A` pasó a verde Razer `#44D62C` y todo el grafito pasó
  de azulado a casi neutro con un toque verde. Hover del primario más oscuro (`#2EB21B`), badges de
  estado con fondos sólidos y badge de éxito en `#81F161` / `#0D2A06`. Se eliminó toda mención al
  modo claro. El tono «hecho» de las barras de progreso sigue en `#2E9B20`.
