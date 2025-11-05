<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your Goals') }}
            </h2>
            <a href="{{ route('goals.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New Goal
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Status Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Goals</h3>
                    <div class="flex space-x-4">
                        <a href="{{ route('goals.index') }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ $statusFilter === 'all' ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:text-gray-700' }}">
                            All Goals ({{ $goals->count() }})
                        </a>
                        <a href="{{ route('goals.index', ['status' => 'active']) }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ $statusFilter === 'active' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Active ({{ $goals->where('status', 'active')->count() }})
                        </a>
                        <a href="{{ route('goals.index', ['status' => 'completed']) }}" 
                           class="px-4 py-2 rounded-md text-sm font-medium {{ $statusFilter === 'completed' ? 'bg-gray-100 text-gray-700' : 'text-gray-500 hover:text-gray-700' }}">
                            Completed ({{ $goals->where('status', 'completed')->count() }})
                        </a>
                    </div>
                </div>
            </div>

            <!-- Goals List -->
            @if($goals->count() > 0)
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($goals as $goal)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <!-- Goal Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                                            {{ $goal->title }}
                                        </h3>
                                        @if($goal->is_recurring)
                                            <span class="text-blue-500" title="Recurring Goal Template">🔄</span>
                                        @elseif($goal->parent_goal_id)
                                            <span class="text-gray-500" title="Part of Recurring Series">↻</span>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
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

                                <!-- Goal Description -->
                                @if($goal->description)
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                        {{ $goal->description }}
                                    </p>
                                @endif

                                <!-- Goal Dates -->
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Created:</span>
                                        <span class="text-gray-900">{{ $goal->created_at->format('M j, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">End Date:</span>
                                        <span class="text-gray-900 @if($goal->isOverdue()) text-red-600 font-medium @endif">
                                            {{ $goal->end_date->format('M j, Y') }}
                                        </span>
                                    </div>
                                    @if($goal->status === 'active')
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Days Remaining:</span>
                                            <span class="text-gray-900 @if($goal->getDaysRemaining() <= 7) text-orange-600 font-medium @endif">
                                                {{ $goal->getDaysRemaining() }} days
                                            </span>
                                        </div>
                                    @endif
                                    @if($goal->completed_at)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Completed:</span>
                                            <span class="text-gray-900">{{ $goal->completed_at->format('M j, Y') }}</span>
                                        </div>
                                    @endif
                                    @if($goal->is_recurring)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Recurrence:</span>
                                            <span class="text-blue-600 font-medium">{{ $goal->recurrence_display }}</span>
                                        </div>
                                        @if($goal->next_due_date)
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Next Due:</span>
                                                <span class="text-gray-900">{{ $goal->next_due_date->format('M j, Y') }}</span>
                                            </div>
                                        @endif
                                    @elseif($goal->parent_goal_id)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Type:</span>
                                            <span class="text-gray-600">Recurring Instance</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex space-x-2">
                                    <a href="{{ route('goals.show', $goal) }}" 
                                       class="flex-1 bg-blue-500 hover:bg-blue-700 text-white text-center py-2 px-4 rounded text-sm">
                                        View Details
                                    </a>
                                    @if($goal->status === 'active')
                                        <button onclick="openCompleteModal({{ $goal->id }}, '{{ addslashes($goal->title) }}')" 
                                                class="bg-green-500 hover:bg-green-700 text-white py-2 px-4 rounded text-sm">
                                            Mark Complete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No goals yet</h3>
                        <p class="text-gray-500 mb-4">
                            @if($statusFilter === 'all')
                                You haven't created any goals yet. Start by creating your first goal!
                            @else
                                No {{ $statusFilter }} goals found. 
                                <a href="{{ route('goals.index') }}" class="text-blue-600 hover:text-blue-500">View all goals</a>
                            @endif
                        </p>
                        @if($statusFilter === 'all')
                            <a href="{{ route('goals.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Your First Goal
                            </a>
                        @endif
                    </div>
                </div>
            @endif
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