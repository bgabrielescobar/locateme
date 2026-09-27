<?php

namespace Src\Helpers;

// Cada función devuelve el valor limpio o null si no es válido
class Validate
{

    public static function text($value, int $min, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $length = mb_strlen($value);

        return $length >= $min && $length <= $max ? $value : null;
    }

    public static function username($value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9._-]{3,40}$/', strtolower(trim($value)))
            ? strtolower(trim($value))
            : null;
    }

    public static function number($value, float $min, float $max): ?float
    {
        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            return null;
        }

        $value = (float) $value;

        return is_finite($value) && $value >= $min && $value <= $max ? $value : null;
    }

    public static function color($value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : null;
    }

}
