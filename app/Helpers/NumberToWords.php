<?php

namespace App\Helpers;

/**
 * Convierte un número decimal a su representación en palabras en español.
 * Ejemplo: 1234.50 → "UN MIL DOSCIENTOS TREINTA Y CUATRO CON 50/100 Bs." (moneda configurable vía APP_CURRENCY_SYMBOL).
 */
class NumberToWords
{
    private static array $words = [
        0   => 'CERO',
        1   => 'UN',
        2   => 'DOS',
        3   => 'TRES',
        4   => 'CUATRO',
        5   => 'CINCO',
        6   => 'SEIS',
        7   => 'SIETE',
        8   => 'OCHO',
        9   => 'NUEVE',
        10  => 'DIEZ',
        11  => 'ONCE',
        12  => 'DOCE',
        13  => 'TRECE',
        14  => 'CATORCE',
        15  => 'QUINCE',
        16  => 'DIECISEIS',
        17  => 'DIECISIETE',
        18  => 'DIECIOCHO',
        19  => 'DIECINUEVE',
        20  => 'VEINTE',
        30  => 'TREINTA',
        40  => 'CUARENTA',
        50  => 'CINCUENTA',
        60  => 'SESENTA',
        70  => 'SETENTA',
        80  => 'OCHENTA',
        90  => 'NOVENTA',
        100 => 'CIENTO',
        200 => 'DOSCIENTOS',
        300 => 'TRESCIENTOS',
        400 => 'CUATROCIENTOS',
        500 => 'QUINIENTOS',
        600 => 'SEISCIENTOS',
        700 => 'SETECIENTOS',
        800 => 'OCHOCIENTOS',
        900 => 'NOVECIENTOS',
    ];

    /**
     * Convierte un número a su literal en español.
     *
     * @param float $number Número a convertir.
     * @param string|null $currencyLabel Etiqueta de moneda (por defecto, APP_CURRENCY_SYMBOL o "Bs.").
     * @return string Representación en palabras.
     */
    public static function convert(float $number, ?string $currencyLabel = null): string
    {
        $currencyLabel ??= defined('APP_CURRENCY_SYMBOL') ? APP_CURRENCY_SYMBOL : 'Bs.';
        $xarray = self::$words;

        $xcifra     = number_format((float) $number, 2, '.', '');
        $xpos_punto = strpos($xcifra, '.');
        $xaux_int   = $xcifra;
        $xdecimales = '00';

        if ($xpos_punto !== false) {
            if ($xpos_punto == 0) {
                $xcifra     = '0' . $xcifra;
                $xpos_punto = strpos($xcifra, '.');
            }
            $xaux_int   = substr($xcifra, 0, $xpos_punto);
            $xdecimales = substr($xcifra . '00', $xpos_punto + 1, 2);
        }

        $XAUX    = str_pad($xaux_int, 18, ' ', STR_PAD_LEFT);
        $xcadena = '';

        for ($xz = 0; $xz < 3; $xz++) {
            $xaux   = substr($XAUX, $xz * 6, 6);
            $xi     = 0;
            $xlimite = 6;
            $xexit  = true;

            while ($xexit) {
                if ($xi == $xlimite) {
                    break;
                }

                $x3digitios = ($xlimite - $xi) * -1;
                $xaux       = substr($xaux, $x3digitios, abs($x3digitios));

                for ($xy = 1; $xy < 4; $xy++) {
                    switch ($xy) {
                        case 1:
                            if (substr($xaux, 0, 3) >= 100) {
                                $key = (int) substr($xaux, 0, 3);
                                if (array_key_exists($key, $xarray)) {
                                    $xseek   = $xarray[$key];
                                    $xsub    = self::suffix($xaux);
                                    if (substr($xaux, 0, 3) == 100) {
                                        $xcadena = ' ' . $xcadena . ' CIEN ' . $xsub;
                                    } else {
                                        $xcadena = ' ' . $xcadena . ' ' . $xseek . ' ' . $xsub;
                                    }
                                    $xy = 3;
                                } else {
                                    $key     = (int) substr($xaux, 0, 1) * 100;
                                    $xseek   = $xarray[$key];
                                    $xcadena = ' ' . $xcadena . ' ' . $xseek;
                                }
                            }
                            break;
                        case 2:
                            if (substr($xaux, 1, 2) >= 10) {
                                $key = (int) substr($xaux, 1, 2);
                                if (array_key_exists($key, $xarray)) {
                                    $xseek = $xarray[$key];
                                    $xsub  = self::suffix($xaux);
                                    if (substr($xaux, 1, 2) == 20) {
                                        $xcadena = ' ' . $xcadena . ' VEINTE ' . $xsub;
                                    } else {
                                        $xcadena = ' ' . $xcadena . ' ' . $xseek . ' ' . $xsub;
                                    }
                                    $xy = 3;
                                } else {
                                    $key   = (int) substr($xaux, 1, 1) * 10;
                                    $xseek = $xarray[$key];
                                    if (20 == substr($xaux, 1, 1) * 10) {
                                        $xcadena = ' ' . $xcadena . ' ' . $xseek;
                                    } else {
                                        $xcadena = ' ' . $xcadena . ' ' . $xseek . ' Y ';
                                    }
                                }
                            }
                            break;
                        case 3:
                            if (substr($xaux, 2, 1) >= 1) {
                                $key     = (int) substr($xaux, 2, 1);
                                $xseek   = $xarray[$key];
                                $xsub    = self::suffix($xaux);
                                $xcadena = ' ' . $xcadena . ' ' . $xseek . ' ' . $xsub;
                            }
                            break;
                    }
                }
                $xi += 3;
            }

            if (substr(trim($xcadena), -5, 5) === 'ILLON') {
                $xcadena .= ' DE';
            }
            if (substr(trim($xcadena), -7, 7) === 'ILLONES') {
                $xcadena .= ' DE';
            }

            if (trim($xaux) !== '') {
                switch ($xz) {
                    case 0:
                        $xcadena .= trim(substr($XAUX, $xz * 6, 6)) === '1' ? ' UN BILLON' : ' BILLONES';
                        break;
                    case 1:
                        $xcadena .= trim(substr($XAUX, $xz * 6, 6)) === '1' ? ' UN MILLON ' : ' MILLONES ';
                        break;
                    case 2:
                        if ($xcifra < 1) {
                            $xcadena = " CERO CON $xdecimales/100 $currencyLabel";
                        } elseif ($xcifra >= 1 && $xcifra < 2) {
                            $xcadena .= " UN CON $xdecimales/100 $currencyLabel";
                        } else {
                            $xcadena .= " CON $xdecimales/100 $currencyLabel";
                        }
                        break;
                }
            }
        }

        $xcadena = str_replace('VEINTI ', 'VEINTI', $xcadena);
        $xcadena = str_replace('  ', ' ', $xcadena);
        $xcadena = str_replace('UN UN', 'UN', $xcadena);
        $xcadena = str_replace('  ', ' ', $xcadena);

        return trim($xcadena);
    }

    /**
     * Retorna el sufijo de grupo ("MIL" para miles, "" para unidades/centenas).
     *
     * @param string $segment Segmento numérico.
     * @return string Sufijo.
     */
    private static function suffix(string $segment): string
    {
        $segment  = trim($segment);
        $strlen   = strlen($segment);
        if ($strlen >= 4 && $strlen <= 6) {
            return 'MIL';
        }
        return '';
    }
}
