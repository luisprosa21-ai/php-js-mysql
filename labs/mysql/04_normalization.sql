-- ============================================================================
-- LAB 04 MySQL: Normalización de bases de datos (0NF → 3NF)
-- ============================================================================
-- Ejecutar en phpMyAdmin: http://localhost:8080
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- 0NF (Forma No Normalizada): Todo en una tabla, datos repetidos
-- ════════════════════════════════════════════════════════════════════════════
-- Problemas:
--   ❌ Datos repetidos (nombre, email del cliente)
--   ❌ Múltiples valores en una celda (products_purchased)
--   ❌ Anomalías de actualización: cambiar email requiere actualizar múltiples filas

DROP TABLE IF EXISTS orders_0nf;
CREATE TABLE orders_0nf (
    order_id            INT,
    customer_name       VARCHAR(100),
    customer_email      VARCHAR(150),
    customer_city       VARCHAR(50),
    products_purchased  TEXT,           -- ❌ "iPhone 15, MacBook Pro, AirPods"
    quantities          TEXT,           -- ❌ "1, 1, 2"
    total_amount        DECIMAL(10,2)
) ENGINE=InnoDB;

INSERT INTO orders_0nf VALUES
(1, 'Ana García',   'ana@lab.test',   'Madrid',   'iPhone 15, AirPods Pro',      '1, 2',   1759.97),
(2, 'Ana García',   'ana@lab.test',   'Madrid',   'MacBook Pro',                 '1',      2499.99),
(3, 'Carlos López', 'carlos@lab.test','Barcelona', 'Samsung Galaxy S24, iPad Pro','1, 1',   1899.98),
(4, 'Ana García',   'ana@lab.test',   'Madrid',   'Sony WH-1000XM5',             '1',       349.99);

SELECT '0NF — Datos sin normalizar:' AS forma;
SELECT * FROM orders_0nf;

-- ════════════════════════════════════════════════════════════════════════════
-- 1NF (Primera Forma Normal): Valores atómicos, sin grupos repetidos
-- ════════════════════════════════════════════════════════════════════════════
-- Reglas:
--   ✅ Cada celda tiene un único valor (atómico)
--   ✅ Cada fila es única (tiene PK)
--   ❌ Aún hay datos repetidos de clientes

DROP TABLE IF EXISTS orders_1nf;
CREATE TABLE orders_1nf (
    order_id      INT,
    customer_name  VARCHAR(100),
    customer_email VARCHAR(150),
    customer_city  VARCHAR(50),
    product_name   VARCHAR(100),    -- ✅ Un producto por fila
    quantity       INT,             -- ✅ Una cantidad por fila
    unit_price     DECIMAL(10,2),
    PRIMARY KEY (order_id, product_name)  -- PK compuesta
) ENGINE=InnoDB;

INSERT INTO orders_1nf VALUES
(1, 'Ana García',   'ana@lab.test',   'Madrid',    'iPhone 15',       1, 1199.99),
(1, 'Ana García',   'ana@lab.test',   'Madrid',    'AirPods Pro',     2,  279.99),
(2, 'Ana García',   'ana@lab.test',   'Madrid',    'MacBook Pro',     1, 2499.99),
(3, 'Carlos López', 'carlos@lab.test','Barcelona', 'Samsung Galaxy',  1,  899.99),
(3, 'Carlos López', 'carlos@lab.test','Barcelona', 'iPad Pro',        1,  999.99),
(4, 'Ana García',   'ana@lab.test',   'Madrid',    'Sony WH-1000XM5', 1,  349.99);

SELECT '1NF — Valores atómicos:' AS forma;
SELECT * FROM orders_1nf;

-- ════════════════════════════════════════════════════════════════════════════
-- 2NF (Segunda Forma Normal): Sin dependencias parciales de la PK
-- ════════════════════════════════════════════════════════════════════════════
-- Regla: cada columna depende de TODA la clave primaria (no de parte de ella).
-- En 1NF: customer_name depende de order_id (parte de la PK), no de product_name.
-- Solución: separar en tablas customers y orders.

DROP TABLE IF EXISTS customers_2nf;
DROP TABLE IF EXISTS orders_2nf;
DROP TABLE IF EXISTS order_items_2nf;

CREATE TABLE customers_2nf (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) UNIQUE NOT NULL,
    city        VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE orders_2nf (
    order_id    INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers_2nf(customer_id)
) ENGINE=InnoDB;

CREATE TABLE order_items_2nf (
    order_id     INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    quantity     INT NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (order_id, product_name),
    FOREIGN KEY (order_id) REFERENCES orders_2nf(order_id)
) ENGINE=InnoDB;

INSERT INTO customers_2nf (name, email, city) VALUES
('Ana García',   'ana@lab.test',   'Madrid'),
('Carlos López', 'carlos@lab.test','Barcelona');

INSERT INTO orders_2nf (customer_id) VALUES (1),(1),(2),(1);

INSERT INTO order_items_2nf VALUES
(1, 'iPhone 15',       1, 1199.99),
(1, 'AirPods Pro',     2,  279.99),
(2, 'MacBook Pro',     1, 2499.99),
(3, 'Samsung Galaxy',  1,  899.99),
(3, 'iPad Pro',        1,  999.99),
(4, 'Sony WH-1000XM5', 1,  349.99);

SELECT '2NF — Sin dependencias parciales:' AS forma;
SELECT c.name, c.email, o.order_id, oi.product_name, oi.quantity, oi.unit_price
FROM customers_2nf c
JOIN orders_2nf o  ON c.customer_id = o.customer_id
JOIN order_items_2nf oi ON o.order_id = oi.order_id;

-- ════════════════════════════════════════════════════════════════════════════
-- 3NF (Tercera Forma Normal): Sin dependencias transitivas
-- ════════════════════════════════════════════════════════════════════════════
-- Regla: las columnas no-clave no deben depender de otras columnas no-clave.
-- Problema en 2NF: 'city' depende del cliente (bien), pero si quisiéramos
-- guardar el código postal, este dependería de 'city', no del customer_id.
-- En nuestro caso ya estamos en 3NF, pero veamos un ejemplo con productos:

-- product_name → category → category_description
-- ← dependencia transitiva: category_description depende de category, no del item

DROP TABLE IF EXISTS products_3nf;
DROP TABLE IF EXISTS categories_3nf;

CREATE TABLE categories_3nf (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE products_3nf (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories_3nf(id)
) ENGINE=InnoDB;

INSERT INTO categories_3nf (name, description) VALUES
('Smartphones', 'Teléfonos inteligentes'),
('Laptops', 'Ordenadores portátiles'),
('Audio', 'Auriculares y altavoces');

INSERT INTO products_3nf (category_id, name, unit_price) VALUES
(1, 'iPhone 15', 1199.99),
(1, 'Samsung Galaxy S24', 899.99),
(2, 'MacBook Pro M3', 2499.99),
(3, 'AirPods Pro', 279.99),
(3, 'Sony WH-1000XM5', 349.99);

SELECT '3NF — Sin dependencias transitivas:' AS forma;
SELECT p.name AS producto, c.name AS categoria, c.description, p.unit_price
FROM products_3nf p
JOIN categories_3nf c ON p.category_id = c.id;

-- Limpieza
DROP TABLE IF EXISTS order_items_2nf;
DROP TABLE IF EXISTS orders_2nf;
DROP TABLE IF EXISTS customers_2nf;
DROP TABLE IF EXISTS orders_1nf;
DROP TABLE IF EXISTS orders_0nf;
DROP TABLE IF EXISTS products_3nf;
DROP TABLE IF EXISTS categories_3nf;
