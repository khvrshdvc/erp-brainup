@php
    $editing = isset($assignment) && $assignment;
    $published = $editing && ! $assignment->isDraft();
@endphp

<form method="POST" enctype="multipart/form-data" class="stack-lg"
    action="{{ $editing ? route('teacher.assignments.update', $assignment) : route('teacher.assignments.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <section class="card card-pad stack">
        <h2 class="card-title">1. Asosiy ma‘lumotlar</h2>

        <div class="field">
            <label for="title">Vazifa nomi *</label>
            <input id="title" class="input" type="text" name="title" maxlength="160" required
                value="{{ old('title', $assignment->title ?? '') }}">
        </div>

        <div class="split" style="align-items: flex-start">
            <div class="field" style="flex: 1 1 260px">
                <label for="study_group_id">Guruh *</label>
                @if ($editing)
                    <input type="hidden" name="study_group_id" value="{{ $assignment->study_group_id }}">
                    <input class="input" type="text" value="{{ $assignment->studyGroup?->name }}" disabled>
                @else
                    <select id="study_group_id" name="study_group_id" class="select" required>
                        <option value="">Guruhni tanlang</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('study_group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="field" style="flex: 1 1 220px">
                <label for="due_at">Topshirish muddati *</label>
                <input id="due_at" class="input" type="datetime-local" name="due_at" required
                    value="{{ old('due_at', optional($assignment->due_at ?? null)->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="field" style="flex: 0 1 140px">
                <label for="max_score">Maksimal ball *</label>
                <input id="max_score" class="input" type="number" name="max_score" min="1" max="1000" required
                    value="{{ old('max_score', $assignment->max_score ?? 100) }}">
            </div>
        </div>

        <div class="field" x-data="{ n: {{ mb_strlen(old('description', $assignment->description ?? '')) }} }">
            <label for="description">Vazifa tavsifi *</label>
            <textarea id="description" name="description" class="textarea" maxlength="2000" required
                @input="n = $event.target.value.length">{{ old('description', $assignment->description ?? '') }}</textarea>
            <div class="counter"><span x-text="n">0</span> / 2000 belgi</div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">2. Qo‘shimcha materiallar <span class="card-sub" style="font-weight: 400">(ixtiyoriy)</span></h2>

        @if ($editing)
            @foreach ($assignment->attachments as $attachment)
                <x-file-chip :attachment="$attachment" />
            @endforeach
        @endif

        <div class="field">
            <label for="material">{{ $editing && $assignment->attachments->isNotEmpty() ? 'Faylni almashtirish' : 'Biriktiriladigan fayl' }}</label>
            <input id="material" class="input" type="file" name="material" style="padding-top: 7px"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <div class="field-hint">PDF, DOC, DOCX, JPG yoki PNG · 10 MB gacha</div>
        </div>
    </section>

    <div class="form-actions" style="justify-content: space-between; align-items: center">
        <div class="field-hint" style="max-width: 420px">
            Qoralama o‘quvchilarga ko‘rinmaydi. E‘lon qilingan vazifa guruh o‘quvchilariga darhol ko‘rinadi.
        </div>

        <div class="page-actions">
            <a href="{{ $editing ? route('teacher.assignments.show', $assignment) : route('teacher.assignments.index') }}" class="btn btn-lg">Bekor qilish</a>

            @if ($published)
                <button type="submit" name="action" value="publish" class="btn btn-primary btn-lg">Saqlash</button>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-lg">Qoralama sifatida saqlash</button>
                <button type="submit" name="action" value="publish" class="btn btn-primary btn-lg">E‘lon qilish</button>
            @endif
        </div>
    </div>
</form>
