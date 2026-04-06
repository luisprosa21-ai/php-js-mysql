<?php

declare(strict_types=1);

/**
 * Dashboard principal del laboratorio PHP·JS·MySQL
 *
 * Interfaz web para explorar y ejecutar los labs.
 * Los labs PHP se ejecutan con shell_exec() y el output se muestra en la página.
 *
 * ⚠️ SOLO PARA ENTORNO DE DESARROLLO LOCAL — no exponer en producción.
 */

// Seguridad básica: solo en desarrollo
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    http_response_code(403);
    exit('Forbidden');
}

// Ejecutar lab PHP si se solicita
$output = '';
$labError = '';
if (isset($_GET['run'])) {
    $lab = preg_replace('/[^a-z0-9_\/]/', '', $_GET['run']);
    $labFile = dirname(__DIR__) . "/labs/php/{$lab}.php";
    if (file_exists($labFile)) {
        $output = shell_exec("php " . escapeshellarg($labFile) . " 2>&1");
    } else {
        $labError = "Lab no encontrado: {$lab}";
    }
}

$labs = [
    'php' => [
        ['id' => '01_types_and_operators', 'title' => 'Tipos y Operadores', 'desc' => '== vs ===, Enums, match, nullable'],
        ['id' => '02_oop_pillars', 'title' => 'POO — 4 Pilares', 'desc' => 'Encapsulamiento, Herencia, Polimorfismo, Abstracción'],
        ['id' => '03_traits', 'title' => 'Traits', 'desc' => 'Múltiples traits, conflictos, insteadof, as'],
        ['id' => '04_closures_and_generators', 'title' => 'Closures & Generators', 'desc' => 'Closures, arrow functions, memoria con generators'],
        ['id' => '05_exceptions', 'title' => 'Excepciones', 'desc' => 'Jerarquía custom, try/catch/finally, chaining'],
        ['id' => '06_pdo_security', 'title' => 'PDO & Seguridad', 'desc' => 'SQL Injection, XSS, CSRF, password hashing'],
        ['id' => '07_sessions_auth', 'title' => 'Sesiones & JWT', 'desc' => 'JWT manual paso a paso, configuración segura'],
        ['id' => '08_patterns', 'title' => 'Patrones de Diseño', 'desc' => 'Singleton, Factory, Observer, Strategy, Decorator'],
        ['id' => '09_array_functions', 'title' => 'Funciones de Array', 'desc' => 'map, filter, reduce, usort, chunk, group by'],
    ],
    'mysql' => [
        ['id' => '01_joins_explained', 'title' => 'JOINs explicados', 'desc' => 'INNER, LEFT, RIGHT, SELF, CROSS JOIN'],
        ['id' => '02_indexes_performance', 'title' => 'Índices y Rendimiento', 'desc' => 'EXPLAIN, índices compuestos, covering index'],
        ['id' => '03_transactions_acid', 'title' => 'Transacciones ACID', 'desc' => 'BEGIN, COMMIT, ROLLBACK, SAVEPOINT'],
        ['id' => '04_normalization', 'title' => 'Normalización', 'desc' => '0NF → 1NF → 2NF → 3NF con ejemplos'],
        ['id' => '05_aggregations', 'title' => 'Agregaciones', 'desc' => 'GROUP BY, HAVING, Window Functions, LAG/LEAD'],
        ['id' => '06_subqueries', 'title' => 'Subqueries & CTEs', 'desc' => 'Subqueries correlacionadas, WITH, CTEs recursivas'],
        ['id' => '07_optimization', 'title' => 'Optimización', 'desc' => 'EXPLAIN ANALYZE, rewrite, cursor pagination'],
    ],
    'javascript' => [
        ['id' => '01_this_context', 'title' => 'Contexto this', 'desc' => 'this en funciones, clases, bind/call/apply'],
        ['id' => '02_closures_scope', 'title' => 'Closures & Scope', 'desc' => 'Closures, factory, memoize, module pattern'],
        ['id' => '03_promises_async', 'title' => 'Promises & Async', 'desc' => 'Promise.all/race/any, async/await, retry'],
        ['id' => '04_prototype_chain', 'title' => 'Prototype Chain', 'desc' => 'Object.create, class, extends, mixins'],
        ['id' => '05_event_loop', 'title' => 'Event Loop', 'desc' => 'Call stack, microtasks, macrotasks, nextTick'],
        ['id' => '06_destructuring_spread', 'title' => 'Destructuring & Spread', 'desc' => 'Array/Object destructuring, rest, optional chaining'],
        ['id' => '07_patterns', 'title' => 'Patrones JS', 'desc' => 'Observer, Module, Factory, Singleton, Proxy, Iterator'],
    ],
];

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎓 PHP·JS·MySQL Lab</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <h1>🎓 PHP · JavaScript · MySQL</h1>
            <p class="header-subtitle">Laboratorio Interactivo de Aprendizaje</p>
        </div>
        <nav class="header-nav">
            <a href="?section=php">🐘 PHP</a>
            <a href="?section=mysql">🐬 MySQL</a>
            <a href="?section=javascript">🟨 JavaScript</a>
            <a href="http://localhost:8080" target="_blank">phpMyAdmin ↗</a>
            <a href="/api.php/health">API Health</a>
        </nav>
    </header>

    <main class="container">
        <?php if ($output || $labError): ?>
        <section class="output-panel">
            <div class="output-header">
                <span>⚡ Output del Lab</span>
                <button onclick="document.querySelector('.output-panel').style.display='none'">✕</button>
            </div>
            <?php if ($labError): ?>
                <pre class="output-error"><?= htmlspecialchars($labError, ENT_QUOTES, 'UTF-8') ?></pre>
            <?php else: ?>
                <pre class="output-content"><?= htmlspecialchars($output ?? '', ENT_QUOTES, 'UTF-8') ?></pre>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php foreach ($labs as $section => $sectionLabs): ?>
        <section class="lab-section" id="<?= $section ?>">
            <h2 class="section-title">
                <?= match($section) {
                    'php'        => '🐘 Labs de PHP',
                    'mysql'      => '🐬 Labs de MySQL',
                    'javascript' => '🟨 Labs de JavaScript',
                    default      => $section,
                } ?>
            </h2>
            <div class="lab-grid">
                <?php foreach ($sectionLabs as $i => $lab): ?>
                <div class="lab-card" data-section="<?= $section ?>" data-lab="<?= htmlspecialchars($lab['id']) ?>">
                    <div class="lab-number"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="lab-info">
                        <h3 class="lab-title"><?= htmlspecialchars($lab['title']) ?></h3>
                        <p class="lab-desc"><?= htmlspecialchars($lab['desc']) ?></p>
                    </div>
                    <div class="lab-actions">
                        <?php if ($section === 'php'): ?>
                            <button class="btn btn-run"
                                    onclick="runLab('<?= htmlspecialchars($lab['id']) ?>')">
                                ▶ Ejecutar
                            </button>
                            <a class="btn btn-view"
                               href="/labs/php/<?= htmlspecialchars($lab['id']) ?>.php"
                               target="_blank">Ver código</a>
                        <?php elseif ($section === 'mysql'): ?>
                            <a class="btn btn-run"
                               href="http://localhost:8080"
                               target="_blank">Abrir phpMyAdmin ↗</a>
                        <?php elseif ($section === 'javascript'): ?>
                            <button class="btn btn-js"
                                    onclick="runJsLab('<?= htmlspecialchars($lab['id']) ?>')">
                                ▶ Ejecutar en consola
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </main>

    <footer class="footer">
        <p>🎓 PHP·JS·MySQL Lab — Proyecto educativo interactivo</p>
        <p>PHP <?= PHP_VERSION ?> | <a href="/api.php/health">API Status</a></p>
    </footer>

    <div id="output-modal" class="modal" style="display:none">
        <div class="modal-inner">
            <div class="modal-header">
                <span id="modal-title">Output</span>
                <button onclick="document.getElementById('output-modal').style.display='none'">✕</button>
            </div>
            <pre id="modal-output" class="output-content"></pre>
        </div>
    </div>

    <script src="/assets/js/lab.js"></script>
    <script>
        function runLab(labId) {
            const modal  = document.getElementById('output-modal');
            const output = document.getElementById('modal-output');
            const title  = document.getElementById('modal-title');

            output.textContent = 'Ejecutando...';
            title.textContent  = `PHP Lab: ${labId}`;
            modal.style.display = 'flex';

            fetch(`?run=${labId}`)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const pre = doc.querySelector('.output-content');
                    output.textContent = pre ? pre.textContent : 'No output';
                })
                .catch(e => { output.textContent = 'Error: ' + e.message; });
        }

        function runJsLab(labId) {
            const name = labId.replace(/^\d+_/, '').replace(/_/g, '-');
            if (window.Lab && window.Lab.labs[name]) {
                window.Lab.run(name);
                alert(`Lab "${name}" ejecutado. Abre la consola del navegador (F12) para ver el output.`);
            } else {
                alert(`Abre la consola del navegador (F12) y ejecuta: Lab.run('${name}')`);
            }
        }
    </script>
</body>
</html>
