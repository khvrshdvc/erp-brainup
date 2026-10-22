<x-app-layout crumb="Vazifalar / Topshiriqlarni tekshirish" :title="$assignment->title"
    :subtitle="$assignment->studyGroup?->name.' · muddat: '.$assignment->due_at->format('d.m.Y, H:i')">

    <x-slot name="actions">
        <x-edit-button :href="route('teacher.assignments.edit', $assignment)" />

        @if ($assignment->isDraft())
            <form method="POST" action="{{ route('teacher.assignments.publish', $assignment) }}">
                @csrf
                <button type="submit" class="btn btn-primary">E‘lon qilish</button>
            </form>
        @elseif ($assignment->isClosed())
            <form method="POST" action="{{ route('teacher.assignments.reopen', $assignment) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Qayta ochish</button>
            </form>
        @else
            <form method="POST" action="{{ route('teacher.assignments.close', $assignment) }}"
                onsubmit="return confirm('Vazifa yopilsa, o‘quvchilar yangi topshiriq yubora olmaydi. Davom etasizmi?')">
                @csrf
                <button type="submit" class="btn">Vazifani yopish</button>
            </form>
        @endif
    </x-slot>

    <section class="card card-pad stack">
        <div class="label-up">Tavsif</div>
        <div style="white-space: pre-line">{{ $assignment->description }}</div>

        @foreach ($assignment->attachments as $attachment)
            <x-file-chip :attachment="$attachment" />
        @endforeach

        <div class="split" style="gap: 24px">
            <div><div class="label-up">Topshirdi</div><b>{{ $counts['submitted'] }} / {{ $counts['total'] }}</b></div>
            <div><div class="label-up">Tekshirildi</div><b>{{ $counts['graded'] }}</b></div>
            <div><div class="label-up">Tekshirilmoqda</div><b>{{ $counts['pending'] }}</b></div>
            <div><div class="label-up">Kutilmoqda</div><b>{{ $counts['waiting'] }}</b></div>
            <div><div class="label-up">Maksimal ball</div><b>{{ $assignment->max_score }}</b></div>
        </div>
    </section>

    @if ($assignment->isDraft())
        <div class="alert alert-err" style="background: var(--warn-bg); color: var(--warn); border-color: #F0D9AE">
            Bu vazifa qoralama holatida, o‘quvchilar uni ko‘rmaydi.
        </div>
    @endif

    <div class="review-grid">
        <section class="card" style="overflow: hidden">
            <div class="card-head">
                <h2 class="card-title">O‘quvchilar</h2>
                <span class="card-sub">{{ $counts['total'] }} ta</span>
            </div>

            @forelse ($students as $student)
                @php $submission = $submissions->get($student->id); @endphp

                @if ($submission)
                    <a class="stu-row {{ $selected?->student_id === $student->id ? 'sel' : '' }}"
                        href="{{ route('teacher.assignments.show', [$assignment, 'student' => $student->id]) }}">
                        <span class="avatar avatar-sm">{{ $student->initials }}</span>
                        <span class="nm">
                            {{ $student->name }}
                            <div class="when">{{ $submission->submitted_at->format('d.m.Y, H:i') }}{{ $submission->is_late ? ' · kechikkan' : '' }}</div>
                        </span>
                        <x-badge :type="$submission->status->badge()">{{ $submission->status->label() }}</x-badge>
                    </a>
                @else
                    <div class="stu-row">
                        <span class="avatar avatar-sm">{{ $student->initials }}</span>
                        <span class="nm">{{ $student->name }}</span>
                        <x-badge type="neutral">Kutilmoqda</x-badge>
                    </div>
                @endif
            @empty
                <div class="t-empty">Guruhda o‘quvchi yo‘q</div>
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
                                Topshirildi: {{ $selected->submitted_at->format('d.m.Y, H:i') }}{{ $selected->is_late ? ' · muddatdan keyin' : ' · muddatida' }}
                            </div>
                        </div>
                    </div>
                    <x-badge :type="$selected->status->badge()">{{ $selected->status->label() }}</x-badge>
                </div>

                <div class="stack">
                    <div class="label-up">Biriktirilgan fayl</div>
                    @forelse ($selected->attachments as $attachment)
                        <x-file-chip :attachment="$attachment" />
                    @empty
                        <div class="card-sub">Fayl yo‘q</div>
                    @endforelse
                </div>

                @if ($selected->body)
                    <div class="stack" style="gap: 8px">
                        <div class="label-up">O‘quvchi izohi</div>
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
                                    <div class="label-up">Baho</div>
                                    <div class="grade">
                                        {{ $selected->score !== null ? $selected->score.' / '.$assignment->max_score : 'Ball qo‘yilmagan' }}
                                    </div>
                                </div>
                            @else
                                <div>
                                    <div class="label-up">Holat</div>
                                    <b>O‘quvchi qayta topshirishi kutilmoqda</b>
                                </div>
                            @endif

                            <div>
                                <div class="label-up">Tekshirilgan</div>
                                <b>{{ $selected->graded_at?->format('d.m.Y, H:i') ?? '—' }}</b>
                            </div>

                            <div>
                                <div class="label-up">Tekshirdi</div>
                                <b>{{ $selected->grader?->name ?? '—' }}</b>
                            </div>
                        </div>

                        @if ($selected->feedback)
                            <div class="stack" style="gap: 8px">
                                <div class="label-up">Fikr-mulohaza</div>
                                <div class="quote">{{ $selected->feedback }}</div>
                            </div>
                        @endif

                        <div>
                            <a href="{{ route('teacher.assignments.show', [$assignment, 'student' => $selected->student_id, 'edit' => 1]) }}" class="btn">
                                {{ $selected->isGraded() ? 'Bahoni o‘zgartirish' : 'Baholash' }}
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
                            <label for="score">Baho <span class="card-sub" style="font-weight: 400">(ixtiyoriy)</span></label>
                            <div style="display: flex; align-items: center; gap: 8px">
                                <input id="score" type="number" name="score" min="0" max="{{ $assignment->max_score }}" inputmode="numeric"
                                    @class(['input', 'is-invalid' => $errors->has('score')])
                                    value="{{ old('score', $selected->score) }}">
                                <span class="card-sub">/ {{ $assignment->max_score }}</span>
                            </div>
                            <x-field-error name="score" />
                        </div>

                        <div class="field">
                            <label for="feedback">Fikr-mulohaza <span class="card-sub" style="font-weight: 400">(qaytarishda majburiy)</span></label>
                            <textarea id="feedback" name="feedback" maxlength="2000" x-ref="feedback" x-model="feedback"
                                @class(['textarea', 'is-invalid' => $errors->has('feedback')])
                                :class="{ 'is-invalid': needFeedback && ! feedback.trim() }"
                                placeholder="Nimasi yaxshi chiqqan, nimani tuzatish kerak">{{ old('feedback', $selected->feedback) }}</textarea>
                            <div class="field-error" x-show="needFeedback && ! feedback.trim()" x-cloak role="alert">
                                Qayta topshirishga qaytarishda fikr-mulohaza yozish majburiy.
                            </div>
                            <x-field-error name="feedback" />
                        </div>

                        <div class="form-actions">
                                @if (! $selected->isPending())
                                    <a href="{{ route('teacher.assignments.show', [$assignment, 'student' => $selected->student_id]) }}" class="btn">Bekor qilish</a>
                                @endif
                            <button type="submit" name="action" value="return" class="btn">Qayta topshirishga qaytarish</button>
                            <button type="submit" name="action" value="grade" class="btn btn-primary">Tekshirildi deb saqlash</button>
                        </div>
                    </form>
                @endif
            </section>
        @else
            <section class="card">
                <div class="t-empty">Hali topshiriq yo‘q. O‘quvchi vazifani yuborgach, shu yerda ko‘rinadi.</div>
            </section>
        @endif
    </div>
</x-app-layout>
