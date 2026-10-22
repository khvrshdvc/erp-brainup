<x-app-layout crumb="Guruhlar / {{ $group->name }}" :title="$group->name"
    :subtitle="($group->course?->name ?? 'Kurs tanlanmagan').' · '.$group->branch?->name">

    <x-slot name="actions">
        <a href="{{ route('attendance.create', $group) }}" class="btn">Davomat</a>
        <x-status-switch :action="route('admin.groups.status', $group)"
            :active="$group->isActive()"
            :label="$group->status->label()" />
        <x-edit-button :href="route('admin.groups.edit', $group)" />
    </x-slot>

    <section class="card card-pad">
        <div class="split" style="gap: 32px">
            <div><div class="label-up">Holat</div><x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge></div>
            <div><div class="label-up">O‘qituvchi</div><b>{{ $group->teacher?->name ?? '—' }}</b></div>
            <div><div class="label-up">O‘quvchilar</div><b>{{ $students->count() }} / {{ $group->capacity }}</b></div>
            <div>
                <div class="label-up">Jadval</div>
                <b>
                    @if ($group->schedule)
                        {{ collect($group->schedule['days'] ?? [])->map(fn ($day) => $days[$day] ?? $day)->implode(', ') }}
                        {{ ! empty($group->schedule['time']) ? '· '.$group->schedule['time'] : '' }}
                    @else
                        —
                    @endif
                </b>
            </div>
            <div>
                <div class="label-up">Davr</div>
                <b>{{ $group->starts_on?->format('d.m.Y') ?? '—' }} – {{ $group->ends_on?->format('d.m.Y') ?? '—' }}</b>
            </div>
        </div>
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">O‘quvchilar</h2>

            <form method="POST" action="{{ route('admin.groups.students.add', $group) }}" class="page-actions">
                @csrf
                <select name="student_id" class="select" style="width: 260px" aria-label="O‘quvchi" required>
                    <option value="">O‘quvchi tanlang</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Qo‘shish</button>
            </form>
        </div>

        <div class="t-scroll">
            <div class="t-min" style="min-width: 640px">
                <div class="t-head" style="--cols: 2.4fr 1.4fr 1fr 1fr">
                    <div>O‘quvchi</div>
                    <div>Daraja</div>
                    <div>BrainCoin</div>
                    <div></div>
                </div>

                @forelse ($students as $student)
                    <div class="t-row" style="--cols: 2.4fr 1.4fr 1fr 1fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            <a class="link" href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a>
                        </div>
                        <div>{{ $student->level?->name ?? 'Boshlovchi' }}</div>
                        <div>{{ $student->xp }}</div>
                        <div class="t-right">
                            <form method="POST" action="{{ route('admin.groups.students.remove', [$group, $student]) }}"
                                onsubmit="return confirm('O‘quvchini guruhdan chiqarasizmi?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm">Chiqarish</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">Guruhda o‘quvchi yo‘q</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
