<?php

declare(strict_types=1);

namespace Lab\Traits;

/**
 * Trait Validatable — añade validación de datos a cualquier clase.
 *
 * Proporciona un sistema simple de reglas de validación similar al de
 * frameworks como Laravel. Las reglas se definen como strings separadas
 * por '|' o como array.
 *
 * Reglas soportadas:
 *   required        — el campo no puede estar vacío
 *   email           — debe ser un email válido
 *   min:n           — longitud mínima n caracteres (o valor mínimo para numéricos)
 *   max:n           — longitud máxima n caracteres (o valor máximo para numéricos)
 *   numeric         — debe ser un número
 *   string          — debe ser una cadena de texto
 *   in:a,b,c        — el valor debe estar en la lista
 *
 * Uso:
 *   class MiModelo {
 *       use Validatable;
 *   }
 *   $valid = $obj->validate($data, [
 *       'email' => 'required|email',
 *       'age'   => 'required|numeric|min:18|max:99',
 *       'role'  => 'required|in:admin,user,guest',
 *   ]);
 */
trait Validatable
{
    /** @var array<string, string[]> Errores de validación por campo */
    private array $validationErrors = [];

    /**
     * Valida un array de datos contra un conjunto de reglas.
     *
     * @param array<string, mixed>                    $data  Datos a validar
     * @param array<string, string|string[]>          $rules Reglas por campo
     * @return bool true si todos los campos pasan la validación
     */
    public function validate(array $data, array $rules): bool
    {
        $this->validationErrors = [];

        foreach ($rules as $field => $fieldRules) {
            // Las reglas pueden venir como string 'required|email' o como array
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->applyRule($field, $value, $rule, $data);
                if ($error !== null) {
                    $this->validationErrors[$field][] = $error;
                }
            }
        }

        return empty($this->validationErrors);
    }

    /**
     * Devuelve los errores de validación de la última llamada a validate().
     *
     * @return array<string, string[]> Errores indexados por nombre de campo
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Indica si hay errores de validación pendientes.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Aplica una regla individual a un valor.
     *
     * @param string $field  Nombre del campo
     * @param mixed  $value  Valor del campo
     * @param string $rule   Regla (puede incluir parámetro después de ':')
     * @param array  $data   Todos los datos (para reglas que necesitan contexto)
     * @return string|null   Mensaje de error, o null si pasa la validación
     */
    private function applyRule(string $field, mixed $value, string $rule, array $data): ?string
    {
        // Separar la regla de su parámetro: 'min:8' → ['min', '8']
        [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($ruleName) {
            'required' => ($value === null || $value === '')
                ? "El campo {$field} es obligatorio."
                : null,

            'email' => ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? "El campo {$field} debe ser un email válido."
                : null,

            'min' => ($value !== null && $value !== '' && is_numeric($value))
                ? ($value < (float)$ruleParam ? "El campo {$field} debe ser mayor o igual a {$ruleParam}." : null)
                : (($value !== null && $value !== '' && mb_strlen((string)$value) < (int)$ruleParam)
                    ? "El campo {$field} debe tener al menos {$ruleParam} caracteres."
                    : null),

            'max' => ($value !== null && $value !== '' && is_numeric($value))
                ? ($value > (float)$ruleParam ? "El campo {$field} debe ser menor o igual a {$ruleParam}." : null)
                : (($value !== null && $value !== '' && mb_strlen((string)$value) > (int)$ruleParam)
                    ? "El campo {$field} no puede tener más de {$ruleParam} caracteres."
                    : null),

            'numeric' => ($value !== null && $value !== '' && !is_numeric($value))
                ? "El campo {$field} debe ser un número."
                : null,

            'string' => ($value !== null && !is_string($value))
                ? "El campo {$field} debe ser texto."
                : null,

            'in' => ($value !== null && $value !== '' && !in_array($value, explode(',', $ruleParam ?? ''), true))
                ? "El campo {$field} debe ser uno de: {$ruleParam}."
                : null,

            default => null,
        };
    }
}
