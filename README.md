# 🎓 PHP · JavaScript · MySQL — Laboratorio Interactivo

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES2022-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/docs/Web/JavaScript)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docs.docker.com/compose/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-10.x-366488?style=for-the-badge&logo=php&logoColor=white)](https://phpunit.de/)

> Laboratorio de aprendizaje interactivo para dominar PHP 8.2, JavaScript ES2022 y MySQL 8.0 mediante experimentos prácticos y modificables.

---

## 📋 Descripción del proyecto

Este repositorio es un **laboratorio educativo completo** organizado en tres áreas:

- **🐘 PHP** — 9 labs que cubren tipos, POO, traits, closures, excepciones, PDO/seguridad, JWT, patrones de diseño y funciones de array
- **🐬 MySQL** — 7 labs que cubren JOINs, índices, transacciones ACID, normalización, agregaciones, subqueries y optimización
- **🟨 JavaScript** — 7 labs que cubren `this`, closures, promises/async, prototype chain, event loop, desestructuración y patrones

Cada lab tiene variables configurables marcadas con `// 👉 MODIFICA:` para experimentar fácilmente.

---

## ⚙️ Requisitos previos

### Con Docker (recomendado)
- [Docker](https://docs.docker.com/get-docker/) 20.x+
- [Docker Compose](https://docs.docker.com/compose/install/) 2.x+

### Sin Docker
- PHP 8.2+ con extensiones: `pdo`, `pdo_mysql`, `mbstring`, `opcache`
- MySQL 8.0+
- [Composer](https://getcomposer.org/) 2.x+
- Node.js 18+ (para labs de JavaScript)

---

## 🚀 Instalación

### Con Docker (recomendado)

```bash
# 1. Clona el repositorio
git clone https://github.com/luisprosa21-ai/php-js-mysql.git
cd php-js-mysql

# 2. Ejecuta el setup automático
bash scripts/setup.sh

# 3. ¡Listo! Abre en el navegador:
#    Dashboard:   http://localhost:8000
#    phpMyAdmin:  http://localhost:8080
```

### Sin Docker

```bash
# 1. Clona el repositorio
git clone https://github.com/luisprosa21-ai/php-js-mysql.git
cd php-js-mysql

# 2. Copia el archivo de entorno
cp .env.example .env
# Edita .env con tus credenciales de MySQL

# 3. Instala dependencias PHP
composer install

# 4. Importa la base de datos
mysql -u root -p < database/migrations/001_create_tables.sql
mysql -u root -p php_js_mysql_lab < database/seeds/seed_data.sql

# 5. Levanta el servidor PHP
php -S localhost:8000 -t public/
```

---

## 🧪 Labs disponibles

### 🐘 Labs de PHP

| # | Archivo | Tema | Ejecutar |
|---|---------|------|----------|
| 01 | `labs/php/01_types_and_operators.php` | Tipos, `==` vs `===`, Enums PHP 8.1 | `php labs/php/01_types_and_operators.php` |
| 02 | `labs/php/02_oop_pillars.php` | Los 4 pilares de POO | `php labs/php/02_oop_pillars.php` |
| 03 | `labs/php/03_traits.php` | Traits y resolución de conflictos | `php labs/php/03_traits.php` |
| 04 | `labs/php/04_closures_and_generators.php` | Closures, Generators, memoria | `php labs/php/04_closures_and_generators.php` |
| 05 | `labs/php/05_exceptions.php` | Excepciones personalizadas | `php labs/php/05_exceptions.php` |
| 06 | `labs/php/06_pdo_security.php` | PDO, SQL Injection, XSS, CSRF | `php labs/php/06_pdo_security.php` |
| 07 | `labs/php/07_sessions_auth.php` | JWT manual paso a paso | `php labs/php/07_sessions_auth.php` |
| 08 | `labs/php/08_patterns.php` | Patrones de diseño (6 patrones) | `php labs/php/08_patterns.php` |
| 09 | `labs/php/09_array_functions.php` | Funciones de array avanzadas | `php labs/php/09_array_functions.php` |

### 🐬 Labs de MySQL

| # | Archivo | Tema | Ejecutar en |
|---|---------|------|-------------|
| 01 | `labs/mysql/01_joins_explained.sql` | Todos los tipos de JOIN | phpMyAdmin |
| 02 | `labs/mysql/02_indexes_performance.sql` | Índices y rendimiento con EXPLAIN | phpMyAdmin |
| 03 | `labs/mysql/03_transactions_acid.sql` | Transacciones ACID y ROLLBACK | phpMyAdmin |
| 04 | `labs/mysql/04_normalization.sql` | Normalización 0NF → 3NF | phpMyAdmin |
| 05 | `labs/mysql/05_aggregations.sql` | GROUP BY, HAVING, Window Functions | phpMyAdmin |
| 06 | `labs/mysql/06_subqueries.sql` | Subqueries, CTEs, CTEs recursivas | phpMyAdmin |
| 07 | `labs/mysql/07_optimization.sql` | EXPLAIN, slow queries, optimización | phpMyAdmin |

### 🟨 Labs de JavaScript

| # | Archivo | Tema | Ejecutar |
|---|---------|------|----------|
| 01 | `labs/javascript/01_this_context.js` | Contexto `this` en profundidad | `node labs/javascript/01_this_context.js` |
| 02 | `labs/javascript/02_closures_scope.js` | Closures y scope | `node labs/javascript/02_closures_scope.js` |
| 03 | `labs/javascript/03_promises_async.js` | Promises y async/await | `node labs/javascript/03_promises_async.js` |
| 04 | `labs/javascript/04_prototype_chain.js` | Prototype chain | `node labs/javascript/04_prototype_chain.js` |
| 05 | `labs/javascript/05_event_loop.js` | Event loop y microtasks | `node labs/javascript/05_event_loop.js` |
| 06 | `labs/javascript/06_destructuring_spread.js` | Desestructuración y spread | `node labs/javascript/06_destructuring_spread.js` |
| 07 | `labs/javascript/07_patterns.js` | Patrones de diseño en JS | `node labs/javascript/07_patterns.js` |

---

## 📖 Convenciones del proyecto

| Símbolo | Significado |
|---------|-------------|
| `// 👉 MODIFICA:` | Variable o valor que debes cambiar para experimentar |
| `// ✅ RESULTADO:` | El output esperado después de ejecutar |
| `// ❌ NO HAGAS ESTO:` | Anti-patrón que debes evitar |
| `// ✅ MEJOR ASÍ:` | La forma correcta de hacerlo |

---

## ✅ Cómo ejecutar los tests

```bash
# Instalar dependencias (incluye PHPUnit)
composer install

# Ejecutar todos los tests
composer test

# O directamente con PHPUnit
./vendor/bin/phpunit --testdox

# Ejecutar solo un test específico
./vendor/bin/phpunit tests/Unit/TraitsTest.php --testdox
```

---

## 🛠️ CLI Runner

```bash
# Ver todos los labs disponibles
php scripts/run_labs.php --list

# Ejecutar un lab específico
php scripts/run_labs.php --lab=php/01

# Ejecutar todos los labs de PHP
php scripts/run_labs.php --lab=php/all

# Ejecutar los tests
php scripts/run_labs.php --test

# Ayuda
php scripts/run_labs.php --help
```

---

## 📁 Estructura del proyecto

```
php-js-mysql/
├── src/
│   ├── Core/
│   │   ├── Database.php        # Singleton PDO
│   │   └── Container.php       # DI Container
│   ├── Traits/
│   │   ├── Loggable.php
│   │   ├── Timestampable.php
│   │   └── Validatable.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   └── Order.php
│   ├── Services/
│   │   ├── UserService.php
│   │   ├── AuthService.php     # JWT manual
│   │   └── OrderService.php
│   └── Middleware/
│       ├── AuthMiddleware.php
│       └── RateLimitMiddleware.php
├── labs/
│   ├── php/                    # 9 labs PHP ejecutables
│   ├── mysql/                  # 7 labs SQL para phpMyAdmin
│   └── javascript/             # 7 labs JS para Node.js/browser
├── database/
│   ├── migrations/
│   └── seeds/
├── tests/
│   └── Unit/
├── public/
│   ├── index.php               # Dashboard web
│   ├── api.php                 # API REST
│   └── assets/
│       ├── js/lab.js
│       └── css/style.css
├── scripts/
│   ├── setup.sh
│   └── run_labs.php
├── config/
│   └── database.php
├── docker-compose.yml
├── Dockerfile
├── composer.json
└── .env.example
```

---

## 🐳 Servicios Docker

| Servicio | URL | Descripción |
|----------|-----|-------------|
| App PHP | http://localhost:8000 | Dashboard y API REST |
| phpMyAdmin | http://localhost:8080 | Interfaz web para MySQL |
| MySQL | localhost:3306 | Base de datos |

Credenciales MySQL: usuario `labuser`, contraseña `labpassword`, base de datos `php_js_mysql_lab`

---

## 🤝 Contribuir

1. Fork el repositorio
2. Crea una rama: `git checkout -b feature/nuevo-lab`
3. Haz tus cambios y tests
4. Push: `git push origin feature/nuevo-lab`
5. Abre un Pull Request

---

## 📄 Licencia

MIT © 2024 luisprosa21-ai