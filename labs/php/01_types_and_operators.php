<?php

declare(strict_types=1);

/**
 * LAB 01: Tipos y Operadores en PHP 8.x
 * ======================================
 * Ejecutar: php labs/php/01_types_and_operators.php
 *
 * Este lab explora las diferencias entre == y ===, type juggling,
 * Union Types, Enums, match, nullable types y named arguments.
 */

// ⚙️ CONFIGURACIÓN — 👉 MODIFICA estos valores para cambiar el comportamiento
const SHOW_DETAILS = true;   // 👉 MODIFICA: false para output más compacto
const SHOW_TYPES   = true;   // 👉 MODIFICA: false para ocultar tipos

// ── Helpers ──────────────────────────────────────────────────────────────────
function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n";
    echo "  {$title}\n";
    echo str_repeat('═', 60) . "\n";
}

function show(string $label, mixed $value, bool $showType = SHOW_TYPES): void {
    $type = $showType ? ' (' . gettype($value) . ')' : '';
    $display = is_bool($value) ? ($value ? 'true' : 'false') : var_export($value, true);
    echo "  {$label}: {$display}{$type}\n";
}

function compare(mixed $a, mixed $b): void {
    $loose  = $a == $b  ? '✅ true'  : '❌ false';
    $strict = $a === $b ? '✅ true'  : '❌ false';
    $aType  = gettype($a);
    $bType  = gettype($b);
    printf("  %-20s == %-20s → %s\n", var_export($a, true)." ({$aType})", var_export($b, true)." ({$bType})", $loose);
    printf("  %-20s === %-20s → %s\n\n", var_export($a, true)." ({$aType})", var_export($b, true)." ({$bType})", $strict);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: == vs === (Comparación suelta vs estricta)');
// ════════════════════════════════════════════════════════════════════════════
// == compara valores después de conversión de tipos (type coercion)
// === compara valores Y tipos sin conversión
// ❌ NO HAGAS ESTO: usar == cuando quieras comparar tipos distintos
// ✅ MEJOR ASÍ: usar === siempre que sea posible

echo "\n  Las 12 comparaciones más sorprendentes de PHP:\n\n";
compare(0,      "0");         // ✅ RESULTADO: == true, === false
compare(0,      "");          // PHP 8: == false (cambio de PHP 7!)
compare(0,      "a");         // PHP 8: == false (cambio de PHP 7!)
compare("1",    "01");        // == true (ambos son "1" numéricamente)
compare("10",   "1e1");       // == true ("1e1" = 10)
compare(100,    "1e2");       // == true
compare(null,   false);       // == true
compare(null,   0);           // == true
compare("",     null);        // == true
compare([],     false);       // == true
compare("0",    false);       // == true
compare("0",    null);        // == false (¡sorpresa!)

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Type Juggling (Conversión implícita de tipos)');
// ════════════════════════════════════════════════════════════════════════════
// PHP convierte tipos automáticamente según el contexto
// 👉 MODIFICA: cambia los valores y observa las conversiones

$valores = [
    "42",       // string → int en contexto aritmético
    "3.14",     // string → float
    "0x1A",     // string hexadecimal (NO se convierte en PHP 7+)
    true,       // bool → int: true=1, false=0
    false,
    null,       // null → 0, "", false
    [],         // array vacío → false en contexto booleano
    [1, 2, 3],  // array no vacío → true
];

echo "\n  Conversiones implícitas:\n\n";
foreach ($valores as $v) {
    $asInt    = (int)$v;
    $asFloat  = (float)$v;
    $asBool   = (bool)$v ? 'true' : 'false';
    $asString = (string)(is_array($v) ? '[Array]' : $v);
    printf("  %-15s → int:%-5s float:%-8s bool:%-6s string:'%s'\n",
        var_export($v, true), $asInt, $asFloat, $asBool, $asString);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Union Types (PHP 8.0+)');
// ════════════════════════════════════════════════════════════════════════════
// Union Types permiten declarar múltiples tipos posibles para un parámetro
// Sintaxis: int|string, float|null, etc.
// Con strict_types=1, PHP valida los tipos en llamadas a funciones

function procesar(int|float|string $valor): string {
    return match(true) {
        is_int($valor)   => "Entero: {$valor}",
        is_float($valor) => "Decimal: {$valor}",
        is_string($valor) => "Texto: '{$valor}'",
    };
}

echo "\n  Union Types int|float|string:\n\n";
$ejemplos = [42, 3.14, "hola", 0, -5, 1.0];
foreach ($ejemplos as $ej) {
    // 👉 MODIFICA: añade más valores para probar
    echo "  " . procesar($ej) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: Enums PHP 8.1+ (con métodos)');
// ════════════════════════════════════════════════════════════════════════════
// Los Enums garantizan que un valor solo pueda ser uno de los definidos.
// Backed Enums tienen un valor escalar (int o string) asociado.
// ✅ RESULTADO: Los Enums son typesafe, no se pueden crear valores arbitrarios.

// 👉 MODIFICA: añade más casos al enum
enum OrderStatus: string {
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Shipped   = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string {
        return match($this) {
            self::Pending   => '⏳ Pendiente',
            self::Confirmed => '✅ Confirmado',
            self::Shipped   => '🚚 Enviado',
            self::Delivered => '📦 Entregado',
            self::Cancelled => '❌ Cancelado',
        };
    }

    public function canTransitionTo(self $next): bool {
        return match($this) {
            self::Pending   => in_array($next, [self::Confirmed, self::Cancelled]),
            self::Confirmed => in_array($next, [self::Shipped, self::Cancelled]),
            self::Shipped   => $next === self::Delivered,
            default         => false,
        };
    }
}

echo "\n  Estados del pedido:\n\n";
foreach (OrderStatus::cases() as $status) {
    echo "  OrderStatus::{$status->name} = '{$status->value}' → {$status->label()}\n";
}

echo "\n  Transiciones válidas:\n\n";
$current = OrderStatus::Pending;
$transitions = [OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Cancelled];
foreach ($transitions as $next) {
    $can = $current->canTransitionTo($next) ? '✅ Puede' : '❌ No puede';
    echo "  {$current->label()} → {$next->label()}: {$can}\n";
}

// Obtener Enum desde valor (backed enum)
$fromValue = OrderStatus::from('shipped');
echo "\n  OrderStatus::from('shipped') = {$fromValue->label()}\n";

$maybeEnum = OrderStatus::tryFrom('unknown'); // No lanza excepción
echo "  OrderStatus::tryFrom('unknown') = " . ($maybeEnum?->label() ?? 'null') . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: match vs switch');
// ════════════════════════════════════════════════════════════════════════════
// match usa comparación estricta (===), switch usa comparación suelta (==)
// match es una expresión (devuelve valor), switch es una sentencia
// match lanza UnhandledMatchError si no hay rama → más seguro

$httpCode = 404; // 👉 MODIFICA: prueba con 200, 301, 404, 500, etc.

// ❌ NO HAGAS ESTO con switch:
$switchResult = '';
switch ($httpCode) {
    case 200: $switchResult = 'OK'; break;
    case 404: $switchResult = 'Not Found'; break;
    case 500: $switchResult = 'Server Error'; break;
    default:  $switchResult = 'Unknown';
}

// ✅ MEJOR ASÍ con match:
$matchResult = match($httpCode) {
    200, 201, 204 => '✅ Success',
    301, 302      => '↪️  Redirect',
    400           => '❌ Bad Request',
    401           => '🔒 Unauthorized',
    403           => '🚫 Forbidden',
    404           => '🔍 Not Found',
    422           => '⚠️  Unprocessable',
    500           => '💥 Server Error',
    default       => '❓ Unknown',
};

echo "\n  HTTP {$httpCode}:\n";
echo "  switch → {$switchResult}\n";
echo "  match  → {$matchResult}\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: Nullable types y operador ?? (Null coalescing)');
// ════════════════════════════════════════════════════════════════════════════

function getUserName(?int $userId): ?string {
    $users = [1 => 'Ana', 2 => 'Carlos', 3 => 'María'];
    return $users[$userId] ?? null; // ?? devuelve el lado derecho si el izquierdo es null
}

echo "\n  Nullable types y ??:\n\n";

$userId = null; // 👉 MODIFICA: prueba con 1, 2, 3, 99
$name = getUserName($userId) ?? 'Usuario desconocido';
echo "  getUserName(null) ?? 'desconocido' = '{$name}'\n";

$name2 = getUserName(2) ?? 'Usuario desconocido';
echo "  getUserName(2) ?? 'desconocido'    = '{$name2}'\n";

// ??= (null coalescing assignment, PHP 7.4+)
$config = [];
$config['timeout'] ??= 30; // Solo asigna si es null o no existe
echo "\n  config['timeout'] ??= 30 → {$config['timeout']}\n";
$config['timeout'] ??= 999; // Ya tiene valor, no cambia
echo "  config['timeout'] ??= 999 → {$config['timeout']} (no cambió)\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 7: Named Arguments (PHP 8.0+)');
// ════════════════════════════════════════════════════════════════════════════
// Named arguments permiten pasar argumentos por nombre en lugar de posición.
// Ventaja: legibilidad, puedes omitir parámetros opcionales intermedios.

function crearUsuario(
    string $name,
    string $email,
    string $role    = 'user',
    bool   $active  = true,
    int    $age     = 0,
): string {
    $activeStr = $active ? 'activo' : 'inactivo';
    return "{$name} ({$email}) | rol:{$role} | {$activeStr} | edad:{$age}";
}

echo "\n  Named Arguments:\n\n";

// Sin named arguments: tienes que recordar el orden
$u1 = crearUsuario('Ana', 'ana@lab.test', 'admin', true, 30);
echo "  Posicional: {$u1}\n";

// ✅ Con named arguments: más claro y puedes saltar parámetros
$u2 = crearUsuario(
    name:  'Carlos',
    email: 'carlos@lab.test',
    age:   25,   // Saltamos role y active (usan sus defaults)
    // 👉 MODIFICA: añade role: 'admin' para ver el efecto
);
echo "  Named args: {$u2}\n";

// Named args también funcionan con funciones internas de PHP
$array = [3, 1, 4, 1, 5, 9, 2, 6];
$sliced = array_slice(array: $array, offset: 2, length: 3, preserve_keys: true);
echo "\n  array_slice con named args: " . implode(', ', $sliced) . "\n";

echo "\n\n  ✅ Lab 01 completado. Modifica las variables en ⚙️ CONFIGURACIÓN y vuelve a ejecutar.\n\n";
