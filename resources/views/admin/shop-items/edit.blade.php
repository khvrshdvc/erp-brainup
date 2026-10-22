<x-app-layout :crumb="__('Do‘kon') . ' / ' . __('Mahsulotlar') . ' / ' . __('Tahrirlash')" :title="__('Mahsulotni tahrirlash')" :subtitle="$item->name">
    @include('admin.shop-items._form')
</x-app-layout>
