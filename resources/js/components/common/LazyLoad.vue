<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface Props {
    threshold?: number;
    rootMargin?: string;
    loadingHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
    threshold: 0.1,
    rootMargin: '50px',
    loadingHeight: '200px',
});

const containerRef = ref<HTMLElement | null>(null);
const isLoaded = ref(false);
const isLoading = ref(false);
let observer: IntersectionObserver | null = null;

const loadContent = () => {
    if (isLoaded.value || isLoading.value) return;
    
    isLoading.value = true;
    
    // Small delay to show loading state
    setTimeout(() => {
        isLoaded.value = true;
        isLoading.value = false;
        
        if (observer && containerRef.value) {
            observer.unobserve(containerRef.value);
        }
    }, 100);
};

onMounted(() => {
    if (containerRef.value) {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        loadContent();
                    }
                });
            },
            {
                threshold: props.threshold,
                rootMargin: props.rootMargin,
            }
        );
        
        observer.observe(containerRef.value);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <div ref="containerRef" class="lazy-load-container">
        <div v-if="isLoading" class="flex items-center justify-center" :style="{ height: loadingHeight }">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
        
        <div v-else-if="isLoaded">
            <slot />
        </div>
        
        <div v-else class="lazy-load-placeholder" :style="{ height: loadingHeight }">
            <slot name="placeholder">
                <div class="flex items-center justify-center h-full bg-muted/50 rounded-lg">
                    <span class="text-sm text-muted-foreground">Loading...</span>
                </div>
            </slot>
        </div>
    </div>
</template>

<style scoped>
.lazy-load-container {
    @apply w-full;
}

.lazy-load-placeholder {
    @apply animate-pulse;
}
</style>
