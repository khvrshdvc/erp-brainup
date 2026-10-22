<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Vazifalar')" :title="__('Vazifalar')" :subtitle="__('Barcha o‘qituvchilar bergan vazifalar va ularning topshirilish holati')">
    <form method="GET" action="{{ route('admin.assignments.index') }}" class="card filters">
        <div class="field">
            <label for="group">{{ __('Guruh') }}</label>
            <select id="group" name="group" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="teacher">{{ __('O‘qituvchi') }}</label>
            <select id="teacher" name="teacher" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) request('teacher') === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">{{ __('Holat') }}</label>
            <select id="status" name="status" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach (\App\Enums\AssignmentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">{{ __('Filtrlash') }}</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 980px">
                <div class="t-head" style="--cols: 2.4fr 1.4fr 1.6fr 1.1fr 1.3fr 1.1fr 1.3fr">
                    <div>{{ __('Vazifa') }}</div>
                    <div>{{ __('Guruh') }}</div>
                    <div>{{ __('O‘qituvchi') }}</div>
                    <div>{{ __('Yaratilgan') }}</div>
                    <div>{{ __('Muddat') }}</div>
                    <div>{{ __('Topshirilgan') }}</div>
                    <div>{{ __('Holat') }}</div>
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
                    <div class="t-empty">{{ __('Vazifa topilmadi') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$assignments" />
</x-app-layout>
