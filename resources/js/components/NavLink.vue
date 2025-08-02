<template>
    <Link
        :href="href"
        :class="[
            'flex items-center space-x-3 px-3 py-2 rounded-md text-sm font-medium transition-colors',
            active
                ? 'bg-gray-700 text-white'
                : 'text-gray-300 hover:text-white hover:bg-gray-700'
        ]"
    >
        <component :is="iconComponent" class="w-5 h-5" />
        <span><slot /></span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    HomeIcon,
    ChartBarIcon,
    UsersIcon,
    DocumentTextIcon,
    BuildingOfficeIcon,
    CogIcon,
    BellIcon,
    ShieldCheckIcon,
    ChartPieIcon,
    CircleStackIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    UserGroupIcon,
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    BookOpenIcon,
    TrophyIcon,
    HeartIcon,
    GiftIcon,
    StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    href: {
        type: String,
        required: true
    },
    active: {
        type: Boolean,
        default: false
    },
    icon: {
        type: String,
        default: 'HomeIcon'
    }
})

const iconMap = {
    HomeIcon,
    ChartBarIcon,
    UsersIcon,
    DocumentTextIcon,
    BuildingOfficeIcon,
    CogIcon,
    BellIcon,
    ShieldCheckIcon,
    ChartPieIcon,
    CircleStackIcon,
    DatabaseIcon: CircleStackIcon, // Alias for backward compatibility
    AcademicCapIcon,
    BriefcaseIcon,
    UserGroupIcon,
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    BookOpenIcon,
    TrophyIcon,
    HeartIcon,
    GiftIcon,
    StarIcon
}

const iconComponent = computed(() => {
    return iconMap[props.icon] || HomeIcon
})
</script>
