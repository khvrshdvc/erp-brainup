<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\ShopOrderStatus;
use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Models\User;
use App\Notifications\ShopOrderPlaced;
use App\Notifications\ShopOrderUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ShopService
{
    /**
     * Reserves the item's price from the student's wallet and one unit of stock.
     * Rows are locked so two quick clicks cannot spend the same coins twice.
     */
    public function purchase(User $student, ShopItem $item): ShopOrder
    {
        $order = DB::transaction(function () use ($student, $item) {
            $student = User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $item = ShopItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            $error = match (true) {
                ! $item->is_active => __('Bu mahsulot hozir sotuvda yo‘q.'),
                ! $item->inStock() => __('Bu mahsulot tugagan.'),
                ! $student->branch_id => __('Sizga filial biriktirilmagan. Administratorga murojaat qiling.'),
                $student->coinBalance() < $item->price => __('BrainCoin yetarli emas.'),
                default => null,
            };

            if ($error) {
                throw ValidationException::withMessages(['item' => $error]);
            }

            if (! $item->hasUnlimitedStock()) {
                $item->decrement('stock');
            }

            return ShopOrder::create([
                'branch_id' => $student->branch_id,
                'user_id' => $student->id,
                'shop_item_id' => $item->id,
                'price' => $item->price,
                'status' => ShopOrderStatus::Pending,
            ]);
        });

        $this->notifyBranchAdmins($order);

        return $order;
    }

    public function deliver(ShopOrder $order, User $admin): void
    {
        $this->close($order, $admin, ShopOrderStatus::Delivered);
    }

    /**
     * Cancels a pending order: the coins go back to the wallet and the unit back to stock.
     */
    public function cancel(ShopOrder $order, User $by, ?string $note = null): void
    {
        $this->close($order, $by, ShopOrderStatus::Cancelled, $note);
    }

    /**
     * Branch admins hand the item over, so they hear about every new order in their branch.
     */
    private function notifyBranchAdmins(ShopOrder $order): void
    {
        $admins = User::query()
            ->ofRole(Role::BranchAdmin)
            ->active()
            ->where('branch_id', $order->branch_id)
            ->get();

        Notification::send($admins, new ShopOrderPlaced($order));
    }

    private function close(ShopOrder $order, User $by, ShopOrderStatus $status, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $by, $status, $note) {
            $locked = ShopOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['order' => __('Bu buyurtma allaqachon yopilgan.')]);
            }

            $locked->update([
                'status' => $status,
                'handled_by' => $by->id,
                'handled_at' => now(),
                'note' => $note,
            ]);

            if ($status === ShopOrderStatus::Cancelled) {
                ShopItem::whereKey($locked->shop_item_id)->whereNotNull('stock')->increment('stock');
            }

            $order->setRawAttributes($locked->getAttributes(), true);
        });

        // Students hear about it only when someone else closed their order.
        if ($order->user_id !== $by->id) {
            $order->student?->notify(new ShopOrderUpdated($order));
        }
    }
}
