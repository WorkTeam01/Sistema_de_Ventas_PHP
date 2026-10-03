<?php

namespace App\Helpers;

class Money
{
    // Tope de dígitos enteros para que `* 100` nunca desborde un int de 64 bits
    private const MAX_INTEGER_DIGITS = 15;

    /**
     * Convierte un monto a céntimos enteros, mitad hacia arriba, sin aritmética float:
     * el texto se separa en parte entera y decimales y solo el tercer decimal decide el redondeo.
     * Devuelve null si no es un monto válido (vacío, negativo, notación científica, desbordado).
     */
    public static function toCents(string|int|float $amount): ?int
    {
        // number_format a 4 decimales absorbe el error binario del float (10.005 → "10.0050")
        $text = is_float($amount) ? number_format($amount, 4, '.', '') : trim((string)$amount);

        if (!preg_match('/^(\d+)(?:[.,](\d+))?$/', $text, $m)) {
            return null;
        }

        if (strlen(ltrim($m[1], '0')) > self::MAX_INTEGER_DIGITS) {
            return null;
        }

        $decimals = str_pad($m[2] ?? '', 3, '0');
        $cents = (int)$m[1] * 100 + (int)substr($decimals, 0, 2);

        return $decimals[2] >= '5' ? $cents + 1 : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($abs, 100), $abs % 100);
    }
}
