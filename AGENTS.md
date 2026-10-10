# AGENTS.md — tele-proxy-admin

Panel de administración de tele-proxy. Aplicación PHP Nette server-rendered que actúa como BFF
(Backend For Frontend): el navegador habla SOLO con Nette, y Nette reenvía server-side al API de
control de tele-proxy.

## Arquitectura

- **Framework**: Nette 3.2+ (PHP 8.3+)
- **Servidor**: FrankenPHP (modo clásico, sin worker) + Caddy
- **Frontend**: Latte templates + Tabulator + Notiflix + Naja
- **Auth**: CouchDB (sesiones, logs) + Valkey (single session, rate limiting)
- **BFF**: Nette reenvía al API de control (`apiteleproxy.velone.ai`) server-side
- **Dominio UI**: `admteleproxy.velone.ai`

## Estructura (hexagonal + vertical slicing)

```
src/
├── ClientManagement/     # Slice: Clientes
│   ├── Domain/           # Client, ClientRepository (puerto), ValueObjects
│   ├── Application/      # CreateClient, UpdateClient, DeleteClient, RotateToken, etc.
│   ├── Infrastructure/   # ApiClientRepository (adaptador → API)
│   └── UI/               # ClientPresenter + templates
├── AdminManagement/      # Slice: Administradores
├── Authentication/       # Slice: Login, sesión, master token
└── Shared/               # Infraestructura compartida
    ├── Infrastructure/   # ApiClient, CouchDbConnection, ValkeyConnection, RateLimiter
    └── UI/               # BasePresenter
```

## Convenciones

- **Idioma**: Todo en español (UI, mensajes, logs). Código en inglés.
- **Commits**: Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`, `test:`, `refactor:`)
- **Rama principal**: `main` (protegida, requiere PR)
- **PHPStan**: Nivel 8 obligatorio sin errores
- **Nombres**: camelCase PHP, snake_case API (mapeo en adaptadores)
- **SOLID**: Puertos/adaptadores, handlers de un método (`__invoke`)
- **Sin comentarios**: Salvo WHY no-obvio. El código se explica solo.

## Variables de entorno

Ver `docs/ENVIRONMENT.md`. Las críticas:
- `PUBLIC_API_URL` — URL del API de control (sin slash final)
- `COUCHDB_URL` — URL de CouchDB
- `VALKEY_URL` — URL de Valkey (formato Redis)
- `SESSION_SECRET` — Secreto para firmar cookies Nette

## Comandos

```bash
# Instalación
composer install

# Desarrollo
composer run dev          # Arranca servidor de desarrollo

# Build
composer run build        # Build de producción

# Tests
composer run test         # Nette Tester
composer run bdd          # Behat

# Calidad
composer run phpstan      # PHPStan nivel 8
composer run cs-fix       # PHP-CS-Fixer
composer run audit        # composer audit

# Docker
docker compose up --build
```

## Endpoints del API de control (BFF target)

El API de control vive en `apiteleproxy.velone.ai`. Nette reenvía con:
- Header `X-Admin-UI: 1` en todas las peticiones
- Cookie `teleproxy_admin` (sesión del API)
- Las mutaciones requieren `X-Admin-UI` header

Endpoints usados:
- `GET /api/v1/admin/auth/google/url` — URL de login Google
- `GET /api/v1/admin/me` — Identidad del admin
- `GET/POST /api/v1/admin/admins` — CRUD admins
- `GET/POST /api/v1/admin/clients` — CRUD clientes
- `POST /api/v1/admin/clients/{id}/rotate-token` — Rotar token
- `POST /api/v1/admin/clients/{id}/rotate-id` — Rotar crypt_id

## Seguridad

- Cookie sesión: `__Host-teleproxy_admin_ui` (Secure, HttpOnly, SameSite=Lax)
- Headers: CSP, HSTS, X-Frame-Options, nosniff (gestionados por Caddy)
- Master token: cifrado con rumi.js (IndexedDB, AES-256-GCM), NUNCA en localStorage
- Rate limiting: Valkey (3 intentos master token / 5 min)
- Single session: Valkey invalida sesión anterior al hacer login
- RBAC: solo rol `admin` puede acceder a la UI

## Despliegue

- Docker: FrankenPHP + Caddy en un solo contenedor
- Healthcheck: `GET /_ok` → 200
- Dokploy: build desde Dockerfile, zero-downtime
- Cloudflare: Full (Strict) SSL, DNS proxied

## Referencias

- Especificación completa: spec adjunta al proyecto
- API contract: `docs/api_contract.yaml` (en repositorio tele-proxy)
- Diseño UI: Adminator, Tabler, CoreUI (tema oscuro)
