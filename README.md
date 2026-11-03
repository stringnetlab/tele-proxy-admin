# tele-proxy-admin

Panel de administración del proxy [tele-proxy](https://github.com/stringnetlab/tele-proxy).

Aplicación PHP **Nette 3.2 server-rendered** que actúa como **BFF** (Backend For Frontend): el
navegador habla solo con Nette y Nette reenvía server-side al API de control. La autenticación es
**exclusivamente Google OAuth**: no hay formulario de token ni flujo alternativo en el panel.

- **Sin CORS**: el navegador nunca llama a `apiteleproxy.velone.ai`.
- **Sesión propia** con cookie `__Host-teleproxy_admin_ui` (Secure, HttpOnly, SameSite=Lax).
- **Cookie del API** (`teleproxy_admin`) cifrada AES-256-GCM dentro de la sesión del panel; nunca
  llega al navegador.
- **Tokens de cliente de un solo uso**: handle en Valkey consumido con `GETDEL`, mostrado una sola
  vez y nunca en URL, logs ni sesión.

## Stack

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP 8.3+ |
| Framework | Nette 3.2 (`application`, `forms`, `routing`, `di`) + Latte 3 |
| Servidor | FrankenPHP (modo clásico) + Caddy |
| Cliente HTTP al API | Guzzle 7 |
| Datos | CouchDB 3 (login, auth, logs) |
| Sesión única y rate limit | Valkey 7 (Predis) |
| Frontend | Latte + CSS/JS propios, self-hosted (sin CDN ni build) |
| Calidad | PHPStan nivel 8, PHP-CS-Fixer, Nette Tester |

## Requisitos

- PHP 8.3+ con extensiones `curl`, `mbstring`, `intl` y `openssl`
- Composer 2.x
- CouchDB 3 y Valkey 7 (el compose los levanta)
- Docker para el despliegue

## Instalación

```bash
git clone git@github.com:stringnetlab/tele-proxy-admin.git
cd tele-proxy-admin

composer install
cp .env.example .env
# Editar .env: PUBLIC_API_URL, COUCHDB_URL, VALKEY_URL y SESSION_SECRET
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # valor para SESSION_SECRET
```

No hay paso de build de assets: HTML, CSS y JS se sirven estáticos desde `www/assets/`
(incluido `netteForms` para la validación cliente). `composer run build` solo instala las
dependencias de producción (`--no-dev --optimize-autoloader`).

## Desarrollo

```bash
composer run dev      # servidor PHP en http://localhost:8080
composer run test     # suite unitaria (Nette Tester)
composer run phpstan  # nivel 8
composer run cs-fix   # PHP-CS-Fixer
composer run audit    # composer audit --no-dev
```

## Rutas

| Ruta | Función |
|---|---|
| `/login`, `/login/google`, `/login/google/callback`, `/logout` | Login Google y sesión |
| `/clients` | Listado paginado con total |
| `/clients/create` | Alta de cliente (token mostrado una sola vez) |
| `/clients/<id>` | Detalle, configuración y acciones |
| `/clients/tokens/<handle>` | Entrega única del token desde Valkey |
| `/clients/export`, `/clients/import` | Export/import sin tokens |
| `/admins`, `/admins/create`, `/admins/<email>` | Administradores |
| `/settings` | Ajustes globales |
| `/_ok` | Healthcheck |

## Estructura

Arquitectura hexagonal con vertical slicing:

```
src/
├── ClientManagement/     # Clientes: dominio, casos de uso, adaptador API y UI
├── AdminManagement/      # Administradores
├── SettingsManagement/   # Ajustes globales
├── Authentication/       # Login Google y sesión
└── Shared/               # ApiClient, cifrado de cookie, CouchDB, Valkey
```

Detalle en [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Variables de entorno

| Variable | Obligatoria | Descripción |
|---|---|---|
| `PUBLIC_API_URL` | Sí | API de control, sin slash final |
| `COUCHDB_URL` | Sí | Conexión a CouchDB |
| `COUCHDB_DATABASE` | No | Nombre de la base (`teleproxy_admin`) |
| `VALKEY_URL` | Sí | Valkey en formato Redis |
| `SESSION_SECRET` | Sí | Firma de sesión y cifrado de la cookie del API (≥ 32 bytes) |
| `APP_ENV` | No | `production` por defecto |
| `TRUSTED_PROXIES` | No | IPs/CIDR para resolver la IP real; vacío = no confiar en cabeceras |
| `LOGIN_RATE_LIMIT_REQUESTS` / `LOGIN_RATE_LIMIT_WINDOW_SECONDS` | No | Límite del login (5 / 60 s) |
| `TZ_DISPLAY` | No | Zona horaria de los timestamps (`America/Lima`) |

Lista canónica y ejemplos por entorno: [docs/ENVIRONMENT.md](docs/ENVIRONMENT.md).

## Docker

```bash
cp .env.example .env   # definir SESSION_SECRET, COUCHDB_PASSWORD y VALKEY_PASSWORD
docker compose up --build
```

El contenedor publica `8082:80`, arranca FrankenPHP + Caddy y expone el healthcheck en `/_ok`.

## Seguridad

- **CSP sin CDNs**: `script-src 'self'`, `style-src 'self'`, `object-src 'none'`; HSTS,
  `X-Frame-Options: DENY`, `nosniff` y `Referrer-Policy: no-referrer` en Caddy.
- **Single session** en Valkey: un login nuevo invalida la sesión anterior.
- **Rate limiting** del login por IP real (resuelta con `TRUSTED_PROXIES`).
- **Errores del API tal cual**: 400/403/404/409/429 muestran el `message` del servidor, incluidos
  los 403 de autodesactivación, autoborrado y último administrador.
- **401 del API**: cierra la sesión del panel y redirige a `/login`.

## Testing y calidad

12 tests unitarios de dominio, aplicación e infraestructura con dobles en `tests/Unit/Support/`.
Gate: PHPStan nivel 8 limpio, suite en verde, `composer validate --strict` y `composer audit`
sin vulnerabilidades.

## Despliegue

- **Dokploy**: build desde `Dockerfile` (FrankenPHP + Caddy), zero-downtime.
- **Cloudflare**: Full (Strict) SSL, DNS proxied.
- **Dominio**: `admteleproxy.velone.ai`.
- **Healthcheck**: `GET /_ok` → `{"status":"ok","timestamp":"<ISO-8601>"}`.

Especificación funcional del panel: `docs/SPEC_ADMIN_UI.md` en el repositorio de tele-proxy.

## Licencia

Propietaria. Copyright (c) stringnetlab.
