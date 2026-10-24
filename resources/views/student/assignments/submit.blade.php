@php
    $hasFile = $submission && $submission->attachments->isNotEmpty();
    $overdue = $assignment->isOverdue();
@endphp

<x-app-layout :crumb="__('Vazifalar') . ' / ' . $assignment->title . ' / ' . __('Topshirish')" :title="__('Vazifani topshirish')"
    :subtitle="$assignment->title.' · '.$assignment->studyGroup?->name">

    <section class="card card-pad">
        <div class="split" style="gap: 24px">
            <div><div class="label-up">{{ __('Muddat') }}</div><b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b></div>
            <div><div class="label-up">{{ __('Maksimal ball') }}</div><b>{{ $assignment->max_score }}</b></div>
            <div>
                <div class="label-up">{{ __('Holat') }}</div>
                @if ($overdue)
                    <x-badge type="err">{{ __('Muddat o‘tgan') }}</x-badge>
                @else
                    <x-badge type="ok">{{ __(':due_at qoldi', ['due_at' => \App\Support\UzDate::remaining($assignment->due_at)]) }}</x-badge>
                @endif
            </div>
        </div>
    </section>

    @if ($overdue)
        <div class="alert" style="background: var(--warn-bg); color: var(--warn); border-color: var(--warn-line)">
            {{ __('Topshirish muddati o‘tgan. Vazifani hali yuborishingiz mumkin, lekin u “kechikkan” deb belgilanadi.') }}
        </div>
    @endif

    @if ($submission?->isReturned() && $submission->feedback)
        <section class="card card-pad stack" style="gap: 8px">
            <div class="label-up">{{ __('O‘qituvchi izohi · qayta topshirish so‘raldi') }}</div>
            <div class="quote">{{ $submission->feedback }}</div>
        </section>
    @endif

    <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data"
        class="stack-lg" x-data="oneSubmit" @submit="guard($event)" :aria-busy="submitting">
        @csrf

        <section class="card card-pad stack">
            <h2 class="card-title">{{ __('1. Fayllarni biriktiring') }} {{ $hasFile ? '' : '*' }}</h2>

            @if ($hasFile)
                <div class="field-hint">{{ __('Avval yuborgan fayllaringiz saqlanadi. Ularni olib tashlashingiz yoki yangilarini qo‘shishingiz mumkin.') }}</div>
            @endif

            <x-files-picker name="files" id="files" :attachments="$submission?->attachments ?? collect()" />
        </section>

        <section class="card card-pad stack">
            <h2 class="card-title">{{ __('2. Izoh') }} <span class="card-sub" style="font-weight: 400">{{ __('(ixtiyoriy)') }}</span></h2>

            <div class="field" x-data="{ n: {{ mb_strlen(old('body', $submission->body ?? '')) }} }">
                <label for="body" class="sr-only">{{ __('Izoh') }}</label>
                <textarea id="body" name="body" maxlength="500"
                    @class(['textarea', 'is-invalid' => $errors->has('body')])
                    placeholder="{{ __('O‘qituvchiga qisqacha izoh yozishingiz mumkin') }}"
                    @input="n = $event.target.value.length">{{ old('body', $submission->body ?? '') }}</textarea>
                <div class="counter">{{ __('O‘qituvchiga ko‘rsatiladi') }} · <span x-text="n">0</span> / 500</div>
                <x-field-error name="body" />
            </div>
        </section>

        <div class="form-actions" style="justify-content: space-between; align-items: center">
            <div class="field-hint" style="max-width: 420px">
                {{ __('O‘qituvchi tekshirguncha vazifani qayta yuborishingiz mumkin.') }}
            </div>
            <div class="page-actions">
                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-lg">{{ __('Bekor qilish') }}</a>
                <button type="submit" class="btn btn-primary btn-lg"
                    x-text="submitting ? @js(__('Yuborilmoqda…')) : @js($submission ? __('Qayta yuborish') : __('Vazifani yuborish'))">
                    {{ $submission ? __('Qayta yuborish') : __('Vazifani yuborish') }}
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
