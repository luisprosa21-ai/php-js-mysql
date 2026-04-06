<?php

declare(strict_types=1);

/**
 * LAB 02: Los 4 Pilares de la POO en PHP
 * ========================================
 * Ejecutar: php labs/php/02_oop_pillars.php
 *
 * Cubre: Encapsulamiento, Herencia, Polimorfismo y Abstracción.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PILAR 1: Encapsulamiento — BankAccount');
// ════════════════════════════════════════════════════════════════════════════
// Encapsulamiento: ocultar el estado interno y exponer solo lo necesario.
// El saldo es privado; solo puede cambiarse a través de deposit/withdraw.
// ✅ RESULTADO: imposible asignar un saldo negativo directamente.

class BankAccount
{
    private float $balance;
    private array $history = [];

    public function __construct(private string $owner, float $initialBalance = 0.0)
    {
        if ($initialBalance < 0) {
            throw new \InvalidArgumentException('El saldo inicial no puede ser negativo.');
        }
        $this->balance = $initialBalance;
        $this->addHistory('open', $initialBalance);
    }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) throw new \InvalidArgumentException('El depósito debe ser positivo.');
        $this->balance += $amount;
        $this->addHistory('deposit', $amount);
    }

    public function withdraw(float $amount): void
    {
        if ($amount <= 0) throw new \InvalidArgumentException('El retiro debe ser positivo.');
        if ($amount > $this->balance) throw new \RuntimeException('Saldo insuficiente.');
        $this->balance -= $amount;
        $this->addHistory('withdraw', $amount);
    }

    public function getBalance(): float { return $this->balance; }
    public function getOwner(): string { return $this->owner; }
    public function getHistory(): array { return $this->history; }

    private function addHistory(string $type, float $amount): void
    {
        $this->history[] = ['type' => $type, 'amount' => $amount, 'balance' => $this->balance];
    }
}

$account = new BankAccount('Ana García', 1000.0);
$account->deposit(500.0);
$account->withdraw(200.0);

echo "\n  Cuenta de: {$account->getOwner()}\n";
echo "  Saldo final: €{$account->getBalance()}\n";
echo "\n  Historial:\n";
foreach ($account->getHistory() as $entry) {
    printf("    %-10s €%7.2f | Saldo: €%.2f\n", $entry['type'], $entry['amount'], $entry['balance']);
}

// ❌ NO HAGAS ESTO:
// $account->balance = -9999; // Error: Cannot access private property

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PILAR 2: Herencia — Vehicle Hierarchy');
// ════════════════════════════════════════════════════════════════════════════
// Herencia: una clase hija hereda propiedades y métodos de la clase padre.
// 👉 MODIFICA: añade un método en Vehicle y observa cómo lo heredan los hijos

abstract class Vehicle
{
    public function __construct(
        protected string $brand,
        protected string $model,
        protected int $year
    ) {}

    abstract public function fuelType(): string;
    abstract public function maxSpeed(): int;

    public function describe(): string {
        return "{$this->year} {$this->brand} {$this->model}";
    }

    // Template Method: define el esqueleto del algoritmo
    public function startUp(): string {
        $steps = $this->getStartUpSteps();
        return implode(' → ', $steps) . ' ✅';
    }

    protected function getStartUpSteps(): array {
        return ['Insertar llave', 'Verificar panel', 'Arrancar motor'];
    }
}

class Car extends Vehicle
{
    public function __construct(
        string $brand,
        string $model,
        int $year,
        private int $doors = 4
    ) {
        parent::__construct($brand, $model, $year);
    }

    public function fuelType(): string { return 'Gasolina'; }
    public function maxSpeed(): int { return 200; }
}

class ElectricCar extends Car
{
    public function __construct(
        string $brand,
        string $model,
        int $year,
        private int $batteryKwh = 75
    ) {
        parent::__construct($brand, $model, $year);
    }

    public function fuelType(): string { return 'Eléctrico'; }
    public function maxSpeed(): int { return 250; }
    public function range(): int { return $this->batteryKwh * 6; } // ~6km/kWh

    protected function getStartUpSteps(): array {
        return ['Conectar batería', 'Verificar BMS', 'Modo eléctrico activo'];
    }
}

$vehicles = [
    new Car('Toyota', 'Corolla', 2022),
    new ElectricCar('Tesla', 'Model 3', 2024, 82),
    new Car('BMW', '320i', 2023, 4),
];

echo "\n  Jerarquía de vehículos:\n\n";
foreach ($vehicles as $v) {
    echo "  📋 {$v->describe()}\n";
    echo "     Combustible: {$v->fuelType()} | Vmax: {$v->maxSpeed()} km/h\n";
    echo "     Arranque: {$v->startUp()}\n";
    if ($v instanceof ElectricCar) {
        echo "     Autonomía: {$v->range()} km\n";
    }
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PILAR 3: Polimorfismo — Shapes (array_reduce)');
// ════════════════════════════════════════════════════════════════════════════
// Polimorfismo: mismo mensaje, comportamiento diferente según el tipo real.
// array_reduce aplica una función sobre cada elemento acumulando un resultado.

abstract class Shape
{
    abstract public function area(): float;
    abstract public function perimeter(): float;
    abstract public function name(): string;

    public function describe(): string {
        return sprintf("%-12s área: %8.2f | perímetro: %8.2f", $this->name(), $this->area(), $this->perimeter());
    }
}

class Circle extends Shape
{
    public function __construct(private float $radius) {}
    public function area(): float { return M_PI * $this->radius ** 2; }
    public function perimeter(): float { return 2 * M_PI * $this->radius; }
    public function name(): string { return "Círculo r={$this->radius}"; }
}

class Rectangle extends Shape
{
    public function __construct(private float $width, private float $height) {}
    public function area(): float { return $this->width * $this->height; }
    public function perimeter(): float { return 2 * ($this->width + $this->height); }
    public function name(): string { return "Rectángulo {$this->width}x{$this->height}"; }
}

class Triangle extends Shape
{
    public function __construct(private float $a, private float $b, private float $c) {}
    public function area(): float {
        $s = ($this->a + $this->b + $this->c) / 2;
        return sqrt($s * ($s-$this->a) * ($s-$this->b) * ($s-$this->c));
    }
    public function perimeter(): float { return $this->a + $this->b + $this->c; }
    public function name(): string { return "Triángulo {$this->a},{$this->b},{$this->c}"; }
}

// 👉 MODIFICA: añade más figuras al array
$shapes = [
    new Circle(5),
    new Rectangle(4, 6),
    new Rectangle(10, 3),
    new Triangle(3, 4, 5),
    new Circle(2.5),
];

echo "\n  Figuras geométricas (polimorfismo):\n\n";
foreach ($shapes as $shape) {
    echo "  {$shape->describe()}\n";
}

// Calcular área total con array_reduce (programación funcional)
$totalArea = array_reduce(
    $shapes,
    fn(float $carry, Shape $shape) => $carry + $shape->area(),
    0.0
);
echo "\n  Área total (array_reduce): " . round($totalArea, 2) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 PILAR 4: Abstracción — Payment Gateways');
// ════════════════════════════════════════════════════════════════════════════
// Abstracción: definir QUÉ debe hacerse sin especificar CÓMO.
// Una interfaz define el contrato; las clases concretas lo implementan.
// ✅ RESULTADO: el código cliente no necesita saber qué gateway se usa.

interface PaymentGateway
{
    public function charge(float $amount, string $currency, array $metadata): array;
    public function refund(string $transactionId, float $amount): bool;
    public function getName(): string;
}

class StripeGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency, array $metadata): array {
        // En producción: llamada a Stripe API
        $txId = 'stripe_' . bin2hex(random_bytes(8));
        return ['success' => true, 'transaction_id' => $txId, 'gateway' => 'Stripe', 'amount' => $amount];
    }

    public function refund(string $transactionId, float $amount): bool {
        echo "    Stripe: Reembolsando €{$amount} de {$transactionId}\n";
        return true;
    }

    public function getName(): string { return 'Stripe'; }
}

class PayPalGateway implements PaymentGateway
{
    public function charge(float $amount, string $currency, array $metadata): array {
        $txId = 'paypal_' . bin2hex(random_bytes(8));
        return ['success' => true, 'transaction_id' => $txId, 'gateway' => 'PayPal', 'amount' => $amount];
    }

    public function refund(string $transactionId, float $amount): bool {
        echo "    PayPal: Reembolsando €{$amount} de {$transactionId}\n";
        return true;
    }

    public function getName(): string { return 'PayPal'; }
}

// El código cliente solo conoce la interfaz, no la implementación
function processPayment(PaymentGateway $gateway, float $amount): void {
    echo "\n  Procesando pago con {$gateway->getName()}:\n";
    $result = $gateway->charge($amount, 'EUR', ['order_id' => 42]);
    echo "    " . ($result['success'] ? '✅' : '❌') . " TX: {$result['transaction_id']} | €{$result['amount']}\n";
    $gateway->refund($result['transaction_id'], 10.0);
}

// 👉 MODIFICA: cambia el gateway para ver el polimorfismo en acción
$gateways = [new StripeGateway(), new PayPalGateway()];
foreach ($gateways as $gw) {
    processPayment($gw, 99.99);
}

echo "\n\n  ✅ Lab 02 completado. Los 4 pilares de POO en acción.\n\n";
