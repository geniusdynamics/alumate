<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 max-h-[80vh] w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b p-6">
                <h3 class="text-lg font-semibold text-gray-900">Conversation Participants ({{ participants.length }})</h3>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Add Participants Section -->
                <div v-if="canAddParticipants" class="mb-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900">Add Participants</h4>
                        <button @click="showAddForm = !showAddForm" class="text-sm text-blue-600 hover:text-blue-800">
                            {{ showAddForm ? 'Cancel' : 'Add People' }}
                        </button>
                    </div>

                    <div v-if="showAddForm" class="space-y-3">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search people to add..."
                                class="w-full rounded-md border border-gray-300 py-2 pl-10 pr-4 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @input="searchPeople"
                            />
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    ></path>
                                </svg>
                            </div>
                        </div>

                        <div v-if="searchResults.length > 0" class="max-h-32 overflow-y-auto rounded-md border border-gray-200">
                            <div
                                v-for="person in searchResults"
                                :key="person.id"
                                class="flex cursor-pointer items-center justify-between p-3 hover:bg-gray-50"
                                @click="addParticipant(person)"
                            >
                                <div class="flex items-center">
                                    <img :src="person.avatar || '/default-avatar.png'" :alt="person.name" class="mr-3 h-8 w-8 rounded-full" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ person.name }}</p>
                                        <p class="text-xs text-gray-500">{{ person.title || person.email }}</p>
                                    </div>
                                </div>
                                <button class="text-blue-600 hover:text-blue-800">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Participants List -->
                <div>
                    <h4 class="mb-3 text-sm font-medium text-gray-900">Current Participants</h4>
                    <div class="max-h-64 space-y-3 overflow-y-auto">
                        <div
                            v-for="participant in participants"
                            :key="participant.id"
                            class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                        >
                            <div class="flex items-center">
                                <div class="relative">
                                    <img :src="participant.avatar || '/default-avatar.png'" :alt="participant.name" class="h-10 w-10 rounded-full" />
                                    <div
                                        v-if="participant.is_online"
                                        class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-400"
                                    ></div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ participant.name }}
                                        <span v-if="participant.is_admin" class="ml-1 text-xs text-blue-600">(Admin)</span>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ participant.title || participant.email }}
                                    </p>
                                    <p v-if="participant.last_seen" class="text-xs text-gray-400">
                                        Last seen {{ formatLastSeen(participant.last_seen) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <!-- Role Management -->
                                <div v-if="canManageRoles && participant.id !== currentUserId" class="relative">
                                    <button @click="toggleRoleMenu(participant.id)" class="text-gray-400 hover:text-gray-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                            ></path>
                                        </svg>
                                    </button>

                                    <div
                                        v-if="activeRoleMenu === participant.id"
                                        class="absolute right-0 z-10 mt-2 w-48 rounded-md border bg-white shadow-lg"
                                    >
                                        <div class="py-1">
                                            <button
                                                @click="toggleAdminRole(participant)"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                {{ participant.is_admin ? 'Remove Admin' : 'Make Admin' }}
                                            </button>
                                            <button
                                                @click="removeParticipant(participant)"
                                                class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100"
                                            >
                                                Remove from conversation
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remove Participant -->
                                <button
                                    v-else-if="canRemoveParticipants && participant.id !== currentUserId"
                                    @click="removeParticipant(participant)"
                                    class="text-red-400 hover:text-red-600"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 border-t bg-gray-50 p-6">
                <button @click="close" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface Participant {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    title?: string;
    is_online: boolean;
    is_admin: boolean;
    last_seen?: string;
}

interface Person {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    title?: string;
}

interface Props {
    isOpen: boolean;
    conversationId: number;
    participants: Participant[];
    currentUserId: number;
    canAddParticipants?: boolean;
    canRemoveParticipants?: boolean;
    canManageRoles?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canAddParticipants: false,
    canRemoveParticipants: false,
    canManageRoles: false,
});

const emit = defineEmits<{
    close: [];
    addParticipant: [person: Person];
    removeParticipant: [participant: Participant];
    toggleAdminRole: [participant: Participant];
}>();

const showAddForm = ref(false);
const searchQuery = ref('');
const searchResults = ref<Person[]>([]);
const activeRoleMenu = ref<number | null>(null);

const close = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    showAddForm.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    activeRoleMenu.value = null;
};

const searchPeople = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    try {
        const response = await fetch('/api/users/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                query: searchQuery.value,
                exclude_conversation: props.conversationId,
            }),
        });

        const data = await response.json();

        if (data.success) {
            searchResults.value = data.data;
        }
    } catch (error) {
        console.error('Error searching people:', error);
    }
};

const addParticipant = (person: Person) => {
    emit('addParticipant', person);
    searchQuery.value = '';
    searchResults.value = [];
    showAddForm.value = false;
};

const removeParticipant = (participant: Participant) => {
    if (confirm(`Are you sure you want to remove ${participant.name} from this conversation?`)) {
        emit('removeParticipant', participant);
    }
    activeRoleMenu.value = null;
};

const toggleAdminRole = (participant: Participant) => {
    const action = participant.is_admin ? 'remove admin privileges from' : 'make admin';
    if (confirm(`Are you sure you want to ${action} ${participant.name}?`)) {
        emit('toggleAdminRole', participant);
    }
    activeRoleMenu.value = null;
};

const toggleRoleMenu = (participantId: number) => {
    activeRoleMenu.value = activeRoleMenu.value === participantId ? null : participantId;
};

const formatLastSeen = (lastSeen: string): string => {
    const date = new Date(lastSeen);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) {
        return 'just now';
    } else if (diffInMinutes < 60) {
        return `${diffInMinutes}m ago`;
    } else if (diffInMinutes < 1440) {
        const hours = Math.floor(diffInMinutes / 60);
        return `${hours}h ago`;
    } else {
        const days = Math.floor(diffInMinutes / 1440);
        return `${days}d ago`;
    }
};

// Close role menu when clicking outside
document.addEventListener('click', (event) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.relative')) {
        activeRoleMenu.value = null;
    }
});
</script>
