'use strict';
/**
 * LAB 05 JavaScript: El Event Loop
 * ==================================
 * Ejecutar: node labs/javascript/05_event_loop.js
 *
 * El Event Loop de JavaScript determina el orden en que se ejecuta el código.
 * Prioridades: Call Stack → Microtasks (Promises) → Macrotasks (setTimeout)
 */

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 1: Orden básico
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Orden Call Stack → Microtask → Macrotask');

console.log('\n  PREDICCIÓN:');
console.log('  ¿Qué orden esperas?');
console.log('  A: sync, B: microtask, C: macrotask\n');

console.log('  [sync 1] Call stack');

Promise.resolve().then(() => console.log('  [microtask] Promise.resolve().then'));
queueMicrotask(()  => console.log('  [microtask] queueMicrotask'));
setTimeout(()      => console.log('  [macrotask] setTimeout(0)'), 0);

console.log('  [sync 2] Call stack');

// ✅ RESULTADO: sync 1, sync 2, microtask (promise), microtask (queue), macrotask (timeout)

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 2: Microtasks bloquean macrotasks
// ════════════════════════════════════════════════════════════════
setTimeout(() => {
    separator('🔬 EXPERIMENTO 2: Microtasks tienen prioridad sobre Macrotasks');

    setTimeout(() => console.log('\n  [macrotask] setTimeout dentro de macrotask'), 0);

    Promise.resolve()
        .then(() => console.log('  [microtask 1] primera microtask'))
        .then(() => console.log('  [microtask 2] segunda microtask (encadenada)'))
        .then(() => console.log('  [microtask 3] tercera microtask (encadenada)'));

    // ✅ RESULTADO: microtasks 1, 2, 3 se ejecutan ANTES del macrotask siguiente
    console.log('\n  [sync] inicio de macrotask');
}, 100);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 3: async/await y el event loop
// ════════════════════════════════════════════════════════════════
setTimeout(async () => {
    separator('🔬 EXPERIMENTO 3: async/await en el Event Loop');

    async function asyncFn() {
        console.log('  [sync] Inicio de asyncFn');
        const r1 = await Promise.resolve('primer await');
        console.log('  [microtask] después del primer await:', r1);
        const r2 = await Promise.resolve('segundo await');
        console.log('  [microtask] después del segundo await:', r2);
        return 'done';
    }

    console.log('\n  [sync] Antes de asyncFn()');
    const promise = asyncFn(); // Llama a asyncFn, que inicia síncronamente
    console.log('  [sync] Después de asyncFn() (promesa pendiente)');
    const result = await promise;
    console.log('  [sync] asyncFn completed:', result);
}, 300);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 4: setInterval vs setTimeout recursivo
// ════════════════════════════════════════════════════════════════
setTimeout(() => {
    separator('🔬 EXPERIMENTO 4: setInterval vs setTimeout recursivo');

    let intervalCount = 0;
    let timeoutCount  = 0;

    // setInterval: fijo (puede acumularse si la tarea es lenta)
    const intervalId = setInterval(() => {
        intervalCount++;
        process.stdout.write(`  interval[${intervalCount}] `);
        if (intervalCount >= 3) {
            clearInterval(intervalId);
            console.log('');
        }
    }, 30);

    // setTimeout recursivo: siguiente tick solo comienza DESPUÉS de que termine el actual
    function recursiveTimeout() {
        timeoutCount++;
        process.stdout.write(`  recursive[${timeoutCount}] `);
        if (timeoutCount < 3) setTimeout(recursiveTimeout, 30);
        else console.log('');
    }
    recursiveTimeout();

    // ✅ setTimeout recursivo es más seguro para tareas largas
}, 500);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 5: process.nextTick (Node.js) vs Promise
// ════════════════════════════════════════════════════════════════
setTimeout(() => {
    separator('🔬 EXPERIMENTO 5: process.nextTick vs Promise.then');

    // En Node.js: nextTick tiene mayor prioridad que Promise microtasks
    Promise.resolve().then(() => console.log('  [Promise.then]'));
    process.nextTick(() => console.log('  [process.nextTick]'));
    queueMicrotask(() => console.log('  [queueMicrotask]'));

    // ✅ RESULTADO en Node.js: nextTick, Promise.then, queueMicrotask
    console.log('\n  [sync] programadas las microtasks');
}, 900);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 6: Starving el event loop con microtasks infinitas
// ════════════════════════════════════════════════════════════════
setTimeout(() => {
    separator('🔬 EXPERIMENTO 6: Starving (micro-bloqueando el event loop)');

    let count = 0;
    const MAX = 10; // 👉 MODIFICA: pon un número muy grande para ver el problema

    // ❌ NO HAGAS ESTO con bucle infinito de microtasks:
    function infiniteMicrotasks() {
        if (count++ < MAX) {
            Promise.resolve().then(infiniteMicrotasks);
        } else {
            console.log(`\n  Ejecutadas ${count} microtasks — macrotasks bloqueadas durante ese tiempo`);
        }
    }

    console.log('\n  Iniciando microtask recursiva (limitada a ' + MAX + ')...');
    setTimeout(() => console.log('  [macrotask] Este macrotask espera a que terminen las microtasks'), 0);
    infiniteMicrotasks();
}, 1300);

setTimeout(() => {
    console.log('\n\n  ✅ Lab 05 JS completado. El Event Loop es el motor de la asincronía en JS.\n');
}, 2000);
