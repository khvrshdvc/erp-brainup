<x-app-layout crumb="Bosh sahifa / Vazifalar" title="Vazifalar" subtitle="Barcha o‘qituvchilar bergan vazifalar va ularning topshirilish holati">
    <form method="GET" action="{{ route('admin.assignments.index') }}" class="card filters">
        <div class="field">
            <label for="group">Guruh</label>
            <select id="group" name="group" class="select">
                <option value="">Barchasi</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="teacher">O‘qituvchi</label>
            <select id="teacher" name="teacher" class="select">
                <option value="">Barchasi</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) request('teacher') === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">Holat</label>
            <select id="status" name="status" class="select">
                <option value="">Barchasi</option>
                @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">Filtrlash</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 980px">
                <div class="t-head" style="--cols: 2.4fr 1.4fr 1.6fr 1.1fr 1.3fr 1.1fr 1.3fr">
                    <div>Vazifa</div>
                    <div>Guruh</div>
                    <div>O‘qituvchi</div>
                    <div>Yaratilgan</div>
                    <div>Muddat</div>
                    <div>Topshirilgan</div>
                    <div>Holat</div>
                </div>

                @forelse ($assignments as $assignment)
                    @php [$label, $type] = $assignment->teacherState((int) $assignment->pending_count); @endphp
                    <div class="t-row" style="--cols: 2.4fr 1.4fr 1.6fr 1.1fr 1.3fr 1.1fr 1.3fr">
                        <div><a class="link" style="font-weight: 600" href="{{ route('admin.assignments.show', $assignment) }}">{{ $assignment->title }}</a></div>
                        <div>{{ $assignment->studyGroup?->name }}</div>
                        <div>{{ $assignment->teacher?->name ?? '—' }}</div>
                        <div>{{ $assignment->created_at->format('d.m.Y') }}</div>
                        <div>{{ $assignment->due_at->format('d.m.Y, H:i') }}</div>
                        <div>{{ $assignment->submissions_count }} / {{ $assignment->studyGroup?->students_count ?? 0 }}</div>
                        <div><x-badge :type="$type">{{ $label }}</x-badge></div>
                    </div>
                @empty
                    <div class="t-empty">Vazifa topilmadi</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$assignments" />
</x-app-layout>
