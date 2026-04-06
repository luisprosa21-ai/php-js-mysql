'use strict';

/**
 * LAB 01 JavaScript: El contexto `this`
 * ========================================
 * Ejecutar: node labs/javascript/01_this_context.js
 */

// ⚙️ CONFIGURACIÓN
const CONFIG = { verbose: true }; // 👉 MODIFICA: false para output más compacto

function separator(title) {
    console.log('\n' + '═'.repeat(60));
    console.log('  ' + title);
    console.log('═'.repeat(60));
}

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 1: this en función regular vs arrow function
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Función regular vs Arrow Function');

function regularFunc() {
    // En modo estricto ('use strict'), this es undefined en función suelta
    // ❌ En modo no estricto, sería el objeto global (window/global)
    return this;
}

const arrowFunc = () => {
    // Arrow function NUNCA tiene su propio `this`
    // Captura el `this` del scope léxico donde fue definida
    return this; // undefined en módulo estricto
};

console.log('\n  regularFunc() en modo estricto:', regularFunc()); // undefined
// arrowFunc() también undefined en modo estricto de módulo

const obj = {
    name: 'Mi Objeto',
    regular: function() { return this.name; },   // ✅ this = obj
    arrow: () => { return typeof this; },        // ❌ this NO es obj
};

console.log('  obj.regular():', obj.regular()); // 'Mi Objeto'
console.log('  obj.arrow()  :', obj.arrow());   // 'undefined' (scope léxico)

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 2: bind, call y apply
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: bind(), call() y apply()');

function greet(greeting, punctuation) {
    return `${greeting}, ${this.name}${punctuation}`;
}

const person1 = { name: 'Ana' };
const person2 = { name: 'Carlos' };

// call(): invoca con this y args individuales
console.log('\n  call():  ', greet.call(person1, 'Hola', '!'));

// apply(): invoca con this y args como array
console.log('  apply(): ', greet.apply(person2, ['Buenos días', '.']));

// bind(): devuelve nueva función con this fijado (no invoca)
const greetAna = greet.bind(person1, 'Hey');
console.log('  bind():  ', greetAna('!!!'));

// 👉 MODIFICA: experimenta con bind parcial (partial application)
const helloCarlos = greet.bind(person2, 'Hello');
console.log('  bind parcial:', helloCarlos(' ;)'));

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 3: this en clases ES6
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: this en Clases ES6');

class Timer {
    constructor(name) {
        this.name = name;
        this.ticks = 0;
        // ✅ Bind en constructor: garantiza que this sea el Timer
        this.tick = this.tick.bind(this);
    }

    tick() {
        this.ticks++;
        console.log(`  ${this.name}: tick #${this.ticks}`);
    }

    // ✅ Alternativa: field declaration (arrow function como método)
    tickArrow = () => {
        this.ticks++;
        return `${this.name}: arrow tick #${this.ticks}`;
    };

    startSimulated() {
        // Simula comportamiento asíncrono (setTimeout, addEventListener)
        const methods = [this.tick, this.tickArrow];
        methods.forEach(fn => fn()); // tick() funciona por el bind del constructor
    }
}

const timer = new Timer('Reloj');
timer.startSimulated();
console.log('  tickArrow:', timer.tickArrow());

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 4: this en callbacks
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: this en Callbacks (problema clásico)');

class Counter {
    constructor() {
        this.count = 0;
    }

    // ❌ Problema: this se pierde en forEach callback (función regular)
    countWithRegular(arr) {
        arr.forEach(function(item) {
            this.count++; // 💥 TypeError: Cannot set properties of undefined
        }.bind(this));    // ← fix con bind
        return this.count;
    }

    // ✅ Solución 1: arrow function (captura this léxico)
    countWithArrow(arr) {
        arr.forEach((item) => {
            this.count++;
        });
        return this.count;
    }

    // ✅ Solución 2: guardar referencia (patrón antiguo)
    countWithSelf(arr) {
        const self = this; // 'that', 'self' o '_this'
        arr.forEach(function(item) {
            self.count++;
        });
        return this.count;
    }
}

const c = new Counter();
const items = [1, 2, 3, 4, 5];
// 👉 MODIFICA: prueba diferentes métodos
console.log('\n  countWithArrow([1..5]):', c.countWithArrow(items));
console.log('  Resultado count:        ', c.count);

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 5: Getters y this
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: this en Getters y Setters');

class Rectangle {
    #width;  // Campo privado (ES2022)
    #height;

    constructor(w, h) {
        this.#width  = w;
        this.#height = h;
    }

    get area()      { return this.#width * this.#height; }  // this = la instancia
    get perimeter() { return 2 * (this.#width + this.#height); }

    set width(w)    {
        if (w <= 0) throw new Error('El ancho debe ser positivo');
        this.#width = w;
    }

    toString() {
        return `Rectangle(${this.#width}x${this.#height}) → área:${this.area}, perímetro:${this.perimeter}`;
    }
}

const rect = new Rectangle(4, 6);
console.log('\n  ' + rect.toString());
rect.width = 10; // Setter
console.log('  Después de cambiar ancho a 10: ' + rect.toString());

// ════════════════════════════════════════════════════════════════
// EXPERIMENTO 6: Encadenamiento de métodos (fluent interface)
// ════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 6: Method Chaining con this');

class QueryBuilder {
    #table  = '';
    #wheres = [];
    #limit  = null;
    #order  = null;

    from(table)  { this.#table = table; return this; }  // ✅ return this
    where(cond)  { this.#wheres.push(cond); return this; }
    orderBy(col) { this.#order = col; return this; }
    take(n)      { this.#limit = n; return this; }

    build() {
        let sql = `SELECT * FROM ${this.#table}`;
        if (this.#wheres.length) sql += ` WHERE ${this.#wheres.join(' AND ')}`;
        if (this.#order) sql += ` ORDER BY ${this.#order}`;
        if (this.#limit) sql += ` LIMIT ${this.#limit}`;
        return sql;
    }
}

// 👉 MODIFICA: añade más condiciones al fluent query
const query = new QueryBuilder()
    .from('orders')
    .where("status = 'delivered'")
    .where('total > 100')
    .orderBy('created_at DESC')
    .take(10)
    .build();

console.log('\n  QueryBuilder:\n  ' + query);

console.log('\n\n  ✅ Lab 01 JS completado. `this` en JavaScript es contextual, no léxico.\n');
