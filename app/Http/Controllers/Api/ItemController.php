<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * GET /api/v1/items
     *
     * Query param:
     * - q               : search code / name
     * - type            : material / finished_good / dsb
     *                     (bisa multi: ?type=material,finished_good)
     * - item_category_id: filter kategori
     * - active          : 1/0 (default: 1 -> hanya active)
     * - per_page        : default 20, max 100
     */
    public function index(Request $request)
    {
        $query = Item::query()->with('category');

        // 🔎 Search kode / nama
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        // 🎯 Filter type (bisa single / multi)
        if ($type = $request->input('type')) {
            // contoh: ?type=material atau ?type=material,finished_good
            $types = collect(explode(',', $type))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values();

            if ($types->isNotEmpty()) {
                $query->whereIn('type', $types);
            }
        }

        // 🎯 Filter kategori
        if ($categoryId = $request->input('item_category_id')) {
            $query->where('item_category_id', $categoryId);
        }

        // ✅ Filter active (default: hanya active=1)
        if ($request->has('active')) {
            $query->where('active', (int) $request->input('active') === 1);
        } else {
            $query->where('active', 1);
        }

        // 📄 Pagination
        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 100 ? 100 : $perPage;

        $items = $query
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $items->items(), // sudah include relasi "category"
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
            'filters' => [
                'q' => $request->input('q'),
                'type' => $request->input('type'),
                'item_category_id' => $request->input('item_category_id'),
                'active' => $request->input('active', 1),
            ],
        ]);
    }

    /**
     * GET /api/v1/items/suggest
     *
     * Dipakai untuk autocomplete: return ringan
     * Query param:
     * - q
     * - type (opsional)
     * - item_category_id (opsional)
     * - limit (default 20)
     */
    public function suggest(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type'); // material / finished_good / dll
        $itemCategoryId = $request->query('item_category_id');
        $limit = (int) $request->query('limit', 20);

        $limit = $limit > 50 ? 50 : $limit; // batas aman

        $items = Item::query()
            ->with('category') // ⬅️ supaya bisa ambil nama kategori
            ->where('active', 1)
            ->when($q, function ($query, $q) {
                $like = '%' . $q . '%';

                $query->where(function ($qq) use ($like) {
                    $qq->where('code', 'like', $like)
                        ->orWhere('name', 'like', $like);
                });
            })
            ->when($type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($itemCategoryId, function ($query, $catId) {
                $query->where('item_category_id', $catId);
            })
            ->orderBy('code')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $items->map(function (Item $item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'type' => $item->type,
                    'item_category_id' => $item->item_category_id,
                    // ⬇⬇⬇ INI YANG PENTING – DIPAKAI HIDDEN "item_category"
                    'item_category' => optional($item->category)->name,
                ];
            }),
        ]);
    }

    /**
     * GET /api/v1/items/{item}
     */
    public function show(Item $item)
    {
        $item->load('category');

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }
}
