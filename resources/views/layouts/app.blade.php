<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a1a42">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen">
            <div class="app-top {{ isset($header) ? 'has-hero' : '' }}">
                <livewire:layout.navigation />

                @if (isset($header))
                    <header class="page-hero">
                        <div class="max-w-7xl mx-auto pt-8 pb-14 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @else
                    <div class="h-10"></div>
                @endif
            </div>

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>