<x-app-layout crumb="Bosh sahifa / Guruhim"
    :title="$group?->name ?? 'Guruhim'"
    :subtitle="$group ? ($group->course?->name ?? 'Kurs tanlanmagan').' · '.$group->branch?->name : 'Sizga hali guruh biriktirilmagan'">

    @if ($groups->count() > 1)
        <div class="tabs">
            @foreach ($groups as $item)
                <a class="tab {{ $item->id === $group->id ? 'on' : '' }}" href="{{ route('student.groups.index', ['group' => $item->id]) }}">{{ $item->name }}</a>
            @endforeach
        </div>
    @endif

    @if (! $group)
        <section class="card"><div class="t-empty">Guruhga biriktirilmagan. Administratorga murojaat qiling.</div></section>
    @else
        <div class="split" style="align-items: flex-start">
            <div class="stack-lg" style="flex: 2 1 420px; min-width: 0">
                <section class="card card-pad stack">
                    <div class="card-head" style="padding: 0; border: 0">
                        <h2 class="card-title">Guruh haqida</h2>
                        <x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge>
                    </div>

                    @if ($group->course?->description)
                        <div style="white-space: pre-line">{{ $group->course->description }}</div>
                    @endif

                    <div>
                        <div class="kv"><span>Kurs</span><b>{{ $group->course?->name ?? '—' }}</b></div>
                        <div class="kv"><span>Boshlanish sanasi</span><b>{{ $group->starts_on?->format('d.m.Y') ?? '—' }}</b></div>
                        <div class="kv"><span>Tugash sanasi</span><b>{{ $group->ends_on?->format('d.m.Y') ?? '—' }}</b></div>
                        <div class="kv"><span>O‘quvchilar soni</span><b>{{ $group->students_count }} / {{ $group->capacity }}</b></div>
                        <div class="kv"><span>Filial</span><b>{{ $group->branch?->name ?? '—' }}</b></div>
                    </div>
                </section>

                <section class="card" style="overflow: hidden">
                    <div class="card-head"><h2 class="card-title">Dars jadvali</h2></div>

                    @php $scheduleDays = $group->schedule['days'] ?? []; @endphp

                    @forelse ($scheduleDays as $day)
                        <div class="t-row" style="--cols: 2fr 1fr">
                            <div style="font-weight: 600">{{ $days[$day] ?? $day }}</div>
                            <div>{{ $group->schedule['time'] ?? '—' }}</div>
                        </div>
                    @empty
                        <div class="t-empty">Dars jadvali belgilanmagan</div>
                    @endforelse
                </section>
            </div>

            <div class="stack-lg" style="flex: 1 1 300px; min-width: 0">
                <section class="card card-pad stack">
                    <h2 class="card-title">O‘qituvchi</h2>

                    @if ($group->teacher)
                        <div class="t-name">
                            <span class="avatar">{{ $group->teacher->initials }}</span>
                            <div>
                                {{ $group->teacher->name }}
                                <div class="card-sub" style="font-weight: 400">{{ $group->course?->name ?? 'O‘qituvchi' }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="kv"><span>Telefon</span><b>{{ $group->teacher->phone ?? '—' }}</b></div>
                            <div class="kv"><span>Email</span><b style="overflow-wrap: anywhere">{{ $group->teacher->email }}</b></div>
                        </div>
                    @else
                        <div class="card-sub">O‘qituvchi biriktirilmagan</div>
                    @endif
                </section>

                <section class="card card-pad stack">
                    <h2 class="card-title">Mening davomatim</h2>

                    @if ($percent === null)
                        <div class="card-sub">Bu guruhda hali davomat belgilanmagan</div>
                    @else
                        <div>
                            <div class="kpi-value" style="font-size: 36px">{{ $percent }}%</div>
                            <div class="card-sub">{{ $total }} ta darsdan</div>
                        </div>

                        <div class="progress"><i style="width: {{ $percent }}%"></i></div>

                        <div class="stack" style="gap: 10px">
                            @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                <div class="sum-row">
                                    <x-badge :type="$status->badge()">{{ $status->label() }}</x-badge>
                                    <span>{{ $counts->get($status->value, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    @endif
</x-app-layout>
