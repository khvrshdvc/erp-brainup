<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', ShopOrderStatus::Pending->value);
        $status = in_array($status, [...ShopOrderStatus::values(), 'all'], true) ? $status : ShopOrderStatus::Pending->value;

        $orders = ShopOrder::query()
            ->with(['student', 'item', 'branch', 'handler'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = ShopOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.shop.orders', compact('orders', 'status', 'counts'));
    }

    public function deliver(Request $request, ShopOrder $shopOrder, ShopService $shop): RedirectResponse
    {
        Gate::authorize('handle', $shopOrder);

        $shop->deliver($shopOrder, $request->user());

        return back()->with('status', __('Buyurtma topshirildi deb belgilandi'));
    }

    public function cancel(Request $request, ShopOrder $shopOrder, ShopService $shop): RedirectResponse
    {
        Gate::authorize('handle', $shopOrder);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']], [], ['note' => 'sabab']);

        $shop->cancel($shopOrder, $request->user(), $data['note'] ?? null);

        return back()->with('status', __('Buyurtma bekor qilindi, BrainCoin o‘quvchiga qaytarildi'));
    }
}
