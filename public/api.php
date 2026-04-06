<?php

declare(strict_types=1);

/**
 * API REST simple para el laboratorio PHP/JS/MySQL.
 *
 * Endpoints disponibles:
 *   GET  /health
 *   GET  /api/users
 *   POST /api/users
 *   GET  /api/users/{id}
 *   PUT  /api/users/{id}
 *   DELETE /api/users/{id}
 *   POST /api/auth/login
 *   GET  /api/products
 *   GET  /api/orders/{id}
 *   POST /api/orders
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// ── CORS Headers ─────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Router ────────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');

/**
 * Envía respuesta JSON estandarizada.
 *
 * @param mixed $data    Datos a devolver
 * @param int   $status  HTTP status code
 * @param string|null $error Mensaje de error (opcional)
 */
function jsonResponse(mixed $data, int $status = 200, ?string $error = null): never
{
    http_response_code($status);
    $response = ['success' => $status < 400];

    if ($error !== null) {
        $response['error'] = $error;
    } else {
        $response['data'] = $data;
    }

    $response['timestamp'] = date('c');

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtiene el cuerpo de la petición como array.
 */
function getBody(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ── Rutas ─────────────────────────────────────────────────────────────────────

// GET /health
if ($method === 'GET' && $uri === '/health') {
    jsonResponse([
        'status'  => 'ok',
        'version' => '1.0.0',
        'php'     => PHP_VERSION,
    ]);
}

// GET /api/users | POST /api/users
if (preg_match('#^/api/users$#', $uri)) {
    if ($method === 'GET') {
        // 👉 MODIFICA: conecta a BD real usando Database::getInstance()
        jsonResponse([
            'users'   => [],
            'total'   => 0,
            'page'    => (int)($_GET['page'] ?? 1),
            'message' => 'Conecta la BD para ver usuarios reales.',
        ]);
    }

    if ($method === 'POST') {
        $body = getBody();
        if (empty($body['name']) || empty($body['email']) || empty($body['password'])) {
            jsonResponse(null, 422, 'Se requieren: name, email, password');
        }

        // ✅ RESULTADO: en un entorno real se crearía el usuario con UserService
        jsonResponse([
            'id'      => rand(100, 999),
            'name'    => htmlspecialchars($body['name'], ENT_QUOTES, 'UTF-8'),
            'email'   => filter_var($body['email'], FILTER_SANITIZE_EMAIL),
            'role'    => 'user',
            'message' => 'Usuario creado (demo — conecta la BD para persistir).',
        ], 201);
    }
}

// GET /api/users/{id} | PUT /api/users/{id} | DELETE /api/users/{id}
if (preg_match('#^/api/users/(\d+)$#', $uri, $matches)) {
    $userId = (int)$matches[1];

    if ($method === 'GET') {
        // Demo: devuelve usuario ficticio
        jsonResponse([
            'id'    => $userId,
            'name'  => 'Usuario Demo',
            'email' => "demo{$userId}@lab.test",
            'role'  => 'user',
        ]);
    }

    if ($method === 'PUT') {
        $body = getBody();
        jsonResponse([
            'id'      => $userId,
            'updated' => array_keys($body),
            'message' => 'Usuario actualizado (demo).',
        ]);
    }

    if ($method === 'DELETE') {
        jsonResponse([
            'id'      => $userId,
            'deleted' => true,
            'message' => 'Usuario eliminado (soft delete demo).',
        ]);
    }
}

// POST /api/auth/login
if ($method === 'POST' && $uri === '/api/auth/login') {
    $body = getBody();

    if (empty($body['email']) || empty($body['password'])) {
        jsonResponse(null, 422, 'Se requieren: email, password');
    }

    // Demo: aceptar admin@lab.test / password123
    $email    = filter_var($body['email'], FILTER_SANITIZE_EMAIL);
    $password = $body['password'] ?? '';

    if ($email === 'admin@lab.test' && $password === 'password123') {
        // Genera JWT manual simplificado
        $header  = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub'   => 1,
            'email' => $email,
            'role'  => 'admin',
            'iat'   => time(),
            'exp'   => time() + 3600,
        ]));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", 'demo-secret', true));
        $token     = "{$header}.{$payload}.{$signature}";

        jsonResponse([
            'token'   => $token,
            'type'    => 'Bearer',
            'expires' => 3600,
            'user'    => ['id' => 1, 'email' => $email, 'role' => 'admin'],
        ]);
    }

    jsonResponse(null, 401, 'Credenciales inválidas. Usa admin@lab.test / password123 para demo.');
}

// GET /api/products
if ($method === 'GET' && $uri === '/api/products') {
    $products = [];
    $names    = ['Laptop Pro', 'Mouse Inalámbrico', 'Teclado Mecánico', 'Monitor 4K', 'Webcam HD'];
    for ($i = 1; $i <= 5; $i++) {
        $products[] = [
            'id'    => $i,
            'name'  => $names[$i - 1],
            'price' => round(99.99 * $i, 2),
            'stock' => rand(0, 100),
        ];
    }
    jsonResponse(['products' => $products, 'total' => 5]);
}

// GET /api/orders/{id}
if (preg_match('#^/api/orders/(\d+)$#', $uri, $matches)) {
    $orderId = (int)$matches[1];

    if ($method === 'GET') {
        jsonResponse([
            'id'     => $orderId,
            'status' => 'pending',
            'total'  => 299.99,
            'items'  => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 99.99],
                ['product_id' => 3, 'quantity' => 1, 'price' => 100.01],
            ],
        ]);
    }
}

// POST /api/orders
if ($method === 'POST' && $uri === '/api/orders') {
    $body = getBody();

    if (empty($body['user_id']) || empty($body['items'])) {
        jsonResponse(null, 422, 'Se requieren: user_id, items');
    }

    $total = 0.0;
    foreach ($body['items'] as $item) {
        $total += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
    }

    jsonResponse([
        'id'      => rand(1000, 9999),
        'user_id' => (int)$body['user_id'],
        'status'  => 'pending',
        'items'   => $body['items'],
        'total'   => round($total, 2),
        'message' => 'Pedido creado (demo).',
    ], 201);
}

// ── 404 ───────────────────────────────────────────────────────────────────────
jsonResponse(null, 404, "Ruta no encontrada: {$method} {$uri}");
