# LocateMe

Aplicación web para que los padres vean en un mapa dónde están sus hijos.

- **Panel de los padres** (`/`): mapa con la última ubicación de cada hijo, batería, precisión del GPS,
  recorrido de las últimas 24 horas, zonas seguras (casa, escuela…) y avisos cuando alguien llega,
  sale de una zona o pide ayuda.
- **Página del niño** (`/nino`): se abre en el teléfono del niño con un enlace o código QR. Comparte
  su ubicación y tiene un botón **SOS** (hay que mantenerlo presionado para evitar toques accidentales).

Hecha con PHP 8.1+ ([Slim 4](https://www.slimframework.com/)), MySQL/MariaDB y
[Leaflet](https://leafletjs.com/) con mapas de OpenStreetMap (no necesita llave de API).

> **¿Vas a trabajar en el código?** Empieza por la [guía para desarrolladores](docs/GUIA-DESARROLLO.md):
> explica la arquitectura, qué hace cada archivo y cómo hacer los cambios más comunes.

## Instalación

```bash
composer install

# Base de datos
mysql -u root -p -e "CREATE DATABASE locateme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p locateme < database/schema.sql

# Configuración: copia el ejemplo y llena tus datos
cp .env.example .env
```

Para probar en tu computadora:

```bash
php -S localhost:8000 index.php
```

y abre <http://localhost:8000>. En producción, con Apache, el `.htaccess` incluido redirige todo a
`index.php` y bloquea el acceso a `.env`, `vendor/`, el código PHP y demás archivos privados.

> **Importante:** el navegador sólo permite leer el GPS en páginas con **https://** (o en `localhost`).
> Publica la app con un certificado (por ejemplo, Let's Encrypt) para que funcione en los teléfonos.

### Variables del `.env`

| Variable | Para qué sirve |
| --- | --- |
| `DB_SERVER_NAME`, `DB_PORT`, `USER_NAME`, `PASSWORD`, `DB` | Conexión a MySQL |
| `APP_DEBUG` | `true` muestra el detalle de los errores. Déjalo en `false` en producción |
| `ALLOW_REGISTRATION` | `false` impide crear cuentas nuevas cuando tu familia ya está registrada |
| `LOCATION_RETENTION_DAYS` | Días que se guarda el historial de ubicaciones (30 por defecto) |

## Cómo se usa

1. Crea tu cuenta en `/`.
2. En «Mis hijos» toca **Agregar**, escribe el nombre y elige un color.
3. Escanea el código QR con el teléfono de tu hijo (o envíale el enlace) y toca «Permitir» cuando pida la ubicación.
4. Crea zonas seguras con **Agregar** en «Zonas seguras» y tocando el mapa.

Si el teléfono se pierde o cambias de teléfono, toca **Vincular** para generar un enlace nuevo: el anterior
deja de funcionar al instante.

## API

Todas las rutas responden JSON. Los errores tienen la forma `{ "error": "mensaje" }`.

| Método y ruta | Quién | Descripción |
| --- | --- | --- |
| `POST /api/register` | — | `{ name, username, password }` crea la cuenta e inicia sesión |
| `POST /api/login` | — | `{ username, password }` |
| `POST /api/logout` | Padre | Cierra la sesión |
| `GET /api/me` | Padre | Datos de la cuenta |
| `GET /api/children` | Padre | Hijos con su última ubicación y alerta SOS |
| `POST /api/children` | Padre | `{ name, color }` → devuelve `device_token` (sólo esa vez) |
| `POST /api/children/{id}/token` | Padre | Genera un enlace nuevo y revoca el anterior |
| `POST /api/children/{id}/sos/ack` | Padre | Marca la alerta SOS como atendida |
| `GET /api/children/{id}/locations?hours=24` | Padre | Recorrido (máximo 7 días) |
| `DELETE /api/children/{id}` | Padre | Borra al hijo y su historial |
| `GET /api/zones` · `POST /api/zones` · `DELETE /api/zones/{id}` | Padre | Zonas seguras `{ name, latitude, longitude, radius }` |
| `GET /api/device` | Teléfono | Valida el enlace y devuelve el nombre del niño |
| `POST /api/locations` | Teléfono | `{ latitude, longitude, accuracy?, battery?, sos? }` |

El padre se identifica con la cookie de sesión y el teléfono del niño con `Authorization: Bearer <token>`.

Ejemplo, enviando una ubicación a mano:

```bash
curl -X POST http://localhost:8000/api/locations \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"latitude": 32.6040599, "longitude": -115.4804385, "accuracy": 15}'
```

## Seguridad y privacidad

- Contraseñas guardadas con `password_hash` (bcrypt) y consultas SQL siempre preparadas.
- Cada padre sólo puede ver y modificar a sus propios hijos y zonas.
- El token del teléfono se guarda como hash SHA-256; si alguien obtiene la base de datos no puede
  hacerse pasar por el teléfono. El enlace lleva el token después de `#`, así que no queda en los logs del servidor.
- La API sólo acepta cuerpos JSON y la cookie de sesión es `HttpOnly` y `SameSite=Lax` (protección CSRF).
- Las páginas envían `Content-Security-Policy` y las librerías externas se cargan con integridad (SRI).
- El historial de ubicaciones se borra automáticamente después de `LOCATION_RETENTION_DAYS` días.

## Limitaciones

La página del niño es una página web: el navegador deja de enviar la ubicación si el teléfono bloquea
la pantalla por mucho tiempo o se cierra la pestaña. Para seguimiento continuo en segundo plano haría
falta una app nativa (o una PWA con permisos de ubicación en segundo plano).
