<?php

declare(strict_types=1);

/**
 * LAB 03: Traits en PHP
 * =====================
 * Ejecutar: php labs/php/03_traits.php
 *
 * Cubre: traits básicos, múltiples traits, conflictos (insteadof/as),
 * trait con método abstracto, y trait que usa otro trait.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Trait básico — Serializable');
// ════════════════════════════════════════════════════════════════════════════
// Un trait agrupa métodos reutilizables que se "mezclan" en clases.
// ✅ RESULTADO: la clase obtiene los métodos del trait como si los definiera.

trait Serializable
{
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function toArray(): array
    {
        // Obtiene todas las propiedades de la clase usando Reflection
        $reflection = new ReflectionClass($this);
        $result = [];
        foreach ($reflection->getProperties() as $prop) {
            $prop->setAccessible(true);
            $result[$prop->getName()] = $prop->getValue($this);
        }
        return $result;
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        $instance = new static();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }
}

class Config
{
    use Serializable;

    public function __construct(
        public string $host    = 'localhost',
        public int    $port    = 3306,
        public bool   $debug   = false,
    ) {}
}

$config = new Config(host: 'db.server.com', port: 5432, debug: true);
echo "\n  Config como JSON:\n";
echo $config->toJson() . "\n";
$restored = Config::fromJson($config->toJson());
echo "  Restaurado: host={$restored->host}, port={$restored->port}, debug=" . ($restored->debug ? 'true' : 'false') . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Múltiples Traits en una clase');
// ════════════════════════════════════════════════════════════════════════════
// Una clase puede usar múltiples traits al mismo tiempo.
// Los traits se comportan como "mixins" de otros lenguajes.

trait HasTimestamps
{
    private string $createdAt = '';
    private string $updatedAt = '';

    public function initTimestamps(): void {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): void {
        $this->updatedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
}

trait HasLogger
{
    private array $logs = [];

    public function log(string $msg): void {
        $this->logs[] = '[' . date('H:i:s') . '] ' . $msg;
    }

    public function getLogs(): array { return $this->logs; }
}

// 👉 MODIFICA: añade otro trait a esta clase
class Article
{
    use HasTimestamps, HasLogger, Serializable;

    public string $title   = '';
    public string $content = '';

    public function __construct(string $title, string $content)
    {
        $this->title   = $title;
        $this->content = $content;
        $this->initTimestamps();
        $this->log("Artículo '{$title}' creado.");
    }

    public function update(string $content): void
    {
        $this->content = $content;
        $this->touch();
        $this->log("Artículo actualizado.");
    }
}

$article = new Article('PHP Traits', 'Los traits son geniales...');
sleep(0); // simular paso del tiempo
$article->update('¡Los traits en PHP son muy poderosos!');

echo "\n  Artículo usando múltiples traits:\n";
echo "  Título: {$article->title}\n";
echo "  Creado: {$article->getCreatedAt()}\n";
echo "  Logs:\n";
foreach ($article->getLogs() as $log) {
    echo "    {$log}\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Conflicto de nombres — insteadof y as');
// ════════════════════════════════════════════════════════════════════════════
// Cuando dos traits tienen un método con el mismo nombre, PHP lanza error.
// Solución: usar `insteadof` para elegir cuál usar, y `as` para renombrar.
// 👉 MODIFICA: cambia el insteadof para ver el conflicto resuelto diferente

trait Logger
{
    public function log(string $msg): void {
        echo "  [Logger] {$msg}\n";
    }

    public function format(string $msg): string {
        return "[LOG] {$msg}";
    }
}

trait Monitor
{
    public function log(string $msg): void {
        echo "  [Monitor] {$msg}\n";
    }

    public function format(string $msg): string {
        return "[MON] {$msg}";
    }
}

class Application
{
    use Logger, Monitor {
        // insteadof: cuando hay conflicto, usar Logger::log en lugar de Monitor::log
        // 👉 MODIFICA: cambia Logger por Monitor para usar la implementación de Monitor
        Logger::log      insteadof Monitor;

        // as: Monitor::log sigue disponible con el alias monitorLog
        Monitor::log     as monitorLog;

        // Para format: usamos Monitor y renombramos Logger::format
        Monitor::format  insteadof Logger;
        Logger::format   as loggerFormat;
    }

    public function run(): void
    {
        $this->log("Aplicación iniciada");             // Usa Logger::log
        $this->monitorLog("Monitor activo");            // Usa Monitor::log
        echo "  Logger format:  " . $this->loggerFormat("test") . "\n";
        echo "  Monitor format: " . $this->format("test") . "\n";
    }
}

echo "\n  Resolución de conflictos de traits:\n\n";
$app = new Application();
$app->run();

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: Trait con método abstracto');
// ════════════════════════════════════════════════════════════════════════════
// Un trait puede declarar métodos abstractos que la clase que lo use
// DEBE implementar. Esto combina reutilización con contrato obligatorio.

trait Renderable
{
    // El trait exige que la clase implemente este método
    abstract protected function template(): string;

    public function render(): string
    {
        $template = $this->template();
        // Reemplazar variables {{var}} en el template
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) {
            $prop = $matches[1];
            return property_exists($this, $prop) ? (string)$this->$prop : "{{$prop}}";
        }, $template);
    }
}

class EmailTemplate
{
    use Renderable;

    public function __construct(
        public string $name    = '',
        public string $subject = '',
        public string $body    = '',
    ) {}

    protected function template(): string {
        return "Para: {{name}}\nAsunto: {{subject}}\n\n{{body}}";
    }
}

$email = new EmailTemplate(name: 'Carlos', subject: 'Bienvenido', body: '¡Hola! Tu cuenta ha sido creada.');
echo "\n  Email renderizado:\n\n";
echo $email->render() . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Trait que usa otro trait');
// ════════════════════════════════════════════════════════════════════════════
// Los traits pueden incluir otros traits, creando composición de traits.

trait Cacheable
{
    use HasLogger; // Este trait reutiliza HasLogger

    private array $cache = [];

    public function remember(string $key, callable $callback, int $ttl = 60): mixed
    {
        if (isset($this->cache[$key])) {
            $this->log("Cache HIT: {$key}");
            return $this->cache[$key];
        }
        $this->log("Cache MISS: {$key} — calculando...");
        $this->cache[$key] = $callback();
        return $this->cache[$key];
    }

    public function forget(string $key): void
    {
        unset($this->cache[$key]);
        $this->log("Cache invalidado: {$key}");
    }
}

class ProductRepository
{
    use Cacheable;

    // Simula una query costosa a BD
    public function findExpensive(): array
    {
        return $this->remember('expensive_products', function() {
            // En producción: SELECT * FROM products WHERE price > 1000
            echo "    [BD] Ejecutando query costosa...\n";
            return [
                ['name' => 'MacBook Pro', 'price' => 2499.99],
                ['name' => 'iPhone 15 Pro', 'price' => 1199.99],
            ];
        });
    }
}

echo "\n  Trait usando otro trait (Cacheable usa HasLogger):\n\n";
$repo = new ProductRepository();
$products1 = $repo->findExpensive(); // Miss → ejecuta callback
$products2 = $repo->findExpensive(); // Hit → devuelve cache
echo "\n  Logs del repositorio:\n";
foreach ($repo->getLogs() as $log) {
    echo "  {$log}\n";
}

echo "\n\n  ✅ Lab 03 completado. Los traits son una herramienta poderosa de reutilización.\n\n";
