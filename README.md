# Doctor Motor — Sistema de Gestión Integral Automotriz

Monorepo del sistema de gestión para **Doctor Motor · Mustang's Garage** (ERP/CRM/Portal de
clientes). Ver [CLAUDE.md](CLAUDE.md) para el mapa completo de documentación técnica y
convenciones del proyecto.

## Estructura

- `backend/` — API en Laravel 13 + panel administrativo en Filament 5.
- `frontend/` — PWA en React 19 + Vite para mecánicos y clientes.
- `docs/` — especificación técnica: arquitectura, base de datos, API, sistema de diseño.

## Estado actual

**Fase 0 y Fase 1 completas**, probadas de punta a punta contra PostgreSQL/Redis reales (no solo
"compila") — ver `docs/04-PROMPTS-DESARROLLO.md` para el detalle de cada fase.

**Fase 0** — entorno completo: Laravel 13 + Filament 5 (panel `/admin`, tema oscuro + paleta de
marca) + Sanctum + spatie/laravel-permission, las 25 tablas de `docs/02-BASE-DE-DATOS.md`
migradas, y auth completo (correo/WhatsApp/Google — Google necesita credenciales reales de
Google Cloud para probarse fin a fin).

**Fase 1** — Módulo 1 y 2 completos:
- Backend: CRUD de clientes/vehículos, órdenes de trabajo con Kanban (`PATCH .../estado` dispara
  evento por Reverb), inspección digital + firma, evidencias (+ `sync-batch` idempotente),
  presupuestos + ítems + adicionales + aprobación del cliente, cobros básicos y estado de cuenta.
- Filament: recursos de Clientes y Vehículos.
- PWA React: tablero de OTs (mobile-first, cambio de estado por acción en vez de drag&drop —
  más confiable en touch), inspección con firma digital (`signature_pad`), carga de fotos/video,
  y "Mi garaje" con línea de tiempo para el cliente.
- 3 bugs reales encontrados y corregidos en el camino: nombres de tabla en español mal
  adivinados por Eloquent, un mismatch de nombre de parámetro de ruta que rompía el binding de
  modelo, y una carrera de datos en la hidratación de la sesión (Zustand) que causaba 401
  espurios en la carga inicial.
- Usuario de prueba `super_admin` ya cargado: `yesid@doctormotor.test` / `secreto123`.

**Fase 2** — Módulo 3 y 4 completos (100% back-office, sin cambios en el frontend React):
- Backend: caja chica (apertura/cierre con diferencia calculada solo sobre efectivo), costos
  directos por OT (+ `/margen`), gastos fijos/variables, socios + reglas de reparto (valida que
  sumen 100%) + generación de reparto de utilidades por período, kardex de productos con
  movimientos automáticos (compras, consumo en OT, ajustes manuales) y alertas de stock mínimo,
  proveedores, compras y cuentas por pagar.
- Filament: recursos de Gastos/Egresos, Productos (con indicador de stock bajo), Proveedores y
  Socios.
- Reportes: dashboard, serie de ingresos/egresos, rentabilidad por OT.
- Todo probado con datos reales: una compra de verdad mueve el stock del producto, el cálculo de
  reparto de utilidades da el monto correcto por socio, el cierre de caja calcula la diferencia
  bien. Encontré y corregí más mismatches de nombre de tabla/parámetro (mismo patrón de Fase 1)
  y un slug de URL de Filament mal generado (`/admin/proveedors` → `/admin/proveedores`).

**Fase 3** — Módulo 5 e integraciones:
- PDFs reales (dompdf): presupuesto, recibo, historial clínico automotriz, y un formato ticket
  angosto (80mm) pensado para impresoras térmicas con driver estándar de Windows — verificados
  visualmente, no solo "generan bytes".
- `WhatsAppService`: capa real contra la Cloud API de Meta (plantillas por estado de la OT —
  diagnóstico, en reparación, lista para entrega — invitación de cuenta, enlace de acceso,
  presupuesto enviado). Sin `WHATSAPP_TOKEN` configurado cae a un log local automáticamente, así
  que el resto del sistema funciona igual sin credenciales reales de Meta todavía. Cada envío
  queda auditado en `notificaciones` (`/notificaciones`, reintento incluido) + webhook entrante
  con verificación de token.
- PWA: cola offline real con IndexedDB (Dexie) para evidencias — encola aunque no haya señal,
  sincroniza sola al reconectar y cada 30s mientras la pestaña sigue abierta, verificado
  apagando/prendiendo `navigator.onLine` en el navegador real.
- Pantalla de aprobación de presupuesto para el cliente (botones Aprobar/Rechazar, adicionales
  destacados aparte) y descarga de historial clínico en PDF desde "Mi garaje".
- Impresión térmica ESC/POS de bajo nivel **no** se implementó — necesita el modelo real de
  impresora del taller para poder probarse en serio; el formato ticket en PDF es la alternativa
  verificable que cubre la misma necesidad mientras tanto.
- Otro bug real corregido: un link de descarga de PDF armado como `<a href>` simple no habría
  funcionado nunca (el navegador no manda el token Bearer en una navegación normal) — se cambió
  a fetch+blob antes de darlo por terminado.

**Fase 4** — cierre:
- Gaps chicos que aparecieron al revisar el alcance completo, ya cerrados: `GET/PUT /me/perfil`
  para que el cliente edite sus datos de contacto, y un recurso de Filament para que Super Admin
  gestione usuarios internos (cajeros, técnicos) — separado de Clientes, con contraseña opcional
  al editar.
- Despliegue real a producción **no se ejecutó** — necesita acceso SSH al droplet y credenciales
  externas que no están disponibles en esta máquina. Todo lo que falta para eso, paso a paso,
  quedó en [docs/06-CHECKLIST-DESPLIEGUE.md](docs/06-CHECKLIST-DESPLIEGUE.md).

**Las 4 fases del roadmap original están completas en su alcance funcional**, probado con datos
reales en cada una. Lo que queda es "conectar el mundo exterior": credenciales reales de
Google/Meta, y el despliegue al droplet — ver el checklist de arriba.

## Entorno local (ya instalado en esta máquina)

- PHP 8.3, Composer — vía Laravel Herd (`~/.config/herd/bin`)
- Node.js 26 — vía Herd (`~/.config/herd/bin/nvm/v26.7.0`)
- PostgreSQL 18 — servicio de Windows `postgresql-x64-18`, base `doctor_motor`
- Redis 8.10 (redis-windows/redis-windows) — servicio de Windows `Redis`, en `C:\redis`

Ninguno de estos binarios está en el PATH del sistema todavía (requiere reabrir terminal/sesión
para que Windows lo propague) — hasta entonces, anteponer las rutas de Herd al `PATH` de la
sesión antes de correr `composer`/`npm`/`php`.
