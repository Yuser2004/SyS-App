<?php

/**
 * Clase para convertir números a letras en español.
 * Maneja millones, miles y casos especiales como 'cien' vs. 'ciento'.
 */
class NumeroALetras
{
    private static $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    private static $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    private static $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
    private static $excepciones = [
        11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
        16 => 'dieciseis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve',
        21 => 'veintiun', 22 => 'veintidos', 23 => 'veintitres', 24 => 'veinticuatro',
        25 => 'veinticinco', 26 => 'veintiseis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve'
    ];

    /**
     * Método principal que convierte un número a su representación en letras.
     *
     * @param int|float $numero El número a convertir.
     * @param string $moneda El nombre de la moneda en singular (ej. 'PESO').
     * @return string La representación del número en letras.
     */
    public static function convertir($numero, $moneda = 'PESOS')
    {
        $numero = (float) $numero;
        $entero = (int) floor($numero);
        
        if ($entero == 0) {
            return 'CERO ' . $moneda;
        }

        if ($entero == 1) {
             // Ajuste para la moneda en singular si es necesario
            $nombre_moneda = rtrim($moneda, 'S');
            return 'UN ' . $nombre_moneda;
        }

        $partes = self::separarEnGrupos($entero);
        $texto = self::procesarGrupos($partes);

        return trim(strtoupper($texto)) . ' ' . $moneda;
    }

    private static function separarEnGrupos($numero)
    {
        $partes = [];
        $numeroStr = str_pad((string)$numero, ceil(strlen((string)$numero) / 3) * 3, '0', STR_PAD_LEFT);
        $grupos = str_split($numeroStr, 3);
        
        // Invertimos los grupos para procesar de menor a mayor (unidades, miles, millones)
        return array_reverse($grupos);
    }

    private static function procesarGrupos(array $grupos)
    {
        $textoFinal = [];
        $nombresGrupos = ['', 'mil', 'millones'];

        foreach ($grupos as $indice => $grupo) {
            $numeroGrupo = (int)$grupo;
            if ($numeroGrupo == 0) {
                continue;
            }

            $textoGrupo = self::convertirGrupo($numeroGrupo);

            // Manejo de 'un mil' a 'mil'
            if ($indice == 1 && $numeroGrupo == 1) {
                $textoFinal[] = 'mil';
            } 
            // Manejo de 'un millón'
            elseif ($indice == 2 && $numeroGrupo == 1) {
                $textoFinal[] = 'un millón';
            }
            // Manejo de plurales de millones
            elseif ($indice == 2 && $numeroGrupo > 1) {
                 $textoFinal[] = $textoGrupo . ' millones';
            }
            else {
                $textoFinal[] = $textoGrupo . ' ' . $nombresGrupos[$indice];
            }
        }
        
        // Unimos las partes en orden inverso para la lectura correcta
        return implode(' ', array_reverse($textoFinal));
    }

    private static function convertirGrupo($n)
    {
        if ($n == 100) {
            return 'cien';
        }
        
        $texto = '';
        $c = (int)($n / 100);
        $d = (int)(($n % 100) / 10);
        $u = (int)($n % 10);

        if ($c > 0) {
            $texto .= self::$centenas[$c];
        }

        $decenasUnidades = $n % 100;
        if ($decenasUnidades > 0) {
            $texto .= ($c > 0 ? ' ' : '');
            if (isset(self::$excepciones[$decenasUnidades])) {
                $texto .= self::$excepciones[$decenasUnidades];
            } else {
                $texto .= self::$decenas[$d];
                if ($u > 0) {
                    $texto .= ($d > 0 ? ' y ' : '') . self::$unidades[$u];
                }
            }
        }
        
        // Reemplaza "veintiun" por "veintiuno" si es el final de un grupo
        if (substr($texto, -6) === 'veintiun') {
            $texto = substr($texto, 0, -2) . 'o';
        }
        
        // Reemplaza "un" por "uno" al final de un grupo
        if(substr($texto, -2) === 'un') {
           $texto .= 'o';
        }

        return $texto;
    }
}