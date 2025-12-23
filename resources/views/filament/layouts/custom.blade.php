<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="filament antialiased">
<head>
    {{ \Filament\Support\Facades\FilamentView::renderHook('head.start') }}

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'لوحة التحكم') }}</title>

    <!-- الخطوط -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{ \Filament\Support\Facades\FilamentView::renderHook('styles.before') }}
    @vite('resources/css/app.css')
    {{ \Filament\Support\Facades\FilamentView::renderHook('styles.after') }}

    {{ \Filament\Support\Facades\FilamentView::renderHook('head.end') }}
</head>

<body class="min-h-screen bg-gray-100 flex flex-col">
    {{ \Filament\Support\Facades\FilamentView::renderHook('body.start') }}

    <!-- الهيدر المنفصل -->
    <header class="bg-white shadow-sm z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- الشعار -->
                <div class="flex-shrink-0 flex items-center">
                    <x-filament::brand />
                </div>

                <!-- قائمة المستخدم والإشعارات -->
                <div class="flex items-center space-x-4">
                    <x-filament::notifications />
                    <x-filament::user-menu />
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1">
        <!-- السايدبار -->
        <nav class="bg-white w-64 border-r hidden md:block transition-all duration-300"
             :class="{ 'md:w-20': isSidebarCollapsed }">
            <x-filament::sidebar />
        </nav>

        <!-- المحتوى الرئيسي -->
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>

    <!-- السكربتات -->
    {{ \Filament\Support\Facades\FilamentView::renderHook('scripts.before') }}
    @vite('resources/js/app.js')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    {{ \Filament\Support\Facades\FilamentView::renderHook('scripts.after') }}

    {{ \Filament\Support\Facades\FilamentView::renderHook('body.end') }}
</body>
</html>
<!-- أضف هذا السكربت في نهاية ملف التخطيط المخصص -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // إضافة انزلاق سلس للسايدبار
    const sidebar = document.querySelector('nav');
    const toggleBtn = document.createElement('button');

    toggleBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
    `;

    toggleBtn.classList.add('bg-primary-500', 'text-white', 'p-2', 'rounded-full',
        'absolute', '-right-3', 'top-5', 'shadow-lg', 'focus:outline-none');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('md:w-20');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('md:w-20'));
    });

    sidebar.appendChild(toggleBtn);

    // استعادة حالة السايدبار
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('md:w-20');
    }
});
</script>
