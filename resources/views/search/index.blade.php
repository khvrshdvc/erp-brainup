<x-app-layout crumb="Bosh sahifa / Qidiruv" title="Qidiruv" subtitle="{{ $term !== '' ? '“'.$term.'” bo‘yicha natijalar' : 'Qidirish uchun so‘z kiriting' }}">
    @if ($term !== '' && $students->isEmpty() && $teachers->isEmpty() && $groups->isEmpty())
        <section class="card"><div class="t-empty">Hech narsa topilmadi</div></section>
    @endif

    @if ($students->isNotEmpty())
        <section class="card" style="margin-bottom: 16px">
            <div class="card-title" style="padding: 16px 16px 0">O‘quvchilar</div>
            @foreach ($students as $student)
                <div class="t-row" style="--cols: 2fr 1.4fr .8fr">
                    <div><a class="link" href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a>
                        <div class="card-sub" style="font-weight: 400">{{ $student->email }}</div></div>
                    <div>{{ $student->phone ?? '—' }}</div>
                    <div class="t-right"><x-edit-button :href="route('admin.students.edit', $student)" /></div>
                </div>
            @endforeach
            <div style="padding: 8px 16px 16px"><a class="link" href="{{ route('admin.students.index', ['q' => $term]) }}">Barchasini ko‘rish</a></div>
        </section>
    @endif

    @if ($teachers->isNotEmpty())
        <section class="card" style="margin-bottom: 16px">
            <div class="card-title" style="padding: 16px 16px 0">O‘qituvchilar</div>
            @foreach ($teachers as $teacher)
                <div class="t-row" style="--cols: 2fr 1.4fr .8fr">
                    <div>{{ $teacher->name }}<div class="card-sub" style="font-weight: 400">{{ $teacher->email }}</div></div>
                    <div>{{ $teacher->phone ?? '—' }}</div>
                    <div class="t-right"><x-edit-button :href="route('admin.teachers.edit', $teacher)" /></div>
                </div>
            @endforeach
            <div style="padding: 8px 16px 16px"><a class="link" href="{{ route('admin.teachers.index', ['q' => $term]) }}">Barchasini ko‘rish</a></div>
        </section>
    @endif

    @if ($groups->isNotEmpty())
        <section class="card">
            <div class="card-title" style="padding: 16px 16px 0">Guruhlar</div>
            @foreach ($groups as $group)
                <div class="t-row" style="--cols: 2fr 1.4fr .8fr">
                    <div>
                        @if ($isAdmin)
                            <a class="link" href="{{ route('admin.groups.show', $group) }}">{{ $group->name }}</a>
                        @else
                            {{ $group->name }}
                        @endif
                        <div class="card-sub" style="font-weight: 400">{{ $group->course?->name }} · {{ $group->teacher?->name }}</div>
                    </div>
                    <div>{{ $group->students_count }} o‘quvchi</div>
                    <div class="t-right"><a class="btn btn-sm" href="{{ route('attendance.create', $group) }}">Davomat</a></div>
                </div>
            @endforeach
        </section>
    @endif
</x-app-layout>
