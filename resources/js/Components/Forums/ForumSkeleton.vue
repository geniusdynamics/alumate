<template>
    <div class="animate-pulse rounded-lg bg-white p-6 shadow">
        <div class="flex items-start space-x-4">
            <!-- Forum Icon Skeleton -->
            <div class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-200"></div>

            <!-- Forum Info Skeleton -->
            <div class="flex-1 space-y-3">
                <div class="h-6 w-3/4 rounded bg-gray-200"></div>
                <div class="space-y-2">
                    <div class="h-4 w-full rounded bg-gray-200"></div>
                    <div class="h-4 w-2/3 rounded bg-gray-200"></div>
                </div>
                <div class="h-5 w-1/4 rounded bg-gray-200"></div>
            </div>

            <!-- Stats Skeleton -->
            <div class="flex-shrink-0 space-y-2">
                <div class="text-center">
                    <div class="mx-auto h-6 w-8 rounded bg-gray-200"></div>
                    <div class="mt-1 h-3 w-12 rounded bg-gray-200"></div>
                </div>
            </div>
        </div>

        <!-- Latest Topics Skeleton -->
        <div class="mt-4 border-t border-gray-200 pt-4">
            <div class="mb-2 h-4 w-1/4 rounded bg-gray-200"></div>
            <div class="space-y-2">
                <div v-for="i in 3" :key="i" class="flex justify-between">
                    <div class="h-3 w-2/3 rounded bg-gray-200"></div>
                    <div class="h-3 w-1/4 rounded bg-gray-200"></div>
                </div>
            </div>
        </div>

        <!-- Last Activity Skeleton -->
        <div class="mt-4 border-t border-gray-200 pt-4">
            <div class="flex justify-between">
                <div class="h-3 w-1/4 rounded bg-gray-200"></div>
                <div class="h-3 w-1/6 rounded bg-gray-200"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
// This is a skeleton loading component for forums
</script>
