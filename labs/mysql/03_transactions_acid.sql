-- ============================================================================
-- LAB 03 MySQL: Transacciones ACID
-- ============================================================================
-- Ejecutar en phpMyAdmin: http://localhost:8080
-- ============================================================================

USE php_js_mysql_lab;

-- ACID significa:
--   A — Atomicidad: todo o nada (si falla una operación, se revierten todas)
--   C — Consistencia: la BD siempre va de un estado válido a otro válido
--   I — Aislamiento: las transacciones concurrentes no interfieren entre sí
--   D — Durabilidad: una vez COMMIT, los datos persisten incluso tras crash

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 1: Transferencia bancaria (COMMIT exitoso)
-- ════════════════════════════════════════════════════════════════════════════

-- Tabla de cuentas bancarias para el experimento
DROP TABLE IF EXISTS bank_accounts;
CREATE TABLE bank_accounts (
    id      INT PRIMARY KEY AUTO_INCREMENT,
    owner   VARCHAR(100),
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CHECK (balance >= 0)  -- Constraint: saldo no puede ser negativo
) ENGINE=InnoDB;

INSERT INTO bank_accounts (owner, balance) VALUES
('Ana García',   1000.00),
('Carlos López',  500.00);

SELECT 'ANTES de la transferencia:' AS estado;
SELECT id, owner, balance FROM bank_accounts;

-- Transferencia de Ana → Carlos: €200
START TRANSACTION;

    UPDATE bank_accounts SET balance = balance - 200 WHERE id = 1;  -- Debitar Ana
    UPDATE bank_accounts SET balance = balance + 200 WHERE id = 2;  -- Acreditar Carlos

    -- Verificación intermedia (dentro de la transacción)
    SELECT 'DENTRO de la transacción:' AS estado;
    SELECT id, owner, balance FROM bank_accounts;

COMMIT;  -- ✅ Todo bien → confirmar cambios

SELECT 'DESPUÉS del COMMIT:' AS estado;
SELECT id, owner, balance FROM bank_accounts;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 2: Transferencia fallida (ROLLBACK)
-- ════════════════════════════════════════════════════════════════════════════

SELECT 'Intentando transferir €600 (más de lo disponible):' AS estado;

START TRANSACTION;

    UPDATE bank_accounts SET balance = balance - 600 WHERE id = 2;  -- Carlos solo tiene €700
    -- Si el CHECK constraint falla, MySQL hace ROLLBACK automático
    -- Si no, hacemos ROLLBACK manual en la app

    SELECT 'Balance de Carlos durante la transacción:' AS estado;
    SELECT id, owner, balance FROM bank_accounts WHERE id = 2;

ROLLBACK;  -- ← El CHECK constraint detectó saldo negativo → ROLLBACK

SELECT 'DESPUÉS del ROLLBACK (sin cambios):' AS estado;
SELECT id, owner, balance FROM bank_accounts;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 3: SAVEPOINT — Rollback parcial
-- ════════════════════════════════════════════════════════════════════════════
-- SAVEPOINT permite hacer rollback a un punto intermedio, no al inicio.

START TRANSACTION;

    UPDATE bank_accounts SET balance = balance + 100 WHERE id = 1;  -- Ana: +100
    SAVEPOINT after_first_credit;  -- Guardar punto de restauración

    UPDATE bank_accounts SET balance = balance + 200 WHERE id = 2;  -- Carlos: +200
    SAVEPOINT after_second_credit;

    SELECT 'Balances con ambos créditos:' AS estado;
    SELECT id, owner, balance FROM bank_accounts;

    -- Decidimos deshacer solo el segundo crédito
    ROLLBACK TO SAVEPOINT after_first_credit;

    SELECT 'Después de ROLLBACK TO after_first_credit:' AS estado;
    SELECT id, owner, balance FROM bank_accounts;

COMMIT;

SELECT 'FINAL (solo primer crédito aplicado):' AS estado;
SELECT id, owner, balance FROM bank_accounts;

-- ════════════════════════════════════════════════════════════════════════════
-- EXPERIMENTO 4: Niveles de aislamiento
-- ════════════════════════════════════════════════════════════════════════════
-- Los niveles de aislamiento controlan qué puede ver una transacción
-- de los cambios no confirmados de otras transacciones.

SHOW VARIABLES LIKE 'transaction_isolation';

-- Niveles disponibles (de menor a mayor aislamiento):
-- READ UNCOMMITTED: puede leer datos no confirmados (dirty reads) → PELIGROSO
-- READ COMMITTED:   solo lee datos confirmados (no dirty reads)
-- REPEATABLE READ:  una lectura da siempre el mismo resultado (default en MySQL)
-- SERIALIZABLE:     máximo aislamiento, pero menor concurrencia

-- 👉 MODIFICA: cambia el nivel y observa el comportamiento
SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;

-- Limpieza
DROP TABLE IF EXISTS bank_accounts;
