<?php
// Cara Penggunaaan di Blade Template:
// @rupiah($value)
// @decimal($value)

if (!function_exists('rupiah')) {
    function rupiah($value, $decimal = 0)
    {
        return 'Rp ' . number_format($value, $decimal, ',', '.');
    }
}

if (!function_exists('decimal_id')) {
    function decimal_id($value, $decimal = 2)
    {
        return number_format($value, $decimal, ',', '.');
    }
}

if (!function_exists('angka')) {
    function angka($value, $decimal = 0)
    {
        return number_format($value, $decimal, ',', '.');
    }
}
