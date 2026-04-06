<?php

declare(strict_types=1);

/**
 * LAB 05: Excepciones personalizadas en PHP
 * ==========================================
 * Ejecutar: php labs/php/05_exceptions.php
 *
 * Cubre: jerarquía de excepciones, try/catch/finally, exception chaining,
 * re-lanzar excepciones, handler global, SPL exceptions.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// Jerarquía de excepciones del proyecto
// ════════════════════════════════════════════════════════════════════════════

// Excepción base del proyecto
class AppException extends \RuntimeException {}

// Errores de dominio/negocio
class DomainException extends AppException {}
class ValidationException extends DomainException {
    public function __construct(private array $errors, string $message = 'Validation failed') {
        parent::__construct($message);
    }
    public function getErrors(): array { return $this->errors; }
}

// Errores de infraestructura
class InfrastructureException extends AppException {}
class DatabaseException extends InfrastructureException {}
class ConnectionException extends DatabaseException {}

// Errores de negocio específicos
class InsufficientStockException extends DomainException {
    public function __construct(string $product, int $requested, int $available) {
        parent::__construct(
            "Stock insuficiente para '{$product}': solicitado {$requested}, disponible {$available}"
        );
    }
}

class OrderNotFoundException extends DomainException {
    public function __construct(int $orderId) {
        parent::__construct("Pedido #{$orderId} no encontrado.");
    }
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: try/catch/finally básico');
// ════════════════════════════════════════════════════════════════════════════
// finally SIEMPRE se ejecuta, incluso si se lanza excepción o se hace return.
// ✅ RESULTADO: finally es ideal para liberar recursos (BD, archivos).

function riskyOperation(int $mode): string {
    // Simular apertura de recurso
    echo "    [recurso] Abierto\n";
    try {
        if ($mode === 1) {
            throw new \InvalidArgumentException("Modo inválido: {$mode}");
        }
        if ($mode === 2) {
            throw new DatabaseException("Conexión perdida");
        }
        return "Operación exitosa";
    } catch (\InvalidArgumentException $e) {
        echo "    [catch] InvalidArgument: {$e->getMessage()}\n";
        return "Fallback por argumento inválido";
    } catch (DatabaseException $e) {
        echo "    [catch] Database: {$e->getMessage()}\n";
        throw new ConnectionException("No se pudo reconectar", previous: $e);
    } finally {
        // Este bloque SIEMPRE se ejecuta
        echo "    [finally] Recurso cerrado (siempre)\n";
    }
}

echo "\n  Modo 0 (éxito):\n";
$result = riskyOperation(0);
echo "  Resultado: {$result}\n";

echo "\n  Modo 1 (InvalidArgument):\n";
$result = riskyOperation(1);
echo "  Resultado: {$result}\n";

echo "\n  Modo 2 (DatabaseException → re-throw como ConnectionException):\n";
try {
    riskyOperation(2);
} catch (ConnectionException $e) {
    echo "  Capturado: {$e->getMessage()}\n";
    echo "  Causado por: {$e->getPrevious()->getMessage()}\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Excepciones de validación con array de errores');
// ════════════════════════════════════════════════════════════════════════════

function createUser(array $data): array {
    $errors = [];

    if (empty($data['name'])) {
        $errors['name'][] = 'El nombre es obligatorio';
    } elseif (strlen($data['name']) < 2) {
        $errors['name'][] = 'El nombre debe tener al menos 2 caracteres';
    }

    if (empty($data['email'])) {
        $errors['email'][] = 'El email es obligatorio';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'][] = 'El email no es válido';
    }

    if (empty($data['password'])) {
        $errors['password'][] = 'La contraseña es obligatoria';
    } elseif (strlen($data['password']) < 8) {
        $errors['password'][] = 'La contraseña debe tener al menos 8 caracteres';
    }

    if (!empty($errors)) {
        throw new ValidationException($errors);
    }

    return ['id' => rand(1, 100), ...$data, 'created_at' => date('Y-m-d H:i:s')];
}

echo "\n  Validación con múltiples errores:\n\n";

// 👉 MODIFICA: cambia los datos para ver distintos errores
$badData = ['name' => 'A', 'email' => 'not-an-email', 'password' => '123'];

try {
    createUser($badData);
} catch (ValidationException $e) {
    echo "  ❌ Errores de validación:\n";
    foreach ($e->getErrors() as $field => $messages) {
        foreach ($messages as $msg) {
            echo "    - {$field}: {$msg}\n";
        }
    }
}

$goodData = ['name' => 'Carlos García', 'email' => 'carlos@lab.test', 'password' => 'secure123'];
try {
    $user = createUser($goodData);
    echo "\n  ✅ Usuario creado: ID={$user['id']}, nombre={$user['name']}\n";
} catch (ValidationException $e) {
    echo "  ❌ No debería llegar aquí\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Jerarquía de excepciones — captura polimórfica');
// ════════════════════════════════════════════════════════════════════════════
// Puedes capturar una excepción base para manejar toda una jerarquía.
// ✅ RESULTADO: el catch más específico tiene prioridad.

function processOrder(int $orderId, string $product, int $qty): void {
    $exceptions = [
        1 => new OrderNotFoundException($orderId),
        2 => new InsufficientStockException($product, $qty, 2),
        3 => new DatabaseException("Timeout al guardar"),
    ];

    if (isset($exceptions[$orderId % 4])) {
        throw $exceptions[$orderId % 4];
    }

    echo "  ✅ Pedido #{$orderId} procesado: {$qty}x {$product}\n";
}

echo "\n  Procesando pedidos (jerarquía de excepciones):\n\n";
$orders = [
    [5, 'iPhone 15', 1],  // 5%4=1 → OrderNotFoundException
    [6, 'MacBook', 5],    // 6%4=2 → InsufficientStockException
    [7, 'iPad', 1],       // 7%4=3 → DatabaseException
    [8, 'AirPods', 2],    // 8%4=0 → éxito
];

foreach ($orders as [$orderId, $product, $qty]) {
    try {
        processOrder($orderId, $product, $qty);
    } catch (OrderNotFoundException $e) {
        echo "  🔍 [OrderNotFound] {$e->getMessage()}\n";
    } catch (InsufficientStockException $e) {
        echo "  📦 [InsufficientStock] {$e->getMessage()}\n";
    } catch (InfrastructureException $e) {
        // Captura DatabaseException, ConnectionException, etc.
        echo "  🔧 [Infrastructure] {$e->getMessage()} — reintentando...\n";
    } catch (AppException $e) {
        // Fallback para cualquier excepción del proyecto
        echo "  ⚠️  [AppException] {$e->getMessage()}\n";
    }
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: Exception Chaining (causas encadenadas)');
// ════════════════════════════════════════════════════════════════════════════
// El tercer argumento de Exception es $previous (la causa original).
// Útil para preservar la causa raíz al lanzar excepciones de alto nivel.

function connectToDatabase(string $host): \PDO {
    throw new \PDOException("SQLSTATE[HY000] [2002] Connection refused");
}

function getProductFromDb(int $id): array {
    try {
        $pdo = connectToDatabase('db.server.com');
        // ... query
        return [];
    } catch (\PDOException $e) {
        // Re-lanzar como excepción de dominio con la causa original
        throw new DatabaseException(
            "No se pudo obtener el producto #{$id}",
            code: 500,
            previous: $e  // ← encadenamos la excepción original
        );
    }
}

echo "\n  Exception chaining (rastrear la causa raíz):\n\n";
try {
    getProductFromDb(42);
} catch (DatabaseException $e) {
    echo "  Excepción de alto nivel: {$e->getMessage()}\n";

    // Recorrer la cadena de causas
    $cause = $e->getPrevious();
    $depth = 1;
    while ($cause !== null) {
        echo "  " . str_repeat('  ', $depth) . "↳ Causada por: [{$cause->getCode()}] {$cause->getMessage()}\n";
        $cause = $cause->getPrevious();
        $depth++;
    }
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Handler global de excepciones no capturadas');
// ════════════════════════════════════════════════════════════════════════════
// set_exception_handler() captura excepciones no manejadas.
// En producción: loggear el error y mostrar página de error genérica.

$previousHandler = set_exception_handler(function(\Throwable $e) {
    echo "\n  🔴 EXCEPCIÓN NO CAPTURADA:\n";
    echo "  Tipo:    " . get_class($e) . "\n";
    echo "  Mensaje: {$e->getMessage()}\n";
    echo "  En:      {$e->getFile()}:{$e->getLine()}\n";
    // En producción: loggear a Sentry/Datadog y mostrar 500.html
});

echo "\n  Ejemplo de handler global (activado pero no disparado aquí).\n";
echo "  El handler global se activa cuando NO hay try/catch.\n";

// Restaurar handler anterior
set_exception_handler($previousHandler);

echo "\n\n  ✅ Lab 05 completado. Las excepciones bien diseñadas hacen el código más robusto.\n\n";
