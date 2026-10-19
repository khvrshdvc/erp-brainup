<x-app-layout crumb="Bosh sahifa / Vazifalar" title="Vazifalar" subtitle="Guruhingiz uchun berilgan barcha vazifalar">
    @php
        $tabs = ['all' => 'Barchasi', 'todo' => 'Topshirilmagan', 'review' => 'Tekshirilmoqda', 'done' => 'Tekshirildi'];
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
                        {{ $assignment->teacher?->name }} · berildi {{ $assignment->published_at?->format('d.m.Y') ?? $assignment->created_at->format('d.m.Y') }}
                    </div>
                </div>

                <div class="due">
                    <small>MUDDAT</small>
                    <b>{{ $assignment->due_at->format('d.m.Y, H:i') }}</b>
                </div>

                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-primary">Vazifani ochish</a>
            </section>
        @empty
            <section class="card"><div class="t-empty">Bu bo‘limda vazifa yo‘q</div></section>
        @endforelse
    </div>
</x-app-layout>
