<x-app-layout crumb="Vazifalar / {{ $assignment->title }} / Topshirish" title="Vazifani topshirish"
    :subtitle="$assignment->title.' · '.$assignment->studyGroup?->name.' · muddat: '.$assignment->due_at->format('d.m.Y, H:i')">

    <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data" class="stack-lg">
        @csrf

        <section class="card card-pad stack">
            <h2 class="card-title">1. Faylni biriktiring *</h2>

            @foreach ($submission?->attachments ?? [] as $attachment)
                <x-file-chip :attachment="$attachment" />
            @endforeach

            <div class="dropzone">
                <div>{{ $submission && $submission->attachments->isNotEmpty() ? 'Yangi fayl tanlasangiz, eskisi almashtiriladi' : 'Qurilmadan fayl tanlang' }}</div>
                <input type="file" name="file" class="input" style="max-width: 360px; padding-top: 7px"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" {{ $submission && $submission->attachments->isNotEmpty() ? '' : 'required' }}>
                <div class="field-hint">PDF, DOC, DOCX, JPG, PNG · Maksimal hajm: 10 MB</div>
            </div>
        </section>

        <section class="card card-pad stack">
            <h2 class="card-title">2. Izoh yoki tavsif <span class="card-sub" style="font-weight: 400">(ixtiyoriy)</span></h2>

            <div class="field" x-data="{ n: {{ mb_strlen(old('body', $submission->body ?? '')) }} }">
                <textarea name="body" class="textarea" maxlength="500" @input="n = $event.target.value.length">{{ old('body', $submission->body ?? '') }}</textarea>
                <div class="counter">O‘qituvchiga ko‘rsatiladi · <span x-text="n">0</span> / 500</div>
            </div>
        </section>

        <div class="form-actions" style="justify-content: space-between; align-items: center">
            <div class="field-hint" style="max-width: 420px">
                Yuborilgandan keyin o‘qituvchi tekshirguncha vazifani qayta yuborish mumkin.
            </div>
            <div class="page-actions">
                <a href="{{ route('student.assignments.show', $assignment) }}" class="btn btn-lg">Bekor qilish</a>
                <button type="submit" class="btn btn-primary btn-lg">Vazifani yuborish</button>
            </div>
        </div>
    </form>
</x-app-layout>
