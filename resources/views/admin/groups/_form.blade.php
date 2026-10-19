@php
    $editing = isset($group) && $group;
    $selectedDays = old('schedule_days', $group->schedule['days'] ?? []);
@endphp

<form method="POST" class="stack-lg"
    action="{{ $editing ? route('admin.groups.update', $group) : route('admin.groups.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <h2 class="card-title">Asosiy ma‘lumotlar</h2>

        <div class="form-grid">
            <div class="field">
                <label for="name">Guruh nomi *</label>
                <input id="name" class="input" type="text" name="name" maxlength="120" required
                    value="{{ old('name', $group->name ?? '') }}">
            </div>

            @if ($editing)
                <div class="field">
                    <label>Filial</label>
                    <input class="input" type="text" value="{{ $group->branch?->name }}" disabled>
                </div>
            @elseif ($branches->count() > 1)
                <div class="field">
                    <label for="branch_id">Filial *</label>
                    <select id="branch_id" name="branch_id" class="select" required>
                        <option value="">Filialni tanlang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id', $defaultBranchId) === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
            @endif

            <div class="field">
                <label for="course_id">Kurs</label>
                <select id="course_id" name="course_id" class="select">
                    <option value="">Tanlanmagan</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((int) old('course_id', $group->course_id ?? 0) === $course->id)>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="teacher_id">O‘qituvchi</label>
                <select id="teacher_id" name="teacher_id" class="select">
                    <option value="">Biriktirilmagan</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((int) old('teacher_id', $group->teacher_id ?? 0) === $teacher->id)>
                            {{ $teacher->name }} ({{ $teacher->branches->pluck('name')->implode(', ') }})
                        </option>
                    @endforeach
                </select>
                <div class="field-hint">O‘qituvchi shu filialga biriktirilgan bo‘lishi kerak</div>
            </div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">Muddat va sig‘im</h2>

        <div class="form-grid">
            <div class="field">
                <label for="starts_on">Boshlanish sanasi</label>
                <input id="starts_on" class="input" type="date" name="starts_on"
                    value="{{ old('starts_on', optional($group->starts_on ?? null)->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="ends_on">Tugash sanasi</label>
                <input id="ends_on" class="input" type="date" name="ends_on"
                    value="{{ old('ends_on', optional($group->ends_on ?? null)->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="capacity">Sig‘im *</label>
                <input id="capacity" class="input" type="number" name="capacity" min="1" max="100" required
                    value="{{ old('capacity', $group->capacity ?? 15) }}">
            </div>

            <div class="field">
                <label for="status">Holat *</label>
                <select id="status" name="status" class="select" required>
                    @foreach (\App\Enums\GroupStatus::cases() as $status)
                        <option value="{{ $status->value }}"
                            @selected(old('status', ($group->status ?? \App\Enums\GroupStatus::Active)->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">Dars jadvali</h2>

        <div class="check-row">
            @foreach ($days as $key => $label)
                <label class="check">
                    <input type="checkbox" name="schedule_days[]" value="{{ $key }}" @checked(in_array($key, $selectedDays))>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="field" style="max-width: 200px">
            <label for="schedule_time">Dars vaqti</label>
            <input id="schedule_time" class="input" type="time" name="schedule_time"
                value="{{ old('schedule_time', $group->schedule['time'] ?? '') }}">
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ $editing ? route('admin.groups.show', $group) : route('admin.groups.index') }}" class="btn btn-lg">Bekor qilish</a>
        <button type="submit" class="btn btn-primary btn-lg">Saqlash</button>
    </div>
</form>
