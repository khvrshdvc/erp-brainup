@php
    $editing = isset($assignment) && $assignment;
    $published = $editing && ! $assignment->isDraft();
    $hasMaterial = $editing && $assignment->attachments->isNotEmpty();
@endphp

<form method="POST" enctype="multipart/form-data" class="stack-lg"
    action="{{ $editing ? route('teacher.assignments.update', $assignment) : route('teacher.assignments.store') }}"
    x-data="oneSubmit" @submit="guard($event)" :aria-busy="submitting">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('1. Asosiy ma‘lumotlar') }}</h2>

        <div class="field">
            <label for="title">{{ __('Vazifa nomi *') }}</label>
            <input id="title" type="text" name="title" maxlength="160" required autofocus
                @class(['input', 'is-invalid' => $errors->has('title')])
                placeholder="{{ __('Masalan: 5-dars bo‘yicha uy vazifasi') }}"
                value="{{ old('title', $assignment->title ?? '') }}">
            <x-field-error name="title" />
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="study_group_id">{{ __('Guruh *') }}</label>
                @if ($editing)
                    <input id="study_group_id" class="input" type="text" value="{{ $assignment->studyGroup?->name }}" disabled>
                    <div class="field-hint">{{ __('Guruhni vazifa yaratilgandan keyin o‘zgartirib bo‘lmaydi') }}</div>
                @else
                    <select id="study_group_id" name="study_group_id" required
                        @class(['select', 'is-invalid' => $errors->has('study_group_id')])>
                        <option value="">{{ __('Guruhni tanlang') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('study_group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @if ($groups->isEmpty())
                        <div class="field-hint">{{ __('Sizga hali guruh biriktirilmagan') }}</div>
                    @endif
                @endif
                <x-field-error name="study_group_id" />
            </div>

            @php
                $due = $assignment->due_at ?? null;
                $dueDate = old('due_date', $due?->format('Y-m-d'));
                $dueTime = old('due_time', $due?->format('H:i') ?? '18:00');
            @endphp
            <div class="field" x-data="{
                inDays(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    const pad = (n) => String(n).padStart(2, '0');
                    $dispatch('set-date', { id: 'due_date', value: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` });
                    $refs.time.value = '18:00';
                },
            }">
                <label for="due_date">{{ __('Topshirish muddati *') }}</label>
                <div class="date-time">
                    <x-date-input name="due_date" :value="$dueDate" required
                        :min="$editing ? null : now()->format('Y-m-d')" :invalid="$errors->has('due_at')" />
                    <x-time-select name="due_time" id="due_time" :value="$dueTime" required x-ref="time"
                        :aria-label="__('Soat')" :class="$errors->has('due_at') ? 'is-invalid' : ''" />
                </div>
                <div class="presets">
                    <button type="button" class="pill" @click="inDays(1)">{{ __('Ertaga') }}</button>
                    <button type="button" class="pill" @click="inDays(3)">{{ __('3 kun') }}</button>
                    <button type="button" class="pill" @click="inDays(7)">{{ __('1 hafta') }}</button>
                </div>
                <x-field-error name="due_at" />
            </div>

            <div class="field">
                <label for="max_score">{{ __('Maksimal ball *') }}</label>
                <input id="max_score" type="number" name="max_score" min="1" max="1000" required inputmode="numeric"
                    @class(['input', 'is-invalid' => $errors->has('max_score')])
                    value="{{ old('max_score', $assignment->max_score ?? 100) }}">
                <div class="field-hint">{{ __('1 dan 1000 gacha') }}</div>
                <x-field-error name="max_score" />
            </div>
        </div>

        <div class="field" x-data="{ n: {{ mb_strlen(old('description', $assignment->description ?? '')) }} }">
            <label for="description">{{ __('Vazifa tavsifi *') }}</label>
            <textarea id="description" name="description" maxlength="2000" required
                @class(['textarea', 'is-invalid' => $errors->has('description')])
                placeholder="{{ __('O‘quvchilar nima qilishi kerakligini batafsil yozing') }}"
                @input="n = $event.target.value.length">{{ old('description', $assignment->description ?? '') }}</textarea>
            <div class="counter"><span x-text="n">0</span> {{ __('/ 2000 belgi') }}</div>
            <x-field-error name="description" />
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('2. Qo‘shimcha materiallar') }} <span class="card-sub" style="font-weight: 400">{{ __('(ixtiyoriy)') }}</span></h2>

        @if ($hasMaterial)
            @foreach ($assignment->attachments as $attachment)
                <x-file-chip :attachment="$attachment" />
            @endforeach
            <div class="field-hint">{{ __('Yangi fayl tanlasangiz, joriy fayl almashtiriladi.') }}</div>
        @endif

        <x-file-picker name="material" id="material" />
    </section>

    <div class="form-actions" style="justify-content: space-between; align-items: center">
        <div class="field-hint" style="max-width: 420px">
            {{ __('Qoralama o‘quvchilarga ko‘rinmaydi. E‘lon qilingan vazifa guruh o‘quvchilariga darhol ko‘rinadi va ularga bildirishnoma yuboriladi.') }}
        </div>

        <div class="page-actions">
            <a href="{{ $editing ? route('teacher.assignments.show', $assignment) : route('teacher.assignments.index') }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>

            @if ($published)
                <button type="submit" name="action" value="publish" class="btn btn-primary btn-lg">{{ __('Saqlash') }}</button>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-lg">{{ __('Qoralama sifatida saqlash') }}</button>
                <button type="submit" name="action" value="publish" class="btn btn-primary btn-lg">{{ __('E‘lon qilish') }}</button>
            @endif
        </div>
    </div>
</form>
