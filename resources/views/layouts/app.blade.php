<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Kitchen Pantry CRM') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-slate-700 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-white text-lg font-semibold">Kitchen Pantry CRM</h1>
                        </div>
                        <div class="ml-10">
                            <div class="flex items-center space-x-8">
                                <input type="search" 
                                       placeholder="Search organizations..." 
                                       class="w-80 px-4 py-2 text-sm text-gray-900 bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-600 text-white' : 'text-gray-300 hover:text-white hover:bg-slate-600' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('organizations') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('organizations') ? 'bg-slate-600 text-white' : 'text-gray-300 hover:text-white hover:bg-slate-600' }}">
                            Organizations
                        </a>
                        <a href="{{ route('contacts') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('contacts') ? 'bg-slate-600 text-white' : 'text-gray-300 hover:text-white hover:bg-slate-600' }}">
                            Contacts
                        </a>
                        <a href="{{ route('interactions') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('interactions') ? 'bg-slate-600 text-white' : 'text-gray-300 hover:text-white hover:bg-slate-600' }}">
                            Interactions
                        </a>
                        <a href="{{ route('reports') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('reports') ? 'bg-slate-600 text-white' : 'text-gray-300 hover:text-white hover:bg-slate-600' }}">
                            Reports
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>