# Plan de despliegue al droplet de DigitalOcean (para pruebas)

Objetivo: dejar el sistema corriendo en el droplet ya contratado (São Paulo, ver
`01-ARQUITECTURA.md`) para que el equipo lo pruebe desde afuera de esta máquina — no es
todavía el despliegue de producción final descrito en `06-CHECKLIST-DESPLIEGUE.md` (ese
sigue pendiente: credenciales reales de Google/Meta/Spaces, dominio, TLS, supervisores de
proceso). Aquí el criterio de éxito es "se puede entrar por una URL y probar el flujo
completo", con las integraciones externas en modo log/local igual que en esta máquina.

## ⚠️ 0. Este NO es un droplet vacío — leer antes de todo

El droplet ya aloja otros proyectos en producción: uno legado en **PHP 7.4** y otro más
reciente en **PHP 8.3** (mismo binario `php8.3` que usa este proyecto). Eso cambia las
reglas del juego frente a un despliegue en servidor limpio:

- **Nunca** `apt upgrade -y` a secas — puede subir de versión paquetes compartidos (PHP,
  Nginx, librerías del sistema) de los que dependen los otros dos proyectos. Solo
  `apt update` (refresca el índice) + `apt install <paquete-puntual>`.
- **Nunca** tocar `/etc/nginx/sites-available/` ni `/etc/nginx/sites-enabled/` de los sitios
  existentes. Este proyecto suma un **archivo nuevo**, no edita los que ya están.
- **Nunca** reemplazar el pool de PHP-FPM que ya usa el otro proyecto en PHP 8.3
  (probablemente `www`, el que viene por defecto). Este proyecto usa **su propio pool**,
  con su propio socket — así un `php artisan` mal ejecutado o un límite de memoria de este
  proyecto no le pega al otro.
- Instalar una extensión nueva de PHP 8.3 (`php8.3-pgsql`, `php8.3-redis`) sí reinicia
  `php8.3-fpm` — eso es inevitable y va a interrumpir por un instante al otro sitio en PHP
  8.3 también. Avisale al dueño de ese proyecto y hacelo en una ventana de bajo tráfico.
- **PostgreSQL** no está instalado — se instala nuevo, no colisiona con nada. Igual: que
  escuche solo en `localhost` (`listen_addresses = 'localhost'`, ya es el default).
- Si **Redis** ya existe (usado por el otro proyecto), no lo reinstales — solo usá un
  `REDIS_DB` distinto (ej. `1` en vez de `0`) para que las claves de este proyecto no se
  mezclen con las del otro. Confirmar con el paso 1 si ya está.

## 1. Auditoría antes de tocar nada (todo de solo lectura)

Corré esto primero y guardá el resultado — define qué falta instalar y qué nombres/puertos
ya están ocupados (para no chocar con el otro proyecto en PHP 8.3):

```bash
# Qué versiones de PHP hay y qué extensiones tiene cada una
php7.4 -v; php8.3 -v
php8.3 -m | grep -iE "pgsql|redis|mbstring|curl|zip|bcmath|gd|xml"

# Sitios y dominios ya configurados en Nginx (no pisar ningún server_name)
ls /etc/nginx/sites-enabled/
nginx -T 2>/dev/null | grep -E "server_name|listen"

# Pools de PHP-FPM existentes (para no reusar el mismo socket/usuario)
ls /etc/php/8.3/fpm/pool.d/ /etc/php/7.4/fpm/pool.d/ 2>/dev/null

# Servicios corriendo: confirma si ya hay Postgres/Redis/Supervisor
systemctl list-units --type=service --state=running | grep -E "php|nginx|postgres|redis|supervisor"

# Recursos disponibles — Postgres + Redis + un tercer pool de PHP-FPM suman memoria
free -h
df -h /

# Puertos ya escuchando (5432/6379 no deberían estar tomados si Postgres/Redis son nuevos)
ss -tlnp
```

Con esto confirmás: si `php8.3 -m` ya trae `pgsql`/`redis` (no hace falta instalar), si hay
Redis corriendo (usar otro `REDIS_DB`), y qué `server_name` usa el otro sitio en PHP 8.3
(para no repetirlo en el nuevo server block).

## 2. Instalar solo lo que falta

```bash
apt update    # NUNCA "apt upgrade"

# Postgres — nuevo, no existe todavía
apt install -y postgresql postgresql-contrib

# Extensiones de PHP 8.3 que falten (saltear las que ya salieron en el paso 1)
apt install -y php8.3-pgsql php8.3-redis

# Redis — solo si el paso 1 confirmó que no existe
apt install -y redis-server
systemctl enable --now redis-server
```

Composer y Node: si el otro proyecto en PHP 8.3 ya es Laravel, probablemente Composer ya
está (`composer -V`). Node para el build del frontend, si no está:
```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
source ~/.bashrc
nvm install 24
```

## 3. Base de datos — aislada, sin tocar el superusuario

```bash
sudo -u postgres psql -c "CREATE USER doctormotor WITH PASSWORD '<una-contraseña-fuerte-nueva>';"
sudo -u postgres psql -c "CREATE DATABASE doctor_motor OWNER doctormotor;"
```
Un usuario propio (no `postgres`) — si en el futuro se agrega otro proyecto con Postgres en
este mismo droplet, cada uno tiene su rol y no comparten superusuario. No reutilices la
contraseña local (`rodri.go`).

## 4. Pool de PHP-FPM dedicado (no tocar el pool del otro proyecto)

```ini
; /etc/php/8.3/fpm/pool.d/doctormotor.conf
[doctormotor]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm-doctormotor.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```
```bash
php-fpm8.3 -t                    # valida sintaxis antes de reiniciar nada
systemctl reload php8.3-fpm      # reload, no restart — agrega el pool nuevo sin tirar el existente
```
Un pool separado con su propio socket (`php8.3-fpm-doctormotor.sock`) — el proyecto en PHP
8.3 que ya vive en el droplet sigue usando el suyo (probablemente `www.sock`) sin enterarse.

## ✅ Ya desplegado una vez — estado real (2026-09-04)

Este plan ya se ejecutó contra el droplet real (`doctormotor.reactive-x.com`, HTTPS con
Let's Encrypt). Lo que sigue abajo quedó corregido con lo que realmente funcionó — si vas a
repetir esto en otro droplet, usá estos pasos, no una versión anterior de este documento.

**Gaps que aparecieron solo al probar de verdad (no eran visibles antes de desplegar):**
- El seeder depende de `fake()` (Faker, paquete de **desarrollo**) para crear el
  `super_admin` — con `composer install --no-dev` el seed falla. Por ahora se instala
  **sin** `--no-dev` en este droplet de pruebas; antes de producción final conviene separar
  esa creación de usuario del factory para no depender de Faker en producción.
- **Node.js no estaba instalado** en el droplet — se instaló Node 22 LTS vía NodeSource
  (`deb.nodesource.com/setup_22.x`).
- **El backend también tiene su propio `npm run build`** (para el tema de Filament vía
  Vite — `resources/css/filament/admin/theme.css`) — es fácil olvidarlo porque el frontend
  tiene el suyo aparte. Sin este build, `/admin` tira 500 ("Vite manifest not found").
  Hace falta `npm install && npm run build` **en `backend/` también**, no solo en
  `frontend/`.
- **Reverb por defecto escucha en el puerto 8080** — si Nginx también usa 8080 (como en
  este manual), chocan. Se fijó `REVERB_SERVER_PORT=8081` en el `.env` del backend.
- El patrón de Nginx original de este documento (alias + rewrites bajo `/api` y `/admin`
  compartiendo dominio con el frontend) tenía un bug real de `try_files` que rompía tanto
  el panel de Filament como las rutas de React Router. La sección 7 de abajo ya tiene la
  versión corregida y probada — no volver al patrón de `alias`.

## 5. Clonar y configurar el backend

El repo ya está en GitHub: `https://github.com/twstd27/doctormotor` (con el commit inicial
subido desde la máquina local).

```bash
mkdir -p /var/www/doctormotor
chown -R www-data:www-data /var/www/doctormotor
cd /var/www/doctormotor
sudo -u www-data git clone https://github.com/twstd27/doctormotor.git .
cd backend
sudo -u www-data composer install --optimize-autoloader --no-interaction   # SIN --no-dev: el seeder usa fake() (Faker)
sudo -u www-data cp .env.example .env
```
Clonar y correr Composer/npm como `www-data` desde el arranque (no como root) evita tener
que hacer `chown -R` después y deja los permisos correctos de una.

Editar `backend/.env`:
```
APP_ENV=local          # dejalo así mientras es solo para pruebas internas; production más adelante
APP_DEBUG=true         # útil para depurar errores durante las pruebas; apagarlo antes de ir a producción real
APP_URL=https://doctormotor.reactive-x.com

DB_HOST=127.0.0.1
DB_DATABASE=doctor_motor
DB_USERNAME=doctormotor
DB_PASSWORD=<la-contraseña-que-pusiste-en-el-paso-3>

REDIS_HOST=127.0.0.1
REDIS_DB=1              # 1 si Redis ya existía para el otro proyecto (que probablemente usa 0); 0 si es nuevo
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REVERB_SERVER_PORT=8081   # el default (8080) choca con el puerto que usa Nginx en el paso 7

# Google/WhatsApp/Spaces: dejalos vacíos por ahora — WhatsApp cae a modo log
# automáticamente y el resto del sistema funciona igual sin esas credenciales.
```

```bash
sudo -u www-data php8.3 artisan key:generate
sudo -u www-data php8.3 artisan migrate --force
sudo -u www-data php8.3 artisan db:seed --force   # crea el super_admin de prueba (yesid@doctormotor.test / secreto123)
sudo -u www-data php8.3 artisan storage:link
```
Usá siempre `php8.3` explícito (como ya venís haciendo con `php8.3 artisan tinker`) — nunca
el `php` pelado, que en un droplet con dos versiones puede resolver a la que no es.

**No te olvides del build de Vite del propio backend** (el tema de Filament — sin esto
`/admin` tira 500 "Vite manifest not found"):
```bash
sudo -u www-data npm install
sudo -u www-data npm run build
```

## 6. Frontend (build de producción, no `npm run dev`)

Si Node no está instalado en el droplet (confirmalo en el paso 1):
```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
```

```bash
cd /var/www/doctormotor/frontend
sudo -u www-data cp .env.example .env
sudo -u www-data sed -i "s|^VITE_API_URL=.*|VITE_API_URL=https://doctormotor.reactive-x.com/api/v1|" .env
sudo -u www-data npm install
sudo -u www-data npm run build
```
Esto genera `frontend/dist/` — es lo que sirve Nginx como estático (no queda corriendo
`npm run dev` en el servidor). Si después cambiás el dominio, hay que volver a correr
`npm run build` — `VITE_API_URL` queda incrustado en el bundle en tiempo de build, no se
lee en runtime.

## 7. Nginx — un server block NUEVO, no tocar los existentes

Laravel es dueño del dominio completo (root apunta a `backend/public`, igual que cualquier
despliegue estándar de Laravel) — así `/admin` y `/api` los resuelve su propio router, sin
`alias` ni reescrituras manuales. Las rutas del lado del cliente de React Router son la
única excepción, y necesitan `try_files` con **un argumento final que no sea el último**
para servir `index.html` sin que Nginx vuelva a evaluar el archivo desde cero contra
Laravel (por eso el `=404` extra al final, aunque nunca se dispare en la práctica):

```nginx
# /etc/nginx/sites-available/doctormotor  (archivo nuevo)
server {
    listen 80;
    listen [::]:80;
    server_name doctormotor.reactive-x.com;   # tu propio subdominio — NO reutilices el
                                                # server_name de otro proyecto del droplet

    root /var/www/doctormotor/backend/public;
    index index.php;

    # PWA: bundle de Vite (JS/CSS con hash)
    location ^~ /assets/ {
        alias /var/www/doctormotor/frontend/dist/assets/;
    }

    # PWA: archivos estáticos sueltos del build (manifest, service worker, iconos)
    location ~ ^/(favicon\.svg|icons\.svg|manifest\.webmanifest|registerSW\.js|sw\.js|workbox-.*\.js)$ {
        root /var/www/doctormotor/frontend/dist;
    }

    # PWA: rutas de cliente de React Router -> siempre index.html, el router decide
    location ~ ^/(login|ordenes-trabajo|garaje|presupuestos|auth)(/.*)?$ {
        root /var/www/doctormotor/frontend/dist;
        try_files $uri /index.html =404;
    }
    location = / {
        root /var/www/doctormotor/frontend/dist;
        try_files /index.html =404;
    }

    # Todo lo demás es Laravel: /api, /admin, /build, /storage, /vendor, /js, /css,
    # /fonts, /livewire-*, etc. — el router de Laravel lo resuelve solo.
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm-doctormotor.sock;   # el pool dedicado del paso 4
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    client_max_body_size 25m;
}
```
```bash
ln -s /etc/nginx/sites-available/doctormotor /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx    # -t primero, SIEMPRE; reload (no restart) para no cortar los otros sitios
```

TLS con Let's Encrypt (certbot ya suele estar instalado si el droplet ya tiene otros
sitios con HTTPS — confirmalo en el paso 1):
```bash
certbot --nginx -d doctormotor.reactive-x.com --agree-tos -m <tu-correo> --redirect
```
Esto edita **solo** `sites-enabled/doctormotor` para agregar el bloque de `listen 443 ssl`
y el redirect de 80→443 — no toca los certificados ni los server blocks de los otros
sitios. Si el dominio está en Cloudflare, tiene que estar en modo **"DNS only"** (nube
gris) mientras se emite el certificado — el proxy naranja de Cloudflare rompe el
challenge HTTP-01 de Certbot.

## 8. Procesos en segundo plano (Reverb + colas) — programas nuevos, no tocan Supervisor existente

Si `supervisor` no está instalado todavía (confirmalo en el paso 1), instalalo — no afecta
a los otros proyectos, solo agrega el servicio:
```bash
apt install -y supervisor
```
```ini
# /etc/supervisor/conf.d/doctormotor-reverb.conf
[program:doctormotor-reverb]
command=php8.3 /var/www/doctormotor/backend/artisan reverb:start
autostart=true
autorestart=true
user=www-data

# /etc/supervisor/conf.d/doctormotor-queue.conf
[program:doctormotor-queue]
command=php8.3 /var/www/doctormotor/backend/artisan queue:work --tries=3
autostart=true
autorestart=true
numprocs=1
user=www-data
```
```bash
supervisorctl reread && supervisorctl update    # solo registra los programas nuevos, no reinicia los que ya corrían
supervisorctl start doctormotor-reverb doctormotor-queue
```

## 9. Verificación (el mismo flujo de humo de `06-CHECKLIST-DESPLIEGUE.md`)

- [x] `https://doctormotor.reactive-x.com` abre el login de la PWA.
- [x] `https://doctormotor.reactive-x.com/admin` abre el login de Filament, y
      `yesid@doctormotor.test` / `secreto123` entra y muestra el dashboard con todos los
      recursos.
- [x] Preflight CORS (`OPTIONS /api/v1/auth/login`) responde 204 y un login real por API
      devuelve el usuario + token.
- [ ] Login → tablero de OTs → abrir una OT → inspección+firma → evidencias → cambiar de
      etapa (confirma que Reverb/colas están vivos) — pendiente de probar desde el
      navegador, no solo por `curl`.
- [ ] Como cliente: Mi garaje → presupuesto → aprobar.
- [x] **Los otros sitios del droplet** (`sublimack.com`, `vida-luz.net`, `reactive-x.com`,
      `la-republica.net`) siguen respondiendo 200 después de cada paso — el chequeo que
      realmente importa en un droplet compartido.
- [ ] Revisar `storage/logs/laravel.log` en el droplet si algo falla.

## 10. Antes de invitar gente externa a probar

- [x] Firewall: el puerto 8080 usado durante las pruebas ya estaba permitido en `ufw`; con
      el dominio funcionando por 80/443 no hace falta dejarlo abierto — considerá cerrarlo
      si ya no se usa (`ufw delete allow 8080`).
- [x] TLS activo vía Certbot (ver paso 7) — se renueva solo (tarea programada por Certbot).
- [ ] `APP_DEBUG=false` antes de compartir la URL fuera del equipo — con `true` cualquiera
      que provoque un error ve el stack trace completo. Sigue en `true` hoy porque estamos
      recién verificando; cambiarlo es el último paso antes de invitar gente de afuera.

## Actualizar el droplet después de un cambio

```bash
cd /var/www/doctormotor && sudo -u www-data git pull
cd backend && sudo -u www-data composer install && sudo -u www-data php8.3 artisan migrate --force
sudo -u www-data npm install && sudo -u www-data npm run build   # solo si cambió el tema de Filament
cd ../frontend && sudo -u www-data npm install && sudo -u www-data npm run build
supervisorctl restart doctormotor-reverb doctormotor-queue
```
