<?php

declare(strict_types=1);

namespace Lab\Core;

/**
 * Dependency Injection Container simple con auto-wiring.
 *
 * Un DI Container es responsable de instanciar objetos y resolver
 * sus dependencias automáticamente. Esto promueve el principio
 * Dependency Inversion (la D de SOLID).
 *
 * Soporta tres modos:
 * - bind(): crea una nueva instancia cada vez que se solicita
 * - singleton(): crea la instancia solo una vez y la reutiliza
 * - make(): resolución automática con ReflectionClass (auto-wiring)
 */
class Container
{
    /** @var array<string, callable> Factories registradas (bind) */
    private array $bindings = [];

    /** @var array<string, object> Instancias singleton cacheadas */
    private array $instances = [];

    /**
     * Registra una factory que se invocará cada vez que se solicite el abstract.
     *
     * @param string   $abstract Identificador (normalmente el FQCN de la interfaz)
     * @param callable $factory  Función que devuelve la implementación
     *
     * @example
     *   $container->bind(UserRepository::class, fn() => new MySqlUserRepository($db));
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Registra una factory que solo se invocará una vez; las sucesivas llamadas
     * devuelven la misma instancia (patrón Singleton por abstract).
     *
     * @param string   $abstract Identificador
     * @param callable $factory  Función que devuelve la implementación
     *
     * @example
     *   $container->singleton(Database::class, fn() => Database::getInstance());
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Resuelve una dependencia del contenedor.
     *
     * Orden de resolución:
     * 1. Si hay un binding registrado (bind/singleton), lo usa.
     * 2. Si no, intenta auto-wiring con ReflectionClass: instancia la clase
     *    e inyecta recursivamente sus dependencias del constructor.
     *
     * @param string $abstract Identificador o FQCN a resolver
     * @return mixed La instancia resuelta
     * @throws \RuntimeException Si no puede resolver la dependencia
     *
     * @example
     *   $userService = $container->make(UserService::class);
     */
    public function make(string $abstract): mixed
    {
        // 1. Binding explícito registrado
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // 2. Auto-wiring con Reflection
        if (!class_exists($abstract)) {
            throw new \RuntimeException("Cannot resolve [{$abstract}]: class does not exist and no binding registered.");
        }

        return $this->buildWithReflection($abstract);
    }

    /**
     * Construye una instancia usando ReflectionClass para resolver
     * automáticamente las dependencias del constructor.
     *
     * @param string $class FQCN de la clase a construir
     * @return object
     * @throws \RuntimeException Si una dependencia no puede resolverse
     */
    private function buildWithReflection(string $class): object
    {
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // Sin constructor o sin parámetros: instanciar directamente
        if ($constructor === null) {
            return $reflector->newInstance();
        }

        $params = $constructor->getParameters();
        $dependencies = [];

        foreach ($params as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                // Dependencia de tipo clase/interfaz: resolver recursivamente
                $dependencies[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                // Parámetro con valor por defecto: usarlo
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    "Cannot resolve parameter [{$param->getName()}] in class [{$class}]."
                );
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
