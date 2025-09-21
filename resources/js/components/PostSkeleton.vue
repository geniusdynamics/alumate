<template>
    <div class="animate-pulse rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <!-- Post header -->
        <div class="mb-4 flex items-center space-x-3">
            <!-- Avatar skeleton -->
            <div class="h-10 w-10 rounded-full bg-gray-300"></div>

            <!-- User info skeleton -->
            <div class="flex-1">
                <div class="mb-2 h-4 w-32 rounded bg-gray-300"></div>
                <div class="h-3 w-24 rounded bg-gray-200"></div>
            </div>

            <!-- Menu button skeleton -->
            <div class="h-6 w-6 rounded bg-gray-300"></div>
        </div>

        <!-- Post content skeleton -->
        <div class="mb-4">
            <div class="mb-2 h-4 w-full rounded bg-gray-300"></div>
            <div class="mb-2 h-4 w-4/5 rounded bg-gray-300"></div>
            <div class="h-4 w-3/5 rounded bg-gray-300"></div>
        </div>

        <!-- Media skeleton (sometimes) -->
        <div v-if="Math.random() > 0.5" class="mb-4">
            <div class="h-48 rounded-lg bg-gray-300"></div>
        </div>

        <!-- Engagement bar skeleton -->
        <div class="flex items-center justify-between border-t border-gray-100 pt-4">
            <!-- Reaction buttons -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-1">
                    <div class="h-5 w-5 rounded bg-gray-300"></div>
                    <div class="h-3 w-6 rounded bg-gray-300"></div>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="h-5 w-5 rounded bg-gray-300"></div>
                    <div class="h-3 w-6 rounded bg-gray-300"></div>
                </div>
                <div class="flex items-center space-x-1">
                    <div class="h-5 w-5 rounded bg-gray-300"></div>
                    <div class="h-3 w-6 rounded bg-gray-300"></div>
                </div>
            </div>

            <!-- Share/bookmark buttons -->
            <div class="flex items-center space-x-2">
                <div class="h-5 w-5 rounded bg-gray-300"></div>
                <div class="h-5 w-5 rounded bg-gray-300"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
// No props or logic needed for skeleton
</script>

<style scoped>
/* Animation for skeleton loading */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
