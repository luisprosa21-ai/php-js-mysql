-- ============================================================================
-- LAB 01 MySQL: Tipos de JOIN explicados
-- ============================================================================
-- Ejecutar en phpMyAdmin: http://localhost:8080
-- Base de datos: php_js_mysql_lab
-- ============================================================================
-- Asegúrate de tener los datos del seed cargados primero.
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: INNER JOIN — Solo filas con coincidencia en AMBAS tablas
-- ════════════════════════════════════════════════════════════════════════════
-- INNER JOIN devuelve solo las filas que tienen coincidencia en ambas tablas.
-- Si un producto no tiene categoría (o viceversa), NO aparece.
-- ✅ RESULTADO: productos con su categoría (solo los que tienen categoría válida)

SELECT
    p.id,
    p.name        AS producto,
    p.price       AS precio,
    c.name        AS categoria
FROM products p
INNER JOIN categories c ON p.category_id = c.id
ORDER BY c.name, p.price DESC
LIMIT 10;

-- 👉 MODIFICA: cambia INNER JOIN por LEFT JOIN y observa la diferencia

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: LEFT JOIN — Todas las filas de la tabla izquierda
-- ════════════════════════════════════════════════════════════════════════════
-- LEFT JOIN devuelve TODOS los registros de la tabla izquierda (FROM),
-- y los datos coincidentes de la tabla derecha (NULL si no hay coincidencia).
-- Útil para: "todos los usuarios, hayan comprado o no"

SELECT
    u.id,
    u.name      AS usuario,
    u.email,
    COUNT(o.id) AS total_pedidos,
    COALESCE(SUM(o.total), 0) AS gasto_total
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
WHERE u.deleted_at IS NULL
GROUP BY u.id, u.name, u.email
ORDER BY gasto_total DESC;

-- ✅ RESULTADO: usuarios sin pedidos aparecen con total_pedidos=0

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: RIGHT JOIN — Todas las filas de la tabla derecha
-- ════════════════════════════════════════════════════════════════════════════
-- RIGHT JOIN es el mirror de LEFT JOIN.
-- Devuelve TODOS los registros de la tabla derecha.
-- 💡 Tip: RIGHT JOIN es raro — en general se prefiere LEFT JOIN invirtiendo tablas.

SELECT
    c.name     AS categoria,
    p.name     AS producto,
    p.price
FROM products p
RIGHT JOIN categories c ON p.category_id = c.id
ORDER BY c.name, p.price DESC;

-- ✅ RESULTADO: categorías sin productos aparecen con NULL en las columnas de products

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: Self JOIN — Una tabla unida consigo misma
-- ════════════════════════════════════════════════════════════════════════════
-- Útil para: jerarquías (categorías padre-hijo), cadenas de mando, etc.

SELECT
    c.name        AS categoria,
    parent.name   AS categoria_padre
FROM categories c
LEFT JOIN categories parent ON c.parent_id = parent.id
ORDER BY categoria_padre, categoria;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 5: JOIN múltiple — orders → users, order_items → products
-- ════════════════════════════════════════════════════════════════════════════
-- 👉 MODIFICA: cambia el filtro de status para ver diferentes pedidos

SELECT
    o.id         AS pedido_id,
    u.name       AS cliente,
    o.status,
    o.total,
    p.name       AS producto,
    oi.quantity  AS cantidad,
    oi.unit_price AS precio_unitario,
    (oi.quantity * oi.unit_price) AS subtotal
FROM orders o
INNER JOIN users u         ON o.user_id    = u.id
INNER JOIN order_items oi  ON oi.order_id  = o.id
INNER JOIN products p      ON oi.product_id = p.id
WHERE o.status = 'delivered'  -- 👉 MODIFICA: 'pending', 'shipped', 'cancelled'
ORDER BY o.id, p.name
LIMIT 20;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 6: CROSS JOIN — Producto cartesiano
-- ════════════════════════════════════════════════════════════════════════════
-- CROSS JOIN devuelve el producto cartesiano: todas las combinaciones posibles.
-- Con 5 categorías x 12 productos = 60 filas (¡sin WHERE!).
-- Útil para: generar matrices de combinaciones, fechas, etc.
-- ⚠️ CUIDADO: sin WHERE puede ser muy lento con tablas grandes.

SELECT
    c.name AS categoria,
    u.role AS rol,
    COUNT(*) AS productos_x_rol
FROM categories c
CROSS JOIN (SELECT DISTINCT role FROM users) u
GROUP BY c.name, u.role
LIMIT 15;
