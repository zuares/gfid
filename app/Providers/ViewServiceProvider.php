<?php

namespace App\Providers;

use App\Models\InventoryStock;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Sidebar global
        View::composer('layouts.partials.sidebar', function ($view) {
            // 1) Outstanding WIP-SEW (contoh kasar: line yang masih punya outstanding qty)
            $badgeSewOutstanding = SewingPickupLine::query()
                ->whereNull('deleted_at') // kalau pakai soft delete
                ->where(function ($q) {
                    $q->whereColumn('qty_bundle', '>', 'qty_returned_ok')
                        ->orWhereNull('qty_returned_ok');
                })
                ->count();

            // 2) Ready items WH-PRD (stok > 0 di gudang WH-PRD)
            $whPrdId = Warehouse::where('code', 'WH-PRD')->value('id');
            $badgePackingReady = 0;

            if ($whPrdId) {
                $badgePackingReady = InventoryStock::query()
                    ->where('warehouse_id', $whPrdId)
                    ->where('qty', '>', 0)
                    ->count();
            }

            $badgeReportsTotal = $badgeSewOutstanding + $badgePackingReady;

            $view->with(compact(
                'badgeSewOutstanding',
                'badgePackingReady',
                'badgeReportsTotal',
            ));
        });
    }
}
