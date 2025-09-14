<?php

namespace App\Support;

class Ci
{
    /** Deja solo dígitos (máx 8) */
    public static function normalize(?string $v): string
    {
        return substr(preg_replace('/\D/', '', (string)$v), 0, 8);
    }

    /** Muestra formateado: 43216543 => 4.321.654-3 */
    public static function format(?string $digits): string
    {
        $d = preg_replace('/\D/', '', (string)$digits);
        if (strlen($d) < 2) return $d;
        $body = substr($d, 0, -1);
        $dv   = substr($d, -1);
        $body = number_format((int)$body, 0, '', '.');
        return "{$body}-{$dv}";
    }
}