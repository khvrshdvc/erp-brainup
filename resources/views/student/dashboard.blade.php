<x-app-layout
    :crumb="__('Bosh sahifa')"
    :title="__('Salom, :name!', ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')])"
    :subtitle="\App\Support\UzDate::long(now())">

    <x-slot name="actions">
        <a href="{{ route('student.achievements') }}" class="btn">{{ __('Yutuqlarim') }}</a>
        <a href="{{ route('student.shop.index') }}" class="btn btn-primary">{{ __('Do‘kon') }}</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card :label="__('Hamyonimdagi')" :value="number_format($balance, 0, '', ' ')" icon="coin" :count-up="$balance"
            :note="__('Hamyonda · jami topilgan: :xp', ['xp' => number_format((int) $student->xp, 0, '', ' ')])" />
        <x-stat-card :label="__('Daraja')" :value="__($student->level?->name ?? 'Uchqun')" icon="level" :note="__('Joriy daraja')" />
        <x-stat-card :label="__('Davomat seriyasi')" :value="$student->streak" icon="streak" :note="__('Ketma-ket darslar')" />
    </div>

    <div class="dash-grid">
        <section class="card">
            <div class="card-head">
                <h2 class="card-title">{{ __('Mening guruhlarim') }}</h2>
                @if ($groups->isNotEmpty())
                    <a class="link" href="{{ route('student.groups.index') }}">{{ __('Batafsil') }}</a>
                @endif
            </div>

            @forelse ($groups as $group)
                @php
                    $scheduleDays = collect($group->schedule['days'] ?? [])->map(fn ($day) => $days[$day] ?? $day)->implode(', ');
                    $time = $group->schedule['time'] ?? null;
                @endphp
                <a class="group-tile" href="{{ route('student.groups.index') }}">
                    <span class="group-tile-badge">{{ \Illuminate\Support\Str::of($group->name)->before('-')->limit(3, '') }}</span>
                    <span class="group-tile-body">
                        <b>{{ $group->name }}</b>
                        <span class="card-sub">{{ __($group->course?->name ?? 'Kurs belgilanmagan') }}</span>
                        <span class="group-tile-meta">
                            <span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8"/></svg>
                                {{ $group->teacher?->name ?? __('O‘qituvchi belgilanmagan') }}
                            </span>
                            @if ($scheduleDays !== '')
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 7v5l3 2M12 21a9 9 0 100-18 9 9 0 000 18"/></svg>
                                    {{ $scheduleDays }}{{ $time ? ' · '.$time : '' }}
                                </span>
                            @endif
                        </span>
                    </span>
                    <svg class="group-tile-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </a>
            @empty
                <div class="t-empty">{{ __('Guruhga biriktirilmagan') }}</div>
            @endforelse
        </section>

        <section class="card">
            <div class="card-head">
                <h2 class="card-title">{{ __('So‘nggi davomat') }}</h2>
                @if ($rate !== null)
                    <span class="card-sub" style="margin: 0"><b style="color: var(--ink)">{{ $rate }}%</b> {{ __('· :attended / :count dars', ['attended' => $attended, 'count' => $attendances->count()]) }}</span>
                @endif
            </div>

            @if ($attendances->isNotEmpty())
                <div class="att-strip" aria-label="{{ __('So‘nggi :count dars', ['count' => $attendances->count()]) }}">
                    @foreach ($attendances->reverse() as $attendance)
                        <span class="att-dot {{ $attendance->status->badge() }}"
                            title="{{ \App\Support\UzDate::dayMonth($attendance->lesson_date) }} · {{ $attendance->status->label() }}"></span>
                    @endforeach
                </div>
            @endif

            @forelse ($attendances as $attendance)
                <div class="att-item">
                    <span class="att-date">
                        <b>{{ $attendance->lesson_date->day }}</b>
                        <small>{{ \App\Support\UzDate::shortDay($attendance->lesson_date) }}</small>
                    </span>
                    <span class="att-body">
                        <b>{{ $attendance->studyGroup?->name ?? '—' }}</b>
                        <span class="card-sub">{{ \App\Support\UzDate::dayMonth($attendance->lesson_date) }}</span>
                    </span>
                    <span class="att-status {{ $attendance->status->badge() }}">{{ $attendance->status->label() }}</span>
                </div>
            @empty
                <div class="t-empty">{{ __('Hali davomat belgilanmagan') }}</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
