@php
    use App\Enums\Role;

    $user = auth()->user();

    $branchId = app(\App\Services\CurrentBranch::class)->id();
    $currentBranch = $branchId ? \App\Models\Branch::find($branchId) : null;

    $icons = [
        'home' => 'M3 11l9-8 9 8M5 10v10h14V10',
        'branch' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6',
        'groups' => 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8M21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8',
        'students' => 'M2 9l10-5 10 5-10 5-10-5zM6 11.5V16c0 1 3 3 6 3s6-2 6-3v-4.5',
        'teachers' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8',
        'tasks' => 'M9 11l3 3 8-8M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9',
        'calendar' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
        'trophy' => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3',
        'reports' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'shop' => 'M6 7h12l1 13H5L6 7zM9 7V5a3 3 0 016 0v2',
        'settings' => 'M12 15a3 3 0 100-6 3 3 0 000 6M4 12h2M18 12h2M12 4v2M12 18v2M6.3 6.3l1.4 1.4M16.3 16.3l1.4 1.4M17.7 6.3l-1.4 1.4M7.7 16.3l-1.4 1.4',
    ];

    if ($user->hasRole(Role::SuperAdmin, Role::BranchAdmin)) {
        $items = [
            ['Bosh sahifa', 'home', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
        ];

        if ($user->isSuperAdmin()) {
            $items[] = ['Filiallar', 'branch', route('admin.branches.index'), request()->routeIs('admin.branches.*')];
        }

        $items = array_merge($items, [
            ['Guruhlar', 'groups', route('admin.groups.index'), request()->routeIs('admin.groups.*')],
            ['O‘quvchilar', 'students', route('admin.students.index'), request()->routeIs('admin.students.*')],
            ['O‘qituvchilar', 'teachers', route('admin.teachers.index'), request()->routeIs('admin.teachers.*')],
            ['Vazifalar', 'tasks', route('admin.assignments.index'), request()->routeIs('admin.assignments.*')],
            ['Topshirganlar', 'students', route('submissions.index'), request()->routeIs('submissions.*')],
            ['Davomat', 'calendar', route('groups.index'), request()->routeIs('groups.index', 'attendance.*')],
        ]);

        if ($user->isSuperAdmin()) {
            $items[] = ['Gamifikatsiya', 'trophy', route('admin.gamification.index'), request()->routeIs('admin.gamification.*')];
        }

        $items[] = ['Do‘kon', 'shop', route('admin.shop.orders.index'), request()->routeIs('admin.shop.*', 'admin.shop-items.*')];

        $items = array_merge($items, [
            ['Hisobotlar', 'reports', route('admin.reports.index'), request()->routeIs('admin.reports.*')],
            ['Sozlamalar', 'settings', route('admin.settings.edit'), request()->routeIs('admin.settings.*')],
        ]);
    } elseif ($user->isTeacher()) {
        $items = [
            ['Bosh sahifa', 'home', route('teacher.dashboard'), request()->routeIs('teacher.dashboard')],
            ['Guruhlar', 'groups', route('groups.index'), request()->routeIs('groups.*')],
            ['Davomat', 'calendar', route('groups.index'), request()->routeIs('attendance.*')],
            ['Vazifalar', 'tasks', route('teacher.assignments.index'), request()->routeIs('teacher.assignments.*', 'teacher.submissions.*')],
            ['Topshirganlar', 'students', route('submissions.index'), request()->routeIs('submissions.*')],
            ['Profil', 'teachers', route('profile.edit'), request()->routeIs('profile.*')],
        ];
    } else {
        $items = [
            ['Bosh sahifa', 'home', route('student.dashboard'), request()->routeIs('student.dashboard')],
            ['Guruhim', 'groups', route('student.groups.index'), request()->routeIs('student.groups.*')],
            ['Vazifalar', 'tasks', route('student.assignments.index'), request()->routeIs('student.assignments.*')],
            ['Topshirganlarim', 'students', route('student.submissions.index'), request()->routeIs('student.submissions.*')],
            ['Yutuqlar', 'trophy', route('student.achievements'), request()->routeIs('student.achievements')],
            ['Do‘kon', 'shop', route('student.shop.index'), request()->routeIs('student.shop.*')],
            ['Profil', 'teachers', route('profile.edit'), request()->routeIs('profile.*')],
        ];
    }
@endphp

<aside class="erp-sidebar" id="erp-sidebar">
    <button type="button" class="erp-sidebar-close" @click="nav = false" aria-label="Menyuni yopish">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12M18 6L6 18"/>
        </svg>
    </button>

    <img
        src="{{ asset('images/logo-brainup-white.png') }}"
        alt="BrainUp Academy"
        class="erp-brand-logo"
    >

    <nav class="erp-nav">
        @foreach ($items as [$label, $icon, $href, $active])
            <a href="{{ $href }}" @class(['active' => $active])>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $icons[$icon] }}"/>
                </svg>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="erp-org">
        <b>{{ \App\Models\Setting::get('center_name', 'O‘quv markazi') }}</b>
        {{ $currentBranch?->name ?? 'Barcha filiallar' }}
    </div>

    <form method="POST" action="{{ route('logout') }}" class="erp-logout-mobile">
        @csrf
        <button type="submit" class="btn">Chiqish</button>
    </form>
</aside>
