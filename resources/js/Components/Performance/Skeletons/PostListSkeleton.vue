<template>
    <div class="post-list-skeleton">
        <div v-for="i in count" :key="i" class="post-item-skeleton">
            <div class="post-header-skeleton">
                <div class="avatar-skeleton skeleton-item"></div>
                <div class="author-info-skeleton">
                    <div class="author-name-skeleton skeleton-item"></div>
                    <div class="post-meta-skeleton skeleton-item"></div>
                </div>
            </div>

            <div class="post-content-skeleton">
                <div class="title-skeleton skeleton-item"></div>
                <div class="text-skeleton">
                    <div class="text-line-skeleton skeleton-item"></div>
                    <div class="text-line-skeleton skeleton-item"></div>
                    <div class="text-line-short-skeleton skeleton-item"></div>
                </div>

                <div v-if="showImage" class="image-skeleton skeleton-item"></div>
            </div>

            <div class="post-actions-skeleton">
                <div class="action-button-skeleton skeleton-item"></div>
                <div class="action-button-skeleton skeleton-item"></div>
                <div class="action-button-skeleton skeleton-item"></div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    count?: number;
    showImage?: boolean;
}

withDefaults(defineProps<Props>(), {
    count: 3,
    showImage: true,
});
</script>

<style scoped>
.post-list-skeleton {
    @apply space-y-6;
}

.post-item-skeleton {
    @apply space-y-4 rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.post-header-skeleton {
    @apply flex items-center space-x-3;
}

.avatar-skeleton {
    @apply h-10 w-10 flex-shrink-0 rounded-full;
}

.author-info-skeleton {
    @apply flex-1 space-y-1;
}

.author-name-skeleton {
    @apply h-4 w-24 rounded;
}

.post-meta-skeleton {
    @apply h-3 w-32 rounded;
}

.post-content-skeleton {
    @apply space-y-3;
}

.title-skeleton {
    @apply h-5 w-3/4 rounded;
}

.text-skeleton {
    @apply space-y-2;
}

.text-line-skeleton {
    @apply h-3 w-full rounded;
}

.text-line-short-skeleton {
    @apply h-3 w-2/3 rounded;
}

.image-skeleton {
    @apply h-48 w-full rounded-lg;
}

.post-actions-skeleton {
    @apply flex items-center space-x-4 border-t border-gray-200 pt-2 dark:border-gray-700;
}

.action-button-skeleton {
    @apply h-6 w-16 rounded;
}
</style>
