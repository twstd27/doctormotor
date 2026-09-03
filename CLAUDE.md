# Doctor Motor — Sistema de Gestión Integral Automotriz

Monorepo del sistema para el taller **Doctor Motor · Mustang's Garage**. Antes de tocar código,
leer los documentos en [`docs/`](docs/) — son la fuente de verdad y este archivo no los repite,
solo los referencia.

## Documentación de referencia (leer en este orden)

1. [docs/01-ARQUITECTURA.md](docs/01-ARQUITECTURA.md) — arquitectura, stack, decisiones y por qué.
2. [docs/02-BASE-DE-DATOS.md](docs/02-BASE-DE-DATOS.md) — esquema completo de PostgreSQL.
3. [docs/03-API-ENDPOINTS.md](docs/03-API-ENDPOINTS.md) — contrato de la API REST.
4. [docs/05-DESIGN-SYSTEM.md](docs/05-DESIGN-SYSTEM.md) — paleta, tipografía, patrones de UI
   (con capturas de referencia en `docs/design-reference/`).
5. [docs/04-PROMPTS-DESARROLLO.md](docs/04-PROMPTS-DESARROLLO.md) — prompt de arranque usado
   para cada fase (0 a 4), todas completas — ver estado detallado en `README.md`.
6. [docs/06-CHECKLIST-DESPLIEGUE.md](docs/06-CHECKLIST-DESPLIEGUE.md) — qué falta para pasar de
   "corre en esta máquina" a producción (credenciales externas, pasos de deploy).

## Un dato importante para cualquier sesión nueva en este repo

En varias fases apareció el mismo bug: **Eloquent adivina mal el nombre de tabla/parámetro de
ruta cuando el nombre en español no sigue el patrón de pluralización en inglés** (ej. modelo
`OrdenTrabajo` → Eloquent asume tabla `orden_trabajos`, pero la real es `ordenes_trabajo`; lo
mismo con parámetros de rutas de recursos como `proveedores` → Laravel genera `{proveedore}`).
Al crear un modelo o una ruta nueva sobre una tabla con nombre compuesto en español, **verificar
explícitamente** con `php artisan route:list` o una query real antes de asumir que el nombre
adivinado es correcto — no cuesta nada setear `protected $table` o `->parameters([...])`
explícito de entrada.

## Estructura del repo

```
app/
├── backend/    Laravel 13 + Filament 5 — API REST + panel admin (Super Admin, Cajero)
├── frontend/   React 19 + Vite — PWA para Operador Técnico y Cliente Final
└── docs/       Especificación técnica (este árbol de documentos)
```

## Stack (resumen — el detalle y el porqué están en 01-ARQUITECTURA.md)

- **Backend**: Laravel 13 (PHP 8.3), PostgreSQL 16, Redis, Laravel Reverb (WebSockets), Sanctum
  (auth API), spatie/laravel-permission (roles).
- **Panel admin**: Filament 5 sobre el mismo backend — Super Admin y Cajero. Tema oscuro fijo con
  el acento lima de `docs/05-DESIGN-SYSTEM.md` vía `Panel::colors()`.
- **PWA**: React 19 + Vite + TypeScript + TanStack Query + Zustand + Tailwind CSS v4,
  `vite-plugin-pwa` para offline (mecánicos) e instalabilidad. Mobile-first siempre — la mayoría
  del tráfico es móvil, así que ninguna pantalla se diseña "desktop primero y se adapta". Tema
  claro por defecto con oscuro automático (`prefers-color-scheme` + override manual) — a
  diferencia del panel Filament, que es oscuro fijo sin toggle.
- **Notificaciones**: WhatsApp Business Cloud API (Meta).
- **Almacenamiento**: DigitalOcean Spaces (S3-compatible) para fotos/video de evidencia.
- **Hosting**: DigitalOcean, región São Paulo.

## Convenciones

- **Mobile-first real**: en el frontend, todo componente se construye primero para viewport móvil
  (~375-428px) y se expande hacia arriba con breakpoints, nunca al revés.
- **Un solo sistema de colores**: los tokens de `docs/05-DESIGN-SYSTEM.md` son la única fuente de
  verdad de color — no se introducen colores nuevos ad-hoc ni en Filament ni en React sin
  actualizar ese documento primero.
- **La API es el contrato**: cualquier endpoint nuevo o cambio de forma de datos se refleja primero
  en `docs/03-API-ENDPOINTS.md`, luego se implementa.
- **Cambios de esquema**: toda migración de base de datos corresponde a una entidad ya descrita en
  `docs/02-BASE-DE-DATOS.md`; si hace falta un campo/tabla nueva, se actualiza ese documento en el
  mismo cambio.
- Commits en español, mensajes cortos y en modo imperativo (ej. "agrega endpoint de cierre de
  caja"), consistente con el resto de la documentación del proyecto.
