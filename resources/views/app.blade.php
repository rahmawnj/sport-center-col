<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sport Center') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    @yield('content')
</body>
</html>
