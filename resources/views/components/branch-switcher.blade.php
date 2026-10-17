@php
    $user = auth()->user();
    $branches = \App\Models\Branch::whereIn('id', $user->availableBranchIds())->orderBy('name')->get();
    $current = (int) session('branch_id');
@endphp

@if ($user->isSuperAdmin() || $branches->count() > 1)
    <form method="POST" action="{{ route('branch.switch') }}">
        @csrf
        <select name="branch_id" onchange="this.form.submit()"
            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
            @if ($user->isSuperAdmin())
                <option value="all" @selected(! $current)>Barcha filiallar</option>
            @endif
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected($current === $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </form>
@endif
