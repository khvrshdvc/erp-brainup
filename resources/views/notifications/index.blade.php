<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Bildirishnomalar')" :title="__('Bildirishnomalar')" :subtitle="__('Yangi vazifalar, topshiriqlar va baholar')">
    @if (auth()->user()->unreadNotifications()->exists())
        <x-slot name="actions">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn">{{ __('Hammasini o‘qilgan deb belgilash') }}</button>
            </form>
        </x-slot>
    @endif

    <section class="card" style="overflow: hidden">
        @forelse ($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                @csrf
                <button type="submit" class="notif {{ $notification->read_at ? '' : 'unread' }}">
                    <span class="notif-dot {{ $notification->read_at ? 'off' : '' }}"></span>
                    <span style="flex: 1; min-width: 0">
                        <b>{{ __($notification->data['title'] ?? 'Bildirishnoma') }}</b>
                        <span class="notif-body">{{ isset($notification->data['body_key']) ? __($notification->data['body_key'], $notification->data['body_params'] ?? []) : ($notification->data['body'] ?? '') }}</span>
                    </span>
                    <span class="card-sub">{{ $notification->created_at->format('d.m.Y, H:i') }}</span>
                </button>
            </form>
        @empty
            <div class="t-empty">{{ __('Bildirishnoma yo‘q') }}</div>
        @endforelse
    </section>

    <x-pager :paginator="$notifications" />
</x-app-layout>
