<x-app-layout crumb="Bosh sahifa / O‘quvchilar" title="O‘quvchilar" subtitle="O‘quvchilarni qo‘shish va boshqarish">
    <x-slot name="actions">
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">+ O‘quvchi qo‘shish</a>
    </x-slot>

    <form method="GET" action="{{ route('admin.students.index') }}" class="card filters">
        <div class="field">
            <label for="q">Qidirish</label>
            <input id="q" class="input" type="text" name="q" value="{{ request('q') }}" placeholder="Ism, email yoki telefon">
        </div>

        <div class="field">
            <label for="group">Guruh</label>
            <select id="group" name="group" class="select">
                <option value="">Barchasi</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected((int) request('group') === $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="status">Holat</label>
            <select id="status" name="status" class="select">
                <option value="">Barchasi</option>
                @foreach (\App\Enums\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn">Filtrlash</button>
    </form>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 940px">
                <div class="t-head" style="--cols: 2.6fr 1.4fr 1.6fr .9fr 1.4fr 1fr .9fr">
                    <div>O‘quvchi</div>
                    <div>Telefon</div>
                    <div>Filial</div>
                    <div>Guruhlar</div>
                    <div>Daraja · XP</div>
                    <div>Holat</div>
                    <div></div>
                </div>

                @forelse ($students as $student)
                    <div class="t-row" style="--cols: 2.6fr 1.4fr 1.6fr .9fr 1.4fr 1fr .9fr">
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            <div>
                                <a class="link" href="{{ route('admin.students.show', $student) }}">{{ $student->name }}</a>
                                <div class="card-sub" style="font-weight: 400">{{ $student->email }}</div>
                            </div>
                        </div>
                        <div>{{ $student->phone ?? '—' }}</div>
                        <div>{{ $student->branch?->name }}</div>
                        <div>{{ $student->study_groups_count }}</div>
                        <div>{{ $student->level?->name ?? 'Boshlovchi' }} · {{ $student->xp }}</div>
                        <div><x-badge :type="$student->status->badge()">{{ $student->status->label() }}</x-badge></div>
                        <div class="t-right"><a class="btn btn-sm" href="{{ route('admin.students.edit', $student) }}">Tahrirlash</a></div>
                    </div>
                @empty
                    <div class="t-empty">O‘quvchi topilmadi</div>
                @endforelse
            </div>
        </div>
    </section>

    <x-pager :paginator="$students" />
</x-app-layout>
