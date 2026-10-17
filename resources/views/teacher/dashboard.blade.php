<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">O‘qituvchi paneli</h2>
            <x-branch-switcher />
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-stat-card label="Mening guruhlarim" :value="$groups->count()" />
            <x-stat-card label="O‘quvchilar" :value="$students" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">Guruh</th>
                        <th class="px-6 py-3">O‘quvchilar</th>
                        <th class="px-6 py-3">Holat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr class="border-t dark:border-gray-700">
                            <td class="px-6 py-3 font-medium">{{ $group->name }}</td>
                            <td class="px-6 py-3">{{ $group->students_count }}</td>
                            <td class="px-6 py-3">{{ $group->status->label() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-6 text-center text-gray-500">Guruh yo‘q</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
