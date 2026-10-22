<x-app-layout crumb="Bosh sahifa" title="Yutuqlarim" subtitle="BrainCoin, daraja, nishonlar va reyting">
    <section class="card level-card">
        <div class="level-badge">{{ $levelNumber }}</div>

        <div class="level-body">
            <div class="level-title">Daraja {{ $levelNumber }} · {{ $student->level?->name ?? 'Boshlovchi' }}</div>
            <div class="page-sub" style="margin: 4px 0 12px">
                @if ($next)
                    Keyingi daraja: {{ $next->name }} — {{ $next->min_xp - $student->xp }} BrainCoin qoldi
                @else
                    Siz eng yuqori darajadasiz
                @endif
            </div>
            <div class="progress"><i style="width: {{ $progress }}%"></i></div>
            <div class="level-meta">
                <span>{{ $student->xp }} BrainCoin</span>
                <span>{{ $next?->min_xp ?? $student->xp }} BrainCoin</span>
            </div>
        </div>

        <div class="streak">
            <div class="n">{{ $student->streak }}</div>
            <div class="kpi-note">darslik davomat seriyasi</div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">Nishonlar</h2>
        <div class="achv-grid">
            @foreach ($badges as $item)
                <div class="achv {{ $item['earned'] ? '' : 'locked' }}">
                    <div class="ico">{{ $item['badge']->icon }}</div>
                    <b>{{ $item['badge']->title }}</b>
                    <small>{{ $item['earned'] ? 'Olingan' : 'Qulflangan' }}</small>
                </div>
            @endforeach
        </div>
    </section>

    <div class="tabs">
        <a class="tab {{ $scope === 'group' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'group']) }}">Guruh</a>
        <a class="tab {{ $scope === 'branch' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'branch']) }}">Filial</a>
        <a class="tab {{ $scope === 'all' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'all']) }}">Barcha filiallar</a>
    </div>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 640px">
                <div class="t-head" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                    <div>№</div>
                    <div>O‘quvchi</div>
                    <div>Filial</div>
                    <div>Daraja</div>
                    <div class="t-right">BrainCoin</div>
                </div>

                @forelse ($leaderboard['rows'] as $row)
                    <div class="t-row {{ $row['me'] ? 'me' : '' }}" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                        <div class="rank">{{ $row['rank'] }}</div>
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $row['initials'] }}</span>
                            {{ $row['name'] }}{{ $row['me'] ? ' (siz)' : '' }}
                        </div>
                        <div>{{ $row['branch'] ?? '—' }}</div>
                        <div>{{ $row['level'] }}</div>
                        <div class="t-right" style="font-weight: 700">{{ $row['xp'] }} BrainCoin</div>
                    </div>
                @empty
                    <div class="t-empty">Reyting hali bo‘sh</div>
                @endforelse

                @unless ($leaderboard['included'])
                    <div class="t-row me" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                        <div class="rank">{{ $leaderboard['myRank'] }}</div>
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            {{ $student->name }} (siz)
                        </div>
                        <div>{{ $student->branch?->name ?? '—' }}</div>
                        <div>{{ $student->level?->name ?? 'Boshlovchi' }}</div>
                        <div class="t-right" style="font-weight: 700">{{ $student->xp }} BrainCoin</div>
                    </div>
                @endunless
            </div>
        </div>
    </section>
</x-app-layout>
