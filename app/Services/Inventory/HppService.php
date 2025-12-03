<?php

namespace App\Services\Inventory;

class HppService
{
    /**
     * Hitung HPP Cutting dari LOT → WIP-CUT.
     */
    public function calculateCuttingHpp($lotCost, $totalLotUsed, $totalQtyOk)
    {
        if ($totalQtyOk <= 0) {
            throw new \RuntimeException("Qty OK tidak boleh nol untuk perhitungan HPP Cutting");
        }

        return round(($lotCost * $totalLotUsed) / $totalQtyOk, 2);
    }

    /**
     * Hitung HPP Sewing (dari cutting + biaya sewing).
     */
    public function calculateSewingHpp($hppCutting, $laborCost)
    {
        return round($hppCutting + $laborCost, 2);
    }

    /**
     * Hitung HPP Finishing (dari sewing + finishing + packaging).
     */
    public function calculateFinishingHpp($hppSewing, $finishingCost, $packagingCost)
    {
        return round($hppSewing + $finishingCost + $packagingCost, 2);
    }

    /**
     * Hitung HPP Final Finished Goods.
     */
    public function calculateFinalHpp($cutting, $sewing, $finishing, $packaging)
    {
        return round($cutting + $sewing + $finishing + $packaging, 2);
    }
}
