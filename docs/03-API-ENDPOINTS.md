# API REST — Endpoints

Base URL: `/api/v1`. Autenticación: **Bearer token** (Laravel Sanctum). Autorización por rol
(`super_admin`, `cajero`, `operador_tecnico`, `cliente`) — la columna "Rol" indica quién puede
llamar el endpoint (además de `super_admin`, que tiene acceso total salvo que se indique lo
contrario).

**Cómo se aplica (backend):** cada ruta lleva el middleware `rol:a,b` (limita por rol; el
`super_admin` siempre pasa y el rol se evalúa **antes** de resolver el registro de la URL, así un
rol sin permiso recibe siempre `403` y no puede distinguir qué IDs existen) y, cuando el endpoint
apunta a un registro puntual, `can:...` con una regla de pertenencia (ver `AppServiceProvider`).
Un rol o registro no permitido responde `403 { "message": "No tienes permiso para realizar esta acción." }`.

Resumen por rol:

- **cajero** — staff del mostrador: clientes, vehículos, OTs (crear, editar, asignar técnico,
  cambiar estado), pagos, **su propia** caja, compras/proveedores y alertas de stock. **No** ve
  reportes, gastos, socios, reparto de utilidades, márgenes ni el log de notificaciones.
- **operador_tecnico** — solo las órdenes **asignadas a él**: ve su detalle, cambia estado, registra
  inspección, evidencias, presupuestos/adicionales y costos directos. No crea OTs ni clientes, no
  ve pagos ni finanzas.
- **cliente** — solo lo suyo (su cuenta, sus vehículos, sus OTs, presupuestos y recibos) y responde
  a los presupuestos de sus OTs.
- **super_admin** — todo.

Convenciones: paginación estándar Laravel (`?page=`), filtros vía query params, respuestas
`{ data, meta }` para listados y `{ data }` para recursos individuales, errores en formato
`{ message, errors }`.

---

## 0. Autenticación

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| POST | `/auth/login` | Login con email/teléfono + password | público |
| POST | `/auth/login/whatsapp-link` | Solicita enlace mágico de acceso por WhatsApp | público |
| POST | `/auth/whatsapp/verify/{token}` | Canjea el token del enlace por sesión | público |
| GET | `/auth/google/redirect` | Devuelve la URL de autorización de Google (Socialite) | público |
| POST | `/auth/google/callback` | Recibe el `code`/`id_token` de Google, crea o vincula el `user` por `google_id`/email y devuelve token de sesión | público |
| POST | `/auth/logout` | Revoca el token actual | autenticado |
| GET | `/auth/me` | Perfil del usuario autenticado + rol/permisos | autenticado |
| POST | `/auth/password/forgot` | Solicita reset de contraseña | público |
| POST | `/auth/password/reset` | Aplica nueva contraseña | público |

---

## 1. Clientes, Vehículos y Cuentas (Módulo 1)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/clientes` | Listado con búsqueda/filtro (nombre, CI, teléfono) | super_admin, cajero |
| POST | `/clientes` | Alta de cliente | super_admin, cajero |
| GET | `/clientes/{id}` | Detalle de cliente + vehículos | super_admin, cajero |
| PUT | `/clientes/{id}` | Editar datos del cliente | super_admin, cajero |
| DELETE | `/clientes/{id}` | Baja lógica | super_admin |
| POST | `/clientes/{id}/invitar` | Envía enlace de creación de cuenta (WhatsApp) | super_admin, cajero |
| GET | `/clientes/{id}/vehiculos` | Vehículos del cliente | super_admin, cajero, cliente (propio) |
| POST | `/clientes/{id}/vehiculos` | Registrar vehículo (garaje digital) | super_admin, cajero |
| GET | `/vehiculos/{id}` | Detalle de vehículo | super_admin, cajero, operador_tecnico (solo si tiene una OT asignada de ese vehículo), cliente (propio) |
| PUT | `/vehiculos/{id}` | Editar vehículo | super_admin, cajero |
| DELETE | `/vehiculos/{id}` | Baja lógica | super_admin |
| GET | `/vehiculos/{id}/historial` | Historial clínico automotriz (todas las OT) | super_admin, cajero, operador_tecnico (con OT asignada de ese vehículo), cliente (propio) |
| GET | `/vehiculos/{id}/historial/pdf` | Descarga historial clínico en PDF | cliente (propio), super_admin, cajero |
| GET | `/me/vehiculos` | "Mi garaje" — vehículos del cliente autenticado | cliente |
| GET | `/vehiculos/qr/{token}` | Historial público resuelto por el QR pegado en el auto (sin montos ni datos del cliente) | público (por `qr_token`, no requiere sesión) |

---

## 2. Órdenes de Trabajo y Kanban (Módulo 2)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/ordenes-trabajo` | Listado/kanban (filtro por `estado`, `tecnico_asignado_id`, fecha). Cada ítem trae `inspeccion_exists` (boolean) para saber si mostrar el acceso a la inspección sin pedir el detalle completo. El técnico recibe **solo sus OTs asignadas** | super_admin, cajero, operador_tecnico (solo las suyas) |
| POST | `/ordenes-trabajo` | Crear OT (recepción de vehículo) | super_admin, cajero |
| GET | `/ordenes-trabajo/{id}` | Detalle completo de la OT | super_admin, cajero, operador_tecnico (asignada a él), cliente (propia) |
| PUT | `/ordenes-trabajo/{id}` | Editar datos generales de la OT | super_admin, cajero |
| PATCH | `/ordenes-trabajo/{id}/estado` | Cambiar estado (drag&drop del Kanban), dispara notificación WhatsApp | super_admin, cajero, operador_tecnico (asignada a él) |
| POST | `/ordenes-trabajo/{id}/asignar-tecnico` | Asignar/reasignar mecánico | super_admin, cajero |
| GET | `/ordenes-trabajo/{id}/historial-estados` | Timeline de cambios de estado | super_admin, cajero, operador_tecnico (asignada a él), cliente (propia) |
| GET | `/ordenes-trabajo/{id}/pdf` | Genera PDF de la orden de trabajo | super_admin, cajero |
| GET | `/ordenes-trabajo/mias` | OTs asignadas al técnico autenticado | operador_tecnico |
| GET | `/me/ordenes-trabajo` | OTs del cliente autenticado (para portal/línea de tiempo) | cliente |

### Inspección digital

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| POST | `/ordenes-trabajo/{id}/inspeccion` | Crear/registrar inspección de recepción. Solo mientras la OT está en estado `recepcionado` (422 en cualquier otro estado) | super_admin, cajero, operador_tecnico (asignada a él) |
| PUT | `/ordenes-trabajo/{id}/inspeccion` | Editar inspección. Mismo límite: solo en `recepcionado` | super_admin, cajero, operador_tecnico (asignada a él) |
| POST | `/ordenes-trabajo/{id}/inspeccion/firma` | Sube firma digital del cliente (imagen base64/canvas). Mismo límite: solo en `recepcionado` — fuera de eso, la firma ya guardada es de solo lectura | super_admin, cajero, operador_tecnico (asignada a él) |

### Evidencias (fotos/video, incluye flujo offline)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/ordenes-trabajo/{id}/evidencias` | Listado de evidencias | super_admin, cajero, operador_tecnico (asignada a él), cliente (propia) |
| POST | `/ordenes-trabajo/{id}/evidencias` | Subir evidencia; body incluye `uuid_cliente` para idempotencia | super_admin, cajero, operador_tecnico (asignada a él) |
| POST | `/evidencias/sync-batch` | Sincroniza un lote de evidencias tomadas offline (upsert por `uuid_cliente`). Las evidencias de OTs que el técnico no tiene asignadas se rechazan por ítem, igual que las de OTs cerradas | operador_tecnico, super_admin |
| DELETE | `/evidencias/{id}` | Elimina evidencia | super_admin |

---

## 3. Presupuestos y Aprobaciones (Módulo 2 y 5)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/ordenes-trabajo/{id}/presupuestos` | Listado de versiones de presupuesto | super_admin, cajero, operador_tecnico (asignada a él), cliente (propio) |
| POST | `/ordenes-trabajo/{id}/presupuestos` | Crear presupuesto (con items) | super_admin, cajero, operador_tecnico (asignada a él) |
| GET | `/presupuestos/{id}` | Detalle con items | super_admin, cajero, operador_tecnico (OT asignada a él), cliente (propio) |
| PUT | `/presupuestos/{id}` | Editar mientras está en `borrador` | super_admin, cajero, operador_tecnico (OT asignada a él) |
| POST | `/presupuestos/{id}/enviar` | Marca como `enviado`, mueve la OT a `esperando_aprobacion` (automático) y notifica al cliente (WhatsApp) | super_admin, cajero, operador_tecnico (OT asignada a él) |
| GET | `/presupuestos/{id}/pdf` | Descarga presupuesto en PDF | super_admin, cajero, operador_tecnico (OT asignada a él), cliente (propio) |
| POST | `/presupuestos/{id}/items/{item_id}/responder` | Cliente aprueba/rechaza un ítem — cualquier ítem del presupuesto, no solo adicionales | cliente (propio), super_admin |
| POST | `/presupuestos/{id}/responder` | Cliente confirma el presupuesto: aprueba lo que quedó pendiente y mueve la OT a `en_reparacion` (automático), o lo rechaza completo | cliente (propio), super_admin |
| POST | `/ordenes-trabajo/{id}/adicionales` | Técnico reporta un hallazgo/costo adicional durante diagnóstico (acepta `producto_id` opcional si es repuesto). Mueve la OT a `esperando_aprobacion` y notifica al cliente por WhatsApp (mismo mecanismo que `PATCH .../estado`) | operador_tecnico (asignada a él), cajero, super_admin |

---

## 4. Finanzas (Módulo 3)

### Pagos / Ingresos

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/pagos` | Listado de pagos (filtro por fecha, cliente, OT, método) | super_admin, cajero |
| POST | `/pagos` | Registrar pago (anticipo/parcial/completo/abono). En efectivo, exige turno de caja abierto del cajero (422 si no hay). Acepta `detalle_tipo` (`resumen`\|`detallado`) y `detalle_servicio` (texto libre) — si es `detallado`, arma `detalle_items` automáticamente desde los ítems aprobados del presupuesto de la OT | cajero, super_admin |
| GET | `/pagos/{id}` | Detalle de pago | super_admin, cajero |
| GET | `/pagos/{id}/recibo` | Descarga recibo/comprobante PDF | super_admin, cajero, cliente (propio) |
| GET | `/clientes/{id}/cuenta` | Estado de cuenta del cliente (deuda, historial de pagos) | super_admin, cajero, cliente (propio) |

### Caja chica

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/caja/actual` | Estado de la caja abierta del cajero autenticado | cajero, super_admin |
| POST | `/caja/apertura` | Abre caja del día con monto inicial | cajero, super_admin |
| POST | `/caja/{id}/cierre` | Cierra caja, registra monto contado y diferencia | cajero, super_admin |
| GET | `/caja/cierres` | Historial de cierres (auditoría) | super_admin |

### Costos, gastos y reportes

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| POST | `/ordenes-trabajo/{id}/costos-directos` | Registrar costo directo (repuesto/mano de obra/tercerizado) imputado a la OT | super_admin, cajero, operador_tecnico (asignada a él) |
| GET | `/ordenes-trabajo/{id}/margen` | Margen neto calculado (ingresos vs. costos directos) de la OT | super_admin |
| GET | `/gastos-egresos` | Listado de gastos (filtro categoría/fecha) | super_admin |
| POST | `/gastos-egresos` | Registrar gasto fijo/variable | super_admin |
| PUT | `/gastos-egresos/{id}` | Editar gasto | super_admin |
| DELETE | `/gastos-egresos/{id}` | Eliminar gasto | super_admin |
| GET | `/reportes/dashboard` | KPIs: ingresos vs egresos, utilidad neta día/mes, OTs activas | super_admin |
| GET | `/reportes/ingresos-egresos` | Serie temporal para gráficos | super_admin |
| GET | `/reportes/rentabilidad-por-ot` | Margen por orden de trabajo en un rango de fechas | super_admin |

### Reparto de utilidades

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/socios` | Listado de socios | super_admin |
| POST | `/socios` | Alta de socio | super_admin |
| GET | `/reglas-reparto` | Reglas vigentes de % por socio | super_admin |
| PUT | `/reglas-reparto` | Actualiza porcentajes (valida que sumen 100%) | super_admin |
| POST | `/reparto-utilidades/generar` | Calcula reparto de un período (`periodo_inicio`, `periodo_fin`) | super_admin |
| GET | `/reparto-utilidades` | Historial de repartos generados | super_admin |
| GET | `/reparto-utilidades/{id}` | Detalle del reparto (monto por socio) | super_admin |

---

## 5. Inventario y Proveedores (Módulo 4)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/productos` | Kardex — listado con stock actual, filtro por stock bajo | super_admin, cajero, operador_tecnico |
| POST | `/productos` | Alta de producto/insumo. Acepta `multipart/form-data` con `imagen` (opcional) para la foto del producto | super_admin, cajero, operador_tecnico |
| GET | `/productos/{id}` | Detalle + movimientos recientes | super_admin, cajero, operador_tecnico |
| PUT | `/productos/{id}` | Editar producto (incl. `stock_minimo`) | super_admin |
| GET | `/productos/{id}/movimientos` | Kardex detallado de movimientos | super_admin |
| POST | `/productos/{id}/ajuste` | Ajuste manual de stock (inventario físico) | super_admin |
| GET | `/productos/alertas-stock` | Productos por debajo de `stock_minimo` | super_admin, cajero |
| GET | `/proveedores` | Listado de proveedores | super_admin, cajero |
| POST | `/proveedores` | Alta de proveedor | super_admin, cajero |
| GET | `/proveedores/{id}` | Detalle + historial de compras | super_admin, cajero |
| PUT | `/proveedores/{id}` | Editar proveedor | super_admin, cajero |
| GET | `/compras` | Listado de compras | super_admin, cajero |
| POST | `/compras` | Registrar compra (genera movimientos de entrada de stock) | super_admin, cajero |
| GET | `/compras/{id}` | Detalle con items | super_admin, cajero |
| GET | `/cuentas-por-pagar` | Listado de cuentas por pagar a proveedores | super_admin |
| POST | `/cuentas-por-pagar/{id}/pagos` | Registrar pago parcial/total a proveedor | super_admin |

---

## 6. Portal e Interacción con el Cliente (Módulo 5)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/me/ordenes-trabajo/{id}/timeline` | Línea de tiempo interactiva de la OT | cliente (propia) |
| GET | `/me/notificaciones` | Notificaciones recibidas en la app (in-app, espejo de WhatsApp) | cliente |
| PATCH | `/me/notificaciones/{id}/leida` | Marca notificación como leída | cliente |
| GET | `/me/perfil` | Datos de la cuenta del cliente | cliente |
| PUT | `/me/perfil` | Editar datos de contacto | cliente |

---

## 7. Notificaciones e integraciones (transversal)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/notificaciones` | Log de notificaciones enviadas (auditoría) | super_admin |
| POST | `/notificaciones/{id}/reintentar` | Reintenta un envío fallido | super_admin |
| POST | `/webhooks/whatsapp` | Webhook entrante de WhatsApp Cloud API (respuestas, estado de entrega) | público (validado por firma de Meta) |
| GET | `/documentos/{id}` | Descarga un documento generado (presupuesto/recibo/OT/historial) | según pertenencia |

---

## 8. Administración (Super Admin)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/usuarios` | Listado de usuarios internos (cajeros, técnicos) | super_admin |
| POST | `/usuarios` | Crear usuario interno + rol | super_admin |
| PUT | `/usuarios/{id}` | Editar usuario/rol/permisos | super_admin |
| PATCH | `/usuarios/{id}/activar` \| `/desactivar` | Activa/desactiva acceso | super_admin |
| GET | `/roles` | Listado de roles y permisos (spatie/permission) | super_admin |
| PUT | `/roles/{id}/permisos` | Ajusta permisos finos de un rol | super_admin |

---

## Notas de diseño de la API

- **Idempotencia offline:** todo endpoint que reciba datos capturados en campo por el
  mecánico (evidencias, cambios de estado) acepta un identificador generado en el cliente para
  poder reintentar sin duplicar al sincronizar.
- **Eventos en tiempo real:** `PATCH /ordenes-trabajo/{id}/estado` y la creación de
  notificaciones disparan eventos por Laravel Reverb (`OrdenTrabajoActualizada`,
  `NotificacionCreada`) para que el panel Kanban y el portal del cliente se actualicen sin
  polling.
- **Rate limiting:** aplicar throttle más estricto en `/auth/*` y `/webhooks/whatsapp`.
- **Versionado:** prefijo `/api/v1` desde el día uno para poder introducir cambios que rompan
  compatibilidad sin afectar la PWA en producción durante actualizaciones.

### Servicios frecuentes (2026-11-24)

| Método | Ruta | Rol | Descripción |
|---|---|---|---|
| GET | `/api/v1/servicios-frecuentes` | cajero, operador_tecnico (y super_admin) | Servicios activos, ordenados por `orden` y `nombre`: `{ data: [{ id, nombre, tipo, descripcion, precio_unitario }] }`. Alimenta los botones de «Agregar ítem». |

### WhatsApp manual (2026-11-24)

- Con `WHATSAPP_MODO=manual` los endpoints que antes mandaban un mensaje (`POST /clientes/{id}/invitar`, `POST /auth/login/whatsapp-link`, cambios de estado de una OT, envío de presupuesto) **dejan el mensaje en `notificaciones` con estado `por_enviar`** y responden igual. El login por enlace (`whatsapp-link`) avisa al cajero/super_admin por la campanita y responde «Avisamos al taller: te van a mandar tu enlace por WhatsApp…».
- Web (sesión del panel, solo super_admin y cajero): `GET /mensajes/{notificacion}/abrir` marca el mensaje como enviado y redirige a `https://wa.me/<número>?text=<mensaje codificado>` (si el texto pasa de ~1.500 caracteres abre el chat sin texto; hay que copiarlo).
