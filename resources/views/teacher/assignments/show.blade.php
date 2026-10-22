<x-app-layout :crumb="__('Vazifalar') . ' / ' . __('Topshiriqlarni tekshirish')" :title="$assignment->title"
    :subtitle="__(':group · muddat: :date', ['group' => $assignment->studyGroup?->name, 'date' => $assignment->due_at->format('d.m.Y, H:i')])">

    <x-slot name="actions">
        <x-edit-button :href="route('teacher.assignments.edit', $assignment)" />

        @if ($assignment->isDraft())
            <form method="POST" action="{{ route('teacher.assignments.publish', $assignment) }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('E‘lon qilish') }}</button>
            </form>
        @elseif ($assignment->isClosed())
            <form method="POST" action="{{ route('teacher.assignments.reopen', $assignment) }}">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('Qayta ochish') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('teacher.assignments.close', $assignment) }}"
                onsubmit="return confirm(@js(__('Vazifa yopilsa, o‘quvchilar yangi topshiriq yubora olmaydi. Davom etasizmi?')))">
                @csrf
                <button type="submit" class="btn">{{ __('Vazifani yopish') }}</button>
            </form>
        @endif
    </x-slot>

    <section class="card card-pad stack">
        <div class="label-up">{{ __('Tavsif') }}</div>
        <div style="white-space: pre-line">{{ $assignment->description }}</div>

        @foreach ($assignment->attachments as $attachment)
            <x-file-chip :attachment="$attachment" />
        @endforeach

        <div class="split" style="gap: 24px">
            <div><div class="label-up">{{ __('Topshirdi') }}</div><b>{{ $counts['submitted'] }} / {{ $counts['total'] }}</b></div>
            <div><div class="label-up">{{ __('Tekshirildi') }}</div><b>{{ $counts['graded'] }}</b></div>
            <div><div class="label-up">{{ __('Tekshirilmoqda') }}</div><b>{{ $counts['pending'] }}</b></div>
            <div><div class="label-up">{{ __('Kutilmoqda') }}</div><b>{{ $counts['waiting'] }}</b></div>
            <div><div class="label-up">{{ __('Maksimal ball') }}</div><b>{{ $assignment->max_score }}</b></div>
        </div>
    </section>

    @if ($assignment->isDraft())
        <div class="alert alert-err" style="background: var(--warn-bg); color: var(--warn); border-color: var(--warn-line)">
            {{ __('Bu vazifa qoralama holatida, o‘quvchilar uni ko‘rmaydi.') }}
        </div>
    @endif

    <div class="review-grid">
        <section class="card" style="overflow: hidden">
            <div class="card-head">
                <h2 class="card-title">{{ __('O‘quvchilar') }}</h2>
                <span class="card-sub">{{ __(':count ta', ['count' => $counts['total']]) }}</span>
            </div>

            @forelse ($students as $student)
                @php $submission = $submissions->get($student->id); @endphp

                @if ($submission)
                    <a class="stu-row {{ $selected?->student_id === $student->id ? 'sel' : '' }}"
                        href="{{ route('teacher.assignments.show', [$assignment, 'student' => $student->id]) }}">
                        <span class="avatar avatar-sm">{{ $student->initials }}</span>
                        <span class="nm">
                            {{ $student->name }}
                            <div class="when">{{ $submission->submitted_at->format('d.m.Y, H:i') }}{{ $submission->is_late ? ' · '.__('kechikkan') : '' }}</div>
                        </span>
                        <x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge>
                    </a>
                @else
                    <div class="stu-row">
                        <span class="avatar avatar-sm">{{ $student->initials }}</span>
                        <span class="nm">{{ $student->name }}</span>
                        <x-badge type="neutral">{{ __('Kutilmoqda') }}</x-badge>
                    </div>
                @endif
            @empty
                <div class="t-empty">{{ __('Guruhda o‘quvchi yo‘q') }}</div>
            @endforelse
        </section>

        @if ($selected && $selectedStudent)
            <section class="card card-pad stack-lg">
                <div class="card-head" style="padding: 0; border: 0">
                    <div class="t-name">
                        <span class="avatar">{{ $selectedStudent->initials }}</span>
                        <div>
                            {{ $selectedStudent->name }}
                            <div class="card-sub" style="font-weight: 400">
                                {{ __('Topshirildi: :submitted_at', ['submitted_at' => $selected->submitted_at->format('d.m.Y, H:i')]) }}{{ $selected->is_late ? ' · '.__('muddatdan keyin') : ' · '.__('muddatida') }}
                            </div>
                        </div>
                    </div>
                    <x-badge :type="$selected->status->badge()">{{ $selected->status->label() }}</x-badge>
                </div>

                <div class="stack">
                    <div class="label-up">{{ __('Biriktirilgan fayl') }}</div>
                    @forelse ($selected->attachments as $attachment)
                        <x-file-chip :attachment="$attachment" />
                    @empty
                        @if ($selected->filesExpired())
                            <x-file-expired :submission="$selected" />
                        @else
                            <div class="card-sub">{{ __('Fayl yo‘q') }}</div>
                        @endif
                    @endforelse
                </div>

                @if ($selected->body)
                    <div class="stack" style="gap: 8px">
                        <div class="label-up">{{ __('O‘quvchi izohi') }}</div>
                        <div class="quote">{{ $selected->body }}</div>
                    </div>
                @endif

                @php
                    $editing = $selected->isPending() || request()->boolean('edit');
                @endphp

                @if (! $editing)
                    <div class="stack">
                        <div class="split" style="gap: 32px">
                            @if ($selected->isGraded())
                                <div>
                                    <div class="label-up">{{ __('Baho') }}</div>
                                    <div class="grade">
                                        {{ $selected->score !== null ? $selected->score.' / '.$assignment->max_score : __('Ball qo‘yilmagan') }}
                                    </div>
                                </div>
                            @else
                                <div>
                                    <div class="label-up">{{ __('Holat') }}</div>
                                    <b>{{ __('O‘quvchi qayta topshirishi kutilmoqda') }}</b>
                                </div>
                            @endif

                            <div>
                                <div class="label-up">{{ __('Tekshirilgan') }}</div>
                                <b>{{ $selected->graded_at?->format('d.m.Y, H:i') ?? '—' }}</b>
                            </div>

                            <div>
                                <div class="label-up">{{ __('Tekshirdi') }}</div>
                                <b>{{ $selected->grader?->name ?? '—' }}</b>
                            </div>
                        </div>

                        @if ($selected->feedback)
                            <div class="stack" style="gap: 8px">
                                <div class="label-up">{{ __('Fikr-mulohaza') }}</div>
                                <div class="quote">{{ $selected->feedback }}</div>
                            </div>
                        @endif

                        <div>
                            <a href="{{ route('teacher.assignments.show', [$assignment, 'student' => $selected->student_id, 'edit' => 1]) }}" class="btn">
                                {{ $selected->isGraded() ? __('Bahoni o‘zgartirish') : __('Baholash') }}
                            </a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('teacher.submissions.update', $selected) }}" class="stack"
                        x-data="{ ...oneSubmit(), feedback: @js(old('feedback', $selected->feedback ?? '')), needFeedback: false }"
                        @submit="if ($event.submitter?.value === 'return' && ! feedback.trim()) { $event.preventDefault(); needFeedback = true; $refs.feedback.focus(); return; } guard($event)"
                        :aria-busy="submitting">
                        @csrf
                        @method('PUT')

                        <div class="field" style="max-width: 200px">
                            <label for="score">{{ __('Baho') }} <span class="card-sub" style="font-weight: 400">{{ __('(ixtiyoriy)') }}</span></label>
                            <div style="display: flex; align-items: center; gap: 8px">
                                <input id="score" type="number" name="score" min="0" max="{{ $assignment->max_score }}" inputmode="numeric"
                                    @class(['input', 'is-invalid' => $errors->has('score')])
                                    value="{{ old('score', $selected->score) }}">
                                <span class="card-sub">/ {{ $assignment->max_score }}</span>
                            </div>
                            <x-field-error name="score" />
                        </div>

                        <div class="field">
                            <label for="feedback">{{ __('Fikr-mulohaza') }} <span class="card-sub" style="font-weight: 400">{{ __('(qaytarishda majburiy)') }}</span></label>
                            <textarea id="feedback" name="feedback" maxlength="2000" x-ref="feedback" x-model="feedback"
                                @class(['textarea', 'is-invalid' => $errors->has('feedback')])
                                :class="{ 'is-invalid': needFeedback && ! feedback.trim() }"
                                placeholder="{{ __('Nimasi yaxshi chiqqan, nimani tuzatish kerak') }}">{{ old('feedback', $selected->feedback) }}</textarea>
                            <div class="field-error" x-show="needFeedback && ! feedback.trim()" x-cloak role="alert">
                                {{ __('Qayta topshirishga qaytarishda fikr-mulohaza yozish majburiy.') }}
                            </div>
                            <x-field-error name="feedback" />
                        </div>

                        <div class="form-actions">
                                @if (! $selected->isPending())
                                    <a href="{{ route('teacher.assignments.show', [$assignment, 'student' => $selected->student_id]) }}" class="btn">{{ __('Bekor qilish') }}</a>
                                @endif
                            <button type="submit" name="action" value="return" class="btn">{{ __('Qayta topshirishga qaytarish') }}</button>
                            <button type="submit" name="action" value="grade" class="btn btn-primary">{{ __('Tekshirildi deb saqlash') }}</button>
                        </div>
                    </form>
                @endif
            </section>
        @else
            <section class="card">
                <div class="t-empty">{{ __('Hali topshiriq yo‘q. O‘quvchi vazifani yuborgach, shu yerda ko‘rinadi.') }}</div>
            </section>
        @endif
    </div>
</x-app-layout>
