# Checklist de despliegue a producción (Fase 4)

Las 4 fases del roadmap están completas en su alcance funcional (ver `README.md` para el
detalle probado de cada una). Esto es lo que falta para pasar de "corre en esta máquina" a
"corre en el droplet de producción" — nada de esto se pudo ejecutar en esta sesión porque
requiere credenciales que no están disponibles acá (acceso SSH al droplet, cuentas de Meta/Google
reales).

## 1. Credenciales y accesos pendientes

- [ ] SSH al droplet de DigitalOcean (región São Paulo, ya contratado — ver
      `01-ARQUITECTURA.md`).
- [ ] Credenciales reales de **Google Cloud** (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`) — el
      login con Google ya está implementado (`AuthController::googleCallback`), solo falta esto
      para probarlo de verdad.
- [ ] Credenciales reales de **Meta / WhatsApp Business Cloud API** (`WHATSAPP_TOKEN`,
      `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_VERIFY_TOKEN`) — `WhatsAppService` ya está
      implementado y cae a modo log automáticamente sin esto, así que el sistema funciona
      igual mientras se consigue.
- [ ] Credenciales de **DigitalOcean Spaces** (o el bucket S3-compatible que se use) para
      fotos/video de evidencias — hoy usan el disco local (`FILESYSTEM_DISK=local`).
- [ ] Modelo real de impresora térmica del taller, para decidir si conviene integrar ESC/POS de
      verdad más adelante (hoy: PDF formato ticket de 80mm como alternativa que sí funciona sin
      hardware específico).

## 2. Pasos de despliegue (cuando haya acceso al droplet)

> El droplet **no está vacío** — ya aloja otro proyecto en PHP 7.4 y otro en PHP 8.3. Ver
> `07-DESPLIEGUE-PRUEBAS.md` §0 para las reglas de convivencia (nunca `apt upgrade` a secas,
> pool de PHP-FPM y server block de Nginx propios, no tocar lo que ya corre ahí) antes de
> seguir esta lista.

- [ ] PHP 8.3 y Composer ya están en el droplet (se usan para el otro proyecto reciente) —
      solo falta agregar las extensiones que falten (`php8.3-pgsql`, `php8.3-redis`) y
      Node 24+, PostgreSQL (nuevo) y Redis (si no existe ya) — ver el detalle en
      `07-DESPLIEGUE-PRUEBAS.md`.
- [ ] Clonar el repo, `composer install --no-dev --optimize-autoloader`.
- [ ] Configurar `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, credenciales
      reales de DB/Redis/Google/WhatsApp/Spaces.
- [ ] `php artisan migrate --force` (las 25 tablas + permission tables, mismas migraciones
      probadas en local).
- [ ] `php artisan storage:link`.
- [ ] Build de producción del frontend: `npm run build` en `frontend/`, servir `dist/` desde
      Nginx (o el mismo droplet vía un segundo proceso).
- [ ] Configurar Nginx: proxy a `php-fpm` para `backend/public`, servir estáticos del frontend,
      certificado TLS (Let's Encrypt).
- [ ] Levantar `php artisan reverb:start` como servicio persistente (systemd) para el
      WebSocket del Kanban en tiempo real.
- [ ] Configurar un worker de colas persistente (`php artisan queue:work --daemon`, vía
      systemd o Supervisor) — hoy las notificaciones de WhatsApp y otros jobs corren
      sincrónicamente en local, en producción deberían ir por Redis + worker.
- [ ] Actualizar `GOOGLE_REDIRECT_URI` y el dominio configurado en Google Cloud / Meta Business
      Manager para que apunten al dominio real, no a `localhost`.
- [ ] Configurar el webhook de WhatsApp (`/api/v1/webhooks/whatsapp`) en Meta Business Manager
      apuntando al dominio real, con el mismo `WHATSAPP_VERIFY_TOKEN` del `.env`.

## 3. Antes de dar por cerrada la fase

- [ ] Correr el mismo flujo de humo probado en local (login → cliente → vehículo → OT → Kanban
      → inspección+firma → presupuesto → aprobación → pago → cierre de caja → reparto de
      utilidades → compra → stock) contra producción.
- [ ] Confirmar que WhatsApp manda mensajes reales (no solo log) con al menos un número de
      prueba.
- [ ] Capacitación al equipo del taller — qué necesita saber cada rol el primer día:
  - **Super Admin**: panel `/admin` completo, reparto de utilidades, reportes.
  - **Cajero**: apertura/cierre de caja, cobros, invitar clientes.
  - **Operador técnico**: tablero de OTs en el celular, inspección+firma, fotos/video
    (funciona sin señal y sincroniza solo), cambio de estado.
  - **Cliente**: cómo se ve su garaje, cómo aprueba un presupuesto, cómo descarga su historial.
