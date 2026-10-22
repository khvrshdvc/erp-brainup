<x-app-layout crumb="Bosh sahifa / Vazifalar" title="Vazifalar" subtitle="Siz bergan vazifalar va o‘quvchilarning topshirishlari">
    <x-slot name="actions">
        <a href="{{ route('teacher.assignments.create') }}" class="btn btn-primary">+ Vazifa yaratish</a>
    </x-slot>

    <form method="GET" action="{{ route('teacher.assignments.index') }}" class="card filters">
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
            <div class="t-min" style="min-width: 960px">
                <div class="t-head" style="--cols: 2.4fr 1.6fr 1.1fr 1.3fr .7fr 1fr 1fr 1.3fr">
                    <div>Vazifa</div>
                    <div>Guruh</div>
                    <div>Yaratilgan</div>
                    <div>Muddat</div>
                    <div>Jami</div>
                    <div>Topshirgan</div>
                    <div>Kutilmoqda</div>
                    <div>Holat</div>
                </div>

                @forelse ($assignments as $assignment)
                    @php
                        $total = $assignment->studyGroup?->students_count ?? 0;
                        [$label, $type] = $assignment->teacherState((int) $assignment->pending_count);
                    @endphp
                    <div class="t-row" style="--cols: 2.4fr 1.6fr 1.1fr 1.3fr .7fr 1fr 1fr 1.3fr">
                        <div>
                            <a class="link" style="font-weight: 600" href="{{ route('teacher.assignments.show', $assignment) }}">{{ $assignment->title }}</a>
                        </div>
                        <div>{{ $assignment->studyGroup?->name }}</div>
                        <div>{{ $assignment->created_at->format('d.m.Y') }}</div>
                        <div>{{ $assignment->due_at->format('d.m.Y, H:i') }}</div>
                        <div>{{ $total }}</div>
                        <div>{{ $assignment->submissions_count }}</div>
                        <div>{{ max(0, $total - $assignment->submissions_count) }}</div>
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
