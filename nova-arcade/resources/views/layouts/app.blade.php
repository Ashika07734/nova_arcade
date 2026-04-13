<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">

    <title>{{ config('app.name', 'Survival Arena 3D') }} - @yield('title', 'Battle Royale')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=rajdhani:400,600,700|orbitron:400,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        body {
            font-family: 'Rajdhani', sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #ffffff;
        }
    </style>
</head>
<body class="antialiased text-slate-100" @yield('body-attrs')>
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl"></div>
    </div>

    <div id="app" class="relative z-10 min-h-screen">
        <!-- Navigation -->
        @include('components.navbar')

        <!-- Page Content -->
        <main class="pb-16 pt-6 sm:pt-8">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('components.footer')
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="flash-toast fixed right-4 top-4 z-50 border-emerald-400/30 bg-emerald-500/10 text-emerald-100 animate-slide-in">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flash-toast fixed right-4 top-4 z-50 border-rose-400/30 bg-rose-500/10 text-rose-100 animate-slide-in">
            {{ session('error') }}
        </div>
    @endif

    @stack('scripts')

    <script>
        // Auto-hide flash messages
        setTimeout(() => {
            document.querySelectorAll('.animate-slide-in').forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
