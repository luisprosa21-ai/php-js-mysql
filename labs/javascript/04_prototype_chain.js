'use strict';
/**
 * LAB 04 JavaScript: Prototype Chain
 * =====================================
 * Ejecutar: node labs/javascript/04_prototype_chain.js
 */

function separator(title) {
    console.log('\n' + '═'.repeat(60) + '\n  ' + title + '\n' + '═'.repeat(60));
}

separator('🔬 EXPERIMENTO 1: Object.create — Prototype manual');
const animal = {
    breathe() { return `${this.name} respira`; },
    describe() { return `Soy ${this.name}, un ${this.species}`; },
};

const dog = Object.create(animal);
dog.name    = 'Rex';
dog.species = 'perro';
dog.bark    = function() { return `${this.name}: ¡Guau!`; };

console.log('\n  dog.describe():', dog.describe());  // heredado de animal
console.log('  dog.bark():    ', dog.bark());
console.log('  dog hasOwnProperty bark:', dog.hasOwnProperty('bark'));      // true
console.log('  dog hasOwnProperty breathe:', dog.hasOwnProperty('breathe')); // false (heredado)
console.log('  Object.getPrototypeOf(dog) === animal:', Object.getPrototypeOf(dog) === animal);

separator('🔬 EXPERIMENTO 2: Constructor Function (ES5)');
function Vehicle(brand, model, year) {
    this.brand = brand;
    this.model = model;
    this.year  = year;
}

// Métodos en el prototype (compartidos entre instancias, ahorra memoria)
Vehicle.prototype.describe = function() {
    return `${this.year} ${this.brand} ${this.model}`;
};
Vehicle.prototype.age = function() {
    return new Date().getFullYear() - this.year;
};

function Car(brand, model, year, doors) {
    Vehicle.call(this, brand, model, year); // super()
    this.doors = doors;
}
Car.prototype = Object.create(Vehicle.prototype);
Car.prototype.constructor = Car; // Restaurar constructor

Car.prototype.describe = function() {
    return Vehicle.prototype.describe.call(this) + ` (${this.doors} puertas)`;
};

const myCar = new Car('Toyota', 'Corolla', 2022, 4);
console.log('\n  myCar.describe():', myCar.describe());
console.log('  myCar.age():     ', myCar.age());
console.log('  myCar instanceof Car:    ', myCar instanceof Car);
console.log('  myCar instanceof Vehicle:', myCar instanceof Vehicle);

separator('🔬 EXPERIMENTO 3: class ES6 (syntactic sugar sobre prototype)');

class Shape {
    #color; // Campo privado ES2022

    constructor(color = 'black') {
        this.#color = color;
    }

    get color() { return this.#color; }

    area()      { throw new Error('area() must be implemented'); }
    perimeter() { throw new Error('perimeter() must be implemented'); }

    toString() {
        return `${this.constructor.name}[color=${this.#color}, area=${this.area().toFixed(2)}]`;
    }

    static compare(a, b) {
        return a.area() - b.area();
    }
}

class Circle extends Shape {
    constructor(radius, color) {
        super(color); // ← llama al constructor de Shape
        this.radius = radius;
    }
    area()      { return Math.PI * this.radius ** 2; }
    perimeter() { return 2 * Math.PI * this.radius; }
}

class Rectangle extends Shape {
    constructor(w, h, color) {
        super(color);
        this.w = w; this.h = h;
    }
    area()      { return this.w * this.h; }
    perimeter() { return 2 * (this.w + this.h); }
}

const shapes = [
    new Circle(5, 'red'),
    new Rectangle(4, 6, 'blue'),
    new Circle(3),
];

console.log('\n  Shapes:');
shapes.forEach(s => console.log('   ', s.toString()));
const sorted = [...shapes].sort(Shape.compare);
console.log('\n  Ordenadas por área:');
sorted.forEach(s => console.log('   ', s.area().toFixed(2), '-', s.constructor.name));

separator('�� EXPERIMENTO 4: instanceof y prototype chain');
console.log('\n  instanceof chain:');
const c = new Circle(5);
console.log('  c instanceof Circle:', c instanceof Circle);   // true
console.log('  c instanceof Shape:', c instanceof Shape);     // true
console.log('  c instanceof Object:', c instanceof Object);   // true (todo en JS)
console.log('  c instanceof Rectangle:', c instanceof Rectangle); // false

// Prototype chain manual
let proto = Object.getPrototypeOf(c);
const chain = [];
while (proto) {
    chain.push(proto.constructor?.name ?? 'null');
    proto = Object.getPrototypeOf(proto);
}
console.log('  Prototype chain:', chain.join(' → '));

separator('🔬 EXPERIMENTO 5: Mixins');
// Mixin: mezclar comportamiento de múltiples "fuentes" (JS solo tiene herencia simple)

const Serializable = (Base) => class extends Base {
    toJSON() {
        return JSON.stringify(this, null, 2);
    }
    static fromJSON(json) {
        return Object.assign(new this(), JSON.parse(json));
    }
};

const Timestampable = (Base) => class extends Base {
    constructor(...args) {
        super(...args);
        this.createdAt = new Date().toISOString();
    }
    age() {
        return Math.round((Date.now() - new Date(this.createdAt).getTime()) / 1000);
    }
};

class BaseModel {
    constructor(data = {}) {
        Object.assign(this, data);
    }
}

// Aplicar mixins
class User extends Serializable(Timestampable(BaseModel)) {
    constructor(data) {
        super(data);
    }
    greet() { return `Hola, soy ${this.name}`; }
}

const user = new User({ id: 1, name: 'Ana', email: 'ana@lab.test' });
console.log('\n  User con mixins:');
console.log('  user.greet():    ', user.greet());
console.log('  user.age():      ', user.age(), 'segundos');
console.log('  JSON:', user.toJSON().slice(0, 80) + '...');

separator('🔬 EXPERIMENTO 6: Symbol.hasInstance');
class EvenNumber {
    static [Symbol.hasInstance](num) {
        return typeof num === 'number' && num % 2 === 0;
    }
}

console.log('\n  Symbol.hasInstance (customizar instanceof):');
console.log('  4 instanceof EvenNumber:', 4 instanceof EvenNumber);    // true
console.log('  7 instanceof EvenNumber:', 7 instanceof EvenNumber);    // false
console.log('  0 instanceof EvenNumber:', 0 instanceof EvenNumber);    // true

console.log('\n\n  ✅ Lab 04 JS completado. El prototype chain es el corazón de la herencia en JS.\n');
