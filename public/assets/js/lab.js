/**
 * Labs JavaScript ejecutables en consola del browser
 *
 * Uso desde la consola del navegador:
 *   Lab.list()              — ver todos los labs disponibles
 *   Lab.run('closures')     — ejecutar un lab por nombre
 *   Lab.runAll()            — ejecutar todos los labs
 *
 * También disponible en: http://localhost:8000
 */

'use strict';

const Lab = {
    // 👉 MODIFICA: cambia verbose a false para ver solo resultados sin explicaciones
    config: { verbose: true },

    /**
     * Muestra todos los labs disponibles.
     */
    list() {
        console.log('%c🎓 Labs disponibles:', 'font-weight:bold;font-size:14px;color:#61dafb');
        const names = Object.keys(this.labs);
        names.forEach((name, i) => {
            console.log(`  ${i + 1}. Lab.run('${name}')`);
        });
        console.log('\nEjecuta Lab.runAll() para correrlos todos.');
        return names;
    },

    /**
     * Ejecuta un lab por nombre.
     * @param {string} name - Nombre del lab
     */
    async run(name) {
        if (!this.labs[name]) {
            console.error(`❌ Lab '${name}' no encontrado. Usa Lab.list() para ver los disponibles.`);
            return;
        }
        console.group(`%c🔬 Lab: ${name}`, 'font-weight:bold;color:#98c379');
        const start = performance.now();
        try {
            await this.labs[name].call(this);
        } catch (e) {
            console.error('Error en el lab:', e);
        }
        const elapsed = (performance.now() - start).toFixed(2);
        console.log(`%c⏱ Tiempo: ${elapsed}ms`, 'color:#abb2bf');
        console.groupEnd();
    },

    /**
     * Ejecuta todos los labs en secuencia.
     */
    async runAll() {
        console.log('%c🎓 Ejecutando todos los labs...', 'font-weight:bold;font-size:14px;color:#61dafb');
        for (const name of Object.keys(this.labs)) {
            await this.run(name);
        }
        console.log('%c✅ Todos los labs completados.', 'font-weight:bold;color:#98c379');
    },

    // ── Labs ────────────────────────────────────────────────────────────────
    labs: {

        // ──────────────────────────────────────────────────────────────────
        'this-context': function () {
            console.log('=== Contexto this ===\n');

            // Experimento 1: this en función regular
            function regularFunc() {
                // En strict mode: undefined; en sloppy: window
                return typeof this;
            }
            console.log('this en función regular (strict):', regularFunc());

            // Experimento 2: this en arrow function
            const obj = {
                name: 'Lab',
                // ❌ NO HAGAS ESTO: arrow function no tiene su propio this
                arrowMethod: () => typeof this,
                // ✅ MEJOR ASÍ: método regular
                regularMethod() { return this.name; },
            };
            console.log('this en arrow (hereda del exterior):', obj.arrowMethod());
            console.log('this en método regular:', obj.regularMethod());

            // Experimento 3: bind / call / apply
            function greet(greeting, punctuation) {
                return `${greeting}, ${this.name}${punctuation}`;
            }
            const user = { name: 'Ana' };
            console.log('call:', greet.call(user, 'Hola', '!'));
            console.log('apply:', greet.apply(user, ['Buenos días', '.']));
            const boundGreet = greet.bind(user, 'Hey');
            console.log('bind:', boundGreet('?'));

            // Experimento 4: this en clases
            class Counter {
                #count = 0;
                increment() { this.#count++; return this; } // fluent interface
                getValue() { return this.#count; }
            }
            const counter = new Counter();
            console.log('Chaining:', counter.increment().increment().increment().getValue());

            // Experimento 5: perder el contexto en callbacks
            class Timer {
                constructor() { this.seconds = 0; }
                // ✅ Arrow function preserva this
                start() {
                    const tick = () => { this.seconds++; };
                    tick(); tick(); tick();
                    return this.seconds;
                }
            }
            console.log('Timer con arrow:', new Timer().start());

            // Experimento 6: getter con this
            const circle = {
                radius: 5,
                get area() { return Math.PI * this.radius ** 2; },
            };
            console.log('Getter area:', circle.area.toFixed(4));
        },

        // ──────────────────────────────────────────────────────────────────
        'closures': function () {
            console.log('=== Closures y Scope ===\n');

            // Experimento 1: closure básico
            function makeCounter(start = 0) {
                let count = start; // variable capturada
                return {
                    increment: () => ++count,
                    decrement: () => --count,
                    value: () => count,
                };
            }
            const c = makeCounter(10);
            c.increment(); c.increment(); c.decrement();
            console.log('Counter value:', c.value()); // 11

            // Experimento 2: factory con closure
            function multiplier(factor) {
                return (n) => n * factor;
            }
            const double = multiplier(2);
            const triple = multiplier(3);
            console.log('double(5):', double(5));
            console.log('triple(5):', triple(5));

            // Experimento 3: memoization
            function memoize(fn) {
                const cache = new Map();
                return function (...args) {
                    const key = JSON.stringify(args);
                    if (cache.has(key)) {
                        console.log('  (from cache)');
                        return cache.get(key);
                    }
                    const result = fn.apply(this, args);
                    cache.set(key, result);
                    return result;
                };
            }
            const expensiveFn = memoize((n) => n ** 2);
            console.log('memoize 5²:', expensiveFn(5));
            console.log('memoize 5² (cached):', expensiveFn(5));

            // Experimento 4: bug de var en loop (✅ solución con let)
            console.log('\n❌ Bug con var:');
            const varFuncs = [];
            for (var i = 0; i < 3; i++) {
                varFuncs.push(() => i); // captura la referencia, no el valor
            }
            console.log(varFuncs.map(f => f())); // [3, 3, 3]

            console.log('✅ Fix con let:');
            const letFuncs = [];
            for (let j = 0; j < 3; j++) {
                letFuncs.push(() => j); // cada iteración crea nuevo binding
            }
            console.log(letFuncs.map(f => f())); // [0, 1, 2]

            // Experimento 5: module pattern
            const BankAccount = (() => {
                let _balance = 0; // privado via closure
                return {
                    deposit(amount) { _balance += amount; return this; },
                    withdraw(amount) {
                        if (amount > _balance) throw new Error('Fondos insuficientes');
                        _balance -= amount; return this;
                    },
                    getBalance() { return _balance; },
                };
            })();
            BankAccount.deposit(100).deposit(50).withdraw(30);
            console.log('\nBankAccount balance:', BankAccount.getBalance()); // 120

            // Experimento 6: currying
            const curry = (fn) => {
                const arity = fn.length;
                return function curried(...args) {
                    return args.length >= arity
                        ? fn(...args)
                        : (...more) => curried(...args, ...more);
                };
            };
            const add = curry((a, b, c) => a + b + c);
            console.log('curry add(1)(2)(3):', add(1)(2)(3));
            console.log('curry add(1, 2)(3):', add(1, 2)(3));
        },

        // ──────────────────────────────────────────────────────────────────
        'promises': async function () {
            // 👉 MODIFICA: ajusta estos valores para experimentar
            const CONFIG = {
                DELAY_MS: 100,   // ms de delay simulado
                FAIL_RATE: 0.0,  // 0.0 = nunca falla, 1.0 = siempre falla
                MAX_RETRIES: 3,
            };

            console.log('=== Promises y Async/Await ===\n');

            const delay = (ms) => new Promise(r => setTimeout(r, ms));
            const mayFail = (id) => new Promise((resolve, reject) => {
                setTimeout(() => {
                    Math.random() < CONFIG.FAIL_RATE
                        ? reject(new Error(`Request ${id} fallida`))
                        : resolve({ id, data: `Result-${id}` });
                }, CONFIG.DELAY_MS);
            });

            // Experimento 1: Promise básica
            const p1 = new Promise((resolve) => resolve('Valor inicial'));
            console.log('Promise resuelta:', await p1);

            // Experimento 2: chaining
            const result = await Promise.resolve(1)
                .then(v => v + 1)
                .then(v => v * 3)
                .then(v => `Resultado: ${v}`);
            console.log('Chaining:', result);

            // Experimento 3: async/await
            async function fetchUser(id) {
                await delay(CONFIG.DELAY_MS);
                return { id, name: `User-${id}` };
            }
            const user = await fetchUser(42);
            console.log('async/await user:', user);

            // Experimento 4: Promise.all (paralelo)
            console.time('Promise.all');
            const all = await Promise.all([mayFail(1), mayFail(2), mayFail(3)]);
            console.timeEnd('Promise.all');
            console.log('Promise.all results:', all.map(r => r.id));

            // Experimento 5: Promise.allSettled
            const settled = await Promise.allSettled([
                Promise.resolve('ok'),
                Promise.reject('fallo'),
                Promise.resolve('ok2'),
            ]);
            console.log('allSettled:', settled.map(r => r.status));

            // Experimento 6: Promise.race
            const race = await Promise.race([
                delay(200).then(() => 'lento'),
                delay(50).then(() => 'rápido'),
            ]);
            console.log('Promise.race ganador:', race);

            // Experimento 7: retry con backoff exponencial
            async function withRetry(fn, maxRetries = CONFIG.MAX_RETRIES) {
                for (let attempt = 1; attempt <= maxRetries; attempt++) {
                    try {
                        return await fn();
                    } catch (e) {
                        if (attempt === maxRetries) throw e;
                        const backoff = Math.min(1000, 100 * 2 ** attempt);
                        console.log(`  Intento ${attempt} fallido. Reintentando en ${backoff}ms...`);
                        await delay(backoff);
                    }
                }
            }
            try {
                const r = await withRetry(() => mayFail(99));
                console.log('retry resultado:', r);
            } catch (e) {
                console.log('retry agotado:', e.message);
            }

            // Experimento 8: timeout
            function withTimeout(promise, ms) {
                const timeout = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error(`Timeout después de ${ms}ms`)), ms)
                );
                return Promise.race([promise, timeout]);
            }
            try {
                const r = await withTimeout(delay(50).then(() => 'ok'), 200);
                console.log('withTimeout resultado:', r);
            } catch (e) {
                console.log('withTimeout error:', e.message);
            }

            console.log('✅ Todos los experimentos de promises completados');
        },

        // ──────────────────────────────────────────────────────────────────
        'prototype': function () {
            console.log('=== Prototype Chain ===\n');

            // Experimento 1: Object.create
            const animal = {
                speak() { return `${this.name} hace un sonido`; }
            };
            const dog = Object.create(animal);
            dog.name = 'Rex';
            console.log('Object.create:', dog.speak());
            console.log('Prototype chain:', Object.getPrototypeOf(dog) === animal);

            // Experimento 2: constructor function
            function Vehicle(make, model) {
                this.make = make;
                this.model = model;
            }
            Vehicle.prototype.toString = function () {
                return `${this.make} ${this.model}`;
            };
            const car = new Vehicle('Toyota', 'Corolla');
            console.log('Constructor fn:', car.toString());
            console.log('instanceof:', car instanceof Vehicle);

            // Experimento 3: class ES6
            class Shape {
                constructor(color = 'black') { this.color = color; }
                area() { return 0; }
                toString() { return `${this.constructor.name}(color=${this.color}, area=${this.area().toFixed(2)})`; }
            }

            class Circle extends Shape {
                constructor(radius, color) {
                    super(color);
                    this.radius = radius;
                }
                area() { return Math.PI * this.radius ** 2; }
            }

            class Rectangle extends Shape {
                constructor(w, h, color) {
                    super(color);
                    this.w = w; this.h = h;
                }
                area() { return this.w * this.h; }
            }

            const shapes = [new Circle(5, 'red'), new Rectangle(4, 6, 'blue')];
            shapes.forEach(s => console.log('Shape:', s.toString()));

            // Experimento 4: mixins
            const Serializable = (Base) => class extends Base {
                serialize() { return JSON.stringify(this); }
                static deserialize(json) { return Object.assign(new this(), JSON.parse(json)); }
            };
            class Point extends Serializable(Shape) {
                constructor(x, y) { super(); this.x = x; this.y = y; }
            }
            const p = new Point(3, 4);
            console.log('Mixin serialize:', p.serialize());

            // Experimento 5: Symbol.hasInstance
            class EvenNumber {
                static [Symbol.hasInstance](num) {
                    return typeof num === 'number' && num % 2 === 0;
                }
            }
            console.log('4 instanceof EvenNumber:', 4 instanceof EvenNumber); // true
            console.log('3 instanceof EvenNumber:', 3 instanceof EvenNumber); // false
        },

        // ──────────────────────────────────────────────────────────────────
        'event-loop': async function () {
            console.log('=== Event Loop ===\n');
            console.log('Orden: call stack → microtasks (Promise) → macrotasks (setTimeout)\n');

            // Experimento 1: orden básico
            console.log('--- Experimento 1 ---');
            console.log('PREDICCIÓN: sync1, sync2, promise1, timeout1');
            console.log('sync1');
            Promise.resolve().then(() => console.log('promise1 (microtask)'));
            setTimeout(() => console.log('timeout1 (macrotask)'), 0);
            console.log('sync2');
            await new Promise(r => setTimeout(r, 10)); // esperar para ver el resultado

            // Experimento 2: microtasks antes de macrotasks
            console.log('\n--- Experimento 2 ---');
            console.log('PREDICCIÓN: A, C, B (microtasks antes de macrotasks)');
            setTimeout(() => console.log('B (macrotask)'), 0);
            Promise.resolve().then(() => console.log('C (microtask)'));
            console.log('A (sync)');
            await new Promise(r => setTimeout(r, 10));

            // Experimento 3: queueMicrotask
            console.log('\n--- Experimento 3 ---');
            console.log('PREDICCIÓN: sync, micro1, micro2, macro');
            setTimeout(() => console.log('macro (setTimeout)'), 0);
            queueMicrotask(() => console.log('micro1 (queueMicrotask)'));
            Promise.resolve().then(() => console.log('micro2 (Promise.then)'));
            console.log('sync');
            await new Promise(r => setTimeout(r, 10));

            // Experimento 4: async/await transforma en microtask
            console.log('\n--- Experimento 4 ---');
            async function asyncFn() {
                console.log('  inside async before await');
                await Promise.resolve();
                console.log('  inside async after await (microtask)');
            }
            console.log('before asyncFn()');
            asyncFn(); // no await — no bloquea
            console.log('after asyncFn() call (sync)');
            await new Promise(r => setTimeout(r, 10));

            console.log('\n✅ Event loop demostrado. Abre DevTools para ver el orden exacto.');
        },

        // ──────────────────────────────────────────────────────────────────
        'destructuring': function () {
            console.log('=== Destructuring y Spread ===\n');

            // Experimento 1: array destructuring
            const [first, second, ...rest] = [1, 2, 3, 4, 5];
            console.log('Array:', first, second, rest);

            // Experimento 2: object destructuring
            const { name, email, role = 'user', ...others } = {
                name: 'Ana', email: 'ana@lab.test', age: 25, city: 'Madrid'
            };
            console.log('Object:', { name, email, role, others });

            // Experimento 3: parámetros de función
            function createUser({ name = 'Anónimo', role = 'user', active = true } = {}) {
                return { name, role, active };
            }
            console.log('Defaults:', createUser({ name: 'Carlos' }));
            console.log('Empty call:', createUser());

            // Experimento 4: spread en arrays
            const arr1 = [1, 2, 3];
            const arr2 = [4, 5, 6];
            const merged = [...arr1, ...arr2];
            const cloned = [...arr1]; // copia superficial
            cloned.push(99);
            console.log('Spread merge:', merged);
            console.log('Original sin cambios:', arr1);

            // Experimento 5: spread en objetos
            const defaults = { theme: 'dark', lang: 'es', debug: false };
            const userPrefs = { lang: 'en', debug: true };
            const config = { ...defaults, ...userPrefs }; // userPrefs sobreescribe
            console.log('Object spread:', config);

            // Experimento 6: swap sin variable temporal
            let a = 1, b = 2;
            [a, b] = [b, a];
            console.log('Swap:', { a, b });

            // Experimento 7: optional chaining
            const data = {
                user: {
                    address: {
                        city: 'Barcelona'
                    }
                }
            };
            console.log('Optional chaining:', data?.user?.address?.city); // 'Barcelona'
            console.log('Optional chaining (null):', data?.user?.phone?.number ?? 'No phone'); // fallback
            console.log('Optional method call:', data?.user?.toString?.() ?? 'No method');
        },

        // ──────────────────────────────────────────────────────────────────
        'patterns': function () {
            console.log('=== Patrones de Diseño en JavaScript ===\n');

            // Patrón 1: Observer / EventEmitter
            console.log('--- Observer ---');
            class EventEmitter {
                #listeners = new Map();
                on(event, cb) {
                    if (!this.#listeners.has(event)) this.#listeners.set(event, []);
                    this.#listeners.get(event).push(cb);
                    return this;
                }
                off(event, cb) {
                    this.#listeners.set(event, (this.#listeners.get(event) ?? []).filter(l => l !== cb));
                    return this;
                }
                emit(event, ...args) {
                    (this.#listeners.get(event) ?? []).forEach(cb => cb(...args));
                    return this;
                }
            }
            const emitter = new EventEmitter();
            emitter.on('data', d => console.log('Observer recibió:', d));
            emitter.emit('data', { id: 1, name: 'Test' });

            // Patrón 2: Module (IIFE)
            console.log('\n--- Module Pattern ---');
            const UserModule = (() => {
                // privado
                let _users = [];
                let _nextId = 1;

                // público
                return {
                    add(name) { _users.push({ id: _nextId++, name }); },
                    getAll() { return [..._users]; },
                    count() { return _users.length; },
                };
            })();
            UserModule.add('Ana');
            UserModule.add('Carlos');
            console.log('Module users:', UserModule.getAll());
            console.log('Module count:', UserModule.count());

            // Patrón 3: Factory
            console.log('\n--- Factory ---');
            class AnimalFactory {
                static create(type, name) {
                    const animals = {
                        dog: (n) => ({ name: n, speak: () => `${n} dice: Guau!` }),
                        cat: (n) => ({ name: n, speak: () => `${n} dice: Miau!` }),
                        bird: (n) => ({ name: n, speak: () => `${n} dice: Pío!` }),
                    };
                    if (!animals[type]) throw new Error(`Tipo desconocido: ${type}`);
                    return animals[type](name);
                }
            }
            ['dog', 'cat', 'bird'].forEach(type => {
                const animal = AnimalFactory.create(type, `Mi ${type}`);
                console.log('Factory:', animal.speak());
            });

            // Patrón 4: Singleton
            console.log('\n--- Singleton ---');
            class AppConfig {
                static #instance = null;
                #settings = {};
                static getInstance() {
                    if (!AppConfig.#instance) AppConfig.#instance = new AppConfig();
                    return AppConfig.#instance;
                }
                set(key, value) { this.#settings[key] = value; return this; }
                get(key) { return this.#settings[key]; }
            }
            const cfg1 = AppConfig.getInstance();
            const cfg2 = AppConfig.getInstance();
            cfg1.set('theme', 'dark');
            console.log('Singleton same instance:', cfg1 === cfg2);
            console.log('Singleton shared state:', cfg2.get('theme'));

            // Patrón 5: Strategy
            console.log('\n--- Strategy ---');
            class Sorter {
                constructor(strategy) { this.strategy = strategy; }
                sort(data) { return this.strategy([...data]); }
            }
            const sorter = new Sorter((arr) => arr.sort((a, b) => a - b));
            console.log('Strategy sort asc:', sorter.sort([3, 1, 4, 1, 5]));
            sorter.strategy = (arr) => arr.sort((a, b) => b - a);
            console.log('Strategy sort desc:', sorter.sort([3, 1, 4, 1, 5]));

            // Patrón 6: Proxy para validación
            console.log('\n--- Proxy ---');
            function createValidatedObject(target, validators) {
                return new Proxy(target, {
                    set(obj, prop, value) {
                        if (validators[prop] && !validators[prop](value)) {
                            throw new TypeError(`Valor inválido para ${String(prop)}: ${value}`);
                        }
                        obj[prop] = value;
                        return true;
                    }
                });
            }
            const user = createValidatedObject({}, {
                age: (v) => typeof v === 'number' && v >= 0 && v <= 150,
                email: (v) => typeof v === 'string' && v.includes('@'),
            });
            user.age = 25;
            user.email = 'ana@lab.test';
            console.log('Proxy validated user:', user);
            try {
                user.age = -5;
            } catch (e) {
                console.log('Proxy validation error:', e.message);
            }

            // Patrón 7: Iterator personalizado
            console.log('\n--- Iterator ---');
            class Range {
                constructor(start, end, step = 1) {
                    this.start = start; this.end = end; this.step = step;
                }
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
                        }
                    };
                }
            }
            const range = new Range(1, 10, 2);
            console.log('Iterator Range(1,10,2):', [...range]);
        },
    }
};

// Exponer globalmente en el browser
if (typeof window !== 'undefined') {
    window.Lab = Lab;
    console.log('%c🎓 Labs cargados. Escribe Lab.list() para ver los disponibles.', 'font-weight:bold;color:#61dafb;font-size:13px');
} else if (typeof module !== 'undefined') {
    module.exports = Lab;
}
