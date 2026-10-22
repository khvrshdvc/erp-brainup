<x-app-layout :crumb="__('Bosh sahifa') . ' / ' . __('Filiallar')" :title="__('Filiallar')" :subtitle="__('O‘quv markazining barcha filiallari va ko‘rsatkichlari')">
    <x-slot name="actions">
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">{{ __('+ Yangi filial') }}</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card :label="__('Jami filiallar')" :value="$branches->count()" icon="groups" :note="__(':count ta faol', ['count' => $branches->where('is_active', true)->count()])" />
        <x-stat-card :label="__('Jami o‘quvchilar')" :value="$branches->sum('students_count')" icon="students" :note="__('Barcha filiallar bo‘yicha')" />
        <x-stat-card :label="__('Jami o‘qituvchilar')" :value="$branches->sum('teachers_count')" icon="teachers" :note="__('Filiallar aro ishlaydiganlar bir necha marta sanalishi mumkin')" />
        <x-stat-card :label="__('Jami guruhlar')" :value="$branches->sum('study_groups_count')" icon="calendar" :note="__('Barcha filiallar bo‘yicha')" />
    </div>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 900px">
                <div class="t-head" style="--cols: 2fr 2fr 1.6fr .9fr 1fr 1fr 1.4fr .5fr">
                    <div>{{ __('Filial') }}</div>
                    <div>{{ __('Manzil') }}</div>
                    <div>{{ __('Menejer') }}</div>
                    <div>{{ __('Guruhlar') }}</div>
                    <div>{{ __('O‘quvchilar') }}</div>
                    <div>{{ __('O‘qituvchilar') }}</div>
                    <div>{{ __('Holat') }}</div>
                    <div></div>
                </div>

                @forelse ($branches as $branch)
                    <div class="t-row" style="--cols: 2fr 2fr 1.6fr .9fr 1fr 1fr 1.4fr .5fr">
                        <div style="font-weight: 600">{{ $branch->name }}</div>
                        <div>{{ $branch->address ?? '—' }}</div>
                        <div>{{ $branch->manager_name ?? '—' }}</div>
                        <div>{{ $branch->study_groups_count }}</div>
                        <div>{{ $branch->students_count }}</div>
                        <div>{{ $branch->teachers_count }}</div>
                        <div>
                            <x-status-switch :action="route('admin.branches.status', $branch)"
                                :active="$branch->is_active"
                                :label="$branch->is_active ? __('Faol') : __('Nofaol')" />
                        </div>
                        <div class="t-right">
                            <x-edit-button :href="route('admin.branches.edit', $branch)" />
                        </div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Filial yo‘q') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
