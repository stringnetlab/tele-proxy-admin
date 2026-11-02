# Variables de Entorno — tele-proxy-admin

Lista canónica de variables de entorno del panel de administración.

## Aplicación

| Variable | Descripción | Default | Req |
| --- | --- | --- | --- |
| `APP_ENV` | Entorno de Nette: `production` desactiva Tracy y el modo debug | `production` | No |
| `PUBLIC_API_URL` | URL del API de control de tele-proxy (sin slash final) | — | **Sí** |
| `COUCHDB_URL` | URL de conexión a CouchDB | — | **Sí** |
| `COUCHDB_DATABASE` | Nombre de la base de datos CouchDB | `teleproxy_admin` | No |
| `VALKEY_URL` | URL de conexión a Valkey (formato Redis) | — | **Sí** |
| `SESSION_SECRET` | Secreto para firmar cookies de sesión Nette y cifrar la cookie del API | — | **Sí** |
| `TRUSTED_PROXIES` | IPs/CIDR de proxies de confianza para resolver la IP real | `""` | No |
| `LOGIN_RATE_LIMIT_REQUESTS` | Peticiones máximas por ventana en el login | `5` | No |
| `LOGIN_RATE_LIMIT_WINDOW_SECONDS` | Duración de la ventana del rate limit del login | `60` | No |
| `TZ_DISPLAY` | Zona horaria para mostrar timestamps | `America/Lima` | No |

## Desarrollo local

```env
APP_ENV=development
PUBLIC_API_URL=http://localhost:8785
COUCHDB_URL=http://localhost:5984
COUCHDB_DATABASE=teleproxy_admin
VALKEY_URL=redis://localhost:6379
SESSION_SECRET=cambia-este-secreto-en-produccion
TRUSTED_PROXIES=
LOGIN_RATE_LIMIT_REQUESTS=20
LOGIN_RATE_LIMIT_WINDOW_SECONDS=60
TZ_DISPLAY=America/Lima
```

## Producción

```env
APP_ENV=production
PUBLIC_API_URL=https://apiteleproxy.velone.ai
COUCHDB_URL=http://couchdb:5984
COUCHDB_DATABASE=teleproxy_admin
VALKEY_URL=redis://valkey:6379
SESSION_SECRET=<generar-con-openssl-rand>
TRUSTED_PROXIES=<CIDR-de-cloudflare>
LOGIN_RATE_LIMIT_REQUESTS=5
LOGIN_RATE_LIMIT_WINDOW_SECONDS=60
TZ_DISPLAY=America/Lima
```

## Notas

- **NUNCA** commitear `.env` con valores reales. Usar `.env.example` como plantilla.
- `SESSION_SECRET` debe ser al menos 32 bytes aleatorios. Generar con: `openssl rand -hex 32`
- En producción, las variables se inyectan vía Docker/Dokploy secrets.
- `PUBLIC_API_URL` no debe tener slash final: `https://apiteleproxy.velone.ai` (correcto),
  `https://apiteleproxy.velone.ai/` (incorrecto).
