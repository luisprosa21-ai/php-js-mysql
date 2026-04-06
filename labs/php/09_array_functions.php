<?php

declare(strict_types=1);

/**
 * LAB 09: Funciones de Array en PHP
 * ===================================
 * Ejecutar: php labs/php/09_array_functions.php
 *
 * Cubre: array_map, array_filter, array_reduce, usort, array_chunk,
 * array_combine, group by custom, array_diff, array_intersect.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ⚙️ CONFIGURACIÓN — Array de productos para todos los experimentos
// 👉 MODIFICA: añade, elimina o cambia productos para experimentar
$products = [
    ['id' => 1,  'name' => 'iPhone 15 Pro',    'price' => 1199.99, 'stock' => 30,  'category' => 'smartphones'],
    ['id' => 2,  'name' => 'MacBook Pro M3',   'price' => 2499.99, 'stock' => 15,  'category' => 'laptops'],
    ['id' => 3,  'name' => 'AirPods Pro',       'price' => 279.99,  'stock' => 55,  'category' => 'audio'],
    ['id' => 4,  'name' => 'iPad Pro',          'price' => 999.99,  'stock' => 25,  'category' => 'tablets'],
    ['id' => 5,  'name' => 'Samsung Galaxy S24','price' => 899.99,  'stock' => 45,  'category' => 'smartphones'],
    ['id' => 6,  'name' => 'Dell XPS 15',       'price' => 1799.99, 'stock' => 20,  'category' => 'laptops'],
    ['id' => 7,  'name' => 'Sony WH-1000XM5',  'price' => 349.99,  'stock' => 40,  'category' => 'audio'],
    ['id' => 8,  'name' => 'Surface Pro 9',     'price' => 1299.99, 'stock' => 18,  'category' => 'tablets'],
    ['id' => 9,  'name' => 'Pixel 8 Pro',       'price' => 799.99,  'stock' => 35,  'category' => 'smartphones'],
    ['id' => 10, 'name' => 'JBL Charge 5',      'price' => 179.99,  'stock' => 85,  'category' => 'audio'],
    ['id' => 11, 'name' => 'Kindle Paperwhite', 'price' =>  139.99, 'stock' => 120, 'category' => 'readers'],
    ['id' => 12, 'name' => 'Garmin Fenix 7',    'price' => 699.99,  'stock' => 20,  'category' => 'wearables'],
];

function show(mixed $data, string $label = ''): void {
    if ($label) echo "  {$label}:\n";
    if (is_array($data)) {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                echo "    [{$key}] " . json_encode($item, JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo "    [{$key}] " . json_encode($item) . "\n";
            }
        }
    } else {
        echo "  {$data}\n";
    }
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: array_map — Transformar datos');
// ════════════════════════════════════════════════════════════════════════════
// array_map aplica una función a cada elemento y devuelve un nuevo array.
// No modifica el array original. Equivalente a .map() en JavaScript.

// Transformar a formato simplificado con precio formateado
$simplified = array_map(
    fn($p) => [
        'id'    => $p['id'],
        'name'  => $p['name'],
        'price' => '€' . number_format($p['price'], 2),
    ],
    $products
);

echo "\n  Productos con precio formateado:\n";
foreach (array_slice($simplified, 0, 3) as $p) {
    echo "  {$p['id']}. {$p['name']} → {$p['price']}\n";
}
echo "  ... (" . count($simplified) . " total)\n";

// array_map con múltiples arrays (zip)
$names  = array_column($products, 'name');
$prices = array_column($products, 'price');
$discounted = array_map(
    fn($name, $price) => "{$name}: €" . round($price * 0.9, 2),
    $names,
    $prices
);
echo "\n  Precios con 10% descuento:\n";
foreach (array_slice($discounted, 0, 3) as $d) {
    echo "  {$d}\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: array_filter — Filtrar elementos');
// ════════════════════════════════════════════════════════════════════════════
// array_filter mantiene solo los elementos que superan el test (callback).
// Sin callback, elimina valores falsy (0, '', null, false, []).

// 👉 MODIFICA: cambia los criterios de filtrado
$expensive  = array_filter($products, fn($p) => $p['price'] >= 500);           // precio >= 500
$inStock    = array_filter($products, fn($p) => $p['stock'] >= 50);            // stock >= 50
$smartphones = array_filter($products, fn($p) => $p['category'] === 'smartphones');

echo "\n  Productos caros (≥€500): " . count($expensive) . "\n";
foreach ($expensive as $p) {
    printf("    %-25s €%.2f\n", $p['name'], $p['price']);
}

echo "\n  Con stock alto (≥50): " . count($inStock) . "\n";
foreach ($inStock as $p) {
    printf("    %-25s stock: %d\n", $p['name'], $p['stock']);
}

// Combinar filter + map (flujo funcional)
$expensiveNames = array_map(
    fn($p) => $p['name'],
    array_filter($products, fn($p) => $p['price'] > 1000)
);
echo "\n  Nombres de productos > €1000: " . implode(', ', $expensiveNames) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: array_reduce — Acumulación');
// ════════════════════════════════════════════════════════════════════════════
// array_reduce aplica una función acumulando un único resultado.
// Equivalente a .reduce() en JavaScript.

$totalValue = array_reduce(
    $products,
    fn(float $carry, array $p) => $carry + ($p['price'] * $p['stock']),
    0.0
);

$totalProducts = array_reduce($products, fn($c, $p) => $c + 1, 0);
$avgPrice      = array_reduce($products, fn($c, $p) => $c + $p['price'], 0.0) / $totalProducts;

// Construir índice por ID (reduce como builder de estructuras)
$indexById = array_reduce($products, function(array $carry, array $p): array {
    $carry[$p['id']] = $p;
    return $carry;
}, []);

echo "\n  Estadísticas (array_reduce):\n";
echo "  Valor total inventario: €" . number_format($totalValue, 2) . "\n";
echo "  Precio medio:           €" . number_format($avgPrice, 2) . "\n";
echo "  indexById[7]['name']:   " . ($indexById[7]['name'] ?? 'N/A') . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: usort — Ordenación personalizada');
// ════════════════════════════════════════════════════════════════════════════

// 👉 MODIFICA: cambia el campo de ordenación ('price', 'stock', 'name')
$sortField = 'price';

$sorted = $products;
usort($sorted, fn($a, $b) => $a[$sortField] <=> $b[$sortField]); // ← spaceship operator

echo "\n  Productos ordenados por {$sortField} (asc):\n";
foreach (array_slice($sorted, 0, 5) as $p) {
    printf("    %-25s %s: %s\n", $p['name'], $sortField,
        $sortField === 'price' ? '€' . number_format($p[$sortField], 2) : $p[$sortField]);
}

// Orden descendente con múltiples criterios
$multiSort = $products;
usort($multiSort, function($a, $b) {
    // Primero por categoría, luego por precio desc
    $catCmp = strcmp($a['category'], $b['category']);
    if ($catCmp !== 0) return $catCmp;
    return $b['price'] <=> $a['price'];
});

echo "\n  Ordenado por categoría asc, luego precio desc:\n";
foreach (array_slice($multiSort, 0, 4) as $p) {
    printf("    %-12s %-25s €%.2f\n", $p['category'], $p['name'], $p['price']);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: array_chunk — Paginar resultados');
// ════════════════════════════════════════════════════════════════════════════

$perPage    = 4; // 👉 MODIFICA: cambia el tamaño de página
$pages      = array_chunk($products, $perPage);
$totalPages = count($pages);

echo "\n  Paginación con array_chunk ({$perPage} por página):\n";
echo "  Total: " . count($products) . " productos | {$totalPages} páginas\n";

$currentPage = 1; // 👉 MODIFICA: cambia a 2 o 3
$page = $pages[$currentPage - 1] ?? [];
echo "\n  Página {$currentPage}:\n";
foreach ($page as $p) {
    printf("    %2d. %-25s €%.2f\n", $p['id'], $p['name'], $p['price']);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: array_combine y array_column');
// ════════════════════════════════════════════════════════════════════════════

// array_column extrae una columna de un array multidimensional
$names  = array_column($products, 'name');
$prices = array_column($products, 'price');

// array_combine crea un array usando un array como claves y otro como valores
$priceByName = array_combine($names, $prices);

echo "\n  Precio por nombre (array_combine + array_column):\n";
$top3 = array_slice($priceByName, 0, 3, true);
foreach ($top3 as $name => $price) {
    echo "  '{$name}': €{$price}\n";
}

// array_column con índice propio: indexar por campo
$byId = array_column($products, null, 'id');
echo "\n  Product by ID[12]: " . json_encode($byId[12] ?? null, JSON_UNESCAPED_UNICODE) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 7: Group By (custom con array_reduce)');
// ════════════════════════════════════════════════════════════════════════════
// PHP no tiene un array_group_by nativo — lo implementamos con array_reduce.

$grouped = array_reduce($products, function(array $carry, array $p): array {
    $carry[$p['category']][] = $p;
    return $carry;
}, []);

echo "\n  Productos agrupados por categoría:\n";
foreach ($grouped as $category => $items) {
    $names = implode(', ', array_column($items, 'name'));
    printf("  %-12s (%d): %s\n", $category, count($items), substr($names, 0, 50));
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 8: array_diff e array_intersect');
// ════════════════════════════════════════════════════════════════════════════

$categoriesA = ['smartphones', 'laptops', 'audio', 'tablets'];
$categoriesB = ['audio', 'tablets', 'wearables', 'readers'];

$onlyInA = array_diff($categoriesA, $categoriesB);    // En A pero no en B
$onlyInB = array_diff($categoriesB, $categoriesA);    // En B pero no en A
$inBoth  = array_intersect($categoriesA, $categoriesB); // En ambos

echo "\n  Set operations:\n";
echo "  A = " . json_encode($categoriesA) . "\n";
echo "  B = " . json_encode($categoriesB) . "\n\n";
echo "  A - B (solo en A): " . json_encode(array_values($onlyInA)) . "\n";
echo "  B - A (solo en B): " . json_encode(array_values($onlyInB)) . "\n";
echo "  A ∩ B (en ambos):  " . json_encode(array_values($inBoth)) . "\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 9: Cadena funcional completa');
// ════════════════════════════════════════════════════════════════════════════
// Combinar múltiples funciones de array en un flujo funcional elegante.

// Obtener el nombre y precio de los 3 productos más baratos en stock (>20 unidades)
$result = array_slice(
    array_map(
        fn($p) => "{$p['name']} (€{$p['price']})",
        array_values(
            array_filter($products, fn($p) => $p['stock'] > 20)
        )
    ),
    0,
    3
);

// La versión con sort incluido:
$cheapInStock = $products;
usort($cheapInStock, fn($a, $b) => $a['price'] <=> $b['price']);
$cheapInStock = array_filter($cheapInStock, fn($p) => $p['stock'] > 20);
$cheapInStock = array_slice(array_values($cheapInStock), 0, 3);

echo "\n  Top 3 más baratos con stock > 20:\n";
foreach ($cheapInStock as $p) {
    printf("    %-25s €%-8.2f stock:%d\n", $p['name'], $p['price'], $p['stock']);
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 10: Funciones de array adicionales');
// ════════════════════════════════════════════════════════════════════════════

echo "\n  Más funciones útiles:\n\n";

// array_unique: eliminar duplicados
$cats = array_map(fn($p) => $p['category'], $products);
$uniqueCats = array_unique($cats);
echo "  array_unique (categorías): " . implode(', ', $uniqueCats) . "\n";

// array_flip: intercambiar claves y valores
$flipped = array_flip(['a' => 1, 'b' => 2, 'c' => 3]);
echo "  array_flip: " . json_encode($flipped) . "\n";

// array_merge vs spread operator
$defaults = ['timeout' => 30, 'retries' => 3, 'debug' => false];
$custom   = ['retries' => 5, 'debug' => true];
$merged   = array_merge($defaults, $custom);
echo "  array_merge: " . json_encode($merged) . "\n";
$spread   = [...$defaults, ...$custom]; // equivalente
echo "  Spread (...): " . json_encode($spread) . "\n";

// array_walk: modificar in-place (con referencia)
$prices2 = array_column($products, 'price', 'name');
array_walk($prices2, function(&$price, $name) {
    $price = '€' . number_format($price, 2);
});
echo "  array_walk (primeros 3): \n";
foreach (array_slice($prices2, 0, 3, true) as $name => $price) {
    echo "    {$name}: {$price}\n";
}

echo "\n\n  ✅ Lab 09 completado. Las funciones de array de PHP son muy poderosas.\n\n";
