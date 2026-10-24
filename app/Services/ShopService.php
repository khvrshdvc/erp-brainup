<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\ShopOrderStatus;
use App\Models\Setting;
use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Models\User;
use App\Notifications\ShopOrderPlaced;
use App\Notifications\ShopOrderUpdated;
use App\Support\UzDate;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

            $waitUntil = $this->repurchaseLocks($student, [$item->id])->get($item->id);

            $error = match (true) {
                $waitUntil !== null => __('Bu mahsulotni yana :date dan keyin buyurtma qilish mumkin.', ['date' => UzDate::dayMonth($waitUntil)]),
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

    /**
     * Products the student bought recently and cannot order again yet: item id => first moment
     * they may order it. Cancelled orders do not count, their coins were refunded.
     *
     * @param  array<int>|null  $itemIds  null checks every product
     * @return Collection<int, Carbon>
     */
    public function repurchaseLocks(User $student, ?array $itemIds = null): Collection
    {
        $days = Setting::shopRepurchaseDays();

        if ($days === 0) {
            return collect();
        }

        return ShopOrder::withoutGlobalScopes()
            ->where('user_id', $student->id)
            ->where('status', '!=', ShopOrderStatus::Cancelled->value)
            ->where('created_at', '>', now()->subDays($days))
            ->when($itemIds !== null, fn ($query) => $query->whereIn('shop_item_id', $itemIds))
            ->get(['shop_item_id', 'created_at'])
            ->groupBy('shop_item_id')
            ->map(fn (Collection $orders) => $orders->max('created_at')->copy()->addDays($days));
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
     * Cancels pending orders that were not handed over within ShopOrder::PICKUP_DAYS.
     * Nobody closes them by hand, so the student is told and handled_by stays empty.
     */
    public function expireOverdue(): int
    {
        $expired = 0;

        ShopOrder::withoutGlobalScopes()
            ->where('status', ShopOrderStatus::Pending->value)
            ->where('created_at', '<=', now()->subDays(ShopOrder::PICKUP_DAYS))
            ->chunkById(100, function ($orders) use (&$expired) {
                foreach ($orders as $order) {
                    try {
                        $this->close($order, null, ShopOrderStatus::Cancelled);
                        $expired++;
                    } catch (ValidationException) {
                        // Closed by someone in the meantime.
                    }
                }
            });

        return $expired;
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

    /**
     * A cancelled order needs no attention from branch admins, so their "new order" notice goes away.
     */
    private function withdrawPlacedNotifications(ShopOrder $order): void
    {
        $notifications = DatabaseNotification::query()
            ->where('type', ShopOrderPlaced::class)
            ->where('data->order_id', $order->id);

        $adminIds = (clone $notifications)->pluck('notifiable_id')->unique();

        $notifications->delete();

        $adminIds->each(fn ($id) => User::forgetUnreadNotificationsCount($id));
    }

    private function close(ShopOrder $order, ?User $by, ShopOrderStatus $status, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $by, $status, $note) {
            $locked = ShopOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['order' => __('Bu buyurtma allaqachon yopilgan.')]);
            }

            $locked->update([
                'status' => $status,
                'handled_by' => $by?->id,
                'handled_at' => now(),
                'note' => $note,
            ]);

            if ($status === ShopOrderStatus::Cancelled) {
                ShopItem::whereKey($locked->shop_item_id)->whereNotNull('stock')->increment('stock');
            }

            $order->setRawAttributes($locked->getAttributes(), true);
        });

        if ($status === ShopOrderStatus::Cancelled) {
            $this->withdrawPlacedNotifications($order);
        }

        // Students hear about it only when someone else (or the deadline) closed their order.
        if ($order->user_id !== $by?->id) {
            $order->student?->notify(new ShopOrderUpdated($order));
        }
    }
}
