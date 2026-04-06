'use strict';
/**
 * LAB 03 JavaScript: Promises y Async/Await
 * ===========================================
 * Ejecutar: node labs/javascript/03_promises_async.js
 */

const CONFIG = {
    DELAY_MS:   200,  // 👉 MODIFICA: simulated network delay
    FAIL_RATE:  0.0,  // 👉 MODIFICA: 0.0 = never fail, 1.0 = always fail
    MAX_RETRIES: 3,   // 👉 MODIFICA: retry attempts
};

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

// Helper: simulated API call
function simulateApiCall(id, failRate = CONFIG.FAIL_RATE) {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            if (Math.random() < failRate) {
                reject(new Error(`API Error: request ${id} failed`));
            } else {
                resolve({ id, data: `Response from request ${id}`, ts: Date.now() });
            }
        }, CONFIG.DELAY_MS + Math.random() * 100);
    });
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 1: Promise básica
// ════════════════════════════════════════════════════════════════
async function exp1() {
    separator('🔬 EXPERIMENTO 1: Promise básica');

    const p = new Promise((resolve, reject) => {
        // La función ejecutora (executor) corre síncronamente
        console.log('\n  Executor corriendo síncronamente...');
        setTimeout(() => resolve('¡Promesa resuelta!'), 50);
        console.log('  (timeout registrado, continuamos síncronamente)');
    });

    console.log('  Esperando promesa...');
    const result = await p;
    console.log('  Resultado:', result);
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 2: Promise chaining
// ════════════════════════════════════════════════════════════════
async function exp2() {
    separator('🔬 EXPERIMENTO 2: Promise Chaining');

    const result = await Promise.resolve(1)
        .then(v => { console.log('\n  Step 1:', v); return v + 1; })
        .then(v => { console.log('  Step 2:', v); return v * 10; })
        .then(v => { console.log('  Step 3:', v); return `Resultado: ${v}`; })
        .catch(err => { console.error('  Error:', err.message); });

    console.log('  Final:', result);
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 3: Promise.all — todas en paralelo
// ════════════════════════════════════════════════════════════════
async function exp3() {
    separator('🔬 EXPERIMENTO 3: Promise.all (todas en paralelo)');

    const start = Date.now();
    try {
        const results = await Promise.all([
            simulateApiCall('A'),
            simulateApiCall('B'),
            simulateApiCall('C'),
        ]);
        const elapsed = Date.now() - start;
        console.log(`\n  Promise.all (${elapsed}ms):`);
        results.forEach(r => console.log(`    ${r.id}: ${r.data}`));
    } catch (err) {
        console.log('  ❌ Promise.all falló (falla si UNA promesa falla):', err.message);
    }
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 4: Promise.allSettled — no falla si alguna falla
// ════════════════════════════════════════════════════════════════
async function exp4() {
    separator('🔬 EXPERIMENTO 4: Promise.allSettled');

    const results = await Promise.allSettled([
        simulateApiCall('X', 0.0),  // éxito
        simulateApiCall('Y', 1.0),  // siempre falla
        simulateApiCall('Z', 0.0),  // éxito
    ]);

    console.log('\n  allSettled (muestra resultados aunque alguno falle):');
    results.forEach(({ status, value, reason }) => {
        if (status === 'fulfilled') console.log(`    ✅ ${value.id}: ${value.data}`);
        else console.log(`    ❌ rejected: ${reason.message}`);
    });
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 5: Promise.race y Promise.any
// ════════════════════════════════════════════════════════════════
async function exp5() {
    separator('🔬 EXPERIMENTO 5: Promise.race y Promise.any');

    // race: la primera en resolver (éxito O fallo)
    const raceResult = await Promise.race([
        new Promise(r => setTimeout(() => r('slow (300ms)'), 300)),
        new Promise(r => setTimeout(() => r('fast (100ms)'), 100)),
        new Promise(r => setTimeout(() => r('medium (200ms)'), 200)),
    ]);
    console.log('\n  Promise.race ganador:', raceResult);

    // any: la primera en RESOLVER (ignora rechazos)
    try {
        const anyResult = await Promise.any([
            Promise.reject(new Error('primero falla')),
            new Promise(r => setTimeout(() => r('segundo éxito'), 100)),
            Promise.reject(new Error('tercero falla')),
        ]);
        console.log('  Promise.any winner:', anyResult);
    } catch (e) {
        console.log('  Promise.any falló (todos rechazados):', e.message);
    }
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 6: Retry con exponential backoff
// ════════════════════════════════════════════════════════════════
async function withRetry(fn, maxRetries = CONFIG.MAX_RETRIES, baseDelay = 50) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await fn();
        } catch (err) {
            if (attempt === maxRetries) throw err;
            const delay = baseDelay * Math.pow(2, attempt - 1); // exponencial
            console.log(`    Intento ${attempt} falló. Reintentando en ${delay}ms...`);
            await new Promise(r => setTimeout(r, delay));
        }
    }
}

async function exp6() {
    separator('🔬 EXPERIMENTO 6: Retry con Exponential Backoff');

    let callCount = 0;
    const flakyApi = () => {
        callCount++;
        // Falla las 2 primeras veces, éxito en la 3ª
        if (callCount < 3) return Promise.reject(new Error(`Intento ${callCount} fallido`));
        return Promise.resolve({ data: '¡Éxito en el intento ' + callCount + '!' });
    };

    console.log('\n  API inestable con retry:');
    try {
        const result = await withRetry(flakyApi, CONFIG.MAX_RETRIES, 20);
        console.log('  ✅ Resultado:', result.data);
    } catch (err) {
        console.log('  ❌ Agotados los reintentos:', err.message);
    }
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 7: Timeout wrapper
// ════════════════════════════════════════════════════════════════
async function withTimeout(promise, ms) {
    const timeout = new Promise((_, reject) =>
        setTimeout(() => reject(new Error(`Timeout después de ${ms}ms`)), ms)
    );
    return Promise.race([promise, timeout]);
}

async function exp7() {
    separator('🔬 EXPERIMENTO 7: Timeout Wrapper');

    // Petición que tarda menos que el timeout
    try {
        const fast = await withTimeout(simulateApiCall('fast'), 500);
        console.log('\n  ✅ Fast request OK:', fast.data);
    } catch (e) {
        console.log('\n  ❌ Fast request timeout:', e.message);
    }

    // Petición que supera el timeout
    try {
        const slow = await withTimeout(
            new Promise(r => setTimeout(() => r('done'), 1000)),
            200 // 👉 MODIFICA: aumenta para que no haga timeout
        );
        console.log('  ✅ Slow request OK:', slow);
    } catch (e) {
        console.log('  ❌ Slow request timeout:', e.message);
    }
}

// ════════════════════════════════════════════════════════════════
// MAIN: ejecutar todos los experimentos
// ════════════════════════════════════════════════════════════════
(async () => {
    await exp1();
    await exp2();
    await exp3();
    await exp4();
    await exp5();
    await exp6();
    await exp7();
    console.log('\n\n  ✅ Lab 03 JS completado. Async/await hace el código asíncrono legible.\n');
})().catch(console.error);
