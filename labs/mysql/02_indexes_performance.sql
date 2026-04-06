-- ============================================================================
-- LAB 02 MySQL: Índices y Rendimiento con EXPLAIN
-- ============================================================================
-- Ejecutar en phpMyAdmin: http://localhost:8080
-- ============================================================================

USE php_js_mysql_lab;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: EXPLAIN básico — leer el plan de ejecución
-- ════════════════════════════════════════════════════════════════════════════
-- EXPLAIN muestra cómo MySQL ejecutará la query SIN ejecutarla realmente.
-- Columnas importantes:
--   type:   ALL=full scan (malo), ref/eq_ref=índice (bueno), const=perfecto
--   key:    índice usado (NULL = sin índice = full table scan)
--   rows:   estimación de filas a examinar (menos es mejor)
--   Extra:  'Using index' = covering index, 'Using filesort' = sort sin índice

EXPLAIN SELECT * FROM users WHERE email = 'admin@lab.test';
-- ✅ RESULTADO: key=uk_users_email, type=const, rows=1 (muy eficiente)

EXPLAIN SELECT * FROM users WHERE name = 'Carlos García';
-- ❌ RESULTADO: key=NULL, type=ALL (full table scan, sin índice en name)
-- 👉 MODIFICA: añade índice abajo y vuelve a ejecutar

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: Tabla de prueba sin índices
-- ════════════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS perf_test;
CREATE TABLE perf_test (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    category   VARCHAR(50) NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar 1000 filas de prueba
DROP PROCEDURE IF EXISTS fill_perf_test;
DELIMITER $$
CREATE PROCEDURE fill_perf_test()
BEGIN
    DECLARE i INT DEFAULT 1;
    WHILE i <= 1000 DO
        INSERT INTO perf_test (user_id, category, amount)
        VALUES (
            FLOOR(RAND() * 20) + 1,
            ELT(FLOOR(RAND()*5)+1, 'A','B','C','D','E'),
            ROUND(RAND() * 1000, 2)
        );
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL fill_perf_test();

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: Query sin índice vs con índice
-- ════════════════════════════════════════════════════════════════════════════

-- Sin índice:
EXPLAIN SELECT * FROM perf_test WHERE user_id = 5;
-- ✅ RESULTADO esperado: type=ALL, key=NULL, rows≈1000 (full scan)

-- Añadir índice:
ALTER TABLE perf_test ADD INDEX idx_user_id (user_id);

-- Con índice:
EXPLAIN SELECT * FROM perf_test WHERE user_id = 5;
-- ✅ RESULTADO esperado: type=ref, key=idx_user_id, rows≈50 (mucho mejor)

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: Índice compuesto y "Leftmost Prefix Rule"
-- ════════════════════════════════════════════════════════════════════════════
-- Un índice compuesto (a, b, c) se puede usar para queries en:
--   ✅ (a), (a, b), (a, b, c) — usando el prefijo izquierdo
--   ❌ (b), (c), (b, c)       — NO empieza por 'a'

ALTER TABLE perf_test ADD INDEX idx_cat_user_amt (category, user_id, amount);

-- ✅ Usa el índice (comienza por 'category')
EXPLAIN SELECT * FROM perf_test WHERE category = 'A';

-- ✅ Usa el índice (category + user_id)
EXPLAIN SELECT * FROM perf_test WHERE category = 'A' AND user_id = 5;

-- ❌ NO usa el índice compuesto (no empieza por category)
-- 👉 MODIFICA: observa cómo cambia el plan
EXPLAIN SELECT * FROM perf_test WHERE user_id = 5;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 5: Covering Index
-- ════════════════════════════════════════════════════════════════════════════
-- Un Covering Index contiene TODOS los campos que necesita la query.
-- MySQL puede responder la query leyendo solo el índice (Extra: 'Using index').

-- Este SELECT solo necesita category y amount → el índice idx_cat_user_amt lo cubre
EXPLAIN SELECT category, amount FROM perf_test WHERE category = 'B';
-- ✅ RESULTADO esperado: Extra='Using index' (no lee la tabla, solo el índice)

EXPLAIN SELECT * FROM perf_test WHERE category = 'B';
-- ❌ RESULTADO: necesita leer la tabla (SELECT * tiene campos no en el índice)

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 6: Funciones en WHERE destruyen el índice
-- ════════════════════════════════════════════════════════════════════════════

-- ❌ NO HAGAS ESTO: aplicar función a la columna indexada
EXPLAIN SELECT * FROM users WHERE YEAR(created_at) = 2024;
-- RESULTADO: full scan aunque created_at tenga índice

-- ✅ MEJOR ASÍ: comparar rangos directamente
EXPLAIN SELECT * FROM users
WHERE created_at >= '2024-01-01' AND created_at < '2025-01-01';

-- Limpieza
DROP TABLE IF EXISTS perf_test;
DROP PROCEDURE IF EXISTS fill_perf_test;
