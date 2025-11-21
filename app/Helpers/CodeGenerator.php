<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    /**
     * Generate kode aman dari race-condition:
     *  PREFIX-YYYYMMDD-###
     *
     * Contoh:
     *  PO-20251121-001
     *  INV-20251121-002
     *
     * @param  string $prefix  PO / INV / LOT / TRF / dll
     * @return string
     *
     * @throws \Throwable
     */
    public static function generate(string $prefix = 'PO'): string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($prefix) {
                    $now = now();
                    $date = $now->toDateString(); // 2025-11-21
                    $dateYmd = $now->format('Ymd'); // 20251121

                    // Lock baris running_numbers untuk prefix+date ini
                    $row = DB::table('running_numbers')
                        ->where('prefix', $prefix)
                        ->where('date', $date)
                        ->lockForUpdate()
                        ->first();

                    if (!$row) {
                        $number = 1;

                        DB::table('running_numbers')->insert([
                            'prefix' => $prefix,
                            'date' => $date,
                            'last_number' => $number,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $number = $row->last_number + 1;

                        DB::table('running_numbers')
                            ->where('id', $row->id)
                            ->update([
                                'last_number' => $number,
                                'updated_at' => $now,
                            ]);
                    }

                    $numberFormatted = str_pad($number, 3, '0', STR_PAD_LEFT);

                    return "{$prefix}-{$dateYmd}-{$numberFormatted}";
                }, 3); // 3x attempt internal transaction (kalau DB error)
            } catch (\Throwable $e) {
                // Retry beberapa kali kalau lagi "tabrakan" / deadlock / transient error
                if ($attempt === $maxAttempts) {
                    throw $e;
                }

                // Tidur sebentar sebelum coba lagi (50ms)
                usleep(50_000);
            }
        }

        // praktiknya tidak akan sampai sini
        throw new \RuntimeException('Gagal generate kode.');
    }
}
