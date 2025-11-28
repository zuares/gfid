<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemSearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * Contoh pemakaian:
     *   /api/items/search?q=TSH
     *   /api/items/search?q=TSH&type=finished_good
     *   /api/items/search?q=kain&category_id=3
     *   /api/items/search?q=kain&type=finished_good,semi_finished&category_id=1,2&limit=20
     */
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $typeParam = $request->query('type'); // string "finished_good" atau "fg,rm"
        $categoryParam = $request->query('category_id'); // bisa "3" atau "1,2,3"
        $limit = (int) $request->query('limit', 10);

        // batasi limit agar tidak kebablasan
        if ($limit < 1) {
            $limit = 10;
        } elseif ($limit > 50) {
            $limit = 50;
        }

        $query = Item::query()
            ->select('id', 'code', 'name', 'type', 'item_category_id')
            ->with(['category:id,code,name'])
            ->orderBy('code');

        // 🔍 Filter q (kode / nama)
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            });
        }

        // 🔍 Filter type (bisa satu atau banyak)
        // contoh: ?type=finished_good atau ?type=finished_good,raw_material
        if ($typeParam) {
            $types = is_array($typeParam)
            ? $typeParam
            : explode(',', (string) $typeParam);

            $types = array_filter(array_map('trim', $types));

            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        // 🔍 Filter berdasarkan item_category_id (bisa satu atau banyak)
        // contoh: ?category_id=3 atau ?category_id=1,2,3
        if ($categoryParam) {
            $categoryIds = is_array($categoryParam)
            ? $categoryParam
            : explode(',', (string) $categoryParam);

            $categoryIds = array_filter(array_map('intval', $categoryIds));

            if (!empty($categoryIds)) {
                $query->whereIn('item_category_id', $categoryIds);
            }
        }

        // (opsional) kalau nanti kamu punya kolom is_active, bisa tambahkan:
        // if ($request->boolean('only_active', true)) {
        //     $query->where('is_active', true);
        // }

        $items = $query->limit($limit)->get();

        // 🔁 Response compact sesuai kebutuhan autosuggest:
        // [{ id, code, name }]
        $result = $items->map(function (Item $item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                // bonus info (kalau mau dipakai di tempat lain)
                'type' => $item->type,
                'category' => $item->category
                ? [
                    'id' => $item->category->id,
                    'code' => $item->category->code,
                    'name' => $item->category->name,
                ]
                : null,
            ];
        });

        return response()->json($result);
    }
}
