<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Admin paneli{{ $branch ? ' · '.$branch->name : ' · Barcha filiallar' }}
            </h2>
            <x-branch-switcher />
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-4 sm:grid-cols-3">
        <x-stat-card label="O‘quvchilar" :value="$students" />
        <x-stat-card label="O‘qituvchilar" :value="$teachers" />
        <x-stat-card label="Guruhlar" :value="$groups" />
    </div>
</x-app-layout>
