<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShopItemRequest;
use App\Models\ShopItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShopItemController extends Controller
{
    public function index(): View
    {
        $items = ShopItem::query()
            ->withCount([
                'orders as pending_count' => fn ($query) => $query->withoutGlobalScopes()->where('status', ShopOrderStatus::Pending->value),
                'orders as delivered_count' => fn ($query) => $query->withoutGlobalScopes()->where('status', ShopOrderStatus::Delivered->value),
            ])
            ->orderByDesc('is_active')
            ->orderBy('price')
            ->get();

        return view('admin.shop-items.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.shop-items.create');
    }

    public function store(ShopItemRequest $request): RedirectResponse
    {
        $item = ShopItem::create($request->payload());

        $this->storeImage($request, $item);

        return redirect()->route('admin.shop-items.index')->with('status', __('Mahsulot qo‘shildi'));
    }

    public function edit(ShopItem $shopItem): View
    {
        return view('admin.shop-items.edit', ['item' => $shopItem]);
    }

    public function update(ShopItemRequest $request, ShopItem $shopItem): RedirectResponse
    {
        $shopItem->update($request->payload());

        $this->storeImage($request, $shopItem);

        return redirect()->route('admin.shop-items.index')->with('status', __('Mahsulot yangilandi'));
    }

    public function toggleStatus(ShopItem $shopItem): RedirectResponse
    {
        $shopItem->update(['is_active' => ! $shopItem->is_active]);

        return back()->with('status', __('Holat o‘zgartirildi'));
    }

    private function storeImage(ShopItemRequest $request, ShopItem $item): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        if ($item->image_path) {
            Storage::disk('local')->delete($item->image_path);
        }

        $item->update(['image_path' => $request->file('image')->store('shop', 'local')]);
    }
}
