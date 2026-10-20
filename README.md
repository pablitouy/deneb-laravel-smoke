# deneb-laravel-smoke

Aplicación Laravel mínima para probar despliegues de Deneb sin usar una app real.
Incluye `composer.lock`, no necesita build de assets y no tiene secretos.

## Endpoints

- `/smoke` — JSON con versión de Laravel y PHP, release activo y estado de la base:
  si conecta, cuántas tablas hay, qué migraciones corrieron y el marcador de prueba.
  Funciona aunque la base esté vacía (no usa sesiones).
- `/smoke/mark` — escribe una sola vez un marcador con fecha y release en
  `storage/app/` y en `data/`; las llamadas siguientes no lo cambian. `/smoke`
  muestra ambos en `persistence` e indica si cada directorio es un enlace administrado.
- `/up` — health check estándar de Laravel.
- `/` — redirige a `/smoke`.

## Migraciones

Además de las tres migraciones de Laravel, `create_deneb_smoke_markers_table` crea
la tabla `deneb_smoke_markers` con la fila `Created by the Deneb migration test`.
Si `/smoke` muestra ese texto en `smoke_marker`, las migraciones corrieron.

## Configuración en Deneb

- Sitio PHP 8.3 o superior.
- **Deployments:** este repositorio, rama `main`, sin directorio de aplicación y
  con el build script **vacío**.
- **Environment:** generar `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`.
  Los valores `DB_*` los publica **Deployments → Application database**.
