-- ============================================================================
-- LAB 06 MySQL: Subqueries, CTEs y CTEs Recursivas
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: Subquery en WHERE
-- ════════════════════════════════════════════════════════════════════════════

-- Productos más caros que el precio promedio
SELECT name, price
FROM products
WHERE price > (SELECT AVG(price) FROM products)
ORDER BY price DESC;

-- Usuarios que han realizado pedidos (EXISTS)
SELECT name, email
FROM users
WHERE EXISTS (
    SELECT 1 FROM orders WHERE orders.user_id = users.id
)
ORDER BY name;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: Subquery en FROM (Derived Table)
-- ════════════════════════════════════════════════════════════════════════════

-- Estadísticas sobre el ticket medio por usuario
SELECT
    AVG(user_stats.ticket_medio) AS media_de_medias,
    MAX(user_stats.ticket_medio) AS cliente_mayor_ticket,
    MIN(user_stats.ticket_medio) AS cliente_menor_ticket
FROM (
    SELECT user_id, AVG(total) AS ticket_medio
    FROM orders
    WHERE status != 'cancelled'
    GROUP BY user_id
) AS user_stats;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: Subquery correlacionada
-- ════════════════════════════════════════════════════════════════════════════
-- Una subquery correlacionada hace referencia a la query exterior.
-- Se ejecuta UNA VEZ POR CADA FILA de la query exterior (puede ser lenta).

-- Para cada usuario, mostrar su pedido más reciente
SELECT
    u.name,
    u.email,
    (SELECT MAX(created_at) FROM orders o WHERE o.user_id = u.id) AS ultimo_pedido,
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id AND o.status = 'delivered') AS pedidos_entregados
FROM users u
WHERE u.deleted_at IS NULL
ORDER BY ultimo_pedido DESC
LIMIT 10;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: CTE (Common Table Expression) con WITH
-- ════════════════════════════════════════════════════════════════════════════
-- Las CTEs son más legibles que las subqueries anidadas.
-- Se definen con WITH y se pueden referenciar múltiples veces.

WITH user_orders AS (
    SELECT
        u.id          AS user_id,
        u.name,
        u.email,
        COUNT(o.id)   AS total_orders,
        SUM(o.total)  AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
    WHERE u.deleted_at IS NULL
    GROUP BY u.id, u.name, u.email
),
high_value_customers AS (
    SELECT *
    FROM user_orders
    WHERE total_spent > 500
)
SELECT
    name,
    email,
    total_orders,
    total_spent,
    CASE
        WHEN total_spent > 5000 THEN '💎 VIP'
        WHEN total_spent > 2000 THEN '🥇 Gold'
        WHEN total_spent > 1000 THEN '🥈 Silver'
        ELSE '🥉 Bronze'
    END AS tier
FROM high_value_customers
ORDER BY total_spent DESC;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 5: CTE Recursiva — jerarquía de categorías
-- ════════════════════════════════════════════════════════════════════════════
-- Las CTEs recursivas son ideales para datos jerárquicos.
-- Requieren: parte base (anchor) + parte recursiva + UNION ALL

-- Primero añadimos subcategorías de ejemplo
INSERT IGNORE INTO categories (id, name, slug, parent_id) VALUES
(11, 'Móviles Android', 'moviles-android', 1),
(12, 'Móviles Apple',   'moviles-apple',   1),
(13, 'Portátiles Windows', 'portatiles-windows', 2),
(14, 'Portátiles Mac',  'portatiles-mac',  2);

WITH RECURSIVE category_tree AS (
    -- Anchor: categorías raíz (sin padre)
    SELECT
        id,
        name,
        parent_id,
        0       AS nivel,
        name    AS ruta
    FROM categories
    WHERE parent_id IS NULL

    UNION ALL

    -- Parte recursiva: hijos de las categorías ya procesadas
    SELECT
        c.id,
        c.name,
        c.parent_id,
        ct.nivel + 1,
        CONCAT(ct.ruta, ' > ', c.name) AS ruta
    FROM categories c
    INNER JOIN category_tree ct ON c.parent_id = ct.id
)
SELECT
    CONCAT(REPEAT('  ', nivel), name) AS categoria,
    nivel,
    ruta
FROM category_tree
ORDER BY ruta;

-- Limpieza de categorías de prueba
DELETE FROM categories WHERE id IN (11, 12, 13, 14);
