<x-app-layout :crumb="__('Bosh sahifa')" :title="__('Yutuqlarim')" :subtitle="__('BrainCoin, daraja, nishonlar va reyting')">
    <section class="card level-card">
        <div class="level-badge">{{ $levelNumber }}</div>

        <div class="level-body">
            <div class="level-title">{{ __('Daraja :level · :name', ['level' => $levelNumber, 'name' => __($student->level?->name ?? 'Boshlovchi')]) }}</div>
            <div class="page-sub" style="margin: 4px 0 12px">
                @if ($next)
                    {{ __('Keyingi daraja: :name — :min_xp BrainCoin qoldi', ['name' => __($next->name), 'min_xp' => $next->min_xp - $student->xp]) }}
                @else
                    {{ __('Siz eng yuqori darajadasiz') }}
                @endif
            </div>
            <div class="progress"><i style="width: {{ $progress }}%"></i></div>
            <div class="level-meta">
                <span>{{ __(':xp BrainCoin', ['xp' => $student->xp]) }}</span>
                <span>{{ $next?->min_xp ?? $student->xp }} {{ __('BrainCoin') }}</span>
            </div>
        </div>

        <div class="streak">
            <div class="n">{{ $student->streak }}</div>
            <div class="kpi-note">{{ __('darslik davomat seriyasi') }}</div>
        </div>
    </section>

    <section class="card card-pad stack">
        <h2 class="card-title">{{ __('Nishonlar') }}</h2>
        <div class="achv-grid">
            @foreach ($badges as $item)
                <x-achievement :badge="$item['badge']" :earned="$item['earned']" :awarded-at="$item['awarded_at']" />
            @endforeach
        </div>
    </section>

    <div class="tabs">
        <a class="tab {{ $scope === 'group' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'group']) }}">{{ __('Guruh') }}</a>
        <a class="tab {{ $scope === 'branch' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'branch']) }}">{{ __('Filial') }}</a>
        <a class="tab {{ $scope === 'all' ? 'on' : '' }}" href="{{ route('student.achievements', ['scope' => 'all']) }}">{{ __('Barcha filiallar') }}</a>
    </div>

    <section class="card" style="overflow: hidden">
        <div class="t-scroll">
            <div class="t-min" style="min-width: 640px">
                <div class="t-head" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                    <div>№</div>
                    <div>{{ __('O‘quvchi') }}</div>
                    <div>{{ __('Filial') }}</div>
                    <div>{{ __('Daraja') }}</div>
                    <div class="t-right"><span class="brand-name">{{ __('BrainCoin') }}</span></div>
                </div>

                @forelse ($leaderboard['rows'] as $row)
                    <div class="t-row {{ $row['me'] ? 'me' : '' }}" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                        <div class="rank">{{ $row['rank'] }}</div>
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $row['initials'] }}</span>
                            {{ $row['name'] }}{{ $row['me'] ? ' (siz)' : '' }}
                        </div>
                        <div>{{ $row['branch'] ?? '—' }}</div>
                        <div>{{ __($row['level']) }}</div>
                        <div class="t-right" style="font-weight: 600">{{ $row['xp'] }} {{ __('BrainCoin') }}</div>
                    </div>
                @empty
                    <div class="t-empty">{{ __('Reyting hali bo‘sh') }}</div>
                @endforelse

                @unless ($leaderboard['included'])
                    <div class="t-row me" style="--cols: 56px 2.4fr 1.6fr 1.2fr 1fr">
                        <div class="rank">{{ $leaderboard['myRank'] }}</div>
                        <div class="t-name">
                            <span class="avatar avatar-sm">{{ $student->initials }}</span>
                            {{ __(':name (siz)', ['name' => $student->name]) }}
                        </div>
                        <div>{{ $student->branch?->name ?? '—' }}</div>
                        <div>{{ __($student->level?->name ?? 'Boshlovchi') }}</div>
                        <div class="t-right" style="font-weight: 600">{{ __(':xp BrainCoin', ['xp' => $student->xp]) }}</div>
                    </div>
                @endunless
            </div>
        </div>
    </section>
</x-app-layout>
