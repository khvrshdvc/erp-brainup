<?php

namespace App\Http\Controllers\Student;

use App\Enums\ShopOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.shop.index', [
            ...$this->wallet($request),
            'items' => ShopItem::active()->orderBy('price')->get(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('student.shop.orders', [
            ...$this->wallet($request),
            'orders' => $request->user()->shopOrders()->with('item')->latest()->get(),
        ]);
    }

    public function buy(Request $request, ShopItem $shopItem, ShopService $shop): RedirectResponse
    {
        $shop->purchase($request->user(), $shopItem);

        return redirect()
            ->route('student.shop.orders')
            ->with('status', '“'.$shopItem->name.'” buyurtma qilindi. Mahsulotni filial administratoridan olasiz.');
    }

    public function cancel(Request $request, ShopOrder $shopOrder, ShopService $shop): RedirectResponse
    {
        Gate::authorize('cancelOwn', $shopOrder);

        $shop->cancel($shopOrder, $request->user());

        return back()->with('status', 'Buyurtma bekor qilindi, BrainCoin hamyoningizga qaytarildi.');
    }

    /**
     * Wallet figures shown on both shop tabs.
     */
    private function wallet(Request $request): array
    {
        $student = $request->user();

        return [
            'balance' => $student->coinBalance(),
            'earned' => (int) $student->xp,
            'pendingCount' => $student->shopOrders()->where('status', ShopOrderStatus::Pending->value)->count(),
        ];
    }
}
