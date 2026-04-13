<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Game') - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=rajdhani:400,600,700" rel="stylesheet" />

    <!-- Game Styles -->
    <link rel="stylesheet" href="{{ asset('games/survival-arena-3d/css/game.css') }}">
    
    @stack('styles')

    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Rajdhani', sans-serif;
        }
    </style>
</head>
<body>
    @yield('content')

    <!-- Three.js Import Map -->
    <script type="importmap">
    {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
        }
    }
    </script>

    @stack('scripts')
</body>
</html>

