'use strict';
/**
 * LAB 06 JavaScript: Destructuring y Spread
 * ============================================
 * Ejecutar: node labs/javascript/06_destructuring_spread.js
 */

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 1: Destructuring de Arrays
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Array Destructuring');

const colors = ['red', 'green', 'blue', 'yellow', 'purple'];

// 👉 MODIFICA: prueba diferentes posiciones y valores por defecto
const [first, second, , fourth = 'default'] = colors;
console.log('\n  first:', first, '| second:', second, '| fourth:', fourth);

// Swap sin variable temporal
let [a, b] = [1, 2];
console.log('  Antes del swap: a=' + a + ', b=' + b);
[a, b] = [b, a];
console.log('  Después del swap: a=' + a + ', b=' + b);

// Rest element
const [head, ...tail] = colors;
console.log('  head:', head, '| tail:', tail);

// Ignorar elementos
const [,, third] = colors;
console.log('  third element:', third);

// Destructuring anidado
const matrix = [[1, 2], [3, 4], [5, 6]];
const [[r1c1, r1c2], [r2c1]] = matrix;
console.log('  matrix[0][0]:', r1c1, '| matrix[0][1]:', r1c2, '| matrix[1][0]:', r2c1);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 2: Destructuring de Objetos
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Object Destructuring');

const product = {
    id:    1,
    name:  'iPhone 15 Pro',
    price: 1199.99,
    specs: {
        storage: '256GB',
        color:   'Titanium',
        camera:  '48MP',
    },
    tags:  ['smartphone', 'apple', '5G'],
};

// Renombrar y valor por defecto
const { name: productName, price, stock = 0, specs: { storage, color } } = product;
console.log('\n  productName:', productName);
console.log('  price:', price, '| stock (default):', stock);
console.log('  specs.storage:', storage, '| specs.color:', color);

// Rest en objetos
const { id, name: _name, ...rest } = product;
console.log('  id:', id);
console.log('  rest keys:', Object.keys(rest));

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 3: Destructuring en parámetros de función
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Destructuring en parámetros');

// ❌ Sin destructuring: muchos parámetros o un objeto opaco
function createUser_old(name, email, role, active, age) {
    return { name, email, role, active, age };
}

// ✅ Con destructuring: named params, valores por defecto claros
function createUser({ name, email, role = 'user', active = true, age = 0 } = {}) {
    return { name, email, role, active, age };
}

const user = createUser({ name: 'Ana', email: 'ana@lab.test', age: 28 });
console.log('\n  createUser:', user);

// Array destructuring en params
function stats([first, ...rest]) {
    return { first, count: rest.length + 1, last: rest[rest.length - 1] };
}
console.log('  stats([10,20,30,40]):', stats([10, 20, 30, 40]));

// Callback con destructuring
const orders = [
    { id: 1, user: 'Ana', total: 99.99, status: 'delivered' },
    { id: 2, user: 'Carlos', total: 299.50, status: 'pending' },
];

const delivered = orders
    .filter(({ status }) => status === 'delivered')
    .map(({ id, user, total }) => `Order #${id} by ${user}: €${total}`);

console.log('  Delivered orders:', delivered);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 4: Spread en Arrays
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: Spread en Arrays');

const arr1 = [1, 2, 3];
const arr2 = [4, 5, 6];

// Spread para combinar arrays (inmutablemente)
const combined = [...arr1, ...arr2];
const withMiddle = [...arr1, 0, ...arr2];
console.log('\n  combined:', combined);
console.log('  withMiddle:', withMiddle);

// Clonar array (shallow copy)
const original = [{ id: 1 }, { id: 2 }];
const shallowCopy = [...original];
shallowCopy.push({ id: 3 }); // No afecta al original
console.log('  original length:', original.length, '| copy length:', shallowCopy.length);

// Spread con Math
const nums = [3, 1, 4, 1, 5, 9, 2, 6];
console.log('  Math.max(...nums):', Math.max(...nums));
console.log('  Math.min(...nums):', Math.min(...nums));

// Convertir string a array de caracteres
const str = 'Hello';
const chars = [...str];
console.log('  [...\"Hello\"]:', chars);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 5: Spread en Objetos
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Spread en Objetos');

const defaults = { timeout: 30, retries: 3, debug: false, host: 'localhost' };
const custom   = { retries: 5, debug: true, port: 3000 };

// Merge (right overrides left)
const config = { ...defaults, ...custom };
console.log('\n  Merged config:', config);

// Update inmutable (patrón Redux/React)
const state = { user: 'Ana', role: 'user', count: 0 };
const newState = { ...state, count: state.count + 1, role: 'admin' };
console.log('  state (unchanged):', state);
console.log('  newState:          ', newState);

// Eliminar propiedad (inmutablemente)
const { debug: _debug, ...configWithoutDebug } = config;
console.log('  Config sin debug:', configWithoutDebug);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 6: Rest Parameters y Optional Chaining
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: Rest Parameters y Optional Chaining');

function sum(first, ...rest) {
    return rest.reduce((acc, n) => acc + n, first);
}
console.log('\n  sum(1,2,3,4,5):', sum(1, 2, 3, 4, 5));

// Optional chaining (?.)
const userData = {
    profile: {
        address: { city: 'Madrid' }
    }
};

console.log('  address city:', userData?.profile?.address?.city);    // 'Madrid'
console.log('  phone:       ', userData?.profile?.phone?.number);    // undefined (no error)
console.log('  method:      ', userData?.profile?.method?.());       // undefined

// Nullish coalescing con optional chaining
const city    = userData?.profile?.address?.city ?? 'Unknown';
const country = userData?.profile?.address?.country ?? 'Unknown';
console.log('  city:', city, '| country:', country);

// 👉 MODIFICA: añade más propiedades al objeto y experimenta
const nested = { a: { b: { c: { d: 42 } } } };
console.log('  nested.a.b.c.d:', nested?.a?.b?.c?.d);
console.log('  nested.a.x.y.z:', nested?.a?.x?.y?.z ?? 'not found');

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 7: Desestructuración avanzada — import/export pattern
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 7: Casos de uso avanzados');

// Swap múltiple
let [x, y, z] = [1, 2, 3];
[x, y, z] = [z, x, y];
console.log('\n  Swap [1,2,3] → [z,x,y]:', x, y, z);

// Extraer valores de Map
const map = new Map([['name', 'Ana'], ['age', 28], ['city', 'Madrid']]);
for (const [key, value] of map) {
    console.log(`  Map: ${key} = ${value}`);
}

// Pattern matching con destructuring
function processEvent({ type, payload: { userId, data } = {} }) {
    return `Event[${type}] user=${userId} data=${JSON.stringify(data)}`;
}
console.log('\n  ' + processEvent({ type: 'LOGIN', payload: { userId: 1, data: { ip: '127.0.0.1' } } }));

console.log('\n\n  ✅ Lab 06 JS completado. Destructuring hace el código más expresivo y limpio.\n');
