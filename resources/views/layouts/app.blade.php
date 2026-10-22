<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $attributes->get('title') ? $attributes->get('title').' · ' : '' }}{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        {{-- erp.js must load before Alpine (in app.js) so its alpine:init listener is registered in time. --}}
        <script src="{{ asset('js/erp.js') }}?v={{ filemtime(public_path('js/erp.js')) }}" defer></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="{{ asset('css/erp.css') }}?v={{ filemtime(public_path('css/erp.css')) }}">
    </head>
    <body class="erp-body">
        <div class="erp" x-data="{ nav: false }" :class="{ 'nav-open': nav }" @keydown.escape.window="nav = false">
            @include('layouts.partials.sidebar')

            <div class="erp-overlay" x-show="nav" x-cloak x-transition.opacity @click="nav = false" aria-hidden="true"></div>

            <div class="erp-main">
                @include('layouts.partials.topbar')

                <main class="erp-content">
                    @if (session('status'))
                        <div class="alert alert-ok">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-err">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if ($attributes->get('title'))
                        <div class="page-head">
                            <div class="stack" style="gap: 4px">
                                @if ($attributes->get('crumb'))
                                    <div class="crumb">{{ $attributes->get('crumb') }}</div>
                                @endif
                                <h1 class="page-title">{{ $attributes->get('title') }}</h1>
                                @if ($attributes->get('subtitle'))
                                    <div class="page-sub">{{ $attributes->get('subtitle') }}</div>
                                @endif
                            </div>
                            @isset($actions)
                                <div class="page-actions">{{ $actions }}</div>
                            @endisset
                        </div>
                    @elseif (isset($header))
                        <div class="page-head">{{ $header }}</div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
