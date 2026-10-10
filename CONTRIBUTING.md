# Contribuir a tele-proxy-admin

Gracias por tu interes en contribuir. Este documento describe el flujo de trabajo y convenciones.

## Flujo de trabajo

1. **Fork** el repositorio (si eres colaborador externo).
2. **Crea una rama** desde `main`:
   - `feature/nombre-descriptivo` para nuevas funcionalidades
   - `fix/nombre-del-bug` para correcciones
   - `chore/descripcion` para tareas de mantenimiento
3. **Desarrolla** siguiendo las convenciones del proyecto.
4. **Tests**: Asegurate de que los tests pasan.
   ```bash
   composer run test
   composer run phpstan
   composer run cs-fix
   ```
5. **Commit** con Conventional Commits:
   - `feat:` nueva funcionalidad
   - `fix:` correccion de bug
   - `docs:` cambios en documentacion
   - `chore:` tareas de mantenimiento
   - `test:` adicion o correccion de tests
   - `refactor:` refactorizacion sin cambios funcionales
6. **Push** a tu rama.
7. **Pull Request** a `main`:
   - Descripcion clara de los cambios
   - Referencia a issues si aplica
   - CI debe pasar (tests, PHPStan, audit)
   - Al menos 1 review aprobado

## Convenciones de codigo

- **PHP**: PSR-12 + PHP-CS-Fixer (configuracion en `.php-cs-fixer.php`)
- **Latte**: Indentacion de 4 espacios, escaping contextual (no `{@html}`)
- **Nombres**: camelCase en PHP, snake_case en API (mapeo en adaptadores)
- **Arquitectura**: Hexagonal + Vertical Slicing (ver `docs/ARCHITECTURE.md`)
- **Idioma**: UI y mensajes en espanol. Codigo y commits en ingles.

## Estructura de slices

Cada slice sigue la estructura:
```
SliceName/
├── Domain/           # Entidades, Value Objects, puertos (interfaces)
├── Application/      # Handlers de casos de uso (__invoke)
├── Infrastructure/   # Adaptadores (API, CouchDB, Valkey)
└── UI/               # Presenters + templates Latte
```

## Variables de entorno

Ver `docs/ENVIRONMENT.md`. Nunca commitear `.env` con valores reales.

## Seguridad

- No commitear secretos (.env, claves, tokens)
- Master token: NUNCA en localStorage (rumi.js gestiona IndexedDB cifrado)
- Validacion server-side obligatoria (Nette Forms)
- Headers de seguridad gestionados por Caddy

## Reportar bugs

Abrir un issue en GitHub con:
- Descripcion clara del problema
- Pasos para reproducir
- Comportamiento esperado vs actual
- Capturas de pantalla si aplica

## Dudas

Contactar con el equipo de stringnetlab.
