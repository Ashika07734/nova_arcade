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
            background: #020617;
            min-height: 100vh;
            color: #ffffff;
            overflow-x: hidden;
        }

        /* ── Particle Canvas ── */
        #particle-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* ── Aurora Gradient Wave ── */
        .aurora-wrap {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .aurora {
            position: absolute;
            width: 150%;
            height: 60%;
            top: -15%;
            left: -25%;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.12;
            animation: aurora-drift 18s ease-in-out infinite alternate;
        }
        .aurora-1 {
            background: radial-gradient(ellipse, rgba(34,211,238,0.5), transparent 70%);
            animation-delay: 0s;
        }
        .aurora-2 {
            background: radial-gradient(ellipse, rgba(16,185,129,0.4), transparent 70%);
            top: auto;
            bottom: -20%;
            animation-delay: -6s;
            animation-duration: 22s;
        }
        .aurora-3 {
            background: radial-gradient(ellipse, rgba(139,92,246,0.3), transparent 70%);
            width: 100%;
            height: 40%;
            top: 30%;
            left: 10%;
            animation-delay: -12s;
            animation-duration: 25s;
        }
        @keyframes aurora-drift {
            0%   { transform: translate(0, 0) rotate(0deg) scale(1); }
            33%  { transform: translate(40px, -30px) rotate(3deg) scale(1.05); }
            66%  { transform: translate(-30px, 20px) rotate(-2deg) scale(0.95); }
            100% { transform: translate(20px, -10px) rotate(1deg) scale(1.02); }
        }

        /* ── Vignette ── */
        .vignette {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: radial-gradient(ellipse at center, transparent 50%, rgba(2,6,23,0.7) 100%);
        }
    </style>
</head>
<body class="antialiased text-slate-100" @yield('body-attrs')>
    <!-- Aurora gradient waves -->
    <div class="aurora-wrap">
        <div class="aurora aurora-1"></div>
        <div class="aurora aurora-2"></div>
        <div class="aurora aurora-3"></div>
    </div>

    <!-- Interactive particle field canvas -->
    <canvas id="particle-canvas"></canvas>

    <!-- Subtle grid overlay for depth -->
    <div class="grid-overlay"></div>

    <!-- Vignette overlay -->
    <div class="vignette"></div>

    <div id="app" class="relative z-10 min-h-screen perspective-scene">
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
    // ═══════════════════════════════════════════
    // PARTICLE FIELD — inspired by ReactBits Particles
    // Clean, interactive canvas particles with connecting threads
    // ═══════════════════════════════════════════
    (function() {
        const canvas = document.getElementById('particle-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let w, h, particles = [], mouse = { x: -9999, y: -9999 };

        const CONFIG = {
            count: 70,
            maxDist: 140,
            speed: 0.3,
            mouseRadius: 180,
            colors: [
                'rgba(34,211,238,',   // cyan
                'rgba(16,185,129,',   // emerald
                'rgba(139,92,246,',   // violet
                'rgba(59,130,246,',   // blue
            ],
            lineAlpha: 0.12,
            dotAlpha: 0.5,
            minSize: 1.2,
            maxSize: 2.8,
        };

        function resize() {
            w = canvas.width = window.innerWidth;
            h = canvas.height = window.innerHeight;
        }

        function createParticle() {
            const colorBase = CONFIG.colors[Math.floor(Math.random() * CONFIG.colors.length)];
            return {
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * CONFIG.speed,
                vy: (Math.random() - 0.5) * CONFIG.speed,
                size: CONFIG.minSize + Math.random() * (CONFIG.maxSize - CONFIG.minSize),
                color: colorBase,
                pulse: Math.random() * Math.PI * 2,
                pulseSpeed: 0.01 + Math.random() * 0.02,
            };
        }

        function init() {
            resize();
            particles = [];
            for (let i = 0; i < CONFIG.count; i++) {
                particles.push(createParticle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, w, h);

            // Update & draw particles
            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];
                p.pulse += p.pulseSpeed;
                const alpha = CONFIG.dotAlpha + Math.sin(p.pulse) * 0.2;

                // Mouse repulsion (gentle push)
                const dx = p.x - mouse.x;
                const dy = p.y - mouse.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < CONFIG.mouseRadius && dist > 0) {
                    const force = (CONFIG.mouseRadius - dist) / CONFIG.mouseRadius * 0.015;
                    p.vx += (dx / dist) * force;
                    p.vy += (dy / dist) * force;
                }

                // Dampen velocity
                p.vx *= 0.998;
                p.vy *= 0.998;

                p.x += p.vx;
                p.y += p.vy;

                // Wrap edges
                if (p.x < -20) p.x = w + 20;
                if (p.x > w + 20) p.x = -20;
                if (p.y < -20) p.y = h + 20;
                if (p.y > h + 20) p.y = -20;

                // Draw dot
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fillStyle = p.color + Math.max(0.15, alpha) + ')';
                ctx.fill();

                // Draw connecting lines (threads)
                for (let j = i + 1; j < particles.length; j++) {
                    const q = particles[j];
                    const ddx = p.x - q.x;
                    const ddy = p.y - q.y;
                    const d = Math.sqrt(ddx * ddx + ddy * ddy);
                    if (d < CONFIG.maxDist) {
                        const lineAlpha = (1 - d / CONFIG.maxDist) * CONFIG.lineAlpha;
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(q.x, q.y);
                        ctx.strokeStyle = p.color + lineAlpha + ')';
                        ctx.lineWidth = 0.6;
                        ctx.stroke();
                    }
                }
            }

            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', () => { resize(); });
        document.addEventListener('mousemove', e => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });
        document.addEventListener('mouseleave', () => {
            mouse.x = -9999;
            mouse.y = -9999;
        });

        init();
        animate();
    })();

    // ═══ AUTO-HIDE FLASH MESSAGES ═══
    setTimeout(() => {
        document.querySelectorAll('.animate-slide-in').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        });
    }, 5000);

    // ═══ 3D TILT EFFECT ═══
    document.querySelectorAll('[data-tilt]').forEach(card => {
        const intensity = parseFloat(card.dataset.tiltIntensity || 8);
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * intensity}deg) rotateX(${-y * intensity}deg) scale(1.02)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(800px) rotateY(0) rotateX(0) scale(1)';
        });
        card.style.transition = 'transform 0.4s cubic-bezier(0.23, 1, 0.32, 1)';
        card.style.transformStyle = 'preserve-3d';
    });

    // ═══ SCROLL REVEAL ═══
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.scroll-reveal').forEach(el => revealObserver.observe(el));
    </script>
</body>
</html>
