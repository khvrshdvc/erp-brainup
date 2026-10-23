@php
    $user = auth()->user();
    $unread = $user->unreadNotificationsCount();
@endphp

<header class="erp-topbar">
    <button type="button" class="iconbtn erp-menu" @click="nav = true" aria-label="{{ __('Menyuni ochish') }}" :aria-expanded="nav.toString()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
    </button>

    @if ($user->role->isStaff())
    <form class="erp-search" method="GET" action="{{ route('search') }}" role="search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14"/>
        </svg>
        <input type="text" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" aria-label="{{ __('Qidirish') }}" placeholder="{{ __('Qidirish: o‘quvchi, guruh, o‘qituvchi…') }}">
    </form>
    @endif

    <div class="erp-spacer"></div>

    <x-branch-switcher />

    <x-language-switcher />

    <x-theme-toggle />

    <a href="{{ route('notifications.index') }}" class="iconbtn" aria-label="{{ __('Bildirishnomalar') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/>
        </svg>
        @if ($unread > 0)
            <span class="count">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </a>

    <div class="erp-user">
        <div class="avatar">{{ $user->initials }}</div>
        <div class="who">
            <b>{{ $user->name }}</b>
            <span>{{ $user->role->label() }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="erp-logout">
        @csrf
        <button type="submit" class="btn btn-sm">{{ __('Chiqish') }}</button>
    </form>
</header>
