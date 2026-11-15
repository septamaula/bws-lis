<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f4f6f5]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#157f4f">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BWS ELAPSE">
    <meta name="description" content="Bank Woori Saudara - Portal Belajar Mandiri Pegawai (ELAPSE Mobile PWA)">

    <title inertia>{{ config('app.name', 'BWS ELAPSE Learner Portal') }}</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- PWA Icons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/portal.tsx'])
    @inertiaHead
</head>
<body class="h-full font-sans antialiased text-gray-900 bg-[#f4f6f5] selection:bg-[#157f4f] selection:text-white">
    @inertia
</body>
</html>

