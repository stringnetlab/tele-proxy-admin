# Variables de Entorno — tele-proxy-admin

Lista canónica de variables de entorno del panel de administración.

## Aplicación

| Variable | Descripción | Default | Req |
| --- | --- | --- | --- |
| `PUBLIC_API_URL` | URL del API de control de tele-proxy (sin slash final) | — | **Sí** |
| `COUCHDB_URL` | URL de conexión a CouchDB | — | **Sí** |
| `COUCHDB_DATABASE` | Nombre de la base de datos CouchDB | `teleproxy_admin` | No |
| `VALKEY_URL` | URL de conexión a Valkey (formato Redis) | — | **Sí** |
| `SESSION_SECRET` | Secreto para firmar cookies de sesión Nette | — | **Sí** |
| `LOGIN_BANDWIDTH_LIMIT_KIB` | Ancho de banda máximo en /login y /auth/* (KiB/s) | `64` | No |
| `TZ_DISPLAY` | Zona horaria para mostrar timestamps | `America/Lima` | No |

## Desarrollo local

```env
PUBLIC_API_URL=http://localhost:8785
ADMIN_UI_URL=http://localhost:5173
COUCHDB_URL=http://couchdb:5984
COUCHDB_DATABASE=teleproxy_admin
VALKEY_URL=redis://valkey:6379
SESSION_SECRET=cambia-este-secreto-en-produccion
LOGIN_BANDWIDTH_LIMIT_KIB=64
TZ_DISPLAY=America/Lima
```

## Producción

```env
PUBLIC_API_URL=https://apiteleproxy.velone.ai
COUCHDB_URL=http://couchdb:5984
COUCHDB_DATABASE=teleproxy_admin
VALKEY_URL=redis://valkey:6379
SESSION_SECRET=<generar-con-openssl-rand>
LOGIN_BANDWIDTH_LIMIT_KIB=64
TZ_DISPLAY=America/Lima
```

## Notas

- **NUNCA** commitear `.env` con valores reales. Usar `.env.example` como plantilla.
- `SESSION_SECRET` debe ser al menos 32 bytes aleatorios. Generar con: `openssl rand -hex 32`
- En producción, las variables se inyectan vía Docker/Dokploy secrets.
- `PUBLIC_API_URL` no debe tener slash final: `https://apiteleproxy.velone.ai` (correcto),
  `https://apiteleproxy.velone.ai/` (incorrecto).
