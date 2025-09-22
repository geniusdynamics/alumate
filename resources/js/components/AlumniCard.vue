<template>
    <div class="alumni-card overflow-hidden rounded-lg bg-white shadow-md transition-shadow duration-200 hover:shadow-lg">
        <!-- Header with Avatar and Basic Info -->
        <div class="card-header p-6 pb-4">
            <div class="flex items-start space-x-4">
                <div class="avatar-container flex-shrink-0">
                    <img
                        :src="alumni.avatar_url || '/images/default-avatar.png'"
                        :alt="alumni.name"
                        class="h-16 w-16 rounded-full border-2 border-gray-200 object-cover"
                    />
                    <div v-if="isOnline" class="online-indicator absolute -mr-1 -mt-3 h-4 w-4 rounded-full border-2 border-white bg-green-400"></div>
                </div>

                <div class="alumni-info min-w-0 flex-1">
                    <h3 class="truncate text-lg font-semibold text-gray-900">
                        {{ alumni.name }}
                    </h3>

                    <p v-if="currentPosition" class="truncate text-sm text-gray-600">
                        {{ currentPosition.title }}
                        <span v-if="currentPosition.company" class="text-gray-500"> at {{ currentPosition.company }} </span>
                    </p>

                    <p v-if="alumni.location" class="mt-1 flex items-center text-sm text-gray-500">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ alumni.location }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Education Info -->
        <div v-if="primaryEducation" class="education-info px-6 pb-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"
                    />
                </svg>
                <span>{{ primaryEducation.institution?.name ?? 'Unknown Institution' }}</span>
                <span v-if="primaryEducation.graduation_year" class="ml-2 text-gray-500">
                    '{{ primaryEducation.graduation_year.toString().slice(-2) }}
                </span>
            </div>
        </div>

        <!-- Shared Connections -->
        <div v-if="sharedConnections.length > 0" class="shared-connections px-6 pb-4">
            <div class="flex items-center text-sm text-gray-600">
                <div class="mr-3 flex -space-x-2">
                    <img
                        v-for="connection in sharedConnections.slice(0, 3)"
                        :key="connection.id"
                        :src="connection.avatar_url || '/images/default-avatar.png'"
                        :alt="connection.name"
                        class="h-6 w-6 rounded-full border-2 border-white object-cover"
                    />
                </div>
                <span> {{ sharedConnections.length }} mutual connection{{ sharedConnections.length !== 1 ? 's' : '' }} </span>
            </div>
        </div>

        <!-- Skills Tags -->
        <div v-if="displaySkills.length > 0" class="skills-section px-6 pb-4">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in displaySkills"
                    :key="skill"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="remainingSkillsCount > 0"
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                >
                    +{{ remainingSkillsCount }} more
                </span>
            </div>
        </div>

        <!-- Shared Circles/Groups -->
        <div v-if="sharedCommunities.length > 0" class="shared-communities px-6 pb-4">
            <div class="text-sm text-gray-600">
                <span class="font-medium">Shared communities:</span>
                <span class="ml-1">
                    {{
                        sharedCommunities
                            .slice(0, 2)
                            .map((c) => c.name ?? 'Unnamed Community')
                            .join(', ')
                    }}
                    <span v-if="sharedCommunities.length > 2" class="text-gray-500"> +{{ sharedCommunities.length - 2 }} more </span>
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card-actions border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex space-x-3">
                <button
                    @click="$emit('view-profile', alumni)"
                    class="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    View Profile
                </button>

                <button
                    v-if="canConnect"
                    @click="$emit('connect', alumni)"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Connect
                </button>

                <button
                    v-else-if="connectionStatus === 'pending'"
                    disabled
                    class="flex-1 cursor-not-allowed rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500"
                >
                    Request Sent
                </button>

                <button
                    v-else-if="connectionStatus === 'accepted'"
                    disabled
                    class="flex-1 cursor-not-allowed rounded-md bg-green-100 px-4 py-2 text-sm font-medium text-green-700"
                >
                    Connected
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AlumniProfile } from '@/Types';
import { computed } from 'vue';

// Props
interface Props {
    alumni: AlumniProfile;
}

const props = defineProps<Props>();

// Emits
defineEmits<{
    'view-profile': [alumni: AlumniProfile];
    connect: [alumni: AlumniProfile];
}>();

// Computed properties with proper null safety
const currentPosition = computed(() => {
    if (!props.alumni.work_experiences?.length) {
        return null;
    }

    return props.alumni.work_experiences.find((exp) => exp.is_current) ?? props.alumni.work_experiences[0] ?? null;
});

const primaryEducation = computed(() => {
    if (!props.alumni.educations?.length) {
        return null;
    }

    // Return the most recent education or the first one
    return props.alumni.educations.sort((a, b) => (b.graduation_year ?? 0) - (a.graduation_year ?? 0))[0] ?? null;
});

const displaySkills = computed(() => {
    return props.alumni.skills?.slice(0, 3) ?? [];
});

const remainingSkillsCount = computed(() => {
    const skillsLength = props.alumni.skills?.length ?? 0;
    return Math.max(0, skillsLength - 3);
});

const sharedConnections = computed(() => {
    return props.alumni.mutual_connections ?? [];
});

const sharedCommunities = computed(() => {
    const communities = [];

    if (props.alumni.shared_circles?.length) {
        communities.push(...props.alumni.shared_circles);
    }

    if (props.alumni.shared_groups?.length) {
        communities.push(...props.alumni.shared_groups);
    }

    return communities;
});

const connectionStatus = computed(() => {
    // Type assertion needed if this property doesn't exist in AlumniProfile interface
    return (props.alumni as any).connection_status ?? 'none';
});

const canConnect = computed(() => {
    return connectionStatus.value === 'none';
});

const isOnline = computed(() => {
    // Type assertion needed if this property doesn't exist in AlumniProfile interface
    return (props.alumni as any).is_online ?? false;
});
</script>

<style scoped>
.alumni-card {
    @apply relative;
}

.avatar-container {
    @apply relative;
}

.online-indicator {
    position: absolute;
    top: 0;
    right: 0;
}

.card-actions button:disabled {
    @apply cursor-not-allowed opacity-60;
}

.skills-section .inline-flex {
    @apply transition-colors duration-150;
}

.shared-connections img {
    box-shadow: 0 0 0 2px white;
}
</style>


