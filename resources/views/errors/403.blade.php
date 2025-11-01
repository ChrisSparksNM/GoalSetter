<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Forbidden - {{ config('app.name', 'Laravel') }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <div class="flex justify-center">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Access Forbidden
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    You don't have permission to access this resource
                </p>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            </svg>
                        </div>
                        
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Permission Denied
                        </h3>
                        
                        <p class="text-sm text-gray-500 mb-6">
                            @if(isset($exception) && $exception->getMessage())
                                {{ $exception->getMessage() }}
                            @else
                                You don't have the necessary permissions to access this page. This could be because you're trying to access someone else's content or a restricted area.
                            @endif
                        </p>
                        
                        <div class="space-y-3">
                            @auth
                                <a href="{{ route('goals.index') }}" 
                                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Go to Your Goals
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Sign In
                                </a>
                            @endauth
                            
                            <a href="{{ url('/') }}" 
                               class="w-full flex justify-center py-2 px-4 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                ← Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>