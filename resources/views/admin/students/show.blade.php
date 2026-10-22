<x-app-layout :crumb="__('O‘quvchilar') . ' / ' . $student->name" :title="$student->name"
    :subtitle="$student->email.' · '.$student->branch?->name">

    <x-slot name="actions">
        <x-status-switch :action="route('admin.students.status', $student)"
            :active="$student->isActive()"
            :label="$student->status->label()" />
        <x-edit-button :href="route('admin.students.edit', $student)" />
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card :label="__('Hamyon')" :value="number_format($student->coinBalance(), 0, '', ' ')" icon="coin" :note="__('Jami topilgan: :xp', ['xp' => number_format((int) $student->xp, 0, '', ' ')])" />
        <x-stat-card :label="__('Daraja')" :value="__($student->level?->name ?? 'Uchqun')" icon="level" :note="__('Joriy daraja')" />
        <x-stat-card :label="__('Davomat seriyasi')" :value="$student->streak" icon="streak" :note="__('Ketma-ket darslar')" />
        <x-stat-card :label="__('Guruhlar')" :value="$student->studyGroups->count()" icon="groups" :note="$student->status->label()" />
    </div>

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('Davomat') }}</h2>
        <div class="page-actions" style="gap: 16px">
            @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                <div class="sum-row" style="gap: 8px">
                    <x-badge :type="$status->badge()">{{ $status->label() }}</x-badge>
                    <span>{{ $attendance[$status->value] ?? 0 }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head"><h2 class="card-title">{{ __('Guruhlar') }}</h2></div>
        <div class="t-scroll">
            <div class="t-min" style="min-width: 520px">
                <div class="t-head" style="--cols: 2fr 2fr">
                    <div>{{ __('Guruh') }}</div>
                    <div>{{ __('O‘qituvchi') }}</div>
                </div>
                @forelse ($student->studyGroups as $group)
                    <div class="t-row" style="--cols: 2fr 2fr">
                        <div><a class="link" style="font-weight: 600" href="{{ route('admin.groups.show', $group) }}">{{ $group->name }}</a></div>
                        <div>{{ $group->teacher?->name ?? '—' }}</div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Guruhga biriktirilmagan') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head"><h2 class="card-title">{{ __('So‘nggi topshiriqlar') }}</h2></div>
        <div class="t-scroll">
            <div class="t-min" style="min-width: 560px">
                <div class="t-head" style="--cols: 2.4fr 1.4fr 1fr 1.4fr">
                    <div>{{ __('Vazifa') }}</div>
                    <div>{{ __('Topshirilgan') }}</div>
                    <div>{{ __('Baho') }}</div>
                    <div>{{ __('Holat') }}</div>
                </div>
                @forelse ($submissions as $submission)
                    <div class="t-row" style="--cols: 2.4fr 1.4fr 1fr 1.4fr">
                        <div style="font-weight: 600">{{ $submission->assignment?->title ?? '—' }}</div>
                        <div>{{ $submission->submitted_at->format('d.m.Y') }}</div>
                        <div>{{ $submission->score !== null ? $submission->score.' / '.($submission->assignment?->max_score) : '—' }}</div>
                        <div><x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge></div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Topshiriq yo‘q') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
