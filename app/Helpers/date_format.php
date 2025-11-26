<?php

use Carbon\Carbon;

/**
 * Format: 27 November 2025
 */
if (!function_exists('id_date')) {
    function id_date($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('d F Y');
    }
}

/**
 * Format: 27 November 2025 19:42
 */
if (!function_exists('id_datetime')) {
    function id_datetime($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('d F Y H:i');
    }
}

/**
 * Format: 19:42
 */
if (!function_exists('id_time')) {
    function id_time($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->format('H:i');
    }
}

/**
 * Format: Rabu, 27 November 2025
 */
if (!function_exists('id_day')) {
    function id_day($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F Y');
    }
}

/**
 * Format: Rabu, 27 November 2025 19:42
 */
if (!function_exists('id_day_datetime')) {
    function id_day_datetime($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('l, d F Y H:i');
    }
}

/**
 * Format: 27/11/25
 */
if (!function_exists('id_short')) {
    function id_short($date)
    {
        if (!$date) {
            return '-';
        }

        return Carbon::parse($date)
            ->format('d/m/y');
    }
}
