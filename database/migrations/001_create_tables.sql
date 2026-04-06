-- ============================================================================
-- MIGRACIÓN 001: Creación de tablas del proyecto PHP·JS·MySQL Lab
-- ============================================================================
-- Ejecutar: mysql -u root -p php_js_mysql_lab < database/migrations/001_create_tables.sql
-- O con Docker: docker exec -i php_js_mysql_lab_db_1 mysql -u root -prootpassword php_js_mysql_lab < ...
--
-- Decisiones de diseño:
--   ENGINE=InnoDB  → Soporte de transacciones ACID y Foreign Keys
--   CHARSET=utf8mb4 → Unicode completo (incluyendo emojis)
--   COLLATE=utf8mb4_unicode_ci → Ordenación insensible a mayúsculas/acentos
-- ============================================================================

CREATE DATABASE IF NOT EXISTS php_js_mysql_lab
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE php_js_mysql_lab;

-- ============================================================================
-- TABLA: categories
-- Catálogo de categorías de productos. Tabla padre de products.
-- ============================================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL,            -- URL-friendly: "electronica-hogar"
    description TEXT,
    parent_id   INT UNSIGNED DEFAULT NULL,        -- Categoría padre (jerarquía)
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_categories_slug (slug),
    INDEX idx_categories_parent (parent_id),

    -- Relación jerárquica (categorías padre-hijo)
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorías de productos con soporte de jerarquía';

-- ============================================================================
-- TABLA: users
-- Usuarios del sistema con roles y soft delete.
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    -- ARGON2ID es el algoritmo recomendado en 2024 para hashing de contraseñas.
    -- El hash de Argon2ID tiene ~95-100 chars, usamos 255 para ser generosos.
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'user', 'guest') NOT NULL DEFAULT 'user',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- soft delete: deleted_at IS NULL = activo; deleted_at IS NOT NULL = borrado
    -- ✅ MEJOR ASÍ: usar soft delete para preservar historial y FK
    -- ❌ NO HAGAS ESTO: DELETE FROM users WHERE id = ?  (rompe FK de orders)
    deleted_at    DATETIME DEFAULT NULL,

    -- Índice único parcial: solo un email activo puede existir
    UNIQUE KEY uk_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuarios del sistema con RBAC simple';

-- ============================================================================
-- TABLA: products
-- Catálogo de productos del e-commerce.
-- ============================================================================
CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    -- DECIMAL vs FLOAT: DECIMAL es exacto (sin errores de punto flotante).
    -- Para valores monetarios SIEMPRE usa DECIMAL, nunca FLOAT/DOUBLE.
    -- ❌ NO HAGAS ESTO: price FLOAT  (puede dar 19.99999... en cálculos)
    -- ✅ MEJOR ASÍ: price DECIMAL(10,2)
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock       INT UNSIGNED NOT NULL DEFAULT 0,
    sku         VARCHAR(50)  DEFAULT NULL,        -- Stock Keeping Unit
    active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL,

    UNIQUE KEY uk_products_sku (sku),
    INDEX idx_products_category (category_id),
    INDEX idx_products_active (active),
    INDEX idx_products_price (price),
    -- Índice compuesto: optimiza búsquedas de productos activos por categoría
    INDEX idx_products_cat_active (category_id, active),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT  -- No borrar categoría si tiene productos
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de productos';

-- ============================================================================
-- TABLA: orders
-- Pedidos de usuarios. Máquina de estados: pending→confirmed→shipped→delivered
-- ============================================================================
CREATE TABLE IF NOT EXISTS orders (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    status     ENUM('pending','confirmed','shipped','delivered','cancelled')
               NOT NULL DEFAULT 'pending',
    -- El total es desnormalización intencional: evita recalcular en cada query
    total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes      TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status),
    -- Índice compuesto para "pedidos recientes de un usuario"
    INDEX idx_orders_user_status (user_id, status),
    INDEX idx_orders_created (created_at),

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos con máquina de estados';

-- ============================================================================
-- TABLA: order_items
-- Líneas de un pedido. Relación N:M entre orders y products.
-- ============================================================================
CREATE TABLE IF NOT EXISTS order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    -- unit_price: precio en el momento de la compra (puede variar después)
    -- Guardamos el precio histórico, no referenciamos el actual
    unit_price  DECIMAL(10,2) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE   -- Si se borra el pedido, se borran sus items
        ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Líneas de pedido con precio histórico';

-- ============================================================================
-- TABLA: audit_log
-- Registro de auditoría: quién hizo qué y cuándo.
-- ============================================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,         -- NULL si acción del sistema
    action      VARCHAR(100) NOT NULL,             -- 'user.created', 'order.cancelled'
    entity_type VARCHAR(50)  NOT NULL,             -- 'user', 'order', 'product'
    entity_id   INT UNSIGNED DEFAULT NULL,
    old_values  JSON DEFAULT NULL,                 -- Estado anterior (JSON de MySQL 5.7+)
    new_values  JSON DEFAULT NULL,                 -- Estado nuevo
    ip_address  VARCHAR(45)  DEFAULT NULL,         -- IPv4 o IPv6
    user_agent  VARCHAR(500) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_user (user_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de auditoría de todas las acciones';

-- ============================================================================
-- TABLA: sessions
-- Sesiones de usuario (alternativa server-side a JWT).
-- ============================================================================
CREATE TABLE IF NOT EXISTS sessions (
    id         VARCHAR(128) NOT NULL PRIMARY KEY,  -- session_id (hash)
    user_id    INT UNSIGNED NOT NULL,
    payload    JSON NOT NULL,                      -- Datos de la sesión
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,

    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_expires (expires_at),

    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sesiones de usuario server-side';
