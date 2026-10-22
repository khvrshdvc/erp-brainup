@php
    $hasFile = $submission && $submission->attachments->isNotEmpty();
    $overdue = $assignment->isOverdue();
@endphp

<x-app-layout crumb="Vazifalar / {{ $assignment->title }} / Topshirish" title="Vazifani topshirish"
    :subtitle="$assignment->title.' · '.$assignment->studyGroup?->name">

    <section class="card card-pad">
        <div class="split" style="gap: 24px">
            <div><div class="label-up">Muddat</div><b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b></div>
            <div><div class="label-up">Maksimal ball</div><b>{{ $assignment->max_score }}</b></div>
            <div>
                <div class="label-up">Holat</div>
                @if ($overdue)
                    <x-badge type="err">Muddat o‘tgan</x-badge>
                @else
                    <x-badge type="ok">{{ \App\Support\UzDate::remaining($assignment->due_at) }} qoldi</x-badge>
                @endif
            </div>
        </div>
    </section>

    @if ($overdue)
        <div class="alert" style="background: var(--warn-bg); color: var(--warn); border-color: #F3D9A6">
            Topshirish muddati o‘tgan. Vazifani hali yuborishingiz mumkin, lekin u “kechikkan” deb belgilanadi.
        </div>
    @endif

    @if ($submission?->isReturned() && $submission->feedback)
        <section class="card card-pad stack" style="gap: 8px">
            <div class="label-up">O‘qituvchi izohi · qayta topshirish so‘raldi</div>
            <div class="quote">{{ $submission->feedback }}</div>
        </section>
    @endif

    <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data"
        class="stack-lg" x-data="oneSubmit" @submit="guard($event)" :aria-busy="submitting">
        @csrf

        <section class="card card-pad stack">
            <h2 class="card-title">1. Faylni biriktiring {{ $hasFile ? '' : '*' }}</h2>

            @if ($hasFile)
                @foreach ($submission->attachments as $attachment)
                    <x-file-chip :attachment="$attachment" />
                @endforeach
                <div class="field-hint">Avval yuborgan faylingiz saqlanadi. Yangi fayl tanlasangiz, u almashtiriladi.</div>
            @endif

            <x-file-picker name="file" id="file" :required="! $hasFile" />
        </section>

        <section class="card card-pad stack">
            <h2 class="card-title">2. Izoh <span class="card-sub" style="font-weight: 400">(ixtiyoriy)</span></h2>

            <div class="field" x-data="{ n: {{ mb_strlen(old('body', $submission->body ?? '')) }} }">
                <label for="body" class="sr-only">Izoh</label>
                <textarea id="body" name="body" maxlength="500"
                    @class(['textarea', 'is-invalid' => $errors->has('body')])
                    placeholder="O‘qituvchiga qisqacha izoh yozishingiz mumkin"
                    @input="n = $event.target.value.length">{{ old('body', $submission->body ?? '') }}</textarea>
                <div class="counter">O‘qituvchiga ko‘rsatiladi · <span x-text="n">0</span> / 500</div>
                <x-field-error name="body" />
            </div>
        </section>

        <div class="form-actions" style="justify-content: space-between; align-items: center">
            <div class="field-hint" style="max-width: 420px">
                O‘qituvchi tekshirguncha vazifani qayta yuborishingiz mumkin.
            </div>
            <div class="page-actions">
                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-lg">Bekor qilish</a>
                <button type="submit" class="btn btn-primary btn-lg"
                    x-text="submitting ? 'Yuborilmoqda…' : @js($submission ? 'Qayta yuborish' : 'Vazifani yuborish')">
                    {{ $submission ? 'Qayta yuborish' : 'Vazifani yuborish' }}
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
