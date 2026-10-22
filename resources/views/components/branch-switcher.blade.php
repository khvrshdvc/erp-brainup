@php
    $user = auth()->user();
    $branches = \App\Models\Branch::whereIn('id', $user->availableBranchIds())->orderBy('name')->get();
    $current = (int) app(\App\Services\CurrentBranch::class)->id();
@endphp

@if ($user->isSuperAdmin() || $branches->count() > 1)
    <form method="POST" action="{{ route('branch.switch') }}">
        @csrf
        <select name="branch_id" class="select" style="width: auto; min-width: 200px"
            aria-label="Filial" onchange="this.form.submit()">
            @if ($user->isSuperAdmin())
                <option value="all" @selected(! $current)>Barcha filiallar</option>
            @endif
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected($current === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </form>
@endif
