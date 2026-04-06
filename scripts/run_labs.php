#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI Runner para el Laboratorio PHP/JS/MySQL.
 *
 * Uso:
 *   php scripts/run_labs.php --list
 *   php scripts/run_labs.php --lab=php/01
 *   php scripts/run_labs.php --lab=php/all
 *   php scripts/run_labs.php --lab=js/01
 *   php scripts/run_labs.php --test
 *   php scripts/run_labs.php --help
 */

// ── Colores ANSI ──────────────────────────────────────────────────────────────
const RESET  = "\033[0m";
const BOLD   = "\033[1m";
const RED    = "\033[0;31m";
const GREEN  = "\033[0;32m";
const YELLOW = "\033[1;33m";
const BLUE   = "\033[0;34m";
const CYAN   = "\033[0;36m";
const MAGENTA = "\033[0;35m";
const WHITE  = "\033[1;37m";

// ── Helpers ────────────────────────────────────────────────────────────────────
function colorize(string $text, string $color): string
{
    return $color . $text . RESET;
}

function printLine(string $text = ''): void
{
    echo $text . "\n";
}

function printHeader(string $title): void
{
    $line = str_repeat('═', 60);
    printLine(colorize("╔{$line}╗", CYAN));
    $padded = str_pad($title, 60, ' ', STR_PAD_BOTH);
    printLine(colorize("║{$padded}║", CYAN));
    printLine(colorize("╚{$line}╝", CYAN));
    printLine();
}

function printSection(string $title): void
{
    printLine(colorize("\n── {$title} ──", BOLD . BLUE));
}

function printSuccess(string $msg): void
{
    printLine(colorize("✅ {$msg}", GREEN));
}

function printError(string $msg): void
{
    printLine(colorize("❌ {$msg}", RED));
}

function printWarning(string $msg): void
{
    printLine(colorize("⚠️  {$msg}", YELLOW));
}

function printInfo(string $msg): void
{
    printLine(colorize("ℹ️  {$msg}", CYAN));
}

// ── Labs disponibles ───────────────────────────────────────────────────────────
const LABS = [
    'php' => [
        '01' => ['file' => 'labs/php/01_types_and_operators.php', 'title' => 'Tipos y Operadores'],
        '02' => ['file' => 'labs/php/02_oop_pillars.php',          'title' => 'Pilares de POO'],
        '03' => ['file' => 'labs/php/03_traits.php',               'title' => 'Traits'],
        '04' => ['file' => 'labs/php/04_closures_and_generators.php', 'title' => 'Closures y Generators'],
        '05' => ['file' => 'labs/php/05_exceptions.php',           'title' => 'Excepciones'],
        '06' => ['file' => 'labs/php/06_pdo_security.php',         'title' => 'PDO y Seguridad'],
        '07' => ['file' => 'labs/php/07_sessions_auth.php',        'title' => 'Sesiones y JWT'],
        '08' => ['file' => 'labs/php/08_patterns.php',             'title' => 'Patrones de Diseño'],
        '09' => ['file' => 'labs/php/09_array_functions.php',      'title' => 'Funciones de Array'],
    ],
    'mysql' => [
        '01' => ['file' => 'labs/mysql/01_joins_explained.sql',      'title' => 'JOINs'],
        '02' => ['file' => 'labs/mysql/02_indexes_performance.sql',  'title' => 'Índices y Rendimiento'],
        '03' => ['file' => 'labs/mysql/03_transactions_acid.sql',    'title' => 'Transacciones ACID'],
        '04' => ['file' => 'labs/mysql/04_normalization.sql',        'title' => 'Normalización'],
        '05' => ['file' => 'labs/mysql/05_aggregations.sql',         'title' => 'Agregaciones y Window Functions'],
        '06' => ['file' => 'labs/mysql/06_subqueries.sql',           'title' => 'Subqueries y CTEs'],
        '07' => ['file' => 'labs/mysql/07_optimization.sql',         'title' => 'Optimización'],
    ],
    'js' => [
        '01' => ['file' => 'labs/javascript/01_this_context.js',        'title' => 'Contexto this'],
        '02' => ['file' => 'labs/javascript/02_closures_scope.js',      'title' => 'Closures y Scope'],
        '03' => ['file' => 'labs/javascript/03_promises_async.js',      'title' => 'Promises y Async/Await'],
        '04' => ['file' => 'labs/javascript/04_prototype_chain.js',     'title' => 'Prototype Chain'],
        '05' => ['file' => 'labs/javascript/05_event_loop.js',          'title' => 'Event Loop'],
        '06' => ['file' => 'labs/javascript/06_destructuring_spread.js', 'title' => 'Destructuring y Spread'],
        '07' => ['file' => 'labs/javascript/07_patterns.js',            'title' => 'Patrones de Diseño'],
    ],
];

// ── Parsear argumentos ─────────────────────────────────────────────────────────
$opts = getopt('', ['list', 'lab:', 'test', 'help']);

$action = 'help';
$labArg = null;

if (isset($opts['help'])) {
    $action = 'help';
} elseif (isset($opts['list'])) {
    $action = 'list';
} elseif (isset($opts['test'])) {
    $action = 'test';
} elseif (isset($opts['lab'])) {
    $action = 'lab';
    $labArg = $opts['lab'];
}

// ── Cambiar al directorio raíz del proyecto ────────────────────────────────────
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// ── Acciones ───────────────────────────────────────────────────────────────────
match($action) {
    'help'  => showHelp(),
    'list'  => listLabs(),
    'test'  => runTests(),
    'lab'   => runLab($labArg ?? ''),
    default => showHelp(),
};

// ── Funciones ──────────────────────────────────────────────────────────────────

function showHelp(): void
{
    printHeader('🎓 CLI Runner — PHP · JavaScript · MySQL Lab');
    printLine(colorize("Uso:", BOLD));
    printLine("  php scripts/run_labs.php " . colorize("--list", CYAN) . "            Ver todos los labs");
    printLine("  php scripts/run_labs.php " . colorize("--lab=php/01", CYAN) . "       Ejecutar lab PHP #01");
    printLine("  php scripts/run_labs.php " . colorize("--lab=php/all", CYAN) . "      Ejecutar todos los labs PHP");
    printLine("  php scripts/run_labs.php " . colorize("--lab=js/01", CYAN) . "        Ejecutar lab JavaScript #01");
    printLine("  php scripts/run_labs.php " . colorize("--lab=js/all", CYAN) . "       Ejecutar todos los labs JS");
    printLine("  php scripts/run_labs.php " . colorize("--test", CYAN) . "             Ejecutar PHPUnit tests");
    printLine("  php scripts/run_labs.php " . colorize("--help", CYAN) . "             Mostrar esta ayuda");
    printLine();
    printLine(colorize("Tipos de labs:", BOLD));
    printLine("  " . colorize("php", MAGENTA) . "   — Labs PHP (ejecutables con php)");
    printLine("  " . colorize("mysql", BLUE) . " — Labs SQL (ver instrucciones para phpMyAdmin)");
    printLine("  " . colorize("js", YELLOW) . "    — Labs JavaScript (ejecutables con node)");
    printLine();
}

function listLabs(): void
{
    printHeader('📋 Labs Disponibles');

    foreach (LABS as $type => $labs) {
        $typeColor = match($type) {
            'php'   => MAGENTA,
            'mysql' => BLUE,
            'js'    => YELLOW,
            default => WHITE,
        };
        $typeLabel = match($type) {
            'php'   => '🐘 PHP',
            'mysql' => '🐬 MySQL',
            'js'    => '🟨 JavaScript',
            default => $type,
        };

        printLine(colorize("\n{$typeLabel}", $typeColor . BOLD));
        foreach ($labs as $num => $lab) {
            $numStr  = colorize("[{$type}/{$num}]", $typeColor);
            $title   = colorize($lab['title'], WHITE);
            $file    = colorize($lab['file'], CYAN);
            printLine("  {$numStr} {$title}");
            printLine("       " . colorize("└─ ", RESET) . $file);
        }
    }

    printLine();
    printInfo("Para ejecutar: php scripts/run_labs.php --lab=php/01");
}

function runLab(string $labArg): void
{
    [$type, $num] = explode('/', $labArg, 2) + ['', ''];

    if (!isset(LABS[$type])) {
        printError("Tipo de lab desconocido: '{$type}'. Usa php, mysql o js.");
        exit(1);
    }

    // Ejecutar todos los labs de un tipo
    if ($num === 'all') {
        printHeader("🚀 Ejecutando todos los labs de " . strtoupper($type));
        foreach (LABS[$type] as $n => $lab) {
            executeLab($type, $n, $lab);
        }
        printSuccess("Todos los labs de {$type} completados.");
        return;
    }

    if (!isset(LABS[$type][$num])) {
        printError("Lab '{$type}/{$num}' no encontrado.");
        printInfo("Usa --list para ver los labs disponibles.");
        exit(1);
    }

    executeLab($type, $num, LABS[$type][$num]);
}

function executeLab(string $type, string $num, array $lab): void
{
    $title = $lab['title'];
    $file  = $lab['file'];

    printLine();
    printLine(colorize("┌─────────────────────────────────────────┐", BLUE));
    printLine(colorize("│ ", BLUE) . colorize(" Lab {$type}/{$num}: {$title}", BOLD . WHITE) . str_repeat(' ', max(0, 40 - strlen("Lab {$type}/{$num}: {$title}"))) . colorize(" │", BLUE));
    printLine(colorize("└─────────────────────────────────────────┘", BLUE));

    if (!file_exists($file)) {
        printError("Archivo no encontrado: {$file}");
        return;
    }

    if ($type === 'mysql') {
        printWarning("Los labs de MySQL se ejecutan en phpMyAdmin o MySQL CLI.");
        printInfo("Archivo: {$file}");
        printInfo("Abre phpMyAdmin en http://localhost:8080 y ejecuta el SQL.");
        return;
    }

    $cmd = match($type) {
        'php' => "php {$file}",
        'js'  => "node {$file}",
        default => null,
    };

    if ($cmd === null) {
        printError("Tipo de lab no ejecutable desde CLI: {$type}");
        return;
    }

    // Verificar que el runtime está disponible
    $runtime = explode(' ', $cmd)[0];
    exec("which {$runtime} 2>/dev/null", $out, $code);
    if ($code !== 0) {
        printError("Runtime no encontrado: {$runtime}. ¿Está instalado?");
        return;
    }

    printInfo("Ejecutando: {$cmd}");
    printLine(colorize(str_repeat('─', 50), CYAN));

    $start = microtime(true);
    passthru($cmd, $exitCode);
    $elapsed = round((microtime(true) - $start) * 1000, 2);

    printLine(colorize(str_repeat('─', 50), CYAN));

    if ($exitCode === 0) {
        printSuccess("Lab completado en {$elapsed}ms");
    } else {
        printError("El lab terminó con código de error: {$exitCode}");
    }
}

function runTests(): void
{
    printHeader('🧪 Ejecutando PHPUnit Tests');

    $phpunit = './vendor/bin/phpunit';
    $config  = 'tests/phpunit.xml';

    if (!file_exists($phpunit)) {
        printError("PHPUnit no encontrado en {$phpunit}");
        printInfo("Ejecuta: composer install");
        exit(1);
    }

    $cmd = "{$phpunit} --configuration {$config} --colors=always --testdox";
    printInfo("Comando: {$cmd}");
    printLine();

    passthru($cmd, $exitCode);

    printLine();
    if ($exitCode === 0) {
        printSuccess("Todos los tests pasaron correctamente.");
    } else {
        printError("Algunos tests fallaron (código: {$exitCode}).");
        exit($exitCode);
    }
}
