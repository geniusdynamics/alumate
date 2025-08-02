<template>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div :class="iconBgClass" class="p-3 rounded-lg">
                    <component :is="iconComponent" class="h-6 w-6 text-white" />
                </div>
            </div>
            <div class="ml-4 flex-1">
                <div class="text-2xl font-bold text-white">{{ formattedValue }}</div>
                <div class="text-sm text-gray-400">{{ title }}</div>
                <div v-if="change" class="flex items-center mt-1">
                    <span :class="changeColorClass" class="text-sm font-medium">{{ change }}</span>
                    <span class="text-xs text-gray-500 ml-1">vs last month</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
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
} from '@heroicons/vue/24/outline'

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
})

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
}

const iconComponent = computed(() => {
    return iconComponents[props.icon] || ChartBarIcon
})

const iconBgClass = computed(() => {
    const colors = {
        blue: 'bg-blue-500',
        green: 'bg-green-500',
        yellow: 'bg-yellow-500',
        red: 'bg-red-500',
        purple: 'bg-purple-500',
        indigo: 'bg-indigo-500',
        pink: 'bg-pink-500',
        gray: 'bg-gray-500',
    }
    return colors[props.color] || colors.blue
})

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString()
    }
    return props.value
})

const changeColorClass = computed(() => {
    if (!props.change) return ''
    
    if (props.change.startsWith('+')) {
        return 'text-green-400'
    } else if (props.change.startsWith('-')) {
        return 'text-red-400'
    }
    return 'text-gray-400'
})
</script>
