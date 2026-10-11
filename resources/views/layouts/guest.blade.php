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
        <div class="min-h-screen grid lg:grid-cols-2">

            <aside class="auth-side hidden lg:flex">
                <a href="/" class="nav-brand">
                    <x-application-logo class="h-11 w-11" />
                    <span class="text-xl">{{ config('app.name', 'School') }}</span>
                </a>

                <div>
                    <h1 class="auth-title">Your school,<br>always in touch.</h1>
                    <p class="auth-sub">One place for announcements, homework and private messages between parents and teachers.</p>

                    <ul class="auth-points">
                        <li><x-icon name="check" :size="18" /> Class and school announcements</li>
                        <li><x-icon name="check" :size="18" /> Private parent and teacher messages</li>
                        <li><x-icon name="check" :size="18" /> Homework, projects and test reminders</li>
                        <li><x-icon name="check" :size="18" /> Every child linked to the right parents</li>
                    </ul>
                </div>

                <p class="text-sm text-white/50">&copy; {{ date('Y') }} {{ config('app.name', 'School') }}</p>
            </aside>

            <section class="flex items-center justify-center p-6 sm:p-10">
                <div class="w-full max-w-md">
                    <a href="/" wire:navigate class="lg:hidden flex justify-center mb-6">
                        <x-application-logo class="h-14 w-14" />
                    </a>

                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </div>
    </body>
</html>