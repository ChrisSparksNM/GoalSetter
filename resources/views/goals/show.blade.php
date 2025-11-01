<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Goal Details') }}
            </h2>
            <a href="{{ route('goals.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Goals
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Goal Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $goal->title }}</h1>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                @if($goal->status === 'active')
                                    @if($goal->isOverdue())
                                        bg-red-100 text-red-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif
                                @elseif($goal->status === 'completed')
                                    bg-gray-100 text-gray-800
                                @endif">
                                @if($goal->status === 'active')
                                    @if($goal->isOverdue())
                                        Overdue
                                    @else
                                        Active
                                    @endif
                                @else
                                    {{ ucfirst($goal->status) }}
                                @endif
                            </span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            @if($goal->status === 'active')
                                <button onclick="openCompleteModal({{ $goal->id }}, '{{ addslashes($goal->title) }}')" 
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Mark as Complete
                                </button>
                            @endif
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Edit Goal
                            </button>
                        </div>
                    </div>

                    <!-- Goal Description -->
                    @if($goal->description)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                            <p class="text-gray-700 leading-relaxed">{{ $goal->description }}</p>
                        </div>
                    @endif

                    <!-- Goal Details Grid -->
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <!-- Dates Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Important Dates</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Created:</span>
                                    <span class="font-medium text-gray-900">{{ $goal->created_at->format('F j, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Target End Date:</span>
                                    <span class="font-medium text-gray-900 @if($goal->isOverdue()) text-red-600 @endif">
                                        {{ $goal->end_date->format('F j, Y') }}
                                    </span>
                                </div>
                                @if($goal->completed_at)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Completed:</span>
                                        <span class="font-medium text-gray-900">{{ $goal->completed_at->format('F j, Y \a\t g:i A') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Last Updated:</span>
                                    <span class="font-medium text-gray-900">{{ $goal->updated_at->format('F j, Y \a\t g:i A') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Progress Information</h3>
                            <div class="space-y-3">
                                @if($goal->status === 'active')
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Days Remaining:</span>
                                        <span class="font-medium @if($goal->getDaysRemaining() <= 7) text-orange-600 @elseif($goal->getDaysRemaining() <= 0) text-red-600 @else text-gray-900 @endif">
                                            @if($goal->getDaysRemaining() > 0)
                                                {{ $goal->getDaysRemaining() }} days
                                            @elseif($goal->getDaysRemaining() === 0)
                                                Due today!
                                            @else
                                                {{ abs($goal->getDaysRemaining()) }} days overdue
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Total Duration:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ $goal->created_at->diffInDays($goal->end_date) }} days
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Days Elapsed:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ $goal->created_at->diffInDays(now()) }} days
                                        </span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    @php
                                        $totalDays = $goal->created_at->diffInDays($goal->end_date);
                                        $elapsedDays = $goal->created_at->diffInDays(now());
                                        $progressPercentage = $totalDays > 0 ? min(100, ($elapsedDays / $totalDays) * 100) : 0;
                                    @endphp
                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span>{{ number_format($progressPercentage, 1) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full @if($progressPercentage > 100) bg-red-500 @elseif($progressPercentage > 80) bg-orange-500 @else bg-blue-500 @endif" 
                                                 style="width: {{ min(100, $progressPercentage) }}%"></div>
                                        </div>
                                    </div>
                                @elseif($goal->status === 'completed')
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Completion Status:</span>
                                        <span class="font-medium text-green-600">✓ Completed</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Time to Complete:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ $goal->created_at->diffInDays($goal->completed_at) }} days
                                        </span>
                                    </div>
                                    @if($goal->completed_at && $goal->completed_at->lt($goal->end_date))
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Completed Early:</span>
                                            <span class="font-medium text-green-600">
                                                {{ $goal->completed_at->diffInDays($goal->end_date) }} days early
                                            </span>
                                        </div>
                                    @elseif($goal->completed_at && $goal->completed_at->gt($goal->end_date))
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Completed Late:</span>
                                            <span class="font-medium text-orange-600">
                                                {{ $goal->end_date->diffInDays($goal->completed_at) }} days late
                                            </span>
                                        </div>
                                    @elseif($goal->completed_at)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Timing:</span>
                                            <span class="font-medium text-green-600">Completed on time!</span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Status-specific Messages -->
                    @if($goal->status === 'active' && $goal->isOverdue())
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Goal Overdue</h3>
                                    <p class="mt-1 text-sm text-red-700">
                                        This goal is {{ abs($goal->getDaysRemaining()) }} days overdue. Consider completing it or updating the end date.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($goal->status === 'active' && $goal->getDaysRemaining() <= 7)
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-orange-800">Goal Due Soon</h3>
                                    <p class="mt-1 text-sm text-orange-700">
                                        This goal is due in {{ $goal->getDaysRemaining() }} days. Time to focus and complete it!
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Goal Completion Modal -->
    <div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Complete Goal</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to mark "<span id="goalTitle" class="font-medium"></span>" as complete? 
                        This action will send a notification email and cannot be undone.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="completeForm" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Complete
                        </button>
                    </form>
                    <button onclick="closeCompleteModal()" 
                            class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCompleteModal(goalId, goalTitle) {
            document.getElementById('goalTitle').textContent = goalTitle;
            document.getElementById('completeForm').action = `/goals/${goalId}/complete`;
            document.getElementById('completeModal').classList.remove('hidden');
        }

        function closeCompleteModal() {
            document.getElementById('completeModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('completeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCompleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCompleteModal();
            }
        });
    </script>
</x-app-layout>