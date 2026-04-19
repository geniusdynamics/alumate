<template>
    <Link
        :href="href"
        :class="[
            'flex items-center space-x-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
            active ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white',
        ]"
    >
        <component :is="iconComponent" class="h-5 w-5" />
        <span><slot /></span>
    </Link>
</template>

<script setup lang="ts">
import {
    AcademicCapIcon,
    BellIcon,
    BookOpenIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ChartBarIcon,
    ChartPieIcon,
    ChatBubbleLeftRightIcon,
    CircleStackIcon,
    CogIcon,
    DocumentTextIcon,
    GiftIcon,
    HeartIcon,
    HomeIcon,
    ShieldCheckIcon,
    StarIcon,
    TrophyIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: 'HomeIcon',
    },
});

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
    StarIcon,
};

const iconComponent = computed(() => {
    return iconMap[props.icon] || HomeIcon;
});
</script>

