@php
    $isTeacher = auth()->user()->isTeacher();
@endphp
<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Topshirganlar')" :title="__('Topshirganlar')" :subtitle="__('O‘quvchilar topshirgan vazifalar')">
    <div class="kpi-grid">
        <x-stat-card :label="__('Jami topshirilgan')" :value="$counts['total']" icon="tasks" />
        <x-stat-card :label="__('Tekshirilmoqda')" :value="$counts['pending']" icon="calendar" />
        <x-stat-card :label="__('Tekshirilgan')" :value="$counts['graded']" icon="level" />
        <x-stat-card :label="__('Qayta topshirish')" :value="$counts['returned']" icon="streak" />
    </div>

    <form method="GET" action="{{ route('submissions.index') }}" class="card filters">
        <div class="field">
            <label for="q">{{ __('Qidirish') }}</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('O‘quvchi ismi') }}">
        </div>

        <div class="field">
            <label for="group">{{ __('Guruh') }}</label>
            <select id="group" name="group" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">{{ __('Holat') }}</label>
            <select id="status" name="status" class="select">
                <option value="">{{ __('Barchasi') }}</option>
                @foreach (\App\Enums\SubmissionStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">{{ __('Filtrlash') }}</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2.2fr 2.2fr 1fr 1.4fr .9fr 1.6fr .8fr">
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Vazifa') }}</div>
                    <div>{{ __('Guruh') }}</div>
                    <div>{{ __('Topshirilgan') }}</div>
                    <div>{{ __('Baho') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($submissions as $submission)
                    @php
                        $assignment = $submission->assignment;
                        $href = $assignment
                            ? ($isTeacher
                                ? route('teacher.assignments.show', [$assignment, 'student' => $submission->student_id])
                                : route('admin.assignments.show', $assignment))
                            : null;
                    @endphp
                    <div class="t-row" style="--cols: 2.2fr 2.2fr 1fr 1.4fr .9fr 1.6fr .8fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $submission->student?->initials }}</span>
                            <div>{{ $submission->student?->name ?? '—' }}</div>
                        </div>
                        <div>{{ $assignment?->title ?? __('O‘chirilgan vazifa') }}</div>
                        <div>{{ $assignment?->studyGroup?->name ?? '—' }}</div>
                        <div>
                            {{ $submission->submitted_at?->format('d.m.Y, H:i') }}
                            @if ($submission->is_late)
                                <div class="card-sub">{{ __('kechikkan') }}</div>
                            @endif
                        </div>
                        <div>{{ $submission->score !== null && $assignment ? $submission->score.' / '.$assignment->max_score : '—' }}</div>
                        <div><x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge></div>
                        <div class="t-right">
                            @if ($href)
                                <a class="btn btn-sm" href="{{ $href }}">{{ __('Ko‘rish') }}</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Topshirilgan vazifa topilmadi') }}</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$submissions" />
</x-app-layout>
