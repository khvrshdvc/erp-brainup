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
        <h2 class="card-title">1. Asosiy ma‘lumotlar</h2>

        <div class="field">
            <label for="title">Vazifa nomi *</label>
            <input id="title" type="text" name="title" maxlength="160" required autofocus
                @class(['input', 'is-invalid' => $errors->has('title')])
                placeholder="Masalan: 5-dars bo‘yicha uy vazifasi"
                value="{{ old('title', $assignment->title ?? '') }}">
            <x-field-error name="title" />
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="study_group_id">Guruh *</label>
                @if ($editing)
                    <input id="study_group_id" class="input" type="text" value="{{ $assignment->studyGroup?->name }}" disabled>
                    <div class="field-hint">Guruhni vazifa yaratilgandan keyin o‘zgartirib bo‘lmaydi</div>
                @else
                    <select id="study_group_id" name="study_group_id" required
                        @class(['select', 'is-invalid' => $errors->has('study_group_id')])>
                        <option value="">Guruhni tanlang</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('study_group_id') === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @if ($groups->isEmpty())
                        <div class="field-hint">Sizga hali guruh biriktirilmagan</div>
                    @endif
                @endif
                <x-field-error name="study_group_id" />
            </div>

            <div class="field" x-data="{
                due: @js(old('due_at', optional($assignment->due_at ?? null)->format('Y-m-d\TH:i'))),
                inDays(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    d.setHours(18, 0, 0, 0);
                    const pad = (n) => String(n).padStart(2, '0');
                    this.due = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T18:00`;
                },
            }">
                <label for="due_at">Topshirish muddati *</label>
                <input id="due_at" type="datetime-local" name="due_at" required x-model="due"
                    value="{{ old('due_at', optional($assignment->due_at ?? null)->format('Y-m-d\TH:i')) }}"
                    @class(['input', 'is-invalid' => $errors->has('due_at')])
                    @unless ($editing) min="{{ now()->format('Y-m-d\TH:i') }}" @endunless>
                <div class="presets">
                    <button type="button" class="pill" @click="inDays(1)">Ertaga</button>
                    <button type="button" class="pill" @click="inDays(3)">3 kun</button>
                    <button type="button" class="pill" @click="inDays(7)">1 hafta</button>
                </div>
                <x-field-error name="due_at" />
            </div>

            <div class="field">
                <label for="max_score">Maksimal ball *</label>
                <input id="max_score" type="number" name="max_score" min="1" max="1000" required inputmode="numeric"
                    @class(['input', 'is-invalid' => $errors->has('max_score')])
                    value="{{ old('max_score', $assignment->max_score ?? 100) }}">
                <div class="field-hint">1 dan 1000 gacha</div>
                <x-field-error name="max_score" />
            </div>
        </div>

        <div class="field" x-data="{ n: {{ mb_strlen(old('description', $assignment->description ?? '')) }} }">
            <label for="description">Vazifa tavsifi *</label>
            <textarea id="description" name="description" maxlength="2000" required
                @class(['textarea', 'is-invalid' => $errors->has('description')])
                placeholder="O‘quvchilar nima qilishi kerakligini batafsil yozing"
                @input="n = $event.target.value.length">{{ old('description', $assignment->description ?? '') }}</textarea>
            <div class="counter"><span x-text="n">0</span> / 2000 belgi</div>
            <x-field-error name="description" />
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">2. Qo‘shimcha materiallar <span class="card-sub" style="font-weight: 400">(ixtiyoriy)</span></h2>

        @if ($hasMaterial)
            @foreach ($assignment->attachments as $attachment)
                <x-file-chip :attachment="$attachment" />
            @endforeach
            <div class="field-hint">Yangi fayl tanlasangiz, joriy fayl almashtiriladi.</div>
        @endif

        <x-file-picker name="material" id="material" />
    </section>

    <div class="form-actions" style="justify-content: space-between; align-items: center">
        <div class="field-hint" style="max-width: 420px">
            Qoralama o‘quvchilarga ko‘rinmaydi. E‘lon qilingan vazifa guruh o‘quvchilariga darhol ko‘rinadi va ularga bildirishnoma yuboriladi.
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
