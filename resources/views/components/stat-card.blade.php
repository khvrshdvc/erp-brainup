@props(['label', 'value'])

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $value }}</div>
</div>
