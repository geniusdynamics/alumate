<template>
    <div class="insight-card rounded-lg border p-4" :class="cardClass">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <component :is="iconComponent" class="h-5 w-5" :class="iconClass" />
            </div>
            <div class="ml-3 flex-1">
                <h4 class="text-sm font-medium text-gray-900">{{ insight.title }}</h4>
                <p class="mt-1 text-sm text-gray-600">{{ insight.description }}</p>
                <div v-if="insight.data && insight.data.length > 0" class="mt-3">
                    <div class="mb-2 text-xs text-gray-500">Related data:</div>
                    <div class="space-y-1">
                        <div v-for="item in insight.data.slice(0, 3)" :key="item.id || item.name" class="rounded bg-gray-50 px-2 py-1 text-xs">
                            {{ item.name || item.title || item.description }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="insight.priority" class="ml-3">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium" :class="priorityClass">
                    {{ insight.priority }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AlertTriangleIcon, CheckCircleIcon, InfoIcon, XCircleIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Insight {
    id?: string;
    type: 'success' | 'warning' | 'info' | 'error';
    title: string;
    description: string;
    data?: any[];
    priority?: 'high' | 'medium' | 'low';
}

interface Props {
    insight: Insight;
}

const props = defineProps<Props>();

const cardClass = computed(() => {
    switch (props.insight.type) {
        case 'success':
            return 'border-green-200 bg-green-50';
        case 'warning':
            return 'border-yellow-200 bg-yellow-50';
        case 'error':
            return 'border-red-200 bg-red-50';
        case 'info':
        default:
            return 'border-blue-200 bg-blue-50';
    }
});

const iconComponent = computed(() => {
    switch (props.insight.type) {
        case 'success':
            return CheckCircleIcon;
        case 'warning':
            return AlertTriangleIcon;
        case 'error':
            return XCircleIcon;
        case 'info':
        default:
            return InfoIcon;
    }
});

const iconClass = computed(() => {
    switch (props.insight.type) {
        case 'success':
            return 'text-green-600';
        case 'warning':
            return 'text-yellow-600';
        case 'error':
            return 'text-red-600';
        case 'info':
        default:
            return 'text-blue-600';
    }
});

const priorityClass = computed(() => {
    switch (props.insight.priority) {
        case 'high':
            return 'bg-red-100 text-red-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
});
</script>

<style scoped>
.insight-card {
    @apply transition-all duration-200 hover:shadow-md;
}
</style>
