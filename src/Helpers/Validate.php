<?php

namespace Src\Helpers;

/**
 * Validación de los datos que llegan en las peticiones.
 *
 * Cada función devuelve el valor ya limpio y del tipo correcto, o null si no es
 * válido. Uso típico en una ruta:
 *
 *   $name = Validate::text($body['name'] ?? null, 1, 40);
 *   if ($name === null) {
 *       return Http::error($response, 'Escribe el nombre.');
 *   }
 *
 * Nunca confíes en lo que manda el navegador: aunque el HTML tenga maxlength o
 * required, cualquiera puede llamar a la API directamente.
 */
class Validate
{

    /**
     * Texto de $min a $max caracteres. Junta los espacios repetidos, quita los de
     * los extremos y cuenta cada letra con acento como una sola (mb_strlen).
     */
    public static function text($value, int $min, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $length = mb_strlen($value);

        return $length >= $min && $length <= $max ? $value : null;
    }

    /**
     * Usuario de 3 a 40 caracteres: letras, números, punto, guion y guion bajo.
     * Se devuelve en minúsculas para que "Ana" y "ana" sean el mismo usuario.
     */
    public static function username($value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9._-]{3,40}$/', strtolower(trim($value)))
            ? strtolower(trim($value))
            : null;
    }

    /**
     * Número entre $min y $max. Acepta números o texto numérico ("32.6") y
     * rechaza texto, NaN e infinito.
     */
    public static function number($value, float $min, float $max): ?float
    {
        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            return null;
        }

        $value = (float) $value;

        return is_finite($value) && $value >= $min && $value <= $max ? $value : null;
    }

    /**
     * Color "#rrggbb", devuelto en minúsculas. El color termina dentro de estilos
     * CSS del panel, por eso no se acepta ningún otro formato.
     */
    public static function color($value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : null;
    }

}
