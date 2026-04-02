<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="mb-4 flex items-start justify-between">
            <div class="flex items-start space-x-4">
                <!-- Company logo -->
                <div class="flex-shrink-0">
                    <img
                        v-if="entry.company_logo_url"
                        :src="entry.company_logo_url"
                        :alt="entry.company"
                        class="h-12 w-12 rounded-lg border border-gray-200 object-cover"
                    />
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-lg border border-gray-200 bg-gray-100">
                        <BriefcaseIcon class="h-6 w-6 text-gray-400" />
                    </div>
                </div>

                <!-- Position info -->
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">{{ entry.title }}</h3>
                    <p class="text-base font-medium text-gray-700">{{ entry.company }}</p>
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                        <span class="flex items-center">
                            <CalendarIcon class="mr-1 h-4 w-4" />
                            {{ formatDateRange(entry.start_date, entry.end_date, entry.is_current) }}
                        </span>
                        <span class="flex items-center">
                            <ClockIcon class="mr-1 h-4 w-4" />
                            {{ entry.formatted_duration }}
                        </span>
                        <span v-if="entry.location" class="flex items-center">
                            <MapPinIcon class="mr-1 h-4 w-4" />
                            {{ entry.location }}
                        </span>
                    </div>
                    <div v-if="entry.employment_type" class="mt-1">
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                            {{ formatEmploymentType(entry.employment_type) }}
                        </span>
                        <span
                            v-if="entry.is_current"
                            class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                        >
                            Current
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div v-if="canEdit" class="flex items-center space-x-2">
                <button @click="$emit('edit', entry)" class="p-2 text-gray-400 transition-colors hover:text-gray-600" title="Edit position">
                    <PencilIcon class="h-4 w-4" />
                </button>
                <button @click="$emit('delete', entry.id)" class="p-2 text-gray-400 transition-colors hover:text-red-600" title="Delete position">
                    <TrashIcon class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Description -->
        <div v-if="entry.description" class="mb-4">
            <p class="leading-relaxed text-gray-700">{{ entry.description }}</p>
        </div>

        <!-- Achievements -->
        <div v-if="entry.achievements && entry.achievements.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900">Key Achievements</h4>
            <ul class="space-y-1">
                <li v-for="(achievement, index) in entry.achievements" :key="index" class="flex items-start text-sm text-gray-700">
                    <span class="mr-2 mt-0.5 text-green-500">â€¢</span>
                    <span>{{ achievement }}</span>
                </li>
            </ul>
        </div>

        <!-- Industry tag -->
        <div v-if="entry.industry" class="flex items-center justify-between">
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                {{ entry.industry }}
            </span>

            <!-- Promotion indicator -->
            <div v-if="isPromotion" class="flex items-center text-xs text-green-600">
                <TrendingUpIcon class="mr-1 h-4 w-4" />
                Promotion
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    BriefcaseIcon,
    CalendarIcon,
    ClockIcon,
    MapPinIcon,
    PencilIcon,
    TrashIcon,
    ArrowTrendingUpIcon as TrendingUpIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    entry: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    previousEntry: {
        type: Object,
        default: null,
    },
});

defineEmits(['edit', 'delete']);

// Computed
const isPromotion = computed(() => {
    if (!props.previousEntry) return false;

    return props.entry.company === props.previousEntry.company && new Date(props.entry.start_date) >= new Date(props.previousEntry.start_date);
});

// Methods
const formatDateRange = (startDate, endDate, isCurrent) => {
    const start = new Date(startDate).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });

    if (isCurrent) {
        return `${start} - Present`;
    }

    if (!endDate) {
        return start;
    }

    const end = new Date(endDate).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });

    return `${start} - ${end}`;
};

const formatEmploymentType = (type) => {
    const types = {
        'full-time': 'Full-time',
        'part-time': 'Part-time',
        contract: 'Contract',
        internship: 'Internship',
        freelance: 'Freelance',
    };

    return types[type] || type;
};
</script>

