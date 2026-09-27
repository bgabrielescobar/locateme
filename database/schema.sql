-- LocateMe: esquema de la base de datos (MySQL 5.7+ / MariaDB 10.3+)
--
--   mysql -u root -p -e "CREATE DATABASE locateme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root -p locateme < database/schema.sql
--
-- Todas las fechas se guardan en UTC.

-- Cuentas de los padres (los que ven el mapa)
CREATE TABLE IF NOT EXISTS parents (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(60)  NOT NULL,
    username      VARCHAR(40)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_parents_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hijos. Cada uno tiene un token secreto para el teléfono que envía su ubicación.
-- Sólo se guarda el hash SHA-256 del token, nunca el token en claro.
CREATE TABLE IF NOT EXISTS children (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id         INT UNSIGNED NOT NULL,
    name              VARCHAR(40)  NOT NULL,
    color             CHAR(7)      NOT NULL DEFAULT '#6366f1',
    device_token_hash CHAR(64)     NOT NULL,
    sos_at            TIMESTAMP    NULL DEFAULT NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_children_device_token (device_token_hash),
    KEY idx_children_parent (parent_id),
    CONSTRAINT fk_children_parent FOREIGN KEY (parent_id) REFERENCES parents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de ubicaciones enviadas por el teléfono del niño
CREATE TABLE IF NOT EXISTS locations (
    id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    child_id    INT UNSIGNED     NOT NULL,
    latitude    DECIMAL(9,6)     NOT NULL,
    longitude   DECIMAL(9,6)     NOT NULL,
    accuracy    INT UNSIGNED     NULL,     -- metros
    battery     TINYINT UNSIGNED NULL,     -- 0-100 %
    is_sos      TINYINT(1)       NOT NULL DEFAULT 0,
    recorded_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_locations_child_time (child_id, recorded_at),
    CONSTRAINT fk_locations_child FOREIGN KEY (child_id) REFERENCES children (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zonas seguras (casa, escuela...). Aplican a todos los hijos del padre.
CREATE TABLE IF NOT EXISTS safe_zones (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id  INT UNSIGNED NOT NULL,
    name       VARCHAR(40)  NOT NULL,
    latitude   DECIMAL(9,6) NOT NULL,
    longitude  DECIMAL(9,6) NOT NULL,
    radius     INT UNSIGNED NOT NULL,      -- metros
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_safe_zones_parent (parent_id),
    CONSTRAINT fk_safe_zones_parent FOREIGN KEY (parent_id) REFERENCES parents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
