<x-app-layout
    crumb="Bosh sahifa"
    :title="'Xush kelibsiz, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ').'!'"
    :subtitle="\App\Support\UzDate::long(now()).' · '.($branch?->name ?? 'Barcha filiallar')">

    <x-slot name="actions">
        <a href="{{ route('groups.index') }}" class="btn btn-primary">Guruhlar va davomat</a>
    </x-slot>

    <div class="kpi-grid">
        <x-stat-card label="Jami o‘quvchilar" :value="$students" icon="students" :note="$branch ? $branch->name : 'Barcha filiallar'" />
        <x-stat-card label="Jami o‘qituvchilar" :value="$teachers" icon="teachers" note="Faol xodimlar" />
        <x-stat-card label="Faol guruhlar" :value="$activeGroups" icon="groups" :note="'Jami '.$allGroups.' ta guruh'" />
        <x-stat-card label="Bugungi davomat" :value="$todayMarked" icon="calendar" note="Belgilangan yozuvlar" />
    </div>

    <section class="card card-pad stack-lg">
        <div class="card-head" style="padding: 0; border: 0">
            <div>
                <h2 class="card-title">Haftalik davomat</h2>
                <div class="card-sub">{{ $weekRange }} · tanlangan filial bo‘yicha o‘rtacha</div>
            </div>
            <a href="{{ route('groups.index') }}" class="link" style="font-size: 13px">Batafsil →</a>
        </div>

        @if ($weekTotal === 0)
            <div class="t-empty">Bu hafta hali davomat belgilanmagan</div>
        @else
            <div class="split">
                <div style="flex: 1 1 280px">
                    <div class="chart">
                        @foreach ($bars as $bar)
                            <div class="chart-col">
                                <span class="chart-pct">{{ $bar['has'] ? $bar['pct'].'%' : '—' }}</span>
                                <div class="chart-bar {{ $bar['has'] ? '' : 'empty' }}" style="height: {{ $bar['has'] ? $bar['height'] : 4 }}px"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="chart-days" style="margin-top: 8px">
                        @foreach ($bars as $bar)
                            <span>{{ $bar['label'] }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="sum-list">
                    @foreach ($summary as $row)
                        <div class="sum-row">
                            <x-badge :type="$row['badge']">{{ $row['label'] }}</x-badge>
                            <span>{{ $row['pct'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="card" style="overflow: hidden">
        <div class="card-head">
            <h2 class="card-title">Guruhlar</h2>
            <a href="{{ route('groups.index') }}" class="link" style="font-size: 13px">Barchasini ko‘rish →</a>
        </div>
        <div class="t-scroll">
            <div class="t-min">
                <div class="t-head" style="--cols: 2.4fr 1.8fr 1fr 1fr">
                    <div>Guruh</div>
                    <div>O‘qituvchi</div>
                    <div>O‘quvchilar</div>
                    <div>Holat</div>
                </div>
                @forelse ($recentGroups as $group)
                    <div class="t-row" style="--cols: 2.4fr 1.8fr 1fr 1fr">
                        <div style="font-weight: 600">{{ $group->name }}</div>
                        <div>{{ $group->teacher?->name ?? '—' }}</div>
                        <div>{{ $group->students_count }}</div>
                        <div><x-badge :type="$group->status->badge()">{{ $group->status->label() }}</x-badge></div>
                    </div>
                @empty
                    <div class="t-empty">Guruh yo‘q</div>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
