<?php

declare(strict_types=1);

/**
 * LAB 04: Closures y Generators en PHP
 * =====================================
 * Ejecutar: php labs/php/04_closures_and_generators.php
 *
 * Cubre: closures, arrow functions, captura de variables, memoization,
 * pipeline funcional, generators vs arrays (memoria), Fibonacci, yield send.
 */

// ⚙️ CONFIGURACIÓN
const GENERATOR_SIZE = 10000; // 👉 MODIFICA: 100, 10000, 1000000 (observa la memoria)

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Closure vs Arrow Function');
// ════════════════════════════════════════════════════════════════════════════
// Closure: function() use (...) {} — captura explícita de variables
// Arrow function: fn() => expr — captura automática del scope externo

$multiplier = 3; // Variable del scope externo

// Closure clásica: debe capturar $multiplier explícitamente con use
$closureMultiply = function(int $n) use ($multiplier): int {
    return $n * $multiplier;
};

// Arrow function (PHP 7.4+): captura automáticamente $multiplier
// Solo para expresiones de una línea — no puede tener múltiples statements
$arrowMultiply = fn(int $n): int => $n * $multiplier;

$numbers = [1, 2, 3, 4, 5];
echo "\n  Closure:      " . implode(', ', array_map($closureMultiply, $numbers)) . "\n";
echo "  Arrow fn:     " . implode(', ', array_map($arrowMultiply, $numbers)) . "\n";

// 👉 MODIFICA: cambia $multiplier y observa si afecta a las funciones ya creadas
$multiplier = 10; // Cambiar DESPUÉS de crear las funciones
echo "  \$multiplier cambió a 10:\n";
echo "  Closure (use por valor): " . $closureMultiply(5) . " (no cambia, capturó por VALOR)\n";
echo "  Arrow fn (captura auto): " . $arrowMultiply(5) . " (no cambia, también por VALOR)\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Captura por valor vs por referencia');
// ════════════════════════════════════════════════════════════════════════════

$counter = 0;

$incrementByValue = function() use ($counter): int {
    return ++$counter; // Modifica la copia, no el original
};

$incrementByRef = function() use (&$counter): int {
    return ++$counter; // Modifica el original
};

echo "\n  Counter inicial: {$counter}\n";
echo "  Llamada por valor: {$incrementByValue()}\n";
echo "  Counter después de llamada por valor: {$counter} (no cambió)\n\n";
echo "  Llamada por referencia: {$incrementByRef()}\n";
echo "  Counter después de llamada por referencia: {$counter} (¡cambió!)\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Memoization con Closure');
// ════════════════════════════════════════════════════════════════════════════
// Memoization: cachear el resultado de funciones puras para evitar recálculo.
// ✅ RESULTADO: las llamadas subsecuentes son instantáneas.

function memoize(callable $fn): \Closure {
    $cache = [];
    return function() use ($fn, &$cache): mixed {
        $args = func_get_args();
        $key  = serialize($args);
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = $fn(...$args);
        }
        return $cache[$key];
    };
}

// Fibonacci recursivo sin memoize (exponencial O(2^n))
function fib(int $n): int {
    if ($n <= 1) return $n;
    return fib($n - 1) + fib($n - 2);
}

$memoFib = memoize('fib');

echo "\n  Comparación de tiempo (n=30):\n\n";
$start = microtime(true);
$result1 = fib(30);
$t1 = round((microtime(true) - $start) * 1000, 2);
echo "  Sin memoize: fib(30) = {$result1} ({$t1}ms)\n";

$start = microtime(true);
$result2 = $memoFib(30);
$t2 = round((microtime(true) - $start) * 1000, 4);
echo "  Con memoize (1ª vez): fib(30) = {$result2} ({$t2}ms)\n";

$start = microtime(true);
$result3 = $memoFib(30);
$t3 = round((microtime(true) - $start) * 1000, 4);
echo "  Con memoize (2ª vez): fib(30) = {$result3} ({$t3}ms) ← cache!\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: Pipeline Funcional');
// ════════════════════════════════════════════════════════════════════════════
// Un pipeline aplica una serie de transformaciones en secuencia.
// Cada función recibe el resultado de la anterior.
// 👉 MODIFICA: añade más pasos al pipeline

function pipeline(mixed $value, callable ...$fns): mixed {
    return array_reduce($fns, fn($carry, $fn) => $fn($carry), $value);
}

$result = pipeline(
    "  Hola, MUNDO! Este es un TEXTO de prueba.  ",
    'trim',                                          // eliminar espacios
    'strtolower',                                    // minúsculas
    fn($s) => preg_replace('/[^a-z0-9 ]/', '', $s), // solo alfanumérico
    fn($s) => explode(' ', $s),                      // split en palabras
    fn($arr) => array_filter($arr),                  // eliminar vacíos
    fn($arr) => array_unique($arr),                  // sin duplicados
    fn($arr) => implode('-', $arr),                  // slug
);

echo "\n  Pipeline de transformación de texto:\n";
echo "  Input:  '  Hola, MUNDO! Este es un TEXTO de prueba.  '\n";
echo "  Output: '{$result}'\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Generator vs Array — comparativa de memoria');
// ════════════════════════════════════════════════════════════════════════════
// Un Generator usa yield para devolver valores uno a uno sin cargar todo en memoria.
// ✅ RESULTADO: el Generator usa ~O(1) de memoria, el array usa ~O(n).

// 👉 MODIFICA: GENERATOR_SIZE = 1000000 para ver la diferencia dramática

function generateRange(int $start, int $end): \Generator {
    for ($i = $start; $i <= $end; $i++) {
        yield $i; // Pausa la ejecución y devuelve $i
    }
    // La ejecución continúa desde aquí en el próximo next()
}

// Versión con array (carga TODO en memoria)
$memBefore = memory_get_usage();
$array = range(1, GENERATOR_SIZE);
$sumArray = array_sum($array);
$memArray = memory_get_usage() - $memBefore;

// Versión con generator (O(1) de memoria)
$memBefore = memory_get_usage();
$generator = generateRange(1, GENERATOR_SIZE);
$sumGen = 0;
foreach ($generator as $value) {
    $sumGen += $value;
}
$memGen = memory_get_usage() - $memBefore;

echo "\n  Suma de 1 a " . GENERATOR_SIZE . " (" . number_format(GENERATOR_SIZE) . " números):\n\n";
echo "  Array:     suma=" . number_format($sumArray) . " | memoria=" . number_format($memArray) . " bytes\n";
echo "  Generator: suma=" . number_format($sumGen) . " | memoria=" . number_format($memGen) . " bytes\n";
echo "\n  Factor de ahorro: " . round($memArray / max($memGen, 1)) . "x menos memoria con Generator\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: Generator de Fibonacci infinito');
// ════════════════════════════════════════════════════════════════════════════
// Un Generator puede ser infinito porque solo calcula el siguiente valor cuando se pide.
// ❌ NO HAGAS ESTO con arrays: range(0, INF) → OutOfMemoryError

function fibonacciGenerator(): \Generator {
    [$a, $b] = [0, 1];
    while (true) { // Loop infinito — OK con Generator, ❌ con array
        yield $a;
        [$a, $b] = [$b, $a + $b];
    }
}

$limit = 15; // 👉 MODIFICA: aumenta el límite para ver más términos
$fib   = fibonacciGenerator();
$terms = [];

for ($i = 0; $i < $limit; $i++) {
    $terms[] = $fib->current();
    $fib->next();
}

echo "\n  Primeros {$limit} números de Fibonacci:\n";
echo "  " . implode(', ', $terms) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 7: yield con send() — Coroutines');
// ════════════════════════════════════════════════════════════════════════════
// send() permite enviar valores AL generator (comunicación bidireccional).
// Esto convierte el generator en una coroutine.

function accumulator(): \Generator {
    $total = 0;
    while (true) {
        $value = yield $total; // yield devuelve $total, recibe $value de send()
        if ($value === null) break;
        $total += $value;
    }
}

$acc = accumulator();
$acc->current(); // Iniciar el generator (llega al primer yield)

echo "\n  Coroutine — Acumulador con send():\n\n";
$values = [10, 25, 5, 100, 50]; // 👉 MODIFICA: cambia los valores
foreach ($values as $v) {
    $total = $acc->send($v);
    echo "  send({$v}) → total acumulado: {$total}\n";
}

echo "\n\n  ✅ Lab 04 completado. Generators: misma potencia, fracción de la memoria.\n\n";
