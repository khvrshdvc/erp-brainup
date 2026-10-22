<x-app-layout :crumb="__('Filiallar') . ' / ' . __('Tahrirlash')" :title="__('Filialni tahrirlash')" :subtitle="$branch->name">
    @include('admin.branches._form')
</x-app-layout>
