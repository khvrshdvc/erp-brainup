<form method="POST" action="{{ route('branch.switch') }}">
    @csrf
    <select name="branch_id" class="select" style="width: auto; min-width: 200px"
        aria-label="Filial" onchange="this.form.submit()">
        @if ($canSeeAll)
            <option value="all" @selected(! $current)>Barcha filiallar</option>
        @endif
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected($current === $branch->id)>{{ $branch->name }}</option>
        @endforeach
    </select>
</form>
