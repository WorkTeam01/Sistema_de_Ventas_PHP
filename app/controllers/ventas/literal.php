<?php

function numeroletras($xcifra)
{
    $xarray = array(
        0 => "CERO",
        1 => "UN",
        2 => "DOS",
        3 => "TRES",
        4 => "CUATRO",
        5 => "CINCO",
        6 => "SEIS",
        7 => "SIETE",
        8 => "OCHO",
        9 => "NUEVE",
        10 => "DIEZ",
        11 => "ONCE",
        12 => "DOCE",
        13 => "TRECE",
        14 => "CATORCE",
        15 => "QUINCE",
        16 => "DIECISEIS",
        17 => "DIECISIETE",
        18 => "DIECIOCHO",
        19 => "DIECINUEVE",
        20 => "VEINTE",
        30 => "TREINTA",
        40 => "CUARENTA",
        50 => "CINCUENTA",
        60 => "SESENTA",
        70 => "SETENTA",
        80 => "OCHENTA",
        90 => "NOVENTA",
        100 => "CIENTO",
        200 => "DOSCIENTOS",
        300 => "TRESCIENTOS",
        400 => "CUATROCIENTOS",
        500 => "QUINIENTOS",
        600 => "SEISCIENTOS",
        700 => "SETECIENTOS",
        800 => "OCHOCIENTOS",
        900 => "NOVECIENTOS"
    );

    $xcifra = number_format((float)$xcifra, 2, '.', '');
    $xlength = strlen($xcifra);
    $xpos_punto = strpos($xcifra, ".");
    $xaux_int = $xcifra;
    $xdecimales = "00";

    if (!($xpos_punto === false)) {
        if ($xpos_punto == 0) {
            $xcifra = "0" . $xcifra;
            $xpos_punto = strpos($xcifra, ".");
        }
        $xaux_int = substr($xcifra, 0, $xpos_punto); // Parte entera
        $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // Parte final
    }

    $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT);
    $xcadena = "";

    for ($xz = 0; $xz < 3; $xz++) {
        $xaux = substr($XAUX, $xz * 6, 6);
        $xi = 0;
        $xlimite = 6;
        $xexit = true;
        while ($xexit) {
            if ($xi == $xlimite) {
                break;
            }

            $x3digitios = ($xlimite - $xi) * -1;
            $xaux = substr($xaux, $x3digitios, abs($x3digitios));
            for ($xy = 1; $xy < 4; $xy++) {
                switch ($xy) {
                    case 1:
                        if (substr($xaux, 0, 3) < 100) {
                        } else {
                            $key = (int) substr($xaux, 0, 3);

                            if (true === array_key_exists($key, $xarray)) {
                                $xseek = $xarray[$key];
                                $xsub = subfijo($xaux);

                                if (substr($xaux, 0, 3) == 100)
                                    $xcadena = " " . $xcadena . " CIEN " . $xsub;
                                else
                                    $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                $xy = 3;
                            } else {
                                $key = (int) substr($xaux, 0, 1) * 100;
                                $xseek = $xarray[$key];
                                $xcadena = " " . $xcadena . " " . $xseek;
                            }
                        }
                        break;
                    case 2:
                        if (substr($xaux, 1, 2) < 10) {
                        } else {
                            $key = (int) substr($xaux, 1, 2);
                            if (true === array_key_exists($key, $xarray)) {
                                $xseek = $xarray[$key];
                                $xsub = subfijo($xaux);
                                if (substr($xaux, 1, 2) == 20)
                                    $xcadena = " " . $xcadena . " VEINTE " . $xsub;
                                else
                                    $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                                $xy = 3;
                            } else {
                                $key = (int) substr($xaux, 1, 1) * 10;
                                $xseek = $xarray[$key];
                                if (20 == substr($xaux, 1, 1) * 10)
                                    $xcadena = " " . $xcadena . " " . $xseek;
                                else
                                    $xcadena = " " . $xcadena . " " . $xseek . " Y ";
                            }
                        }
                        break;
                    case 3:
                        if (substr($xaux, 2, 1) < 1) {
                        } else {
                            $key = (int) substr($xaux, 2, 1);
                            $xseek = $xarray[$key];
                            $xsub = subfijo($xaux);
                            $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                        }
                        break;
                }
            }
            $xi = $xi + 3;
        }

        if (substr(trim($xcadena), -5, 5) == "ILLON")
            $xcadena .= " DE";
        if (substr(trim($xcadena), -7, 7) == "ILLONES")
            $xcadena .= " DE";

        if (trim($xaux) != "") {
            switch ($xz) {
                case 0:
                    if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                        $xcadena .= " UN BILLON";
                    else
                        $xcadena .= " BILLONES";
                    break;
                case 1:
                    if (trim(substr($XAUX, $xz * 6, 6)) == "1")
                        $xcadena .= " UN MILLON ";
                    else
                        $xcadena .= " MILLONES ";
                    break;
                case 2:
                    if ($xcifra < 1) {
                        $xcadena = " CERO CON $xdecimales/100 Bs.";
                    } elseif ($xcifra >= 1 && $xcifra < 2) {
                        $xcadena .= " UN CON $xdecimales/100 Bs.";
                    } else {
                        $xcadena .= " CON $xdecimales/100 Bs.";
                    }
                    break;
            }
        }
    }
    $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena);
    $xcadena = str_replace("  ", " ", $xcadena);
    $xcadena = str_replace("UN UN", "UN", $xcadena);
    $xcadena = str_replace("  ", " ", $xcadena);
    return trim($xcadena);
}

function subfijo($xx)
{
    $xx = trim($xx);
    $xstrlen = strlen($xx);
    if ($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
        $xsub = "";
    if ($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
        $xsub = "MIL";
    return $xsub;
}

// Ejemplo de uso
/*$numero = 1234567.89;
echo numeroletras($numero);*/
