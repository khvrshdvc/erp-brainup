@php
    [$label, $type] = $assignment->studentState($submission);
    $canSubmit = ! $assignment->isClosed() && ! $submission?->isGraded();
@endphp

<x-app-layout crumb="Vazifalar / {{ $assignment->title }}" :title="$assignment->title"
    :subtitle="$assignment->studyGroup?->name.' · '.$assignment->teacher?->name">

    <x-slot name="actions">
        @if ($canSubmit)
            <a href="{{ route('student.assignments.submit', $assignment) }}" class="btn btn-primary">
                {{ $submission ? 'Qayta yuborish' : 'Vazifani topshirish' }}
            </a>
        @endif
    </x-slot>

    <section class="card card-pad stack">
        <div class="split" style="gap: 24px">
            <div><div class="label-up">Holat</div><x-badge :type="$type">{{ $label }}</x-badge></div>
            <div><div class="label-up">Muddat</div><b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b></div>
            <div><div class="label-up">Maksimal ball</div><b>{{ $assignment->max_score }}</b></div>
        </div>

        <div class="label-up">Tavsif</div>
        <div style="white-space: pre-line">{{ $assignment->description }}</div>

        @foreach ($assignment->attachments as $attachment)
            <x-file-chip :attachment="$attachment" />
        @endforeach
    </section>

    @if ($submission)
        <section class="card card-pad stack">
            <div class="card-head" style="padding: 0; border: 0">
                <div>
                    <h2 class="card-title">Mening topshirig‘im</h2>
                    <div class="card-sub">
                        Topshirildi: {{ $submission->submitted_at->format('d.m.Y, H:i') }}{{ $submission->is_late ? ' · muddatdan keyin' : '' }}
                    </div>
                </div>
                @if ($submission->score !== null)
                    <div class="grade">{{ $submission->score }} / {{ $assignment->max_score }}</div>
                @endif
            </div>

            @foreach ($submission->attachments as $attachment)
                <x-file-chip :attachment="$attachment" />
            @endforeach

            @if ($submission->body)
                <div class="stack" style="gap: 8px">
                    <div class="label-up">Izohingiz</div>
                    <div class="quote">{{ $submission->body }}</div>
                </div>
            @endif

            @if ($submission->feedback)
                <div class="stack" style="gap: 8px">
                    <div class="label-up">O‘qituvchi fikri</div>
                    <div class="quote">{{ $submission->feedback }}</div>
                </div>
            @endif
        </section>
    @endif
</x-app-layout>
