<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IronForge Gym') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,800&display=swap" rel="stylesheet" />

    <!-- Vite Assets (Tailwind & JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-zinc-950 text-white antialiased selection:bg-orange-500 selection:text-white">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-800">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <!-- Logo -->
            <a href="/" class="text-2xl font-extrabold tracking-tighter text-white">
                IRON<span class="text-orange-500">FORGE</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-zinc-400">
                <a href="#programs" class="hover:text-white transition-colors">Programs</a>
                <a href="#trainers" class="hover:text-white transition-colors">Trainers</a>
                <a href="#membership" class="hover:text-white transition-colors">Membership</a>
            </div>

            <!-- Auth Buttons -->
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/amdin') }}" class="px-5 py-2.5 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition shadow-lg shadow-orange-900/20">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-300 hover:text-white transition">
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-semibold text-white bg-white text-zinc-950 rounded-lg hover:bg-zinc-200 transition">
                        Join Now
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background Glow Effect -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-orange-600/20 rounded-full blur-[120px] -z-10"></div>

        <div class="max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 leading-tight">
                Forge Your <br class="hidden md:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">
                    Ultimate Physique
                </span>
            </h1>
            <p class="text-lg md:text-xl text-zinc-400 max-w-2xl mx-auto mb-10">
                Premium equipment, expert trainers, and a community that drives results. 
                Start your transformation today at IronForge.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 transition shadow-lg shadow-orange-600/25">
                    Start Free Trial
                </a>
                <a href="#programs" class="w-full sm:w-auto px-8 py-4 bg-zinc-900 border border-zinc-800 text-white font-bold rounded-xl hover:bg-zinc-800 transition">
                    View Programs
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="border-y border-zinc-800 bg-zinc-900/50">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl font-bold text-white mb-1">24/7</div>
                <div class="text-sm text-zinc-500 uppercase tracking-wide">Access</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">50+</div>
                <div class="text-sm text-zinc-500 uppercase tracking-wide">Expert Trainers</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">100+</div>
                <div class="text-sm text-zinc-500 uppercase tracking-wide">Machines</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">4.9</div>
                <div class="text-sm text-zinc-500 uppercase tracking-wide">User Rating</div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section id="programs" class="py-24 bg-zinc-950">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Why Choose IronForge?</h2>
                <p class="text-zinc-400">We provide everything you need to reach your peak performance.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-orange-500/50 transition group">
                    <div class="w-12 h-12 bg-orange-500/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-orange-500/20 transition">
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">High Intensity</h3>
                    <p class="text-zinc-400 leading-relaxed">
                        Specialized HIIT zones designed to maximize calorie burn and improve cardiovascular health.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-orange-500/50 transition group">
                    <div class="w-12 h-12 bg-orange-500/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-orange-500/20 transition">
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Community First</h3>
                    <p class="text-zinc-400 leading-relaxed">
                        Join a supportive community. Group classes, challenges, and social events to keep you motivated.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-2xl bg-zinc-900 border border-zinc-800 hover:border-orange-500/50 transition group">
                    <div class="w-12 h-12 bg-orange-500/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-orange-500/20 transition">
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Smart Tracking</h3>
                    <p class="text-zinc-400 leading-relaxed">
                        Integrated app support to track your workouts, progress, and nutrition all in one place.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-zinc-800 py-12 bg-zinc-950">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-zinc-500 text-sm">
                &copy; {{ date('Y') }} IronForge Gym. All rights reserved.
            </div>
            <div class="flex gap-6 text-sm font-medium text-zinc-400">
                <a href="#" class="hover:text-white transition">Privacy</a>
                <a href="#" class="hover:text-white transition">Terms</a>
                <a href="#" class="hover:text-white transition">Contact</a>
            </div>
        </div>
    </footer>

</body>
</html>