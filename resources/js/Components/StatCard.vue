<template>
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <component :is="iconComponent" class="h-6 w-6" :class="iconColorClass" />
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">{{ title }}</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">{{ formattedValue }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div v-if="change" class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span :class="changeColorClass">{{ change }}</span>
                <span class="text-gray-600"> from last period</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    AcademicCapIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    CheckCircleIcon,
    ClockIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    EyeIcon,
    ShieldCheckIcon,
    UsersIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    icon: {
        type: String,
        default: 'ChartBarIcon',
    },
    color: {
        type: String,
        default: 'blue',
    },
    change: {
        type: String,
        default: null,
    },
});

const iconComponents = {
    BuildingOfficeIcon,
    UsersIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    ClockIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
};

const iconComponent = computed(() => {
    return iconComponents[props.icon] || ChartBarIcon;
});

const iconColorClass = computed(() => {
    const colors = {
        blue: 'text-blue-400',
        green: 'text-green-400',
        yellow: 'text-yellow-400',
        red: 'text-red-400',
        purple: 'text-purple-400',
        indigo: 'text-indigo-400',
        pink: 'text-pink-400',
        gray: 'text-gray-400',
    };
    return colors[props.color] || colors.blue;
});

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString();
    }
    return props.value;
});

const changeColorClass = computed(() => {
    if (!props.change) return '';

    if (props.change.startsWith('+')) {
        return 'text-green-600';
    } else if (props.change.startsWith('-')) {
        return 'text-red-600';
    }
    return 'text-gray-600';
});
</script>

