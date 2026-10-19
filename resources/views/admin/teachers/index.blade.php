<x-app-layout crumb="Bosh sahifa / O‘qituvchilar" title="O‘qituvchilar" subtitle="O‘qituvchilarni qo‘shish va filiallarga biriktirish">
    <x-slot name="actions">
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">+ O‘qituvchi qo‘shish</a>
    </x-slot>

    <form method="GET" action="{{ route('admin.teachers.index') }}" class="card filters">
        <div class="field">
            <label for="q">Qidirish</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Ism, email yoki telefon">
        </div>
        <button type="submit" class="btn">Qidirish</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2.6fr 1.4fr 2.2fr .9fr 1fr 1.6fr">
                    <div>O‘qituvchi</div>
                    <div>Telefon</div>
                    <div>Filiallar</div>
                    <div>Guruhlar</div>
                    <div>Holat</div>
                    <div></div>
                </div>

                @forelse ($teachers as $teacher)
                    <div class="t-row" style="--cols: 2.6fr 1.4fr 2.2fr .9fr 1fr 1.6fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $teacher->initials }}</span>
                            <div>
                                {{ $teacher->name }}
                                <div class="card-sub" style="font-weight: 400">{{ $teacher->email }}</div>
                            </div>
                        </div>
                        <div>{{ $teacher->phone ?? '—' }}</div>
                        <div>{{ $teacher->branches->pluck('name')->implode(', ') }}</div>
                        <div>{{ $teacher->teaching_groups_count }}</div>
                        <div><x-badge :type="$teacher->status->badge()">{{ $teacher->status->label() }}</x-badge></div>
                        <div class="t-right page-actions" style="justify-content: flex-end">
                            <form method="POST" action="{{ route('admin.teachers.status', $teacher) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm">
                                    {{ $teacher->status === \App\Enums\UserStatus::Active ? 'Faolsizlantirish' : 'Faollashtirish' }}
                                </button>
                            </form>
                            <a class="btn btn-sm" href="{{ route('admin.teachers.edit', $teacher) }}">Tahrirlash</a>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">O‘qituvchi topilmadi</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
