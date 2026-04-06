<?php

declare(strict_types=1);

/**
 * LAB 08: Patrones de Diseño en PHP
 * ===================================
 * Ejecutar: php labs/php/08_patterns.php
 *
 * Cubre: Singleton, Factory, Observer, Strategy, Decorator, Repository (InMemory).
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 1: Singleton — Logger global');
// ════════════════════════════════════════════════════════════════════════════
// Singleton: garantiza una única instancia y proporciona acceso global.
// Útil para: logs, configuración, conexión a BD.
// ⚠️ Úsalo con moderación — dificulta las pruebas unitarias.

final class AppLogger
{
    private static ?AppLogger $instance = null;
    private array $entries = [];

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function info(string $msg): void    { $this->add('INFO',  $msg); }
    public function warning(string $msg): void { $this->add('WARN',  $msg); }
    public function error(string $msg): void   { $this->add('ERROR', $msg); }

    private function add(string $level, string $msg): void
    {
        $this->entries[] = sprintf("[%s] %s: %s", date('H:i:s'), $level, $msg);
    }

    public function dump(): void
    {
        foreach ($this->entries as $entry) {
            echo "  {$entry}\n";
        }
    }
}

AppLogger::getInstance()->info("Aplicación iniciada");
AppLogger::getInstance()->warning("Archivo de config no encontrado, usando defaults");
AppLogger::getInstance()->error("No se pudo conectar a Redis");

// Comprobar que es la misma instancia
$logger1 = AppLogger::getInstance();
$logger2 = AppLogger::getInstance();
$isSame  = $logger1 === $logger2;

echo "\n  Logger Singleton:\n";
AppLogger::getInstance()->dump();
echo "\n  ¿\$logger1 === \$logger2? " . ($isSame ? '✅ true (misma instancia)' : '❌ false') . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 2: Factory Method — Creadores de notificaciones');
// ════════════════════════════════════════════════════════════════════════════
// Factory: centraliza la creación de objetos, desacoplando el código cliente
// de las clases concretas.

interface Notification
{
    public function send(string $to, string $message): bool;
    public function getChannel(): string;
}

class EmailNotification implements Notification
{
    public function send(string $to, string $message): bool {
        echo "    📧 Email → {$to}: {$message}\n";
        return true;
    }
    public function getChannel(): string { return 'email'; }
}

class SmsNotification implements Notification
{
    public function send(string $to, string $message): bool {
        echo "    📱 SMS → {$to}: " . substr($message, 0, 50) . "...\n";
        return true;
    }
    public function getChannel(): string { return 'sms'; }
}

class PushNotification implements Notification
{
    public function send(string $to, string $message): bool {
        echo "    🔔 Push → {$to}: {$message}\n";
        return true;
    }
    public function getChannel(): string { return 'push'; }
}

class NotificationFactory
{
    // 👉 MODIFICA: añade un nuevo tipo de notificación (ej. 'slack', 'webhook')
    public static function create(string $type): Notification
    {
        return match($type) {
            'email' => new EmailNotification(),
            'sms'   => new SmsNotification(),
            'push'  => new PushNotification(),
            default => throw new \InvalidArgumentException("Canal desconocido: {$type}"),
        };
    }
}

echo "\n  Factory de notificaciones:\n\n";
$channels = ['email', 'sms', 'push'];
foreach ($channels as $channel) {
    $notification = NotificationFactory::create($channel);
    $notification->send('user@lab.test', "Tu pedido #1234 ha sido enviado.");
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 3: Observer — Sistema de eventos');
// ════════════════════════════════════════════════════════════════════════════
// Observer: cuando un objeto cambia, notifica automáticamente a sus suscriptores.
// Implementa desacoplamiento entre el emisor y los receptores.
// 👉 MODIFICA: añade un nuevo observer

interface EventListener
{
    public function handle(string $event, array $data): void;
}

class EventEmitter
{
    private array $listeners = [];

    public function on(string $event, EventListener $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function emit(string $event, array $data = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener->handle($event, $data);
        }
    }
}

class EmailListener implements EventListener
{
    public function handle(string $event, array $data): void {
        echo "  📧 Email enviado: {$event} → {$data['email']}\n";
    }
}

class AuditListener implements EventListener
{
    public function handle(string $event, array $data): void {
        echo "  📝 Audit log: [{$event}] " . json_encode($data) . "\n";
    }
}

class MetricsListener implements EventListener
{
    public function handle(string $event, array $data): void {
        echo "  📊 Metrics: incrementando contador '{$event}'\n";
    }
}

$emitter = new EventEmitter();
$emitter->on('user.registered', new EmailListener());
$emitter->on('user.registered', new AuditListener());
$emitter->on('user.registered', new MetricsListener());
$emitter->on('order.created', new AuditListener());
$emitter->on('order.created', new MetricsListener());

echo "\n  Sistema de eventos (Observer):\n\n";
$emitter->emit('user.registered', ['id' => 1, 'email' => 'nuevo@lab.test']);
echo "\n";
$emitter->emit('order.created', ['order_id' => 42, 'total' => 299.99]);

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 4: Strategy — Cálculo de precios');
// ════════════════════════════════════════════════════════════════════════════
// Strategy: define una familia de algoritmos, los encapsula y los hace intercambiables.

interface PricingStrategy
{
    public function calculate(float $price, int $quantity): float;
    public function name(): string;
}

class RegularPricing implements PricingStrategy
{
    public function calculate(float $price, int $quantity): float { return $price * $quantity; }
    public function name(): string { return 'Regular'; }
}

class BulkDiscount implements PricingStrategy
{
    public function __construct(private float $discountPct = 15.0) {}
    public function calculate(float $price, int $quantity): float {
        $total = $price * $quantity;
        return $quantity >= 10 ? $total * (1 - $this->discountPct / 100) : $total;
    }
    public function name(): string { return "Bulk -{$this->discountPct}%"; }
}

class MemberPricing implements PricingStrategy
{
    public function calculate(float $price, int $quantity): float {
        return $price * $quantity * 0.9; // 10% de descuento siempre
    }
    public function name(): string { return 'Member -10%'; }
}

class PriceCalculator
{
    public function __construct(private PricingStrategy $strategy) {}

    public function setStrategy(PricingStrategy $strategy): void {
        $this->strategy = $strategy;
    }

    public function calculate(float $price, int $quantity): float {
        return round($this->strategy->calculate($price, $quantity), 2);
    }
}

echo "\n  Estrategias de precio (product: €29.99, qty: 12):\n\n";
$price = 29.99;
$qty   = 12; // 👉 MODIFICA: cambia la cantidad (< 10 para ver diferencia bulk)

$calculator = new PriceCalculator(new RegularPricing());
$strategies = [new RegularPricing(), new BulkDiscount(15), new MemberPricing()];

foreach ($strategies as $strategy) {
    $calculator->setStrategy($strategy);
    $total = $calculator->calculate($price, $qty);
    printf("  %-16s → €%.2f (unitario: €%.2f)\n", $strategy->name(), $total, $total / $qty);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 5: Decorator — Componentes de texto');
// ════════════════════════════════════════════════════════════════════════════
// Decorator: añade responsabilidades a objetos dinámicamente sin modificar su clase.
// Alternativa flexible a la herencia.

interface TextComponent
{
    public function render(): string;
}

class PlainText implements TextComponent
{
    public function __construct(private string $text) {}
    public function render(): string { return $this->text; }
}

abstract class TextDecorator implements TextComponent
{
    public function __construct(protected TextComponent $component) {}
}

class BoldDecorator extends TextDecorator
{
    public function render(): string { return "<b>{$this->component->render()}</b>"; }
}

class ItalicDecorator extends TextDecorator
{
    public function render(): string { return "<i>{$this->component->render()}</i>"; }
}

class ColorDecorator extends TextDecorator
{
    public function __construct(TextComponent $component, private string $color) {
        parent::__construct($component);
    }
    public function render(): string {
        return "<span style='color:{$this->color}'>{$this->component->render()}</span>";
    }
}

$text = new PlainText("Hola, mundo!");
// 👉 MODIFICA: añade o quita capas de decoración
$decorated = new BoldDecorator(new ItalicDecorator(new ColorDecorator($text, 'blue')));

echo "\n  Decorator de texto:\n";
echo "  Sin decorar:  {$text->render()}\n";
echo "  Con decoradores: {$decorated->render()}\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 6: Repository — InMemory (para tests)');
// ════════════════════════════════════════════════════════════════════════════
// Repository: abstrae el acceso a datos. InMemoryRepository es ideal para tests.
// Ventaja: los tests no necesitan BD real.

interface UserRepository
{
    public function save(array $user): array;
    public function findById(int $id): ?array;
    public function findByEmail(string $email): ?array;
    public function delete(int $id): bool;
    public function all(): array;
}

class InMemoryUserRepository implements UserRepository
{
    private array $store = [];
    private int $nextId  = 1;

    public function save(array $user): array {
        if (!isset($user['id'])) {
            $user['id'] = $this->nextId++;
        }
        $this->store[$user['id']] = $user;
        return $user;
    }

    public function findById(int $id): ?array {
        return $this->store[$id] ?? null;
    }

    public function findByEmail(string $email): ?array {
        foreach ($this->store as $user) {
            if (strtolower($user['email']) === strtolower($email)) {
                return $user;
            }
        }
        return null;
    }

    public function delete(int $id): bool {
        if (!isset($this->store[$id])) return false;
        unset($this->store[$id]);
        return true;
    }

    public function all(): array { return array_values($this->store); }
}

$repo = new InMemoryUserRepository();
$u1 = $repo->save(['name' => 'Ana', 'email' => 'ana@lab.test', 'role' => 'admin']);
$u2 = $repo->save(['name' => 'Carlos', 'email' => 'carlos@lab.test', 'role' => 'user']);
$u3 = $repo->save(['name' => 'María', 'email' => 'maria@lab.test', 'role' => 'user']);

echo "\n  InMemory Repository:\n\n";
echo "  findById(1): " . json_encode($repo->findById(1)) . "\n";
echo "  findByEmail('carlos@lab.test'): " . json_encode($repo->findByEmail('carlos@lab.test')) . "\n";
$repo->delete(2);
echo "  delete(2) → all(): " . json_encode(array_column($repo->all(), 'name')) . "\n";

echo "\n\n  ✅ Lab 08 completado. Los patrones de diseño son soluciones probadas a problemas comunes.\n\n";
