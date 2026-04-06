<?php

/**
 * Configuración centralizada de la base de datos.
 *
 * Este archivo configura la conexión PDO con todas las opciones recomendadas
 * para un entorno de producción seguro y eficiente.
 *
 * Carga las variables de entorno desde $_ENV o desde el archivo .env si existe.
 */

declare(strict_types=1);

// ──────────────────────────────────────────────────────────────────────────────
// Carga de variables de entorno
// ──────────────────────────────────────────────────────────────────────────────
// Si existe un archivo .env en la raíz, lo leemos línea a línea.
// En producción real usarías vlucas/phpdotenv, pero aquí lo hacemos manual
// para no añadir dependencias innecesarias.
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Parámetros de conexión
// ──────────────────────────────────────────────────────────────────────────────
$config = [
    'host'     => $_ENV['DB_HOST']     ?? 'localhost',
    'port'     => $_ENV['DB_PORT']     ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'php_js_mysql_lab',
    'username' => $_ENV['DB_USERNAME'] ?? 'labuser',
    'password' => $_ENV['DB_PASSWORD'] ?? 'labpassword',

    // ── DSN (Data Source Name) ────────────────────────────────────────────────
    // El DSN identifica el driver (mysql), el host, el puerto, el nombre de la
    // base de datos y el charset. utf8mb4 soporta emojis y caracteres Unicode
    // completos; utf8 en MySQL solo soporta hasta 3 bytes por carácter.
    'charset'  => 'utf8mb4',

    // ── Opciones PDO ──────────────────────────────────────────────────────────
    'options'  => [
        // ERRMODE_EXCEPTION: PDO lanza excepciones en errores, lo que permite
        // usar try/catch en lugar de comprobar el valor de retorno manualmente.
        // ❌ NO HAGAS ESTO: PDO::ERRMODE_SILENT (los errores se ignoran silenciosamente)
        // ✅ MEJOR ASÍ: PDO::ERRMODE_EXCEPTION
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,

        // FETCH_ASSOC: Por defecto, PDO devuelve arrays con claves numéricas Y
        // de cadena duplicadas. FETCH_ASSOC solo devuelve claves de cadena,
        // reduciendo el uso de memoria a la mitad.
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,

        // EMULATE_PREPARES = false: Desactiva la emulación de prepared statements.
        // Con emulación activa, PDO construye la query en PHP (menos seguro).
        // Con emulación inactiva, MySQL prepara la query en el servidor (más seguro,
        // protección real contra SQL injection, y permite TYPE_CAST correcto).
        \PDO::ATTR_EMULATE_PREPARES   => false,

        // PERSISTENT: Las conexiones persistentes se reutilizan entre requests.
        // Útil en entornos de alto tráfico, pero puede causar problemas con
        // transacciones no cerradas. Usa con precaución.
        // 👉 MODIFICA: cambia a true para ver el efecto en rendimiento
        \PDO::ATTR_PERSISTENT         => false,

        // STRINGIFY_FETCHES = false: Sin esto, todos los valores numéricos de MySQL
        // se devuelven como strings en PHP. Con false, se mantiene el tipo correcto.
        \PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];

// ──────────────────────────────────────────────────────────────────────────────
// Función factory para crear la conexión PDO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Crea y devuelve una nueva conexión PDO configurada.
 *
 * @param array $cfg Configuración (usa $config de este archivo si no se pasa)
 * @return \PDO
 * @throws \PDOException Si la conexión falla
 */
function createPdoConnection(array $cfg = []): \PDO
{
    global $config;
    $cfg = array_merge($config, $cfg);

    // El DSN especifica driver:host=...;port=...;dbname=...;charset=...
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['database'],
        $cfg['charset']
    );

    return new \PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
}

return $config;
