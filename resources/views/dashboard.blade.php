<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-2">Welcome back, {{ Auth::user()->name }}!</h3>
                    <p class="text-gray-600">Ready to achieve your goals today?</p>
                </div>
            </div>

            <!-- SMART Goals Video Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900">Learn About SMART Goals</h3>
                    <div class="bg-gray-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="font-medium text-gray-900">How to Set SMART Goals</h4>
                                <p class="text-sm text-gray-600">Learn the proven framework for setting achievable goals</p>
                            </div>
                            <svg class="w-12 h-12 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                            </svg>
                        </div>
                        <button onclick="showSmartGoalsVideo()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Watch Video
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Quick Goal Creation -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-900">Create a New Goal</h3>
                        <form action="{{ route('goals.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Goal Title</label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                       placeholder="e.g., Run a 5K marathon"
                                       value="{{ old('title') }}"
                                       required>
                                @error('title')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                                <textarea id="description" 
                                          name="description" 
                                          rows="2" 
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                          placeholder="Add more details about your goal...">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Target Date</label>
                                <input type="date" 
                                       id="end_date" 
                                       name="end_date" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       value="{{ old('end_date') }}"
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                       required>
                                @error('end_date')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    Create Goal
                                </button>
                                <a href="{{ route('goals.create') }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium text-center transition-colors">
                                    Advanced Options
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Goals -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Your Goals</h3>
                            <a href="{{ route('goals.index') }}" class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                                View All
                            </a>
                        </div>
                        
                        @php
                            $recentGoals = Auth::user()->goals()->latest()->take(3)->get();
                        @endphp
                        
                        @if($recentGoals->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentGoals as $goal)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 text-sm">{{ $goal->title }}</h4>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                        @if($goal->status === 'active') bg-green-100 text-green-800
                                                        @elseif($goal->status === 'completed') bg-blue-100 text-blue-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ ucfirst($goal->status) }}
                                                    </span>
                                                    <span class="text-xs text-gray-500">
                                                        Due: {{ $goal->end_date->format('M j, Y') }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($goal->status === 'active')
                                                <form action="{{ route('goals.complete', $goal) }}" method="POST" class="ml-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="text-green-600 hover:text-green-700 text-xs font-medium"
                                                            onclick="return confirm('Mark this goal as complete?')">
                                                        Complete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No goals yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first goal!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900">Quick Actions</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <a href="{{ route('goals.index') }}" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900">View All Goals</div>
                                <div class="text-sm text-gray-500">Manage your goals</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('goals.create') }}" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900">Create Goal</div>
                                <div class="text-sm text-gray-500">Set a new target</div>
                            </div>
                        </a>
                        
                        <button onclick="showSmartGoalsVideo()" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left">
                            <svg class="w-6 h-6 text-purple-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900">SMART Goals</div>
                                <div class="text-sm text-gray-500">Learn the framework</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMART Goals Video Modal -->
    <div id="videoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold">How to Set SMART Goals</h3>
                    <button onclick="closeVideoModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center">
                        <iframe id="smartGoalsVideo" 
                                width="100%" 
                                height="100%" 
                                src="" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                                class="rounded-lg">
                        </iframe>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <p><strong>SMART Goals Framework:</strong></p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li><strong>Specific:</strong> Clear and well-defined</li>
                            <li><strong>Measurable:</strong> Quantifiable progress</li>
                            <li><strong>Achievable:</strong> Realistic and attainable</li>
                            <li><strong>Relevant:</strong> Meaningful to you</li>
                            <li><strong>Time-bound:</strong> Has a deadline</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showSmartGoalsVideo() {
            const modal = document.getElementById('videoModal');
            const iframe = document.getElementById('smartGoalsVideo');
            
            // You can replace this with your actual SMART goals video URL
            iframe.src = 'https://www.youtube.com/embed/1-SvuFIQjK8';
            
            modal.classList.remove('hidden');
        }
        
        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const iframe = document.getElementById('smartGoalsVideo');
            
            modal.classList.add('hidden');
            iframe.src = ''; // Stop the video
        }
        
        // Close modal when clicking outside
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });
    </script>
</x-app-layout>
