<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ $user->name }}!</h1>
                <p class="text-lg text-gray-600">
                    Before you start creating goals, let's watch this short video about Setting Smart Goals.
                </p>
            </div>

            <!-- Video Player -->
            <div class="mb-8">
                <div class="relative bg-black rounded-lg overflow-hidden" style="padding-bottom: 56.25%; height: 0;">
                    <video 
                        id="onboarding-video"
                        class="absolute top-0 left-0 w-full h-full"
                        controls
                        preload="metadata"
                        poster="{{ asset('images/video-poster.jpg') }}"
                    >
                        <source src="{{ $videoUrl }}" type="video/mp4">
                        <p class="text-white p-4">
                            Your browser doesn't support HTML5 video. 
                            <a href="{{ $videoUrl }}" class="text-blue-400 underline">Download the video</a> instead.
                        </p>
                    </video>
                </div>
            </div>

            <!-- Video Progress Indicator -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Video Progress</span>
                    <span id="progress-text" class="text-sm text-gray-500">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <form action="{{ route('onboarding.complete') }}" method="POST" class="inline">
                    @csrf
                    <button 
                        type="submit" 
                        id="complete-button"
                        class="w-full sm:w-auto bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-bold py-3 px-6 rounded-lg transition duration-200"
                        disabled
                    >
                        Continue to Goal Creation
                    </button>
                </form>
                
                <button 
                    type="button"
                    id="skip-button"
                    class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition duration-200"
                    onclick="confirmSkip()"
                >
                    Skip Video
                </button>
            </div>

            <!-- Video Information -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-900 mb-2">What you'll learn:</h3>
                <ul class="text-blue-800 space-y-1">
                    <li>• How to set Specific, Measurable, Achievable, Relevant, and Time-bound goals</li>
                    <li>• Best practices for goal tracking and accountability</li>
                    <li>• Tips for staying motivated throughout your goal journey</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('onboarding-video');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const completeButton = document.getElementById('complete-button');
    
    let hasWatchedEnough = false;
    const requiredWatchPercentage = 80; // User needs to watch 80% of the video

    // Update progress as video plays
    video.addEventListener('timeupdate', function() {
        if (video.duration) {
            const progress = (video.currentTime / video.duration) * 100;
            progressBar.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
            
            // Enable complete button when user has watched enough
            if (progress >= requiredWatchPercentage && !hasWatchedEnough) {
                hasWatchedEnough = true;
                completeButton.disabled = false;
                completeButton.classList.remove('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
                completeButton.classList.add('bg-green-600', 'hover:bg-green-700');
                
                // Show success message
                showNotification('Great! You can now continue to goal creation.', 'success');
            }
        }
    });

    // Handle video end
    video.addEventListener('ended', function() {
        hasWatchedEnough = true;
        completeButton.disabled = false;
        progressBar.style.width = '100%';
        progressText.textContent = '100%';
        showNotification('Video completed! You can now continue to goal creation.', 'success');
    });

    // Handle video errors
    video.addEventListener('error', function() {
        showNotification('There was an error loading the video. You can skip to continue.', 'error');
    });
});

function confirmSkip() {
    if (confirm('Are you sure you want to skip the video? We recommend watching it to learn about effective goal setting.')) {
        document.getElementById('complete-button').form.submit();
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
</x-app-layout>