<x-app-layout crumb="Bosh sahifa / Filiallar" title="Filiallar" subtitle="O‘quv markazining barcha filiallari va ko‘rsatkichlari">
    <x-slot name="actions">
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">+ Yangi filial</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card label="Jami filiallar" :value="$branches->count()" icon="groups" :note="$branches->where('is_active', true)->count().' ta faol'" />
        <x-stat-card label="Jami o‘quvchilar" :value="$branches->sum('students_count')" icon="students" note="Barcha filiallar bo‘yicha" />
        <x-stat-card label="Jami o‘qituvchilar" :value="$branches->sum('teachers_count')" icon="teachers" note="Filiallar aro ishlaydiganlar bir necha marta sanalishi mumkin" />
        <x-stat-card label="Jami guruhlar" :value="$branches->sum('study_groups_count')" icon="calendar" note="Barcha filiallar bo‘yicha" />
    </div>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2fr 2fr 1.6fr .9fr 1fr 1fr 1fr .9fr">
                    <div>Filial</div>
                    <div>Manzil</div>
                    <div>Menejer</div>
                    <div>Guruhlar</div>
                    <div>O‘quvchilar</div>
                    <div>O‘qituvchilar</div>
                    <div>Holat</div>
                    <div></div>
                </div>

                @forelse ($branches as $branch)
                    <div class="t-row" style="--cols: 2fr 2fr 1.6fr .9fr 1fr 1fr 1fr .9fr">
                        <div style="font-weight: 600">{{ $branch->name }}</div>
                        <div>{{ $branch->address ?? '—' }}</div>
                        <div>{{ $branch->manager_name ?? '—' }}</div>
                        <div>{{ $branch->study_groups_count }}</div>
                        <div>{{ $branch->students_count }}</div>
                        <div>{{ $branch->teachers_count }}</div>
                        <div>
                            <x-badge :type="$branch->is_active ? 'ok' : 'neutral'">{{ $branch->is_active ? 'Faol' : 'Nofaol' }}</x-badge>
                        </div>
                        <div class="t-right">
                            <a class="btn btn-sm" href="{{ route('admin.branches.edit', $branch) }}">Tahrirlash</a>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">Filial yo‘q</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
