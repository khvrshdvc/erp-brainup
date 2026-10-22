<x-app-layout :crumb="__('Vazifalar') . ' / ' . $assignment->title" :title="$assignment->title"
    :subtitle="$assignment->studyGroup?->name.' · '.($assignment->teacher?->name ?? '—')">

    <x-slot name="actions">
        <a href="{{ route('admin.assignments.index') }}" class="btn">{{ __('← Vazifalar') }}</a>
    </x-slot>

    @php [$label, $type] = $assignment->teacherState($pending); @endphp

    <section class="card card-pad stack">
        <div class="split" style="gap: 32px">
            <div><div class="label-up">{{ __('Holat') }}</div><x-badge :type="$type">{{ $label }}</x-badge></div>
            <div><div class="label-up">{{ __('Muddat') }}</div><b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b></div>
            <div><div class="label-up">{{ __('Topshirdi') }}</div><b>{{ $submissions->count() }} / {{ $total }}</b></div>
            <div><div class="label-up">{{ __('Maksimal ball') }}</div><b>{{ $assignment->max_score }}</b></div>
        </div>

        <div class="label-up">{{ __('Tavsif') }}</div>
        <div style="white-space: pre-line">{{ $assignment->description }}</div>

        @foreach ($assignment->attachments as $attachment)
            <x-file-chip :attachment="$attachment" />
        @endforeach
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head"><h2 class="card-title">{{ __('Topshiriqlar') }}</h2></div>
        <div class="t-scroll">
            <div class="t-min" style="min-width: 700px">
                <div class="t-head" style="--cols: 2.4fr 1.6fr 1fr 1fr 1.6fr">
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Topshirilgan') }}</div>
                    <div>{{ __('Kechikkan') }}</div>
                    <div>{{ __('Baho') }}</div>
                    <div>{{ __('Holat') }}</div>
                </div>

                @forelse ($submissions as $submission)
                    <div class="t-row" style="--cols: 2.4fr 1.6fr 1fr 1fr 1.6fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $submission->student?->initials }}</span>
                            {{ $submission->student?->name }}
                        </div>
                        <div>{{ $submission->submitted_at->format('d.m.Y, H:i') }}</div>
                        <div>{{ $submission->is_late ? __('Ha') : __('Yo‘q') }}</div>
                        <div>{{ $submission->score !== null ? $submission->score.' / '.$assignment->max_score : '—' }}</div>
                        <div><x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge></div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Hali topshiriq yo‘q') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
