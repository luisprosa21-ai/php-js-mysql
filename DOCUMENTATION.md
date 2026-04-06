# 📖 Documentación Exhaustiva — PHP · JavaScript · MySQL Lab

> **Propósito de este documento:** Explicar en detalle cómo funciona cada archivo del proyecto,
> el orden en que se ejecutan y cómo se conectan entre sí para formar el sistema completo.

---

## 📋 Tabla de Contenidos

1. [Visión general del proyecto](#1-visión-general-del-proyecto)
2. [Estructura de directorios](#2-estructura-de-directorios)
3. [Infraestructura y arranque (Docker)](#3-infraestructura-y-arranque-docker)
4. [Carga de dependencias y autoloading](#4-carga-de-dependencias-y-autoloading)
5. [Configuración de la base de datos](#5-configuración-de-la-base-de-datos)
6. [Capa Core — Base de datos y contenedor DI](#6-capa-core--base-de-datos-y-contenedor-di)
7. [Modelos de datos](#7-modelos-de-datos)
8. [Traits reutilizables](#8-traits-reutilizables)
9. [Capa de servicios (lógica de negocio)](#9-capa-de-servicios-lógica-de-negocio)
10. [Middleware](#10-middleware)
11. [Puntos de entrada web](#11-puntos-de-entrada-web)
12. [Flujo completo de una petición HTTP](#12-flujo-completo-de-una-petición-http)
13. [Frontend y JavaScript (lab.js)](#13-frontend-y-javascript-labjs)
14. [Laboratorios educativos](#14-laboratorios-educativos)
15. [Runner CLI (scripts/run_labs.php)](#15-runner-cli-scriptsrun_labsphp)
16. [Tests con PHPUnit](#16-tests-con-phpunit)
17. [Base de datos: esquema y semillas](#17-base-de-datos-esquema-y-semillas)
18. [Diagramas de flujo por escenario](#18-diagramas-de-flujo-por-escenario)

---

## 1. Visión general del proyecto

Este es un **laboratorio educativo interactivo** para aprender PHP 8.2, JavaScript ES2022 y MySQL 8.0. Contiene:

- **9 labs de PHP** — tipos, POO, traits, closures, excepciones, PDO, JWT, patrones de diseño, arrays.
- **7 labs de MySQL** — JOINs, índices, transacciones ACID, normalización, agregaciones, subqueries, optimización.
- **7 labs de JavaScript** — contexto `this`, closures, promises, prototype chain, event loop, destructuring, patrones.
- Un **dashboard web** (`public/index.php`) que permite ejecutar los labs desde el navegador.
- Una **API REST** (`public/api.php`) con 11 endpoints de demostración.
- Un **runner CLI** (`scripts/run_labs.php`) para ejecutar los labs desde la terminal.
- Una arquitectura **limpia y real**: Singleton, DI Container, Repository, Middleware, State Machine.

El entorno completo corre en **Docker Compose** con tres servicios: PHP 8.2-Apache, MySQL 8.0 y phpMyAdmin.

---

## 2. Estructura de directorios

```
php-js-mysql/
│
├── .env.example                    ← Plantilla de variables de entorno
├── .gitignore
├── composer.json                   ← Dependencias PHP y autoloading PSR-4
├── Dockerfile                      ← Imagen PHP 8.2-Apache
├── docker-compose.yml              ← Orquestación de servicios
├── README.md
│
├── config/
│   ├── apache.conf                 ← Configuración de Apache (DocumentRoot, mod_rewrite)
│   └── database.php                ← Fábrica PDO + carga de .env
│
├── database/
│   ├── migrations/
│   │   └── 001_create_tables.sql   ← Esquema: 8 tablas con FK e índices
│   └── seeds/
│       └── seed_data.sql           ← Datos de ejemplo: 20 usuarios, 50 productos, 100 pedidos
│
├── public/                         ← DocumentRoot de Apache (único directorio público)
│   ├── index.php                   ← Dashboard web (punto de entrada principal)
│   ├── api.php                     ← API REST (punto de entrada API)
│   └── assets/
│       ├── css/style.css           ← Estilos del dashboard
│       └── js/lab.js               ← Runner de labs JavaScript en el navegador
│
├── src/                            ← Código de la aplicación (namespace Lab\)
│   ├── Core/
│   │   ├── Database.php            ← Singleton PDO wrapper
│   │   ├── DatabaseInterface.php   ← Contrato de base de datos (DI)
│   │   └── Container.php          ← Contenedor de inyección de dependencias
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   └── Order.php
│   ├── Services/
│   │   ├── UserService.php
│   │   ├── AuthService.php
│   │   └── OrderService.php
│   ├── Traits/
│   │   ├── Timestampable.php
│   │   ├── Validatable.php
│   │   └── Loggable.php
│   └── Middleware/
│       ├── AuthMiddleware.php
│       └── RateLimitMiddleware.php
│
├── labs/
│   ├── php/         (01–09 *.php)  ← Labs PHP ejecutables con `php`
│   ├── mysql/       (01–07 *.sql)  ← Labs SQL para phpMyAdmin/MySQL CLI
│   └── javascript/  (01–07 *.js)   ← Labs JS ejecutables con Node o en el navegador
│
├── tests/
│   ├── Unit/
│   │   ├── DatabaseTest.php
│   │   ├── TraitsTest.php
│   │   └── UserServiceTest.php
│   └── phpunit.xml
│
└── scripts/
    ├── setup.sh                    ← Script de inicialización Docker
    └── run_labs.php                ← Runner CLI con colores ANSI
```

---

## 3. Infraestructura y arranque (Docker)

### `docker-compose.yml` — Orquestación de servicios

Define **tres servicios** que se levantan con `docker compose up`:

| Servicio | Imagen | Puerto local | Función |
|---|---|---|---|
| `app` | Dockerfile propio (PHP 8.2-Apache) | `8000 → 80` | Sirve el dashboard y la API |
| `db` | `mysql:8.0` | `3306 → 3306` | Motor de base de datos |
| `phpmyadmin` | `phpmyadmin:latest` | `8080 → 80` | Interfaz gráfica para MySQL |

**Secuencia de arranque:**

```
docker compose up
    │
    ├─ db (MySQL 8.0) arranca primero
    │   └─ Ejecuta automáticamente al iniciar:
    │       ├─ /docker-entrypoint-initdb.d/001_create_tables.sql  ← crea las 8 tablas
    │       └─ /docker-entrypoint-initdb.d/002_seed_data.sql      ← inserta datos de ejemplo
    │
    ├─ app (PHP-Apache) arranca después de db
    │   └─ Se inyectan variables de entorno:
    │       DB_HOST=db, DB_PORT=3306, DB_DATABASE=php_js_mysql_lab
    │       DB_USERNAME=labuser, DB_PASSWORD=labpassword
    │
    └─ phpmyadmin arranca en paralelo
        └─ Se conecta a db con PMA_HOST=db
```

### `Dockerfile` — Imagen de la aplicación PHP

```
FROM php:8.2-apache
    │
    ├─ 1. Instala dependencias del sistema: libonig-dev, libxml2-dev, zip, unzip, curl
    ├─ 2. Instala extensiones PHP: pdo, pdo_mysql, mbstring, opcache
    ├─ 3. Instala Xdebug (para depuración)
    ├─ 4. Copia Composer desde imagen oficial
    ├─ 5. WORKDIR /var/www/html
    ├─ 6. Copia todo el proyecto al contenedor
    ├─ 7. Ejecuta composer install (instala PHPUnit y genera el autoloader)
    ├─ 8. Copia config/apache.conf → sitio Apache por defecto
    └─ 9. Habilita mod_rewrite
```

Apache tiene como **DocumentRoot** la carpeta `public/`, lo que significa que solo los archivos en `public/` son accesibles desde el navegador. El resto (`src/`, `config/`, `labs/`) son privados por diseño.

### `scripts/setup.sh` — Inicialización manual

Script alternativo que puede ejecutarse directamente para inicializar el entorno Docker. Ejecuta las migraciones y semillas si Docker ya está corriendo.

---

## 4. Carga de dependencias y autoloading

### `composer.json`

```json
{
  "require":     { "php": "^8.1" },
  "require-dev": { "phpunit/phpunit": "^10.0" },
  "autoload":    { "psr-4": { "Lab\\": "src/" } },
  "autoload-dev":{ "psr-4": { "Lab\\Tests\\": "tests/" } },
  "scripts": {
    "test": "phpunit --configuration tests/phpunit.xml",
    "labs": "php scripts/run_labs.php"
  }
}
```

El mapeo PSR-4 `"Lab\\" → "src/"` significa:

| Clase PHP | Archivo en disco |
|---|---|
| `Lab\Core\Database` | `src/Core/Database.php` |
| `Lab\Services\UserService` | `src/Services/UserService.php` |
| `Lab\Models\User` | `src/Models/User.php` |
| `Lab\Middleware\AuthMiddleware` | `src/Middleware/AuthMiddleware.php` |

Cuando `public/api.php` llama a `require_once dirname(__DIR__) . '/vendor/autoload.php'`, Composer registra un autoloader que carga automáticamente cualquier clase del namespace `Lab\` sin necesidad de `require` manual.

---

## 5. Configuración de la base de datos

### `config/database.php`

Este archivo se ejecuta **cada vez que se instancia `Database`** (es decir, una sola vez por proceso PHP gracias al Singleton). Su flujo es:

```
1. Busca el archivo .env en la raíz del proyecto
2. Si existe, lo lee línea por línea:
   - Ignora líneas vacías y comentarios (#)
   - Para cada línea KEY=VALUE:
       $_ENV['KEY'] = 'VALUE'
       putenv('KEY=VALUE')
3. Construye el array $config con valores de $_ENV o defaults:
   host     = DB_HOST     ?? 'localhost'
   port     = DB_PORT     ?? '3306'
   database = DB_DATABASE ?? 'php_js_mysql_lab'
   username = DB_USERNAME ?? 'labuser'
   password = DB_PASSWORD ?? 'labpassword'
   charset  = 'utf8mb4'
4. Define la función createPdoConnection() (factory auxiliar)
5. Retorna $config con return $config
```

**Opciones PDO configuradas:**

| Opción | Valor | Por qué |
|---|---|---|
| `ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | PDO lanza excepciones en errores → se pueden capturar con try/catch |
| `ATTR_DEFAULT_FETCH_MODE` | `FETCH_ASSOC` | Solo claves de cadena, no duplicadas con índices numéricos → menos memoria |
| `ATTR_EMULATE_PREPARES` | `false` | MySQL prepara las queries en el servidor → protección real contra SQL Injection |
| `ATTR_PERSISTENT` | `false` | Conexión nueva por proceso (cambiar a `true` para conexiones persistentes) |
| `ATTR_STRINGIFY_FETCHES` | `false` | Los valores numéricos de MySQL se devuelven como int/float, no como string |

---

## 6. Capa Core — Base de datos y contenedor DI

### `src/Core/Database.php` — Singleton PDO

Implementa el **patrón Singleton** para garantizar que solo exista **una conexión PDO** durante todo el ciclo de vida del proceso PHP (es decir, durante una petición HTTP completa).

**Por qué Singleton:** Cada nueva conexión PDO abre un socket TCP/IP a MySQL. Abrir docenas de conexiones por petición desperdicia recursos. El Singleton garantiza una sola conexión reutilizada.

**Flujo de instanciación:**

```
Database::getInstance()
    │
    ├─ ¿static::$instance === null?
    │   SÍ → new Database()
    │           └─ __construct() (privado)
    │               ├─ require 'config/database.php'  ← carga y parsea .env
    │               ├─ Construye el DSN:
    │               │   "mysql:host=db;port=3306;dbname=php_js_mysql_lab;charset=utf8mb4"
    │               └─ new PDO(dsn, username, password, options)
    │                   └─ Abre la conexión TCP/IP a MySQL
    │
    └─ NO → devuelve static::$instance (la conexión ya existente)
```

**Métodos disponibles:**

```php
// Ejecutar query preparada con parámetros
$db->query('SELECT * FROM users WHERE email = ?', [$email])
    // → PDO::prepare() + PDOStatement::execute() → PDOStatement

// Envolver múltiples queries en una transacción ACID
$db->transaction(function() use ($db) {
    $db->query('INSERT INTO orders ...');
    $db->query('INSERT INTO order_items ...');
    // Si cualquier query lanza excepción → ROLLBACK automático
    // Si todo va bien → COMMIT
})

// Obtener el ID del último INSERT
$db->lastInsertId()  // → string

// Acceder al PDO subyacente directamente
$db->getConnection()  // → PDO
```

**Protecciones del Singleton:**
- Constructor `private` → no se puede hacer `new Database()`.
- `__clone()` privado → no se puede clonar.
- `__wakeup()` lanza excepción → no se puede deserializar.

### `src/Core/DatabaseInterface.php` — Contrato de inyección

Define los métodos que cualquier implementación de base de datos debe tener: `query()`, `transaction()`, `lastInsertId()`, `getConnection()`. Los servicios dependen de esta interfaz, no de la clase concreta, aplicando el principio de inversión de dependencias (D de SOLID).

### `src/Core/Container.php` — Contenedor de Inyección de Dependencias

Permite crear instancias de clases **resolviendo automáticamente sus dependencias** mediante reflexión PHP.

**Modos de registro:**

```php
// bind(): crea nueva instancia cada vez que se solicita
$container->bind(UserRepository::class, fn() => new MySqlUserRepository($db));

// singleton(): crea la instancia solo una vez y la reutiliza
$container->singleton(Database::class, fn() => Database::getInstance());
```

**Flujo de `make()`:**

```
$container->make(UserService::class)
    │
    ├─ ¿Hay binding registrado para UserService?
    │   SÍ → ejecuta la factory y devuelve el resultado
    │
    └─ NO → buildWithReflection('Lab\Services\UserService')
                │
                ├─ new ReflectionClass('Lab\Services\UserService')
                ├─ ¿Es instanciable? (no abstracta, no interfaz) → SÍ
                ├─ Obtiene el constructor: __construct(DatabaseInterface $db)
                ├─ Para cada parámetro del constructor:
                │   ├─ $db → tipo DatabaseInterface (no es builtin)
                │   │   └─ make('Lab\Core\DatabaseInterface') ← recursivo
                │   └─ ...
                └─ new UserService($dbResuelto)
```

---

## 7. Modelos de datos

Los modelos son **objetos de datos** (entidades) que representan registros de la base de datos. Usan los traits `Timestampable`, `Validatable` y `Loggable`.

### `src/Models/User.php`

Representa un usuario del sistema. Campos: `id`, `name`, `email`, `password_hash`, `role` (admin/user/guest), y los timestamps del trait Timestampable.

**Métodos clave:**
- `fromArray(array $row): User` — Fábrica estática que crea un User desde una fila de la BD.
- `toArray(): array` — Serializa el objeto a array (sin password_hash por seguridad).
- `jsonSerialize(): array` — Alias de toArray, implementa `JsonSerializable`.
- `validateUserData(array $data): bool` — Valida los datos antes de guardar (usa el trait Validatable).
- Getters/setters con validación: `setEmail()` normaliza a minúsculas, `setRole()` valida el valor.

### `src/Models/Product.php`

Representa un producto del catálogo. Campos: `id`, `category_id`, `name`, `price` (DECIMAL), `stock`, `sku`, `active`, timestamps.

**Métodos clave:**
- `isInStock(): bool` — Devuelve `true` si `stock > 0` y `active === true`.
- `applyDiscount(float $percentage): float` — Calcula el precio con descuento.
- `fromArray()` / `toArray()` / `jsonSerialize()`.

### `src/Models/Order.php`

Representa un pedido. Implementa una **máquina de estados** para el campo `status`.

**Transiciones de estado válidas:**

```
pending → confirmed → shipped → delivered
                             ↘ cancelled
pending → cancelled
```

**Métodos clave:**
- `addItem(array $item): void` — Agrega un item al pedido y recalcula el total.
- `calculateTotal(): float` — Suma `price × quantity` de todos los items.
- `canBeCancelled(): bool` — Solo `pending` o `confirmed` pueden cancelarse.
- `setStatus(string $status): void` — Valida la transición antes de cambiar el estado.
- `fromArray(array $row): Order` — Fábrica estática con soporte para items embebidos.

---

## 8. Traits reutilizables

Los traits son fragmentos de código reutilizables que se **mezclan** en las clases que los necesitan mediante `use NombreTrait;`. PHP no soporta herencia múltiple, pero sí múltiples traits.

### `src/Traits/Timestampable.php`

Añade gestión de fechas a cualquier modelo:

| Método | Descripción |
|---|---|
| `initTimestamps()` | Fija `createdAt` y `updatedAt` al momento actual. Llamar al crear. |
| `touch()` | Actualiza `updatedAt` al momento actual. Llamar al modificar. |
| `softDelete()` | Fija `deletedAt` al momento actual y llama a `touch()`. |
| `restore()` | Pone `deletedAt = null` y llama a `touch()`. |
| `isDeleted(): bool` | `true` si `deletedAt !== null`. |
| `getCreatedAt()` | Devuelve `DateTimeImmutable\|null`. |
| `getUpdatedAt()` | Devuelve `DateTimeImmutable\|null`. |
| `getDeletedAt()` | Devuelve `DateTimeImmutable\|null`. |

**Por qué soft delete:** Los registros "eliminados" permanecen en la BD con `deleted_at IS NOT NULL`. Las queries usan `WHERE deleted_at IS NULL` para excluirlos. Esto preserva el historial y mantiene integridad referencial.

### `src/Traits/Validatable.php`

Validación basada en reglas declarativas:

```php
$rules = [
    'name'     => 'required|string|min:2|max:100',
    'email'    => 'required|email',
    'password' => 'required|string|min:8',
    'role'     => 'in:admin,user,guest',
];
$valid = $this->validate($data, $rules);
$errors = $this->getValidationErrors();  // ['campo' => ['error1', ...]]
```

**Reglas disponibles:** `required`, `string`, `integer`, `float`, `boolean`, `email`, `min:N`, `max:N`, `in:val1,val2,...`.

### `src/Traits/Loggable.php`

Sistema de auditoría en memoria:

```php
$this->log('info', 'Usuario creado', ['id' => 1]);
$this->info('Mensaje informativo');
$this->warning('Advertencia');
$this->error('Error grave');
$this->getLogs();    // array de todos los mensajes
$this->clearLogs();  // vacía el historial
```

Cada entrada de log incluye: `timestamp`, `level`, `message`, `context` (array de datos adicionales).

---

## 9. Capa de servicios (lógica de negocio)

Los servicios contienen **toda la lógica de negocio** y son los únicos que hablan con la base de datos. Nunca se accede directamente a la BD desde los controladores (endpoints de la API) o los modelos.

### `src/Services/UserService.php`

Dependencias: `DatabaseInterface $db` (inyectada). Usa el trait `Loggable`.

**Métodos y su flujo:**

#### `create(array $data): User`
```
1. new User()
2. $user->validateUserData($data)
   └─ Si falla → throw InvalidArgumentException
3. $user->setName(), setEmail(), setPasswordHash(), setRole()
4. $user->initTimestamps()
5. $db->query('INSERT INTO users ...', [...])
6. $user->id = $db->lastInsertId()
7. $this->info("Usuario creado: email", ['id' => id])
8. return $user
```

#### `findById(int $id): ?User`
```
$db->query('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id])
└─ ->fetch() → array|false
   └─ array → User::fromArray($row)
   └─ false → null
```

#### `findByEmail(string $email): ?User`
```
normaliza email a lowercase
$db->query('SELECT ... WHERE email = ? AND deleted_at IS NULL', [email])
└─ igual que findById
```

#### `update(int $id, array $data): User`
```
1. findById($id) → si null → throw RuntimeException
2. Aplicar cambios al modelo ($user->setName(), etc.)
3. $db->query('UPDATE users SET ... WHERE id = ?', [...])
4. $this->info("Usuario actualizado")
5. return $user
```

#### `delete(int $id): bool`
```
1. findById($id) → si null → return false
2. $user->softDelete()           ← fija deletedAt
3. $db->query('UPDATE users SET deleted_at = ?, updated_at = ? WHERE id = ?', [...])
4. $this->info("Usuario eliminado (soft delete)")
5. return true
```

#### `paginate(int $page, int $perPage): array`
```
offset = ($page - 1) * $perPage
total  = COUNT(*) FROM users WHERE deleted_at IS NULL
rows   = SELECT * FROM users ... LIMIT ? OFFSET ?
return {data: User[], total, page, per_page, last_page}
```

#### `hashPassword(string $password): string`
```
password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB
    'time_cost'   => 4,      // iteraciones
    'threads'     => 1,
])
```
**Por qué Argon2ID:** Es el algoritmo recomendado por OWASP para hashing de contraseñas en 2024. Resistente a ataques de GPU y side-channel. **Nunca usar MD5 o SHA1 para contraseñas.**

#### `verifyPassword(string $password, string $hash): bool`
```
password_verify($password, $hash)  // extrae el salt del hash y verifica
```

### `src/Services/AuthService.php`

Dependencias: `UserService $us`, `string $secret`, `int $expiry = 3600`.

#### `login(string $email, string $password): array`
```
1. $user = $us->findByEmail($email)
2. Si $user === null OR !$us->verifyPassword($password, $user->getPasswordHash())
   └─ throw RuntimeException('Credenciales inválidas.')
   (mismo mensaje para ambos casos → no revela si el email existe)
3. $expiresAt = time() + $this->expiry
4. $token = $this->generateToken([sub, email, role, exp, iat])
5. return { token, expires_at, user: $user->jsonSerialize() }
```

#### `generateToken(array $payload): string`
```
$header    = base64UrlEncode('{"typ":"JWT","alg":"HS256"}')
$payload   = base64UrlEncode(json_encode($payload))
$signature = base64UrlEncode(HMAC-SHA256("$header.$payload", $secret))
return "$header.$payload.$signature"
```

#### `verifyToken(string $token): array`
```
1. Dividir en 3 partes por '.'
2. Recalcular firma esperada: sign($header, $payload)
3. hash_equals($expected, $actual)  ← comparación en tiempo constante (anti timing attack)
   └─ Si no coincide → throw RuntimeException('Token manipulado')
4. Decodificar payload
5. Verificar expiración: if (claims['exp'] < time()) → throw RuntimeException('Token expirado')
6. return $claims
```

### `src/Services/OrderService.php`

Dependencias: `DatabaseInterface $db`. Usa el trait `Loggable`.

#### `create(int $userId, array $items): Order`
```
1. Si $items vacío → throw InvalidArgumentException
2. new Order()
3. $order->setUserId($userId)
4. $order->initTimestamps()
5. foreach $items → $order->addItem($item)
6. $db->transaction(function() use ($order) {
       INSERT INTO orders (user_id, status, total, ...)
       $order->id = lastInsertId()
       foreach $order->getItems() as $item:
           INSERT INTO order_items (order_id, product_id, quantity, unit_price)
   })
   └─ Si cualquier INSERT falla → ROLLBACK automático → re-throw excepción
7. $this->info("Pedido creado: ID", [user_id, total])
8. return $order
```

#### `findById(int $id): ?Order`
```
SELECT * FROM orders WHERE id = ?
SELECT * FROM order_items WHERE order_id = ?
row['items'] = [...items mapeados...]
Order::fromArray($row)
```

#### `cancel(int $id): bool`
```
1. findById($id) → si null → return false
2. $order->canBeCancelled() → si false → return false
3. $order->setStatus('cancelled')
4. UPDATE orders SET status = ?, updated_at = ? WHERE id = ?
5. $this->info("Pedido cancelado")
6. return true
```

---

## 10. Middleware

Los middlewares son **interceptores** que procesan la petición antes (y después) de que llegue al manejador final. Implementan el patrón de cadena de responsabilidad.

### `src/Middleware/AuthMiddleware.php`

Verifica que el token JWT sea válido antes de permitir el acceso a un endpoint protegido.

```
handle(array $request, callable $next): array
    │
    ├─ Extraer header Authorization (o authorization)
    ├─ ¿Empieza con "Bearer "?
    │   NO → return { status: 401, error: 'Token requerido' }
    │
    ├─ Extraer el token: substr($authHeader, 7)
    ├─ $this->auth->verifyToken($token)
    │   ├─ Éxito → $request['auth'] = $claims (payload del JWT)
    │   └─ Excepción → return { status: 401, error: mensaje }
    │
    └─ return $next($request)  ← pasa al siguiente handler
```

### `src/Middleware/RateLimitMiddleware.php`

Limita el número de peticiones por IP (60 por 60 segundos por defecto).

```
handle(array $request, callable $next): array
    │
    ├─ Extraer IP: $request['ip'] ?? '127.0.0.1'
    ├─ ¿Existe entrada para esta IP en static::$store?
    │   NO o ventana expirada → inicializar: { count: 0, reset_at: now + 60 }
    │
    ├─ Incrementar count
    ├─ Calcular remaining = 60 - count
    ├─ Preparar headers: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset
    │
    ├─ ¿count > 60?
    │   SÍ → return { status: 429, error: '...', headers: { Retry-After: ... } }
    │
    └─ $response = $next($request)
       └─ Añadir headers de rate limit a la respuesta
       └─ return $response
```

**Nota:** El almacenamiento es `static::$store` (en memoria del proceso PHP). En producción se usaría Redis o Memcached para compartir el estado entre procesos.

---

## 11. Puntos de entrada web

Apache redirige todas las peticiones HTTP al directorio `public/`. Hay dos puntos de entrada:

### `public/index.php` — Dashboard web

Es el **punto de entrada principal** cuando el usuario abre `http://localhost:8000` en el navegador.

**Flujo de ejecución:**

```
Petición HTTP GET http://localhost:8000
    │
    ├─ Apache → public/index.php
    │
    ├─ 1. Verificar entorno:
    │   └─ Si APP_ENV === 'production' → HTTP 403 Forbidden + exit
    │
    ├─ 2. ¿Hay parámetro ?run= en la URL? (ej: ?run=01_types_and_operators)
    │   SÍ:
    │   ├─ Sanitizar: preg_replace('/[^a-z0-9_\/]/', '', $_GET['run'])
    │   ├─ Construir ruta: /var/www/html/labs/php/{lab}.php
    │   ├─ ¿Existe el archivo?
    │   │   SÍ → $output = shell_exec("php " . escapeshellarg($labFile) . " 2>&1")
    │   │   NO → $labError = "Lab no encontrado"
    │
    ├─ 3. Definir el array $labs con los 23 labs (9 PHP, 7 MySQL, 7 JS)
    │
    └─ 4. Generar HTML:
        ├─ Si hay $output o $labError → mostrar panel de output
        ├─ Para cada sección (php, mysql, javascript):
        │   └─ Para cada lab:
        │       ├─ PHP: botón "Ejecutar" (llama a runLab() via fetch) + enlace "Ver código"
        │       ├─ MySQL: enlace a phpMyAdmin
        │       └─ JavaScript: botón "Ejecutar en consola" (llama a runJsLab())
        └─ Scripts inline:
            ├─ runLab(labId): fetch('?run=labId') → parsea HTML response → muestra en modal
            └─ runJsLab(labId): llama a window.Lab.run(name) si está disponible
```

**Seguridad del parámetro `?run=`:**
1. `preg_replace('/[^a-z0-9_\/]/', '', $_GET['run'])` — elimina cualquier carácter que no sea letra, número, guión bajo o barra. Previene path traversal (`../../etc/passwd`).
2. `escapeshellarg($labFile)` — escapa el argumento para `shell_exec`. Previene inyección de comandos.
3. Solo ejecuta archivos que existan en `labs/php/`.

### `public/api.php` — API REST

Es el **punto de entrada de la API** (`http://localhost:8000/api.php`).

**Flujo general:**

```
Petición HTTP → Apache → public/api.php
    │
    ├─ 1. require_once vendor/autoload.php  ← Composer autoloader
    │
    ├─ 2. Cabeceras CORS:
    │   Content-Type: application/json
    │   Access-Control-Allow-Origin: *
    │   Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
    │
    ├─ 3. Si OPTIONS → HTTP 204 + exit  (preflight CORS)
    │
    ├─ 4. Extraer método y URI:
    │   $method = $_SERVER['REQUEST_METHOD']
    │   $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    │   $uri    = rtrim($uri, '/')  ← elimina trailing slash
    │
    ├─ 5. Enrutamiento: serie de if/preg_match
    │   ├─ GET  /health              → { status, version, php }
    │   ├─ GET  /api/users           → lista paginada (demo)
    │   ├─ POST /api/users           → crear usuario (demo)
    │   ├─ GET  /api/users/{id}      → usuario por ID (demo)
    │   ├─ PUT  /api/users/{id}      → actualizar (demo)
    │   ├─ DELETE /api/users/{id}    → soft delete (demo)
    │   ├─ POST /api/auth/login      → JWT demo
    │   ├─ GET  /api/products        → lista productos (demo)
    │   ├─ GET  /api/orders/{id}     → pedido por ID (demo)
    │   ├─ POST /api/orders          → crear pedido (demo)
    │   └─ *** → HTTP 404
    │
    └─ 6. jsonResponse() → siempre termina con exit
```

**Función `jsonResponse()`:**
```php
function jsonResponse(mixed $data, int $status = 200, ?string $error = null): never
{
    http_response_code($status);
    $response = ['success' => $status < 400];
    if ($error !== null) { $response['error'] = $error; }
    else                 { $response['data']  = $data;  }
    $response['timestamp'] = date('c');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;  // ← termina el proceso siempre
}
```

**Respuesta estándar:**
```json
{
  "success": true,
  "data": { ... },
  "timestamp": "2024-01-15T14:30:00+00:00"
}
```

---

## 12. Flujo completo de una petición HTTP

### Escenario A: Abrir el dashboard

```
Usuario abre http://localhost:8000
    │
    ├─ Docker expone puerto 8000 → Apache en el contenedor app (puerto 80)
    ├─ Apache: DocumentRoot = /var/www/html/public
    ├─ Apache sirve public/index.php
    ├─ PHP ejecuta index.php:
    │   ├─ Verifica APP_ENV ≠ 'production'
    │   ├─ No hay ?run= → $output = '' $labError = ''
    │   ├─ Define $labs (23 labs)
    │   └─ Genera HTML completo con tarjetas de labs
    └─ Apache devuelve HTML al navegador
       └─ Navegador carga /assets/css/style.css y /assets/js/lab.js
```

### Escenario B: Ejecutar un lab PHP desde el dashboard

```
Usuario hace clic en "▶ Ejecutar" del lab "01_types_and_operators"
    │
    ├─ JavaScript: runLab('01_types_and_operators')
    │   ├─ modal.style.display = 'flex'
    │   ├─ output.textContent = 'Ejecutando...'
    │   └─ fetch('?run=01_types_and_operators')
    │
    ├─ Petición HTTP GET /?run=01_types_and_operators → Apache → index.php
    │   ├─ $_GET['run'] = '01_types_and_operators'
    │   ├─ Sanitiza: '01_types_and_operators' (sin cambios, es seguro)
    │   ├─ $labFile = '/var/www/html/labs/php/01_types_and_operators.php'
    │   ├─ file_exists($labFile) = true
    │   ├─ $output = shell_exec('php /var/www/html/labs/php/01_types_and_operators.php 2>&1')
    │   │   └─ PHP ejecuta el lab como proceso hijo y captura stdout+stderr
    │   └─ HTML generado incluye <pre class="output-content">...</pre> con el output
    │
    └─ JavaScript recibe la respuesta HTML:
        ├─ DOMParser parsea el HTML
        ├─ Extrae el contenido de <pre class="output-content">
        └─ Muestra el output en el modal
```

### Escenario C: Login via API

```
POST http://localhost:8000/api.php/api/auth/login
Content-Type: application/json
Body: { "email": "admin@lab.test", "password": "password123" }
    │
    ├─ Apache → public/api.php
    ├─ require_once vendor/autoload.php
    ├─ Cabeceras CORS
    ├─ $method = 'POST', $uri = '/api/auth/login'
    ├─ Router: preg_match fallback... llega a POST /api/auth/login
    ├─ getBody() → file_get_contents('php://input') → json_decode → array
    ├─ Validar: email y password no vacíos
    ├─ filter_var($body['email'], FILTER_SANITIZE_EMAIL)
    ├─ if ($email === 'admin@lab.test' && $password === 'password123'):
    │   ├─ Construir JWT:
    │   │   $header  = base64_encode('{"alg":"HS256","typ":"JWT"}')
    │   │   $payload = base64_encode(json_encode([sub, email, role, iat, exp]))
    │   │   $sig     = base64_encode(hash_hmac('sha256', "$header.$payload", 'demo-secret', true))
    │   │   $token   = "$header.$payload.$sig"
    │   └─ jsonResponse({token, type: 'Bearer', expires: 3600, user}, 200)
    └─ Credenciales incorrectas → jsonResponse(null, 401, 'Credenciales inválidas')
```

### Escenario D: Crear un pedido (con BD real conectada)

```
POST /api/orders
Body: { "user_id": 5, "items": [{"product_id": 1, "quantity": 2, "price": 99.99}] }
    │
    ├─ api.php: POST /api/orders match
    ├─ getBody() → { user_id: 5, items: [...] }
    ├─ Validar: user_id y items no vacíos
    │
    ├─ [CON BD REAL - usando servicios]
    │   ├─ Container::make(OrderService::class)
    │   │   └─ Auto-wiring: OrderService requiere DatabaseInterface
    │   │       └─ Database::getInstance() → conexión PDO (singleton)
    │   │
    │   ├─ RateLimitMiddleware::handle(request, $next)
    │   │   ├─ Verificar IP, contar requests, actualizar static::$store
    │   │   └─ Si OK → $next($request)
    │   │
    │   └─ OrderService::create(5, items)
    │       ├─ new Order()
    │       ├─ $order->setUserId(5), initTimestamps(), addItem(...)
    │       ├─ $db->transaction(function() {
    │       │   ├─ INSERT INTO orders (user_id='5', status='pending', total='199.98', ...)
    │       │   ├─ $order->id = lastInsertId()  → ej. 101
    │       │   └─ INSERT INTO order_items (order_id=101, product_id=1, quantity=2, unit_price=99.99)
    │       │   })  ← COMMIT si todo OK
    │       └─ return $order
    │
    ├─ [MODO DEMO - actual]
    │   ├─ Calcula $total sumando items
    │   └─ jsonResponse({id: random, user_id: 5, status: 'pending', ...}, 201)
    │
    └─ HTTP 201 Created + JSON response
```

---

## 13. Frontend y JavaScript (lab.js)

### `public/assets/js/lab.js`

Archivo cargado en el dashboard. Expone el objeto global `window.Lab` con los 7 labs de JavaScript listos para ejecutar desde la consola del navegador.

**Estructura:**
```javascript
const Lab = {
    config: { verbose: true },

    list()         // Imprime en consola los labs disponibles
    run(name)      // Ejecuta el lab por nombre
    runAll()       // Ejecuta todos los labs

    labs: {
        'this-context':       async function() { /* ... */ },
        'closures':           async function() { /* ... */ },
        'promises-async':     async function() { /* ... */ },
        'prototype-chain':    async function() { /* ... */ },
        'event-loop':         async function() { /* ... */ },
        'destructuring':      async function() { /* ... */ },
        'patterns':           async function() { /* ... */ },
    }
}
window.Lab = Lab;
```

**Uso desde la consola del navegador (F12):**
```javascript
Lab.list()              // Lista todos los labs
Lab.run('closures')     // Ejecuta el lab de closures
Lab.run('promises-async')
Lab.runAll()            // Ejecuta todos
```

**Cuando el usuario hace clic en "▶ Ejecutar en consola":**
```javascript
// index.php genera este onclick:
function runJsLab(labId) {
    const name = labId.replace(/^\d+_/, '').replace(/_/g, '-');
    // '02_closures_scope' → 'closures-scope'

    if (window.Lab && window.Lab.labs[name]) {
        window.Lab.run(name);
        alert('Lab ejecutado. Abre la consola (F12)');
    } else {
        alert(`Ejecuta en consola: Lab.run('${name}')`);
    }
}
```

### `public/assets/css/style.css`

Estilos del dashboard: layout de tarjetas (grid), header, modal de output, botones, panel de output con código monoespaciado. No tiene lógica; es puramente presentacional.

---

## 14. Laboratorios educativos

Los labs son archivos **autocontenidos** diseñados para ejecutarse de forma independiente y producir output educativo en la consola.

### Labs PHP (`labs/php/`)

Cada lab es un script PHP ejecutable con `php nombrearchivo.php`. Produce output en stdout usando `echo` / `var_dump` / `print_r`.

| Archivo | Tema | Lo que demuestra |
|---|---|---|
| `01_types_and_operators.php` | Tipos y operadores | `==` vs `===`, type juggling, Enums, `match`, tipos nullable, union types |
| `02_oop_pillars.php` | POO — 4 pilares | Encapsulamiento (private/protected), herencia, polimorfismo, clases abstractas, interfaces |
| `03_traits.php` | Traits | Múltiples traits, resolución de conflictos con `insteadof` y `as`, traits abstractos |
| `04_closures_and_generators.php` | Closures y generators | Closures, arrow functions (`fn`), `use`, generators con `yield`, lazy evaluation |
| `05_exceptions.php` | Excepciones | Jerarquía custom, `try/catch/finally`, exception chaining, SPL exceptions |
| `06_pdo_security.php` | PDO y seguridad | Prepared statements, SQL injection, XSS con `htmlspecialchars`, CSRF, Argon2ID |
| `07_sessions_auth.php` | Sesiones y JWT | JWT manual paso a paso, configuración segura de sesiones PHP, regeneración de ID |
| `08_patterns.php` | Patrones de diseño | Singleton, Factory, Observer, Strategy, Decorator implementados en PHP |
| `09_array_functions.php` | Funciones de array | `array_map`, `array_filter`, `array_reduce`, `usort`, `array_chunk`, group by manual |

**Flujo de ejecución de un lab PHP:**
```
php labs/php/01_types_and_operators.php
    │
    ├─ PHP interpreta el archivo de arriba a abajo
    ├─ No tiene namespace ni autoloader (son scripts standalone)
    ├─ Usa echo/var_dump/print_r para output
    └─ Finaliza y devuelve control al proceso padre (shell o shell_exec)
```

### Labs MySQL (`labs/mysql/`)

Archivos `.sql` con queries comentadas para ejecutar en phpMyAdmin o MySQL CLI.

| Archivo | Tema |
|---|---|
| `01_joins_explained.sql` | INNER, LEFT, RIGHT, SELF, CROSS JOIN |
| `02_indexes_performance.sql` | EXPLAIN, índices compuestos, covering indexes |
| `03_transactions_acid.sql` | BEGIN/COMMIT/ROLLBACK, SAVEPOINT, isolation levels |
| `04_normalization.sql` | 0NF → 1NF → 2NF → 3NF con ejemplos prácticos |
| `05_aggregations.sql` | GROUP BY, HAVING, Window Functions, LAG/LEAD |
| `06_subqueries.sql` | Subqueries correlacionadas, WITH (CTEs), CTEs recursivas |
| `07_optimization.sql` | EXPLAIN ANALYZE, reescritura de queries, cursor pagination |

**No se ejecutan automáticamente** (el runner CLI los marca como manuales). El usuario los abre en phpMyAdmin (`http://localhost:8080`) y los ejecuta manualmente.

### Labs JavaScript (`labs/javascript/`)

Archivos `.js` con código ES2022 moderno. Pueden ejecutarse de dos formas:
- **Node.js** desde terminal: `node labs/javascript/01_this_context.js`
- **Navegador** via `Lab.run('this-context')` en la consola de DevTools.

| Archivo | Tema |
|---|---|
| `01_this_context.js` | `this` en funciones normales, clases, `bind`/`call`/`apply`, arrow functions |
| `02_closures_scope.js` | Closures, factory functions, memoización, module pattern |
| `03_promises_async.js` | `Promise.all`/`race`/`any`/`allSettled`, `async`/`await`, retry con backoff |
| `04_prototype_chain.js` | `Object.create`, cadena de prototipos, clases ES6, mixins |
| `05_event_loop.js` | Call stack, microtasks (Promises), macrotasks (setTimeout), `nextTick` |
| `06_destructuring_spread.js` | Destructuring de arrays/objetos, rest params, spread, optional chaining `?.` |
| `07_patterns.js` | Observer, Module, Factory, Singleton, Proxy, Iterator en JavaScript moderno |

---

## 15. Runner CLI (`scripts/run_labs.php`)

Herramienta de línea de comandos para ejecutar labs sin necesidad del navegador.

### Arranque

```
php scripts/run_labs.php [--list | --lab=tipo/num | --test | --help]
    │
    ├─ Define constantes de colores ANSI (RESET, BOLD, RED, GREEN, etc.)
    ├─ Define helpers de output: colorize(), printLine(), printHeader(), printSuccess(), ...
    ├─ Define el catálogo LABS (constante) con todos los labs y sus archivos
    ├─ $opts = getopt('', ['list', 'lab:', 'test', 'help'])  ← parsea argv
    ├─ chdir($projectRoot)  ← cambia al directorio raíz (importante para rutas relativas)
    └─ match($action) { 'help', 'list', 'test', 'lab' → funciones correspondientes }
```

### Acciones

#### `--list`
Imprime todos los labs con colores ANSI, agrupados por tipo (PHP 🐘, MySQL 🐬, JS 🟨).

#### `--lab=php/01`
```
executeLab('php', '01', ['file' => 'labs/php/01_types_and_operators.php', ...])
    │
    ├─ Imprime header con nombre del lab
    ├─ file_exists($file) → si no existe → error
    ├─ Si tipo = 'mysql' → instrucciones manuales + exit
    ├─ $cmd = match($type) { 'php' => "php {$file}", 'js' => "node {$file}" }
    ├─ Verificar runtime: exec("which php") → si no existe → error
    ├─ $start = microtime(true)
    ├─ passthru($cmd, $exitCode)  ← ejecuta y muestra output en tiempo real
    ├─ $elapsed = (microtime(true) - $start) * 1000
    └─ Si $exitCode === 0 → "✅ Lab completado en Xms" | "❌ Error: código N"
```

#### `--lab=php/all`
Itera sobre todos los labs del tipo y llama a `executeLab()` para cada uno.

#### `--test`
```
./vendor/bin/phpunit --configuration tests/phpunit.xml --colors=always --testdox
└─ passthru($cmd, $exitCode)
   └─ Si exitCode ≠ 0 → exit($exitCode) (falla el proceso)
```

---

## 16. Tests con PHPUnit

### `tests/phpunit.xml` — Configuración

Define el directorio de tests (`tests/Unit/`), el bootstrap (autoloader de Composer) y la cobertura de código.

### `tests/Unit/DatabaseTest.php`

Prueba la clase `Database`:
- `getInstance()` devuelve siempre la misma instancia (Singleton).
- `query()` ejecuta queries y devuelve `PDOStatement`.
- `transaction()` hace COMMIT en caso de éxito y ROLLBACK en caso de excepción.
- `lastInsertId()` devuelve el ID correcto tras un INSERT.

### `tests/Unit/TraitsTest.php`

Prueba los tres traits:
- **Timestampable:** `initTimestamps()` fija fechas, `touch()` actualiza `updatedAt`, `softDelete()` fija `deletedAt`, `restore()` lo limpia, `isDeleted()` retorna correctamente.
- **Validatable:** reglas `required`, `email`, `min`, `max`, `in` funcionan correctamente.
- **Loggable:** `log()`, `info()`, `warning()`, `error()` almacenan mensajes, `getLogs()` los recupera.

### `tests/Unit/UserServiceTest.php`

Prueba `UserService` con un mock de `DatabaseInterface`:
- `create()` inserta el usuario y devuelve el objeto User con ID asignado.
- `findByEmail()` recupera usuarios por email.
- `hashPassword()` genera un hash Argon2ID verificable con `password_verify()`.
- `verifyPassword()` retorna `true`/`false` correctamente.

**Ejecutar tests:**
```bash
composer test
# o equivalente:
./vendor/bin/phpunit --configuration tests/phpunit.xml
```

---

## 17. Base de datos: esquema y semillas

### `database/migrations/001_create_tables.sql`

Se ejecuta automáticamente al iniciar MySQL en Docker (vía `docker-entrypoint-initdb.d`).

**8 tablas creadas:**

```sql
-- 1. users: autenticación y RBAC
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','user','guest') DEFAULT 'user',
    created_at    DATETIME,
    updated_at    DATETIME,
    deleted_at    DATETIME NULL  -- soft delete
);

-- 2. categories: catálogo jerárquico (self-referencial)
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(110) UNIQUE NOT NULL,
    description TEXT,
    parent_id   INT UNSIGNED NULL REFERENCES categories(id)
);

-- 3. products: catálogo de productos
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED REFERENCES categories(id),
    name        VARCHAR(200) NOT NULL,
    price       DECIMAL(10,2) NOT NULL,  -- nunca FLOAT
    stock       INT UNSIGNED DEFAULT 0,
    sku         VARCHAR(50) UNIQUE,
    active      BOOLEAN DEFAULT true,
    created_at  DATETIME,
    updated_at  DATETIME,
    deleted_at  DATETIME NULL
    -- INDEX (category_id, active) -- índice compuesto
);

-- 4. orders: pedidos con máquina de estados
CREATE TABLE orders (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL REFERENCES users(id),
    status     ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    total      DECIMAL(10,2),
    created_at DATETIME,
    updated_at DATETIME
);

-- 5. order_items: líneas de pedido (precio histórico)
CREATE TABLE order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INT UNSIGNED NOT NULL REFERENCES products(id),
    quantity   INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL  -- precio en el momento de la compra
);

-- 6. sessions: sesiones PHP en BD
CREATE TABLE sessions (
    id            VARCHAR(40) PRIMARY KEY,  -- session_id de PHP
    user_id       INT UNSIGNED REFERENCES users(id),
    payload       JSON,
    ip_address    VARCHAR(45),
    user_agent    TEXT,
    last_activity INT,
    expires_at    DATETIME
);

-- 7. audit_log: trazabilidad de operaciones
CREATE TABLE audit_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED REFERENCES users(id),
    action      VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id   INT UNSIGNED,
    old_values  JSON,
    new_values  JSON,
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Decisiones de diseño clave:**
- `InnoDB` → soporte de FK y transacciones ACID.
- `utf8mb4` → soporte completo de Unicode (emojis y caracteres especiales).
- `DECIMAL(10,2)` para precios → sin errores de redondeo de coma flotante.
- `deleted_at IS NULL` en todas las queries de lectura → soft delete transparente.
- `unit_price` en `order_items` → el precio histórico se preserva aunque cambie en `products`.

### `database/seeds/seed_data.sql`

Inserta datos de ejemplo para poder probar las queries de los labs MySQL sin necesitar crear datos manualmente:
- 20 usuarios (con password_hash de `password123` en Argon2ID).
- 10 categorías (algunas con jerarquía padre-hijo).
- 50 productos distribuidos en las categorías.
- 100 pedidos con sus items correspondientes.
- Registros de auditoría y sesiones de ejemplo.

---

## 18. Diagramas de flujo por escenario

### Flujo de arranque completo del sistema

```
docker compose up
    ├─ MySQL 8.0 arranca
    │   └─ Ejecuta migrations (crea tablas) + seeds (inserta datos)
    │
    ├─ PHP-Apache arranca
    │   ├─ Apache escucha en :80 (expuesto como :8000)
    │   └─ DocumentRoot = /var/www/html/public
    │       └─ Todo el código PHP está en /var/www/html (privado)
    │
    └─ phpMyAdmin arranca → conecta a MySQL

Usuario → http://localhost:8000
    └─ Apache → public/index.php → HTML del dashboard

Usuario → http://localhost:8080
    └─ phpMyAdmin → interfaz gráfica de MySQL

API      → http://localhost:8000/api.php/...
    └─ Apache → public/api.php → JSON response
```

### Árbol de dependencias de clases

```
public/api.php
    └─ vendor/autoload.php (Composer PSR-4)
        └─ Lab\Core\Container
            └─ Lab\Services\UserService
                ├─ Lab\Core\DatabaseInterface (→ Lab\Core\Database)
                │   └─ config/database.php (→ .env)
                │       └─ PDO → MySQL
                ├─ Lab\Models\User
                │   ├─ Lab\Traits\Timestampable
                │   ├─ Lab\Traits\Validatable
                │   └─ Lab\Traits\Loggable
                └─ Lab\Traits\Loggable
            └─ Lab\Services\AuthService
                └─ Lab\Services\UserService (↑ ya resuelto)
            └─ Lab\Services\OrderService
                ├─ Lab\Core\DatabaseInterface (→ singleton ya existente)
                ├─ Lab\Models\Order
                │   ├─ Lab\Traits\Timestampable
                │   └─ Lab\Traits\Loggable
                └─ Lab\Traits\Loggable
        └─ Lab\Middleware\AuthMiddleware
            └─ Lab\Services\AuthService (↑ ya resuelto)
        └─ Lab\Middleware\RateLimitMiddleware
```

### Flujo de autenticación y acceso a endpoint protegido

```
POST /api/auth/login { email, password }
    │
    ├─ api.php → AuthService::login(email, password)
    │   ├─ UserService::findByEmail(email) → SELECT * FROM users WHERE email = ?
    │   ├─ verifyPassword(plain, hash) → password_verify()
    │   └─ generateToken([sub, email, role, iat, exp])
    │       ├─ base64UrlEncode(header) + base64UrlEncode(payload)
    │       └─ HMAC-SHA256(header.payload, $secret) → firma
    └─ { token: "eyJ...", type: "Bearer", expires: 3600 }

Petición a endpoint protegido (ej. DELETE /api/users/5):
Authorization: Bearer eyJ...
    │
    ├─ AuthMiddleware::handle($request, $next)
    │   ├─ Extraer token del header
    │   ├─ AuthService::verifyToken(token)
    │   │   ├─ Verificar firma con hash_equals() (timing-safe)
    │   │   ├─ Verificar expiración (exp < time())
    │   │   └─ Devuelve claims del payload
    │   ├─ $request['auth'] = $claims
    │   └─ $next($request) → handler del endpoint
    │
    └─ Si inválido → HTTP 401 Unauthorized
```

---

## Resumen ejecutivo

| Componente | Archivo | Cuándo se ejecuta |
|---|---|---|
| Arranque Docker | `docker-compose.yml`, `Dockerfile` | `docker compose up` |
| Migraciones BD | `database/migrations/001_create_tables.sql` | Primera vez que arranca MySQL |
| Datos de ejemplo | `database/seeds/seed_data.sql` | Primera vez que arranca MySQL |
| Config BD | `config/database.php` | Al instanciar `Database` (una vez por request) |
| Autoloader | `vendor/autoload.php` | Inicio de cada script PHP |
| Dashboard web | `public/index.php` | Petición HTTP a `/` |
| Ejecución lab PHP | `labs/php/*.php` | Via `shell_exec` desde `index.php` o directamente con `php` |
| API REST | `public/api.php` | Petición HTTP a `/api.php/...` |
| Labs JavaScript | `labs/javascript/*.js` | Via `Lab.run()` en consola del navegador o `node` en terminal |
| Labs MySQL | `labs/mysql/*.sql` | Manual en phpMyAdmin o MySQL CLI |
| Runner CLI | `scripts/run_labs.php` | `php scripts/run_labs.php --lab=php/01` |
| Tests | `tests/Unit/*.php` | `composer test` o `./vendor/bin/phpunit` |
