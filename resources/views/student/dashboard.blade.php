<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">O‘quvchi paneli</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="XP" :value="$student->xp" />
            <x-stat-card label="Daraja" :value="$student->level?->name ?? 'Boshlovchi'" />
            <x-stat-card label="Davomat seriyasi" :value="$student->streak" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">Guruh</th>
                        <th class="px-6 py-3">O‘qituvchi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr class="border-t dark:border-gray-700">
                            <td class="px-6 py-3 font-medium">{{ $group->name }}</td>
                            <td class="px-6 py-3">{{ $group->teacher?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-6 text-center text-gray-500">Guruhga biriktirilmagan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
