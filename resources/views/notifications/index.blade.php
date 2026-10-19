<x-app-layout crumb="Bosh sahifa / Bildirishnomalar" title="Bildirishnomalar" subtitle="Yangi vazifalar, topshiriqlar va baholar">
    @if (auth()->user()->unreadNotifications()->exists())
        <x-slot name="actions">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn">Hammasini o‘qilgan deb belgilash</button>
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
                        <b>{{ $notification->data['title'] ?? 'Bildirishnoma' }}</b>
                        <span class="notif-body">{{ $notification->data['body'] ?? '' }}</span>
                    </span>
                    <span class="card-sub">{{ $notification->created_at->format('d.m.Y, H:i') }}</span>
                </button>
            </form>
        @empty
            <div class="t-empty">Bildirishnoma yo‘q</div>
        @endforelse
    </section>

    <x-pager :paginator="$notifications" />
</x-app-layout>
