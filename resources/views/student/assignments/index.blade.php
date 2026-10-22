<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Vazifalar')" :title="__('Vazifalar')" :subtitle="__('Guruhingiz uchun berilgan barcha vazifalar')">
    @php
        $tabs = ['all' => __('Barchasi'), 'todo' => __('Topshirilmagan'), 'review' => __('Tekshirilmoqda'), 'done' => __('Tekshirildi')];
    @endphp

    <div class="tabs">
        @foreach ($tabs as $key => $label)
            <a class="tab {{ $tab === $key ? 'on' : '' }}" href="{{ route('student.assignments.index', ['tab' => $key]) }}">
                {{ $label }} · {{ $counts[$key] }}
            </a>
        @endforeach
    </div>

    <div class="stack">
        @forelse ($assignments as $assignment)
            @php
                $submission = $submissions->get($assignment->id);
                [$label, $type] = $assignment->studentState($submission);
            @endphp

            <section class="card acard">
                <div style="flex: 1 1 360px; min-width: 0">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px">
                        <h3>{{ $assignment->title }}</h3>
                        <x-badge :type="$type">{{ $label }}</x-badge>
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit($assignment->description, 140) }}</p>
                    <div class="card-sub">
                        {{ __(':name · berildi', ['name' => $assignment->teacher?->name]) }} {{ $assignment->published_at?->format('d.m.Y') ?? $assignment->created_at->format('d.m.Y') }}
                    </div>
                </div>

                <div class="due">
                    <small>{{ __('MUDDAT') }}</small>
                    <b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b>
                </div>

                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-primary">{{ __('Vazifani ochish') }}</a>
            </section>
        @empty
            <section class="card"><div class="t-empty">{{ __('Bu bo‘limda vazifa yo‘q') }}</div></section>
        @endforelse
    </div>
</x-app-layout>
