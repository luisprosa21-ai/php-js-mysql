'use strict';
/**
 * LAB 02 JavaScript: Closures y Scope
 * =====================================
 * Ejecutar: node labs/javascript/02_closures_scope.js
 */

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 1: Closure básico
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Closure básico');

function makeCounter(start = 0) {
    let count = start; // Variable capturada (closed over)

    return {
        increment: () => ++count,
        decrement: () => --count,
        reset:     () => { count = start; return count; },
        value:     () => count,
    };
}

// Cada contador tiene su propio `count` encapsulado
const counter1 = makeCounter(0);
const counter2 = makeCounter(100);

counter1.increment(); counter1.increment(); counter1.increment();
counter2.decrement();

console.log('\n  counter1:', counter1.value()); // 3
console.log('  counter2:', counter2.value()); // 99
console.log('  counter1 reset:', counter1.reset()); // 0

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 2: Factory con closure (configuración privada)
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Closure como Factory');

function createApiClient(baseUrl, apiKey) {
    // apiKey permanece privado en el closure (no accesible externamente)
    const headers = { 'X-API-Key': apiKey, 'Content-Type': 'application/json' };

    async function request(method, path, body = null) {
        // Simular request sin hacer llamada real
        return { url: `${baseUrl}${path}`, method, headers: { ...headers }, body };
    }

    // API pública — apiKey NO está expuesta
    return {
        get:    (path) => request('GET', path),
        post:   (path, data) => request('POST', path, data),
        delete: (path) => request('DELETE', path),
        getBaseUrl: () => baseUrl,  // baseUrl sí es accesible
        // getApiKey: () => apiKey, // ❌ NO expongas esto
    };
}

const client = createApiClient('https://api.lab.test', 'secret-key-123');
console.log('\n  API Client base URL:', client.getBaseUrl());
// console.log(client.apiKey); // undefined — privado gracias al closure

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 3: Memoization con closure
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Memoization');

function memoize(fn) {
    const cache = new Map(); // Privado en el closure

    return function(...args) {
        const key = JSON.stringify(args);
        if (cache.has(key)) {
            console.log(`    Cache HIT: ${key}`);
            return cache.get(key);
        }
        console.log(`    Cache MISS: ${key} — calculando...`);
        const result = fn.apply(this, args);
        cache.set(key, result);
        return result;
    };
}

const expensiveCalc = memoize((n) => {
    let sum = 0;
    for (let i = 1; i <= n; i++) sum += i;
    return sum;
});

console.log('\n  expensiveCalc(100):', expensiveCalc(100));
console.log('  expensiveCalc(100):', expensiveCalc(100)); // desde cache
console.log('  expensiveCalc(200):', expensiveCalc(200));

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 4: El bug clásico de var en loops
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: var vs let en bucles (bug clásico)');

// ❌ Bug con var: todas las funciones comparten la misma variable `i`
const funcsVar = [];
for (var i = 0; i < 3; i++) {
    funcsVar.push(() => i); // todas capturan la MISMA `i`
}
// Al ejecutar, `i` ya es 3 (el loop terminó)
console.log('\n  ❌ Con var:', funcsVar.map(f => f())); // [3, 3, 3] ← bug!

// ✅ Fix con let: cada iteración tiene su propia copia de `i`
const funcsLet = [];
for (let j = 0; j < 3; j++) {
    funcsLet.push(() => j); // cada función captura su propio `j`
}
console.log('  ✅ Con let:', funcsLet.map(f => f())); // [0, 1, 2] ← correcto

// ✅ Fix alternativo con IIFE (antiguo, antes de let/const)
const funcsIIFE = [];
for (var k = 0; k < 3; k++) {
    funcsIIFE.push(((captured) => () => captured)(k));
}
console.log('  ✅ Con IIFE:', funcsIIFE.map(f => f())); // [0, 1, 2]

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 5: Module Pattern con closure
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Module Pattern (IIFE)');

const ShoppingCart = (() => {
    // Estado privado
    let items = [];
    let discount = 0;

    // Métodos privados
    function calculateSubtotal() {
        return items.reduce((sum, item) => sum + item.price * item.qty, 0);
    }

    // API pública
    return {
        add(product, qty = 1) {
            const existing = items.find(i => i.id === product.id);
            if (existing) existing.qty += qty;
            else items.push({ ...product, qty });
        },
        remove(productId) {
            items = items.filter(i => i.id !== productId);
        },
        setDiscount(pct) {
            discount = Math.min(Math.max(pct, 0), 100);
        },
        total() {
            return +(calculateSubtotal() * (1 - discount / 100)).toFixed(2);
        },
        itemCount() { return items.reduce((sum, i) => sum + i.qty, 0); },
        list() { return [...items]; }, // copia inmutable
    };
})();

ShoppingCart.add({ id: 1, name: 'iPhone 15', price: 1199.99 });
ShoppingCart.add({ id: 2, name: 'AirPods', price: 279.99 }, 2);
ShoppingCart.setDiscount(10); // 👉 MODIFICA: cambia el descuento

console.log('\n  Shopping Cart:');
ShoppingCart.list().forEach(i => console.log(`    ${i.name} x${i.qty} = €${(i.price * i.qty).toFixed(2)}`));
console.log(`  Items: ${ShoppingCart.itemCount()}`);
console.log(`  Total (10% desc): €${ShoppingCart.total()}`);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 6: Currying con closures
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: Currying y Partial Application');

// Curry: transformar f(a,b,c) en f(a)(b)(c)
function curry(fn) {
    return function curried(...args) {
        if (args.length >= fn.length) {
            return fn.apply(this, args);
        }
        return function(...args2) {
            return curried.apply(this, args.concat(args2));
        };
    };
}

const add      = curry((a, b, c) => a + b + c);
const multiply = curry((a, b) => a * b);

console.log('\n  add(1)(2)(3):       ', add(1)(2)(3));      // 6
console.log('  add(1, 2)(3):       ', add(1, 2)(3));       // 6
console.log('  add(1)(2, 3):       ', add(1)(2, 3));       // 6

// Partial application: crear funciones especializadas
const double  = multiply(2);
const triple  = multiply(3);
const addTen  = add(10);

console.log('  double(5):          ', double(5));           // 10
console.log('  triple(7):          ', triple(7));           // 21
console.log('  addTen(5)(3):       ', addTen(5)(3));        // 18

// 👉 MODIFICA: crea tu propia función con curry
const tax = curry((rate, amount) => +(amount * (1 + rate / 100)).toFixed(2));
const withVAT = tax(21); // IVA 21%
console.log('  withVAT(100):       ', withVAT(100));        // 121
console.log('  withVAT(1199.99):   ', withVAT(1199.99));    // 1451.99

console.log('\n\n  ✅ Lab 02 JS completado. Los closures son el corazón de JavaScript.\n');
