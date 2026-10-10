# tele-proxy-admin

Panel de administracion de [tele-proxy](https://github.com/stringnetlab/tele-proxy).

Aplicacion PHP Nette server-rendered que actua como BFF (Backend For Frontend):
el navegador habla SOLO con Nette, y Nette reenvia server-side al API de control de tele-proxy.

## Requisitos

- PHP 8.3+
- Composer 2.x
- CouchDB 3.x
- Valkey 7.x+
- Docker (para despliegue)

## Instalacion

```bash
# Clonar repositorio
git clone git@github.com:stringnetlab/tele-proxy-admin.git
cd tele-proxy-admin

# Instalar dependencias
composer install

# Configurar variables de entorno
cp .env.example .env
# Editar .env con los valores correctos

# Build
composer run build
```

## Desarrollo

```bash
# Arrancar servidor de desarrollo
composer run dev

# Tests
composer run test

# PHPStan
composer run phpstan

# PHP-CS-Fixer
composer run cs-fix
```

## Docker

```bash
docker compose up --build
```

El panel estara disponible en `http://localhost:8080`.

## Estructura

Ver [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) para detalles.

```
src/
├── ClientManagement/     # CRUD de clientes del proxy
├── AdminManagement/      # Gestion de administradores
├── Authentication/       # Login (Google + master token), sesion
└── Shared/               # Infraestructura compartida (ApiClient, CouchDB, Valkey)
```

## Despliegue

- **Dokploy**: Build desde Dockerfile (FrankenPHP + Caddy)
- **Cloudflare**: Full (Strict) SSL, DNS proxied
- **Dominio**: `admteleproxy.velone.ai`
- **Healthcheck**: `GET /_ok`

## Licencia

Propietaria. Copyright (c) stringnetlab.
