-- ============================================================================
-- LAB 07 MySQL: Optimización de Queries
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: EXPLAIN columna por columna
-- ════════════════════════════════════════════════════════════════════════════
-- id:            número de la SELECT (mayor = ejecutada antes)
-- select_type:   SIMPLE, PRIMARY, SUBQUERY, DERIVED, UNION
-- table:         tabla a la que se aplica la fila
-- type:          ALL < index < range < ref < eq_ref < const < system
-- possible_keys: índices que MySQL considera usar
-- key:           índice que MySQL realmente usa
-- key_len:       bytes del índice usados
-- ref:           columna o constante comparada con el índice
-- rows:          estimación de filas examinadas
-- filtered:      % de filas que pasarán el WHERE
-- Extra:         info adicional (Using index, Using filesort, Using temporary)

EXPLAIN FORMAT=TRADITIONAL
SELECT p.name, p.price, c.name AS categoria
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.price > 500
ORDER BY p.price DESC;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: EXPLAIN ANALYZE (MySQL 8.0.18+)
-- ════════════════════════════════════════════════════════════════════════════
-- EXPLAIN ANALYZE EJECUTA la query y muestra tiempos reales (no estimados).

EXPLAIN ANALYZE
SELECT
    u.name,
    COUNT(o.id) AS pedidos,
    SUM(o.total) AS total
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
GROUP BY u.id, u.name
ORDER BY total DESC;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: Reescribir subquery como JOIN (más eficiente)
-- ════════════════════════════════════════════════════════════════════════════

-- ❌ Subquery correlacionada (lenta): se ejecuta N veces (una por usuario)
EXPLAIN
SELECT u.name
FROM users u
WHERE (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) > 2;

-- ✅ JOIN equivalente (más eficiente): MySQL la optimiza mejor
EXPLAIN
SELECT DISTINCT u.name
FROM users u
JOIN orders o ON u.id = o.user_id
GROUP BY u.id
HAVING COUNT(o.id) > 2;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: Cursor Pagination vs OFFSET (escala mejor)
-- ════════════════════════════════════════════════════════════════════════════

-- ❌ OFFSET pagination: lento con páginas grandes (MySQL lee y descarta filas)
-- Con OFFSET 10000, MySQL lee y descarta 10000 filas antes de devolver 20
EXPLAIN SELECT * FROM orders ORDER BY id LIMIT 20 OFFSET 100;

-- ✅ Cursor pagination: siempre O(log n) con índice en id
-- Guarda el último id de la página anterior y úsalo como cursor
SET @last_id = 50;  -- 👉 MODIFICA: el último ID de la página anterior

EXPLAIN SELECT * FROM orders WHERE id > @last_id ORDER BY id LIMIT 20;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 5: Detectar queries lentas con SHOW STATUS
-- ════════════════════════════════════════════════════════════════════════════

SHOW STATUS LIKE 'Handler_read%';
-- Handler_read_rnd_next alto → muchos full table scans
-- Handler_read_key alto     → buen uso de índices

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 6: Optimización de INSERT masivo
-- ════════════════════════════════════════════════════════════════════════════

-- ❌ N inserts individuales: N round-trips a la BD
-- INSERT INTO t VALUES (1); INSERT INTO t VALUES (2); ...

-- ✅ Un solo INSERT con múltiples filas: 1 round-trip
-- INSERT INTO t VALUES (1), (2), (3), ...;

-- También: deshabilitar autocommit durante inserts masivos
-- START TRANSACTION;
-- INSERT INTO t VALUES (1),(2),(3),...;
-- COMMIT;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 7: Query del informe de ventas optimizada
-- ════════════════════════════════════════════════════════════════════════════
-- 👉 MODIFICA: quita el WHERE y mide cuánto más tarda con EXPLAIN ANALYZE

EXPLAIN ANALYZE
SELECT
    c.name                              AS categoria,
    COUNT(DISTINCT o.id)                AS pedidos,
    SUM(oi.quantity)                    AS unidades,
    SUM(oi.quantity * oi.unit_price)    AS facturacion,
    AVG(oi.unit_price)                  AS precio_medio
FROM order_items oi
JOIN orders o    ON oi.order_id   = o.id
JOIN products p  ON oi.product_id = p.id
JOIN categories c ON p.category_id = c.id
WHERE o.status = 'delivered'        -- 👉 MODIFICA: elimina para ver todas
  AND o.created_at >= '2024-01-01'  -- 👉 MODIFICA: cambia el rango de fechas
GROUP BY c.id, c.name
ORDER BY facturacion DESC;
