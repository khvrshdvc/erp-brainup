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
        <h2 class="card-title">{{ __('Asosiy ma‘lumotlar') }}</h2>

        <div class="form-grid">
            <div class="field">
                <label for="name">{{ __('Guruh nomi *') }}</label>
                <input id="name" class="input" type="text" name="name" maxlength="120" required
                    value="{{ old('name', $group->name ?? '') }}">
            </div>

            @if ($editing)
                <div class="field">
                    <label>{{ __('Filial') }}</label>
                    <input class="input" type="text" value="{{ $group->branch?->name }}" disabled>
                </div>
            @elseif ($branches->count() > 1)
                <div class="field">
                    <label for="branch_id">{{ __('Filial *') }}</label>
                    <select id="branch_id" name="branch_id" class="select" required>
                        <option value="">{{ __('Filialni tanlang') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id', $defaultBranchId) === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
            @endif

            <div class="field">
                <label for="course_id">{{ __('Kurs') }}</label>
                <select id="course_id" name="course_id" class="select">
                    <option value="">{{ __('Tanlanmagan') }}</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((int) old('course_id', $group->course_id ?? 0) === $course->id)>{{ __($course->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="teacher_id">{{ __('O‘qituvchi') }}</label>
                <select id="teacher_id" name="teacher_id" class="select">
                    <option value="">{{ __('Biriktirilmagan') }}</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((int) old('teacher_id', $group->teacher_id ?? 0) === $teacher->id)>
                            {{ $teacher->name }} ({{ $teacher->branches->pluck('name')->implode(', ') }})
                        </option>
                    @endforeach
                </select>
                <div class="field-hint">{{ __('O‘qituvchi shu filialga biriktirilgan bo‘lishi kerak') }}</div>
            </div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('Muddat va sig‘im') }}</h2>

        <div class="form-grid">
            <div class="field">
                <label for="starts_on">{{ __('Boshlanish sanasi') }}</label>
                <x-date-input name="starts_on" :value="old('starts_on', optional($group->starts_on ?? null)->format('Y-m-d'))" />
                <x-field-error name="starts_on" />
            </div>

            <div class="field">
                <label for="ends_on">{{ __('Tugash sanasi') }}</label>
                <x-date-input name="ends_on" :value="old('ends_on', optional($group->ends_on ?? null)->format('Y-m-d'))" />
                <x-field-error name="ends_on" />
            </div>

            <div class="field">
                <label for="capacity">{{ __('Sig‘im *') }}</label>
                <input id="capacity" class="input" type="number" name="capacity" min="1" max="100" required
                    value="{{ old('capacity', $group->capacity ?? 15) }}">
            </div>

            <div class="field">
                <label for="status">{{ __('Holat *') }}</label>
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
        <h2 class="card-title">{{ __('Dars jadvali') }}</h2>

        <div class="check-row">
            @foreach ($days as $key => $label)
                <label class="check">
                    <input type="checkbox" name="schedule_days[]" value="{{ $key }}" @checked(in_array($key, $selectedDays))>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="field" style="max-width: 200px">
            <label for="schedule_time">{{ __('Dars vaqti') }}</label>
            <x-time-select name="schedule_time" id="schedule_time" :step="5" :placeholder="__('Belgilanmagan')"
                :value="old('schedule_time', $group->schedule['time'] ?? null)" />
            <x-field-error name="schedule_time" />
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ $editing ? route('admin.groups.show', $group) : route('admin.groups.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
        <button type="submit" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
    </div>
</form>
