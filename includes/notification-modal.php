<!-- Notification Modal - Reusable Component -->
<div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full shadow-xl transform transition-all relative">
        <!-- Close Button (X) -->
        <button
            onclick="closeNotificationModal()"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <!-- Modal Content -->
        <div class="p-6">
            <!-- Icon Container -->
            <div class="flex items-center justify-center mb-4">
                <div id="notificationIcon" class="w-16 h-16 rounded-full flex items-center justify-center">
                    <!-- Icon will be inserted here by JavaScript -->
                </div>
            </div>
            
            <!-- Title -->
            <h3 id="notificationTitle" class="text-title text-xl text-center text-gray-800 mb-2">
                Notification
            </h3>
            
            <!-- Message -->
            <p id="notificationMessage" class="text-regular text-center text-gray-600 mb-6">
                Your message will appear here.
            </p>
            
            <!-- Action Buttons -->
            <div id="notificationButtons" class="flex gap-3 justify-center">
                <button
                    id="notificationOkButton"
                    onclick="closeNotificationModal()"
                    class="bg-black text-white px-6 py-2 rounded-md text-product hover:bg-gray-800 transition-colors">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>