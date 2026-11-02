# Arquitectura — tele-proxy-admin

## Vista general

Panel de administración PHP Nette server-rendered que actúa como BFF (Backend For Frontend).
El navegador habla SOLO con Nette, y Nette reenvía server-side al API de control de tele-proxy.

## Dominios

| Dominio | Servicio | Rol |
| --- | --- | --- |
| `teleproxy.velone.ai` | tele-proxy (proxy) | Proxy público `/aq/{crypt_id}/` |
| `apiteleproxy.velone.ai` | tele-proxy (control) | API de control y administración |
| `admteleproxy.velone.ai` | tele-proxy-admin | UI de administración (Nette BFF) |

## Flujo de datos

```
┌─────────────┐   HTTP (HTML/AJAX)   ┌──────────────┐   HTTP (JSON)   ┌──────────┐
│  Navegador  │ ◄──────────────────► │ Nette (BFF)  │ ◄─────────────► │  API     │
│ (Chrome)    │  Cookie __Host-      │ + FrankenPHP │  X-Admin-UI: 1  │ tele-    │
│ HTML/CSS/JS │  admin_ui            │ + Caddy      │  Cookie API     │ proxy    │
│ propios     │                      │              │                 │          │
└─────────────┘                      └──────┬───────┘                 └──────────┘
                                            │
                                            ▼
                                     ┌──────────────┐    ┌──────────┐
                                     │   CouchDB    │    │  Valkey  │
                                     │ (login, auth,│    │ (cache,  │
                                     │  logs)       │    │  rate)   │
                                     └──────────────┘    └──────────┘
```

**No hay CORS**: el navegador nunca habla directo con `apiteleproxy.velone.ai`.
Nette añade `X-Admin-UI` server-side.

## Decisiones arquitectónicas

| ID | Decisión | Racional |
| --- | --- | --- |
| A1 | UI es app Nette server-rendered | Simplicidad, SEO, seguridad (sin CORS) |
| A2 | CouchDB solo para escenarios que el API no cubre | Login, auth, logs. El API es fuente de verdad para clientes/admins |
| A3 | Sesión propia de Nette | Cookie `__Host-teleproxy_admin_ui`, independiente del API |
| A4 | Nette es BFF que reenvía al API server-side | Sin CORS, headers de seguridad centralizados, rate limiting propio |

## Arquitectura hexagonal + Vertical Slicing

```
src/
├── ClientManagement/              # Slice: Clientes
│   ├── Domain/
│   │   ├── Client.php             # Entidad
│   │   ├── ClientRepository.php   # Puerto
│   │   └── ValueObjects/
│   │       ├── CryptId.php
│   │       ├── RateLimit.php
│   │       └── Whitelist.php
│   ├── Application/
│   │   ├── CreateClient/
│   │   │   └── CreateClientHandler.php
│   │   ├── UpdateClient/
│   │   ├── DeleteClient/
│   │   ├── RotateToken/
│   │   ├── RotateCryptId/
│   │   ├── CloneClient/
│   │   └── ExportImportClient/
│   ├── Infrastructure/
│   │   └── ApiClientRepository.php  # Adaptador → API
│   └── UI/
│       ├── ClientPresenter.php
│       └── templates/
├── AdminManagement/               # Slice: Administradores
│   ├── Domain/
│   │   ├── AdminUser.php
│   │   └── AdminRepository.php
│   ├── Application/
│   │   ├── ListAdmins/
│   │   ├── AddAdmin/
│   │   ├── ToggleAdmin/
│   │   └── DeleteAdmin/
│   ├── Infrastructure/
│   │   └── ApiAdminRepository.php
│   └── UI/
│       ├── AdminPresenter.php
│       └── templates/
├── SettingsManagement/            # Slice: Ajustes globales
│   ├── Domain/Settings.php        # + SettingsRepository
│   ├── Application/               # GetSettings, UpdateSettings
│   ├── Infrastructure/ApiSettingsRepository.php
│   └── UI/SettingsPresenter.php   # + templates/
├── Authentication/                # Slice: Login Google, sesión
│   ├── Domain/Session.php
│   ├── Application/               # LoginWithGoogle, Logout, EnforceSingleSession
│   ├── Infrastructure/
│   │   ├── CouchDbSessionRepository.php
│   │   ├── ValkeySingleSessionStore.php
│   │   └── ApiAuthAdapter.php
│   └── UI/
│       ├── LoginPresenter.php
│       └── templates/
└── Shared/
    ├── Infrastructure/
    │   ├── ApiClient.php          # HTTP client → API de control
    │   ├── CouchDbConnection.php
    │   ├── ValkeyConnection.php
    │   └── RateLimiter.php
    └── UI/
        └── BasePresenter.php
```

## Principios

- **SOLID**: Puertos/adaptadores. Domain no conoce HTTP ni CouchDB.
- **SLAP**: Capas con nivel de abstracción consistente.
- **KISS**: Handlers de un método (`__invoke`).
- **DRY**: ApiClient, RateLimiter, BandwidthLimiter compartidos.

## Seguridad

### Headers (Caddy)

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: no-referrer
Permissions-Policy: camera=(), microphone=(), geolocation=()
Content-Security-Policy: default-src 'self'; base-uri 'none'; form-action 'self';
    frame-ancestors 'none'; img-src 'self' data:; style-src 'self';
    script-src 'self'; object-src 'none'
```

### Cookies

- Sesión Nette: `__Host-teleproxy_admin_ui`
  - Secure, HttpOnly, SameSite=Lax, Path=/, sin Domain
  - TTL: 15 min de inactividad, renovable

### Sesión y tokens

- Login exclusivamente con Google OAuth; sin formularios de token en el navegador
- Cookie del API: cifrada AES-256-GCM en la sesión del panel, nunca expuesta al navegador
- Tokens de cliente: handle aleatorio en Valkey consumido con GETDEL; se muestran una sola vez
- Rate limiting del login: `LOGIN_RATE_LIMIT_REQUESTS` por `LOGIN_RATE_LIMIT_WINDOW_SECONDS`

### Single session

- Valkey almacena `session:{email}` = `{sessionId, ip, userAgent, createdAt}`
- Nuevo login invalida sesión anterior
- Previene conflictos de edición concurrente

## Rate limiting (Valkey)

| Operación | Límite | Ventana |
| --- | --- | --- |
| Login (por IP) | `LOGIN_RATE_LIMIT_REQUESTS` (5) | `LOGIN_RATE_LIMIT_WINDOW_SECONDS` (60 s) |

Las mutaciones de clientes, administradores y ajustes se limitan en el API de control.

## Despliegue

- **Docker**: FrankenPHP + Caddy en un solo contenedor
- **Healthcheck**: `GET /_ok` → 200 `{"status":"ok","timestamp":"<UTC>"}`
- **Dokploy**: Build desde Dockerfile, zero-downtime, rollback automático
- **Cloudflare**: Full (Strict) SSL, Authenticated Origin Pulls, DNS proxied

## Testing

- **TDD**: Nette Tester (Value Objects, Handlers, ApiClient, RateLimiter)
- **BDD**: Behat (escenarios de autenticación, RBAC, CRUD, export/import)
- **Cobertura**: 80% mínimo en dominio y aplicación
