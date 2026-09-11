<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Sport Center'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    @auth
    <div class="min-h-screen md:flex">
        <aside class="hidden w-64 shrink-0 border-r bg-card p-5 md:block">
            <a href="{{ route('dashboard') }}" class="mb-8 block text-xl font-bold">Sport Center</a>
            <nav class="space-y-1 text-sm">
                @foreach([
                    'dashboard'=>'Dashboard','bookings.index'=>'Booking','users.index'=>'Pengguna','zones.index'=>'Zona','zone-spaces.index'=>'Space Zona',
                    'pricing-rates.index'=>'Harga','membership-packages.index'=>'Paket Membership','memberships.index'=>'Member','subscriptions.index'=>'Subscription',
                    'trainers.index'=>'Trainer','facilities.index'=>'Fasilitas','add-ons.index'=>'Add-on','profile.edit'=>'Profil'
                ] as $routeName => $label)
                    @if(Route::has($routeName))
                        <a href="{{ route($routeName) }}" class="block rounded-lg px-3 py-2 hover:bg-muted">{{ $label }}</a>
                    @endif
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf<button class="w-full rounded-lg border px-3 py-2 text-left hover:bg-muted">Keluar</button></form>
        </aside>
        <main class="min-w-0 flex-1">
            <header class="border-b bg-card px-5 py-4 md:px-8"><div class="flex items-center justify-between"><div><h1 class="text-lg font-semibold">@yield('heading', 'Dashboard')</h1></div><span class="text-sm text-muted-foreground">{{ auth()->user()->name }}</span></div></header>
            <div class="p-5 md:p-8">
    @else
        <main class="min-h-screen">
    @endauth
            @if(session('success'))<div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </main>
</body>
</html>
