@php
    $user = auth()->user();
@endphp

<header class="erp-topbar">
    <div class="erp-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14"/>
        </svg>
        <input type="text" aria-label="Qidirish" placeholder="Qidirish: o‘quvchi, guruh, o‘qituvchi…">
    </div>

    <div class="erp-spacer"></div>

    <x-branch-switcher />

    <button type="button" class="iconbtn" aria-label="Bildirishnomalar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/>
        </svg>
    </button>

    <div class="erp-user">
        <div class="avatar">{{ $user->initials }}</div>
        <div class="who">
            <b>{{ $user->name }}</b>
            <span>{{ $user->role->label() }}</span>
        </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-sm">Chiqish</button>
    </form>
</header>
