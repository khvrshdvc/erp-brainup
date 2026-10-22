<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Topshirganlarim')" :title="__('Topshirganlarim')"
    :subtitle="__('Yuborgan vazifalaringiz va o‘qituvchi fikr-mulohazalari')">

    <div class="kpi-grid">
        <x-stat-card :label="__('Jami topshirilgan')" :value="$total" icon="tasks" />
        <x-stat-card :label="__('Tekshirilgan')" :value="$gradedCount" icon="level" />
        <x-stat-card :label="__('Tekshirilmoqda')" :value="$pendingCount" icon="calendar" />
        <x-stat-card :label="__('O‘rtacha baho')" :value="$average === null ? '—' : $average.' / 100'" icon="xp" />
    </div>

    <div class="stack">
        @forelse ($submissions as $submission)
            <section class="card card-pad stack">
                <div class="card-head" style="padding: 0; border: 0">
                    <div>
                        <h2 class="card-title">{{ $submission->assignment?->title ?? __('O‘chirilgan vazifa') }}</h2>
                        <div class="card-sub">{{ __('Topshirilgan: :submitted_at', ['submitted_at' => $submission->submitted_at->format('d.m.Y, H:i')]) }}</div>
                    </div>
                    <div class="page-actions">
                        @if ($submission->score !== null && $submission->assignment)
                            <span class="grade">{{ $submission->score }} / {{ $submission->assignment->max_score }}</span>
                        @endif
                        <x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge>
                    </div>
                </div>

                @foreach ($submission->attachments as $attachment)
                    <x-file-chip :attachment="$attachment" />
                @endforeach
                <x-file-expired :submission="$submission" />

                @if ($submission->body)
                    <div class="stack" style="gap: 8px">
                        <div class="label-up">{{ __('Izohingiz') }}</div>
                        <div class="quote">{{ $submission->body }}</div>
                    </div>
                @endif

                @if ($submission->feedback)
                    <div class="stack" style="gap: 8px">
                        <div class="label-up">{{ __('O‘qituvchi fikri') }}</div>
                        <div class="quote">{{ $submission->feedback }}</div>
                    </div>
                @endif

                @if ($submission->assignment)
                    <div>
                        <a class="btn btn-sm" href="{{ route('student.assignments.show', $submission->assignment) }}">{{ __('Batafsil') }}</a>
                    </div>
                @endif
            </section>
        @empty
            <section class="card"><div class="t-empty">{{ __('Hali vazifa topshirmagansiz') }}</div></section>
        @endforelse
    </div>

    <x-pager :paginator="$submissions" />
</x-app-layout>
