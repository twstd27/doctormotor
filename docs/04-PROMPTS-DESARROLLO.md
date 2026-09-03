# Prompts de arranque por fase

Cómo usar esto: al empezar cada fase, abrí una sesión de Claude Code con el directorio de trabajo
en `app/` (raíz del repo) y pegá el prompt de esa fase tal cual. Claude ya va a leer `CLAUDE.md`
automáticamente al arrancar; el prompt solo apunta a qué construir en esta fase específica. No
hace falta re-explicar la arquitectura ni el stack en cada prompt — ya vive en `docs/`.

Antes de la Fase 0, confirmar que el entorno local tiene PHP 8.3 + Composer, Node.js 24+,
PostgreSQL y Redis corriendo (ver `README.md`). Este entorno ya está armado en esta máquina —
ver la sección "Entorno local" de `README.md` para las rutas y credenciales.

---

## Fase 0 — Preparación

```
Arrancá la Fase 0 del proyecto Doctor Motor (ver docs/01-ARQUITECTURA.md, sección de fases).

Backend (backend/):
- Proyecto Laravel 13 nuevo, PostgreSQL como conexión por defecto.
- Instalar y configurar Filament 5 como panel admin en /admin.
- Instalar Sanctum (auth de API) y spatie/laravel-permission (roles: super_admin, cajero,
  operador_tecnico, cliente) según docs/02-BASE-DE-DATOS.md tabla `users`.
- Migraciones de TODAS las tablas de docs/02-BASE-DE-DATOS.md (todavía sin datos ni lógica de
  negocio — solo el esquema completo, para no tener que ir agregando tablas sueltas fase a fase).
- Endpoints de /api/v1/auth/* de docs/03-API-ENDPOINTS.md sección 0, incluyendo login con
  correo/password, enlace mágico de WhatsApp (dejar el envío real de WhatsApp mockeado/logueado
  por ahora, se implementa en Fase 3) y login con Google vía Laravel Socialite.
- Panel de Filament con el tema oscuro fijo + acento lima de docs/05-DESIGN-SYSTEM.md sección 04
  (usar el código de AdminPanelProvider.php y theme.css tal cual está ahí, no reinventarlo).

Frontend (frontend/):
- Proyecto React 19 + Vite + TypeScript, Tailwind CSS v4 configurado con el index.css de
  docs/05-DESIGN-SYSTEM.md sección 04 (tokens de color, tema claro por defecto con oscuro
  automático vía prefers-color-scheme + override data-theme — no confundir con el admin, que es
  oscuro fijo).
- vite-plugin-pwa configurado (instalable, service worker básico, todavía sin lógica de
  sincronización offline real — eso es Fase 3).
- Layout base mobile-first (viewport ~375-428px como diseño primario) con navegación inferior
  tipo la referencia en docs/design-reference/ (pantallas de iPhone).
- Pantalla de login/registro (correo, Google, WhatsApp) conectada a los endpoints de auth.

Al terminar, dejame un resumen de qué falta configurar manualmente (variables de entorno, keys,
etc.) antes de poder correr todo localmente.
```

## Fase 1 — MVP operativo

```
Arrancá la Fase 1 del proyecto Doctor Motor: Módulo 1 (Clientes, Vehículos y Cuentas) y Módulo 2
(Órdenes de Trabajo) completos, según docs/01-ARQUITECTURA.md, docs/02-BASE-DE-DATOS.md y
docs/03-API-ENDPOINTS.md secciones 1 y 2.

Backend: implementar todos los endpoints de esas dos secciones (clientes, vehículos, órdenes de
trabajo, cambio de estado del Kanban con evento por Reverb, inspección digital con firma, carga
de evidencias con idempotencia por uuid_cliente). Cobros básicos de la sección 4 (solo POST /pagos
y GET /clientes/{id}/cuenta — el resto de finanzas es Fase 2).

Frontend — Filament: recursos de Clientes y Vehículos (CRUD) para Super Admin/Cajero.

Frontend — PWA React (mobile-first, paleta de docs/05-DESIGN-SYSTEM.md):
- Tablero Kanban de OTs (drag & drop) para Operador Técnico, con los patrones de tarjeta de
  docs/design-reference/ (mini-tarjeta por etapa con contador y barra de progreso).
- Formulario de recepción/inspección con firma digital (canvas) para Cajero/Operador.
- Carga de fotos/video de evidencia (usar API del dispositivo, sin lógica offline todavía).
- Vista "Mi garaje" y línea de tiempo de la OT para el cliente final, siguiendo el patrón de
  timeline vertical de la referencia (punto lleno lima = completado, punto vacío = pendiente).

Verificar en el navegador (mobile viewport) que el Kanban y la línea de tiempo del cliente
funcionan de punta a punta con datos reales antes de dar la fase por terminada.
```

## Fase 2 — Finanzas e inventario

```
Arrancá la Fase 2 del proyecto Doctor Motor: Módulo 3 (Administración Financiera) y Módulo 4
(Inventario y Proveedores) completos, según docs/02-BASE-DE-DATOS.md y docs/03-API-ENDPOINTS.md
secciones 4 y 5.

Backend: caja chica (apertura/cierre), costos directos por OT, gastos y egresos (incluyendo
sueldos y salarios del personal como categoría), reglas de reparto de utilidades entre socios y
su cálculo periódico, kardex de productos con movimientos de inventario, alertas de stock mínimo,
proveedores, compras y cuentas por pagar.

Filament: recursos/paneles para todo lo anterior, más los dashboards de reportes
(docs/03-API-ENDPOINTS.md sección 4: /reportes/*) con gráficos de ingresos vs. egresos y
rentabilidad por OT — usar los patrones visuales de docs/design-reference/ (KPI grande + contexto
secundario, gráfico de barras ingresos vs. costos).

No hace falta tocar el frontend React en esta fase salvo que un endpoint nuevo lo requiera — este
módulo es 100% back-office (Super Admin).
```

## Fase 3 — Portal e integraciones

```
Arrancá la Fase 3 del proyecto Doctor Motor: Módulo 5 (Portal del Cliente) completo, más las
integraciones críticas, según docs/01-ARQUITECTURA.md sección "Notas técnicas críticas" y
docs/03-API-ENDPOINTS.md secciones 3, 6 y 7.

- WhatsApp Business Cloud API real (reemplazar el mock de Fase 0): envío de notificaciones de
  cambio de estado, webhook entrante.
- Generación de PDF: presupuestos, recibos, historial clínico automotriz descargable.
- Impresión térmica ESC/POS para tickets de caja.
- Sincronización offline real en la PWA: cola en IndexedDB (Dexie.js) para evidencias/estados
  tomados sin señal, sync al reconectar contra POST /evidencias/sync-batch.
- Frontend: aprobación de presupuestos y adicionales con notificación interactiva (botones
  Aprobar/Rechazar como en docs/design-reference/), historial clínico descargable desde la app
  del cliente.

Probar el flujo offline apagando la red del dispositivo/emulador a mitad de una carga de
evidencia y confirmando que sincroniza al reconectar.
```

## Fase 4 — Cierre

```
Arrancá la Fase 4 (cierre) del proyecto Doctor Motor.

- Revisar los 5 módulos contra docs/01-ARQUITECTURA.md sección 2 (alcance) y marcar cualquier
  gap.
- Pruebas de los flujos críticos: recepción → Kanban → aprobación de presupuesto → cobro → cierre
  de caja → reparto de utilidades; y el flujo de inventario (compra → stock → consumo en OT →
  alerta de stock mínimo).
- Preparar despliegue a producción en el droplet de DigitalOcean (región São Paulo) según
  docs/01-ARQUITECTURA.md: variables de entorno, migraciones en producción, build de la PWA,
  configuración de DigitalOcean Spaces para medios.
- Checklist de capacitación para el taller: qué necesita saber cada rol (Super Admin, Cajero,
  Operador Técnico) para usar el sistema el primer día.
```
