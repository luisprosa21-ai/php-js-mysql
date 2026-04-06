-- ============================================================================
-- LAB 05 MySQL: Agregaciones y Window Functions
-- ============================================================================
-- Ejecutar en phpMyAdmin: http://localhost:8080
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: GROUP BY y funciones de agregación básicas
-- ════════════════════════════════════════════════════════════════════════════

-- Estadísticas de pedidos por estado
SELECT
    status,
    COUNT(*)           AS total_pedidos,
    SUM(total)         AS ingresos_totales,
    AVG(total)         AS ticket_medio,
    MIN(total)         AS pedido_minimo,
    MAX(total)         AS pedido_maximo
FROM orders
GROUP BY status
ORDER BY ingresos_totales DESC;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: HAVING — Filtrar grupos (≠ WHERE que filtra filas)
-- ════════════════════════════════════════════════════════════════════════════
-- WHERE filtra ANTES de agrupar (filas individuales)
-- HAVING filtra DESPUÉS de agrupar (grupos completos)

-- Usuarios que han gastado más de €1000 en pedidos 'delivered'
SELECT
    u.name,
    u.email,
    COUNT(o.id)    AS pedidos,
    SUM(o.total)   AS gasto_total
FROM users u
JOIN orders o ON u.id = o.user_id
WHERE o.status = 'delivered'   -- ← WHERE filtra pedidos antes de agrupar
GROUP BY u.id, u.name, u.email
HAVING gasto_total > 1000      -- ← HAVING filtra grupos (no puede ir en WHERE)
ORDER BY gasto_total DESC;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: Window Functions — ROW_NUMBER, RANK, DENSE_RANK
-- ════════════════════════════════════════════════════════════════════════════
-- Window Functions calculan valores sobre una "ventana" de filas relacionadas
-- SIN colapsar las filas en grupos (como hace GROUP BY).

-- Ranking de productos más vendidos
SELECT
    p.name AS producto,
    SUM(oi.quantity) AS unidades_vendidas,
    ROW_NUMBER() OVER (ORDER BY SUM(oi.quantity) DESC) AS row_num,
    RANK()       OVER (ORDER BY SUM(oi.quantity) DESC) AS rank_pos,
    DENSE_RANK() OVER (ORDER BY SUM(oi.quantity) DESC) AS dense_rank
FROM order_items oi
JOIN products p ON oi.product_id = p.id
GROUP BY p.id, p.name
ORDER BY unidades_vendidas DESC
LIMIT 10;

-- ✅ Diferencia entre RANK y DENSE_RANK:
--   RANK:       1, 2, 2, 4, 5 (salta el 3)
--   DENSE_RANK: 1, 2, 2, 3, 4 (no salta)

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: LAG y LEAD — Comparar con fila anterior/siguiente
-- ════════════════════════════════════════════════════════════════════════════

-- Comparar ingresos de pedidos consecutivos por usuario
SELECT
    o.id,
    u.name AS usuario,
    o.total,
    o.created_at,
    LAG(o.total)  OVER (PARTITION BY o.user_id ORDER BY o.created_at) AS pedido_anterior,
    LEAD(o.total) OVER (PARTITION BY o.user_id ORDER BY o.created_at) AS proximo_pedido,
    o.total - LAG(o.total, 1, o.total) OVER (PARTITION BY o.user_id ORDER BY o.created_at) AS diferencia
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.user_id IN (3, 4)   -- 👉 MODIFICA: cambia los user_id
ORDER BY o.user_id, o.created_at;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 5: Running Total (suma acumulada)
-- ════════════════════════════════════════════════════════════════════════════

SELECT
    DATE_FORMAT(o.created_at, '%Y-%m') AS mes,
    SUM(o.total) AS ingresos_mes,
    SUM(SUM(o.total)) OVER (ORDER BY DATE_FORMAT(o.created_at, '%Y-%m')) AS acumulado
FROM orders o
WHERE o.status = 'delivered'
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
ORDER BY mes;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 6: Top N por categoría (PARTITION BY)
-- ════════════════════════════════════════════════════════════════════════════
-- Obtener el producto más vendido de cada categoría

SELECT *
FROM (
    SELECT
        c.name AS categoria,
        p.name AS producto,
        SUM(oi.quantity) AS total_vendido,
        RANK() OVER (PARTITION BY p.category_id ORDER BY SUM(oi.quantity) DESC) AS rank_en_cat
    FROM order_items oi
    JOIN products p   ON oi.product_id  = p.id
    JOIN categories c ON p.category_id  = c.id
    GROUP BY p.category_id, c.name, p.id, p.name
) ranked
WHERE rank_en_cat = 1   -- 👉 MODIFICA: = 2 para el segundo más vendido
ORDER BY total_vendido DESC;
