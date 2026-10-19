@php
    $tabs = ['30d' => 'So‘nggi 30 kun', '6m' => 'So‘nggi 6 oy', 'year' => 'Joriy o‘quv yili'];
@endphp

<x-app-layout crumb="Bosh sahifa / Hisobotlar" title="Hisobotlar" subtitle="Markazning umumiy ko‘rsatkichlari">
    <x-slot name="actions">
        <a href="{{ route('admin.reports.export', ['period' => $period]) }}" class="btn">Eksport (CSV)</a>
    </x-slot>

    <div class="tabs">
        @foreach ($tabs as $key => $label)
            <a class="tab {{ $period === $key ? 'on' : '' }}" href="{{ route('admin.reports.index', ['period' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="kpi-grid">
        <x-stat-card label="O‘rtacha davomat" :value="$kpis['attendance'] === null ? '—' : $kpis['attendance'].'%'" icon="calendar" :note="$kpis['attendanceNote']" />
        <x-stat-card label="Yangi o‘quvchilar" :value="$kpis['newStudents']" icon="students" note="Tanlangan davrda" />
        <x-stat-card label="O‘rtacha baho" :value="$kpis['grade'] === null ? '—' : $kpis['grade'].' / 100'" icon="level" note="Tekshirilgan vazifalar bo‘yicha" />
        <x-stat-card label="Vaqtida topshirish" :value="$kpis['onTime'] === null ? '—' : $kpis['onTime'].'%'" icon="xp" :note="$kpis['submitted'].' ta topshiriq'" />
    </div>

    <div class="split" style="align-items: stretch">
        <section class="card card-pad stack" style="flex: 1 1 360px">
            <div>
                <h2 class="card-title">Yangi o‘quvchilar</h2>
                <div class="card-sub">Oxirgi 6 oy, oylar bo‘yicha ro‘yxatdan o‘tganlar</div>
            </div>
            <div class="chart wide">
                @foreach ($months as $month)
                    <div class="chart-col">
                        <span class="chart-pct">{{ $month['count'] }}</span>
                        <div class="chart-bar {{ $month['count'] ? '' : 'empty' }}" style="height: {{ $month['height'] }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="chart-days wide">
                @foreach ($months as $month)
                    <span>{{ $month['label'] }}</span>
                @endforeach
            </div>
        </section>

        <section class="card card-pad stack" style="flex: 1 1 360px">
            <div>
                <h2 class="card-title">Davomat dinamikasi</h2>
                <div class="card-sub">Haftalik o‘rtacha davomat, % (oxirgi 8 hafta)</div>
            </div>
            <div class="chart wide">
                @foreach ($weeks as $week)
                    <div class="chart-col">
                        <span class="chart-pct">{{ $week['has'] ? $week['pct'] : '—' }}</span>
                        <div class="chart-bar {{ $week['has'] ? '' : 'empty' }}" style="height: {{ $week['height'] }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="chart-days wide">
                @foreach ($weeks as $week)
                    <span>{{ $week['label'] }}</span>
                @endforeach
            </div>
        </section>
    </div>

    <div class="split" style="align-items: stretch">
        <section class="card card-pad stack" style="flex: 1 1 360px">
            <div>
                <h2 class="card-title">Yo‘nalishlar bo‘yicha guruhlar</h2>
                <div class="card-sub">Guruhlar va o‘quvchilar soni</div>
            </div>

            @forelse ($courses as $course)
                <div class="bar-row">
                    <span class="name">{{ $course['name'] }}</span>
                    <div class="progress"><i style="width: {{ $course['pct'] }}%"></i></div>
                    <span class="val">{{ $course['groups'] }} guruh · {{ $course['students'] }}</span>
                </div>
            @empty
                <div class="t-empty">Guruh yo‘q</div>
            @endforelse
        </section>

        <section class="card card-pad stack" style="flex: 1 1 360px">
            <div>
                <h2 class="card-title">Vazifalarni bajarish</h2>
                <div class="card-sub">Tanlangan davrda berilgan vazifalar bo‘yicha ({{ $statusRows['expected'] }} ta kutilgan topshiriq)</div>
            </div>

            @foreach ($statusRows['rows'] as $row)
                <div class="bar-row" style="grid-template-columns: 190px minmax(0, 1fr) 90px">
                    <span><x-badge :type="$row['badge']">{{ $row['label'] }}</x-badge></span>
                    <div class="progress"><i style="width: {{ $row['pct'] }}%"></i></div>
                    <span class="val">{{ $row['count'] }} · {{ $row['pct'] }}%</span>
                </div>
            @endforeach
        </section>
    </div>

    @if ($branches)
        <section class="card" style="overflow: hidden">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Filiallar bo‘yicha</h2>
                    <div class="card-sub">Tanlangan davrdagi davomat</div>
                </div>
            </div>
            <div class="t-scroll">
                <div class="t-min" style="min-width: 520px">
                    <div class="t-head" style="--cols: 2.4fr 1fr 1.4fr">
                        <div>Filial</div>
                        <div>O‘quvchilar</div>
                        <div>O‘rtacha davomat</div>
                    </div>
                    @foreach ($branches as $branch)
                        <div class="t-row" style="--cols: 2.4fr 1fr 1.4fr">
                            <div style="font-weight: 600">{{ $branch['name'] }}</div>
                            <div>{{ $branch['students'] }}</div>
                            <div>{{ $branch['pct'] === null ? '—' : $branch['pct'].'%' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-app-layout>
