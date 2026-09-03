# Sistema de Gestión Integral Automotriz — Arquitectura, Stack y Estimación

> Basado en el PRD `sistema taller automotriz.pdf` (Horse Garage / Mustang Garage).
> Contexto de estimación: desarrollo por **1 freelancer/dev independiente**, mercado boliviano.

---

## 1. Resumen del alcance

El PRD pide un ERP/CRM/Portal de clientes con 4 roles (Super Admin, Cajero, Operador Técnico,
Cliente Final) y 5 módulos: Clientes/Vehículos, Órdenes de Trabajo (Kanban), Finanzas,
Inventario/Proveedores, y Portal del Cliente. Además exige WhatsApp, PDF/impresión térmica,
funcionamiento offline para mecánicos, y multiplataforma (web + móvil).

Es, en esencia, **un ERP interno (back-office) + un portal de cliente + una app de campo
offline-first**, no tres apps independientes. Esa lectura es la que guía las decisiones de
abajo: se prioriza un solo backend y el mínimo número de codebases de frontend que cubran los
tres tipos de experiencia sin triplicar el trabajo de un solo desarrollador.

---

## 2. Arquitectura recomendada

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTES (Frontends)                      │
│                                                                   │
│  ┌───────────────────────┐   ┌─────────────────────────────┐    │
│  │  Panel Admin/Cajero    │   │   PWA Taller + Cliente        │  │
│  │  (Filament / web)      │   │   (React 19 + Vite, instalable)│ │
│  │  Super Admin, Cajero   │   │   Mecánicos (offline) +       │  │
│  │  gestión, reportes     │   │   Portal Cliente (garaje,     │  │
│  │                        │   │   línea de tiempo, aprobación)│  │
│  └───────────┬────────────┘   └───────────────┬───────────────┘ │
└──────────────┼─────────────────────────────────┼─────────────────┘
               │   HTTPS / REST JSON (Sanctum)    │  + WebSocket (Reverb)
               ▼                                  ▼
        ┌────────────────────────────────────────────────┐
        │              API REST — Laravel 13               │
        │  Auth (Sanctum) · Roles (spatie/permission)       │
        │  Módulos: Clientes · OT/Kanban · Finanzas ·       │
        │  Inventario · Notificaciones · Reportes           │
        │  Jobs en cola: WhatsApp, PDF, cálculo de reparto  │
        └───────┬───────────────┬───────────────┬──────────┘
                │               │               │
                ▼               ▼               ▼
        ┌───────────┐   ┌──────────────┐   ┌─────────────────┐
        │ PostgreSQL │   │ Redis (colas, │   │ Storage S3-comp. │
        │ (datos)    │   │ caché, WS)    │   │ (fotos/videos,   │
        │            │   │               │   │ PDFs)            │
        └───────────┘   └──────────────┘   └─────────────────┘
                                │
                                ▼
                     ┌────────────────────┐
                     │ WhatsApp Cloud API  │
                     │ (Meta) — notifs.    │
                     └────────────────────┘
```

**Decisión clave: un solo backend, dos frontends.**

1. **Panel administrativo (Super Admin / Cajero)** — construido con **Filament**
   (framework de admin panels sobre Laravel). Genera automáticamente CRUDs, tablas con
   filtros, formularios y dashboards a partir de los modelos de Eloquent. Para los módulos
   3 (Finanzas) y 4 (Inventario), que son 80% CRUD + reportes, esto ahorra semanas de
   desarrollo frente a construir un frontend React a mano.
2. **PWA única para Mecánicos + Clientes** — un solo codebase React, con vistas distintas
   según rol. Se justifica un único proyecto porque comparten los mismos requisitos técnicos
   (instalable, cámara, notificaciones push, funcionar en móvil) y así se evita mantener tres
   frontends distintos como un solo desarrollador.

No se recomienda Flutter/React Native nativo: dobla el trabajo de un dev solo (dos
plataformas o un puente adicional) sin beneficio claro, dado que ninguna funcionalidad del
PRD (cámara, GPS, firma, offline) requiere APIs nativas que un PWA moderno no cubra.

### ¿React o Vue para la PWA?

Ambos son perfectamente capaces de cumplir todo lo que pide el PRD (instalable, offline,
cámara, firma digital, sincronización en segundo plano) — no hay una funcionalidad del
proyecto que dependa de uno u otro. La elección es más de preferencia/productividad personal
que técnica. Dado que el desarrollador prefiere React, se adopta **React 19 + Vite** (no
Next.js): al ser una app autenticada sin necesidad de SEO/SSR, Vite da un service worker más
simple de controlar para el modo offline que Next.js, con menos capas encima. Si más adelante
se quisiera routing por archivos o SSR, Next.js seguiría siendo una migración razonable.
El panel Admin (Filament) es independiente de esta elección — corre sobre Blade/Livewire del
lado de Laravel, así que da igual si el resto del proyecto usa React o Vue.

### Alternativa considerada (Node/NestJS + React)

Válida y más "de mercado" si en el futuro se contrata un equipo, pero para un desarrollador
único construyendo un ERP con muchos formularios/tablas, Laravel+Filament reduce
significativamente las horas de los módulos 3 y 4. Se documenta como alternativa por si el
cliente ya tiene preferencia de stack o se prevé escalar el equipo pronto.

---

## 3. Stack propuesto

| Capa | Tecnología | Por qué |
|---|---|---|
| Backend / API | **Laravel 13 (PHP 8.3)** | Productividad alta para un solo dev: migraciones, colas, scheduler, Eloquent ORM, ecosistema maduro de paquetes (PDF, permisos, WhatsApp). Hosting barato y ampliamente disponible en Bolivia/LatAm. |
| Panel Admin | **Filament 5** | Admin panel "gratis" sobre los modelos Eloquent; cubre gran parte de Módulo 3 y 4 sin frontend a medida. |
| Auth & permisos | **Laravel Sanctum** + **spatie/laravel-permission** | Tokens para API/PWA + roles y permisos granulares (Super Admin, Cajero, Operador, Cliente). |
| Base de datos | **PostgreSQL 16** | Tipos robustos (enum, jsonb para evidencias/metadata), buen soporte de reportes financieros. |
| Colas / caché / websockets | **Redis + Laravel Reverb** | Reverb (self-hosted, gratis) para el tablero Kanban en tiempo real y notificaciones live; Redis para colas (envío WhatsApp, generación de PDF) y caché. |
| Frontend Taller/Cliente | **React 19 + Vite + TypeScript + TanStack Query + Zustand + Tailwind CSS** | PWA instalable con `vite-plugin-pwa` (Workbox/service worker), Dexie.js (IndexedDB) para cola offline, cámara vía `<input capture>` / `getUserMedia`. |
| Firma digital | **signature_pad (JS)** | Captura de firma en canvas, se sube como imagen adjunta a la inspección. |
| Notificaciones WhatsApp | **WhatsApp Business Cloud API (Meta)** | Oficial; se paga por mensaje de plantilla enviado (ver sección 4), pero el costo es bajo para el volumen de un taller. Evita depender de un tercero de pago como Twilio. |
| Login con Google | **Laravel Socialite** (backend) + **Google Identity Services** (botón en React) | Gratis, sin límite de usuarios (ver sección 4). |
| PDF | **barryvdh/laravel-dompdf** (o Browsershot si se necesita más fidelidad) | Presupuestos, recibos, historial clínico automotriz descargable. |
| Impresión térmica | **ESC/POS vía navegador** (librería `escpos` + impresora de red/USB compartida, o QZ Tray) | Tickets de caja sin depender de app nativa. |
| Almacenamiento de archivos | **DigitalOcean Spaces / Backblaze B2 (S3-compatible)** | Fotos/videos de evidencia, más barato que AWS S3 puro. |
| Hosting | **VPS (Hetzner o DigitalOcean) + Cloudflare** | Costo controlado, suficiente para el tamaño de un taller (no requiere arquitectura serverless). |
| CI/CD | **GitHub Actions** | Deploy automático a VPS (o Laravel Forge si se prefiere gestionar menos infraestructura a mano). |

### Notas técnicas críticas del PRD

- **Offline-first (mecánicos):** la PWA guarda fotos/estados en IndexedDB cuando no hay señal
  y sincroniza vía *Background Sync* al reconectar. Se diseña el API para aceptar cargas
  idempotentes (client-generated UUID por evidencia) para evitar duplicados en la sync.
- **Kanban en tiempo real:** cambios de estado de la OT se emiten por Reverb (WebSocket) para
  que el panel admin y el portal del cliente reflejen el estado sin refrescar.
- **Reparto de utilidades entre socios:** se modela como reglas configurables (porcentaje por
  socio) aplicadas sobre la utilidad neta calculada por período — ver `DATABASE.md`, tabla
  `reglas_reparto` y `reparto_utilidades`.

---

## 4. Estimación de precio (BOB)

**Supuesto de tarifa:** Bs 70/hora — tarifa razonable para un freelancer con experiencia en
Bolivia construyendo un sistema de esta complejidad (por encima de tarifa junior, por debajo
de tarifa de agencia). Ajustable: cambiar la tarifa re-escala linealmente todos los totales.

> Esta es una estimación de planeación, no una cotización cerrada. El número final depende de
> alcance exacto, integraciones de terceros (costos de WhatsApp/SMS a volumen), y feedback del
> cliente durante el desarrollo.

### Desglose por fase (entrega incremental recomendada)

| Fase | Contenido | Horas est. | Duración aprox. | Costo (Bs) |
|---|---|---:|---|---:|
| **Fase 0 — Setup** | Repo, infraestructura, CI/CD, auth + roles, esqueleto Filament + PWA | 35 h | 1 semana | 2,450 |
| **Fase 1 — MVP Operativo** | Módulo 1 (Clientes/Vehículos/Cuentas) + Módulo 2 (OT, Kanban, inspección digital, firma, evidencias) + cobros básicos | 150 h | 4–5 semanas | 10,500 |
| **Fase 2 — Financiero e Inventario** | Módulo 3 completo (ingresos, costos directos, gastos, caja, reparto de utilidades, dashboards) + Módulo 4 (Kardex, alertas de stock, proveedores) | 150 h | 4–5 semanas | 10,500 |
| **Fase 3 — Portal Cliente e Integraciones** | Módulo 5 (línea de tiempo, aprobación de adicionales, historial clínico PDF) + WhatsApp API + impresión térmica + offline sync PWA | 110 h | 3–4 semanas | 7,700 |
| **Fase 4 — QA, pulido y despliegue** | Pruebas end-to-end, corrección de bugs, documentación, despliegue a producción, capacitación al taller | 35 h | 1 semana | 2,450 |
| **Total** | | **480 h** | **~13–16 semanas (3–4 meses)** | **Bs 33,600** |

### Costos recurrentes (no incluidos arriba, van aparte)

| Ítem | Costo estimado |
|---|---|
| VPS (Hetzner/DigitalOcean, 2–4GB RAM) | Bs 150 – 300 / mes |
| Almacenamiento S3-compatible (fotos/videos) | Bs 50 – 150 / mes (según volumen) |
| Dominio (.com o .bo) | Bs 100 – 250 / año |
| WhatsApp Cloud API | Ver detalle abajo — estimado real: Bs 100 – 200 / mes para el volumen de un taller |
| Mantenimiento/soporte post-lanzamiento (opcional) | Bs 1,500 – 3,000 / mes (bolsa de horas) |

#### Detalle: cómo cobra Meta el WhatsApp Cloud API (actualizado, no es "1,000 gratis/mes")

Meta cambió el modelo de cobro el **1 de julio de 2025**: pasó de "pricing por conversación"
a **pricing por mensaje individual**. El viejo esquema de "las primeras 1,000 conversaciones
gratis al mes" **ya no existe** en esa forma. Lo que aplica hoy:

- **Gratis, sin límite:** cualquier respuesta dentro de la **ventana de servicio de 24 horas**
  que se abre cuando el *cliente* te escribe primero (mensajes libres y plantillas de tipo
  "utility" dentro de esa ventana).
- **Se cobra por cada mensaje entregado** (no por conversación) cuando el *taller* inicia el
  contacto fuera de esa ventana de 24h, usando una plantilla aprobada:
  - **Marketing** (promociones): la más cara.
  - **Utility** (transaccional — "tu auto ingresó a diagnóstico", "tu auto está listo"): mucho
    más barata que marketing.
  - **Authentication** (códigos OTP): tarifa similar a utility.
- **No hay cuota mensual gratis** para estas plantillas — se cobra desde el mensaje #1.

**Sobre reiniciar contacto con el mismo cliente días después:** cada vez que la ventana de 24h
se cierra (por inactividad) y el taller vuelve a escribir primero, es un **envío nuevo e
independiente, facturado de nuevo** — no se acumula ni se cuenta como "respuesta" de la
conversación anterior. Solo si es el *cliente* quien reabre el contacto escribiendo primero,
esa nueva ventana de 24h vuelve a ser gratis para las respuestas del taller.

**Tarifas aproximadas para Bolivia** (referencia de mercado, no es el rate card oficial de
Meta — verificar en Meta Business Manager antes de presupuestar en firme, ya que Meta ajusta
tarifas por país varias veces al año): Marketing ≈ US$ 0.074/mensaje, Utility/Authentication ≈
US$ 0.011/mensaje. Al tipo de cambio oficial BCB de ~Bs 12/US$ (ago. 2026), eso es
aproximadamente **Bs 0.89 por notificación de marketing** y **Bs 0.14 por notificación
transaccional** (utility/auth).

**Estimado de costo mensual real para el taller:** la mayoría de las notificaciones del PRD
("ingresó a diagnóstico", "listo para entrega") son plantillas *utility* iniciadas por el
taller. Con ~300 OTs/mes y ~3 notificaciones por OT (≈900 mensajes/mes), el costo mensual
rondaría **Bs 100 – 150** — es decir, un rubro menor dentro del presupuesto operativo, aunque
ya no exista una franja gratuita. No se recomienda usar plantillas de *marketing* para las
notificaciones operativas del taller (son 6-7× más caras); basta con plantillas *utility*.

#### Registro/login con cuenta de Google ("Sign in with Google")

El PRD pide autenticación por correo/contraseña o enlace de WhatsApp para clientes; sumar
"iniciar sesión con Google" como tercera opción es una mejora razonable de UX y **no tiene
costo**, con dos matices importantes:

- **Es gratis y sin límite de usuarios** para el caso de uso del taller: solo se piden los
  *scopes* básicos/no sensibles (`openid`, `email`, `profile`) — el mínimo para saber quién es
  el cliente y autocompletar nombre/correo. Google no cobra por esto, sin importar cuántos
  clientes se registren.
- **Hay un paso de verificación (gratis, pero con trámite) para publicar la app fuera de modo
  "Testing":** mientras el proyecto de Google Cloud esté en modo *Testing*, solo pueden iniciar
  sesión ~100 cuentas de Google que agregues manualmente como "test users" — insuficiente para
  producción. Para pasar a "In production" con scopes básicos y mostrar tu logo/nombre en la
  pantalla de consentimiento, Google pide **"brand verification"**: cargar política de
  privacidad, términos de uso, dominio verificado (Google Search Console) y logo. Es un
  trámite, no un pago — tarda ~2–3 días hábiles una vez enviado.
- **Lo que sí cuesta dinero (miles de USD) es un proceso distinto** — la evaluación de
  seguridad ("CASA assessment") que Google exige solo a apps que piden *scopes* **sensibles o
  restringidos** (ej. leer todo el Gmail, acceso amplio a Drive). El taller **no necesita eso**:
  solo pide identidad básica para crear la cuenta, así que ese costo no aplica.

**Implementación:** en el backend, `Laravel Socialite` (paquete gratuito) maneja el
intercambio OAuth con Google y crea/vincula el registro en `users`; en el frontend, el botón
oficial de Google Identity Services (JS, gratis) dispara el flujo. Se guarda `google_id` en
`users` para vincular cuentas que después también podrían loguearse por correo.

### Rango total sugerido para presentar al socio

**Bs 30,000 – 38,000** por el desarrollo completo (Fases 0–4), dependiendo de ajustes de
alcance durante Fase 1. Se recomienda cobrar por fase (no todo al inicio) para dar visibilidad
de avance real al socio inversionista y reducir riesgo para ambas partes.

---

## 5. Riesgos y supuestos a validar con el cliente

- **Impresoras térmicas:** confirmar modelo/marca disponible en el taller (afecta si se usa
  ESC/POS directo, QZ Tray, o driver del fabricante).
- **Volumen de WhatsApp:** no hay franja gratuita para plantillas iniciadas por el taller (ver
  detalle en la sección 4) — es costo variable desde el mensaje 1, aunque bajo para el volumen
  típico de un taller. Confirmar tarifa vigente en Meta Business Manager antes de cerrar
  presupuesto con el cliente, ya que Meta actualiza tarifas por país varias veces al año.
- **Firma digital con validez legal:** el PRD pide firma digital en la recepción; se asume que
  es para evidencia interna (no firma electrónica certificada/legal), lo cual simplifica mucho
  la implementación. Confirmar con el cliente.
- **Multi-taller / multi-sucursal:** el PRD no lo menciona; el modelo de datos se deja simple
  (un solo taller) pero puede extenderse a multi-tenant en el futuro si crecen a más
  sucursales — implicaría cambios de alcance y precio.
