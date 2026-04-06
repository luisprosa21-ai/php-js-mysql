'use strict';
/**
 * LAB 07 JavaScript: Patrones de Diseño
 * ========================================
 * Ejecutar: node labs/javascript/07_patterns.js
 */

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

// ════════════════════════════════════════════════════════════════
// PATRÓN 1: Observer / EventEmitter
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 1: Observer / EventEmitter');

class EventEmitter {
    #listeners = new Map();

    on(event, handler) {
        if (!this.#listeners.has(event)) this.#listeners.set(event, new Set());
        this.#listeners.get(event).add(handler);
        return () => this.off(event, handler); // devuelve función de cleanup
    }

    once(event, handler) {
        const wrapper = (...args) => { handler(...args); this.off(event, wrapper); };
        return this.on(event, wrapper);
    }

    off(event, handler) {
        this.#listeners.get(event)?.delete(handler);
    }

    emit(event, ...args) {
        this.#listeners.get(event)?.forEach(h => h(...args));
    }
}

class Store extends EventEmitter {
    #state;

    constructor(initialState) {
        super();
        this.#state = { ...initialState };
    }

    get state() { return { ...this.#state }; } // Inmutable externamente

    dispatch(action) {
        const prev = this.#state;
        this.#state = this.reduce(this.#state, action);
        this.emit('change', this.#state, prev, action);
    }

    reduce(state, { type, payload }) {
        switch (type) {
            case 'INCREMENT': return { ...state, count: state.count + (payload ?? 1) };
            case 'DECREMENT': return { ...state, count: state.count - (payload ?? 1) };
            case 'RESET':     return { ...state, count: 0 };
            default: return state;
        }
    }
}

const store = new Store({ count: 0, user: 'Ana' });
const unsubscribe = store.on('change', (state, prev, action) => {
    console.log(`  [change] ${action.type}: ${prev.count} → ${state.count}`);
});

console.log('\n  Redux-like Store:');
store.dispatch({ type: 'INCREMENT' });
store.dispatch({ type: 'INCREMENT', payload: 5 });
store.dispatch({ type: 'DECREMENT' });
store.dispatch({ type: 'RESET' });
unsubscribe();
store.dispatch({ type: 'INCREMENT' }); // no se loggea

// ════════════════════════════════════════════════════════════════
// PATRÓN 2: Module Pattern (IIFE)
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 2: Module Pattern');

const CacheModule = (() => {
    const _store = new Map();
    const _stats = { hits: 0, misses: 0 };

    return {
        set(key, value, ttl = Infinity) {
            _store.set(key, { value, expiresAt: ttl === Infinity ? Infinity : Date.now() + ttl });
        },
        get(key) {
            const entry = _store.get(key);
            if (!entry) { _stats.misses++; return undefined; }
            if (Date.now() > entry.expiresAt) { _store.delete(key); _stats.misses++; return undefined; }
            _stats.hits++;
            return entry.value;
        },
        delete: (key) => _store.delete(key),
        clear:  () => _store.clear(),
        stats:  () => ({ ..._stats, size: _store.size }),
    };
})();

console.log('\n  Cache Module:');
CacheModule.set('user:1', { name: 'Ana' }, 5000);
CacheModule.set('config', { debug: true });
console.log('  get user:1:', CacheModule.get('user:1'));
console.log('  get missing:', CacheModule.get('missing'));
console.log('  stats:', CacheModule.stats());

// ════════════════════════════════════════════════════════════════
// PATRÓN 3: Factory
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 3: Factory Function');

// Factory sin class (ventaja: no necesita `new`, no expone prototype)
function createLogger(namespace, level = 'info') {
    const levels = { debug: 0, info: 1, warn: 2, error: 3 };
    const minLevel = levels[level] ?? 1;

    function log(lvl, ...args) {
        if ((levels[lvl] ?? 0) >= minLevel) {
            const time = new Date().toTimeString().slice(0, 8);
            console.log(`  [${time}][${namespace}][${lvl.toUpperCase()}]`, ...args);
        }
    }

    return {
        debug: (...a) => log('debug', ...a),
        info:  (...a) => log('info',  ...a),
        warn:  (...a) => log('warn',  ...a),
        error: (...a) => log('error', ...a),
        child: (sub)  => createLogger(`${namespace}:${sub}`, level),
    };
}

console.log('\n  Factory Logger:');
const logger   = createLogger('App', 'info');
const dbLogger = logger.child('DB');
logger.debug('Este mensaje no aparece (level=info)');
logger.info('Aplicación iniciada');
dbLogger.warn('Conexión lenta a la BD');
dbLogger.error('Query fallida');

// ════════════════════════════════════════════════════════════════
// PATRÓN 4: Singleton
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 4: Singleton');

class Config {
    static #instance = null;
    #settings = {};

    constructor() {
        if (Config.#instance) return Config.#instance;
        this.#settings = { env: 'development', debug: true, version: '1.0.0' };
        Config.#instance = this;
    }

    get(key) { return this.#settings[key]; }
    set(key, value) { this.#settings[key] = value; }
    all() { return { ...this.#settings }; }

    static getInstance() {
        return Config.#instance ?? new Config();
    }
}

const cfg1 = new Config();
const cfg2 = new Config();
cfg1.set('debug', false);

console.log('\n  Singleton:');
console.log('  cfg1 === cfg2:', cfg1 === cfg2);  // true
console.log('  cfg2.debug:', cfg2.get('debug'));  // false (misma instancia)

// ════════════════════════════════════════════════════════════════
// PATRÓN 5: Strategy
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 5: Strategy Pattern');

class Sorter {
    #strategy;

    constructor(strategy) { this.#strategy = strategy; }
    setStrategy(s) { this.#strategy = s; }
    sort(arr) { return this.#strategy([...arr]); } // no mutamos el original
}

const strategies = {
    bubble: (arr) => {
        for (let i = 0; i < arr.length; i++)
            for (let j = 0; j < arr.length - i - 1; j++)
                if (arr[j] > arr[j+1]) [arr[j], arr[j+1]] = [arr[j+1], arr[j]];
        return arr;
    },
    quick: (arr) => {
        if (arr.length <= 1) return arr;
        const [pivot, ...rest] = arr;
        return [...strategies.quick(rest.filter(x => x <= pivot)), pivot, ...strategies.quick(rest.filter(x => x > pivot))];
    },
    builtin: (arr) => arr.sort((a, b) => a - b),
};

const data = [64, 34, 25, 12, 22, 11, 90];
const sorter = new Sorter(strategies.builtin);

console.log('\n  Original:', data);
Object.entries(strategies).forEach(([name, strategy]) => {
    sorter.setStrategy(strategy);
    console.log(`  ${name.padEnd(8)}:`, sorter.sort(data));
});

// ════════════════════════════════════════════════════════════════
// PATRÓN 6: Proxy para validación
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 6: Proxy para validación y observación');

function createValidatedObject(target, validators) {
    return new Proxy(target, {
        set(obj, key, value) {
            if (key in validators) {
                const error = validators[key](value);
                if (error) throw new TypeError(`${key}: ${error}`);
            }
            obj[key] = value;
            console.log(`  ✅ ${key} = ${JSON.stringify(value)}`);
            return true;
        },
        get(obj, key) {
            return obj[key];
        },
    });
}

const user = createValidatedObject({}, {
    name:  (v) => typeof v !== 'string' || v.length < 2 ? 'Debe ser string de min 2 chars' : null,
    email: (v) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? 'Email inválido' : null,
    age:   (v) => typeof v !== 'number' || v < 0 || v > 150 ? 'Debe ser número entre 0 y 150' : null,
});

console.log('\n  Proxy con validación:');
user.name  = 'Ana García';
user.email = 'ana@lab.test';
user.age   = 28;

try { user.email = 'not-an-email'; }
catch (e) { console.log('  ❌', e.message); }

try { user.age = -5; }
catch (e) { console.log('  ❌', e.message); }

// ════════════════════════════════════════════════════════════════
// PATRÓN 7: Iterator personalizado
// ════════════════════════════════════════════════════════════════
separator('🔬 PATRÓN 7: Iterator personalizado');

class Range {
    constructor(start, end, step = 1) {
        this.start = start;
        this.end   = end;
        this.step  = step;
    }

    // Hacer la clase iterable con Symbol.iterator
    [Symbol.iterator]() {
        let current = this.start;
        const { end, step } = this;
        return {
            next() {
                if (current <= end) {
                    const value = current;
                    current += step;
                    return { value, done: false };
                }
                return { value: undefined, done: true };
            },
        };
    }
}

const range = new Range(1, 10, 2); // 1, 3, 5, 7, 9
console.log('\n  Range(1, 10, 2):');
console.log('  for..of:', [...range]);
console.log('  destructuring:', [...new Range(0, 4)]);

// Generator como iterator (más conciso)
function* fibonacci() {
    let [a, b] = [0, 1];
    while (true) { yield a; [a, b] = [b, a + b]; }
}

function take(n, gen) {
    const result = [];
    for (const val of gen) {
        result.push(val);
        if (result.length >= n) break;
    }
    return result;
}

console.log('\n  Fibonacci (primeros 10):', take(10, fibonacci()));

console.log('\n\n  ✅ Lab 07 JS completado. Los patrones son soluciones reutilizables a problemas comunes.\n');
