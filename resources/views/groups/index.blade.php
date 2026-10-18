<x-app-layout crumb="Bosh sahifa" title="Guruhlar" subtitle="Davomat olish uchun guruhni tanlang">
    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min">
                <div class="t-head" style="--cols: 2.2fr 1.6fr 1.8fr 1fr 1.2fr 1fr">
                    <div>Guruh</div>
                    <div>Filial</div>
                    <div>O‘qituvchi</div>
                    <div>O‘quvchilar</div>
                    <div>Holat</div>
                    <div></div>
                </div>
                @forelse ($groups as $group)
                    <div class="t-row" style="--cols: 2.2fr 1.6fr 1.8fr 1fr 1.2fr 1fr">
                        <div style="font-weight: 600">{{ $group->name }}</div>
                        <div>{{ $group->branch?->name }}</div>
                        <div>
                            @if ($group->teacher)
                                <div class="t-name" style="font-weight: 500">
                                    <span class="avatar avatar-sm">{{ $group->teacher->initials }}</span>
                                    {{ $group->teacher->name }}
                                </div>
                            @else
                                —
                            @endif
                        </div>
                        <div>{{ $group->students_count }}</div>
                        <div><x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge></div>
                        <div class="t-right">
                            <a href="{{ route('attendance.create', $group) }}" class="btn btn-sm">Davomat</a>
                        </div>
                    </div>
                @empty
                    <div class="t-empty">Guruh yo‘q</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
