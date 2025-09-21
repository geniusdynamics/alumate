<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b p-6">
                <h3 class="text-lg font-semibold text-gray-900">Schedule Call</h3>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitSchedule" class="p-6">
                <div class="mb-4">
                    <label for="title" class="mb-2 block text-sm font-medium text-gray-700"> Call Title </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Career Discussion, Project Review"
                        required
                    />
                </div>

                <div class="mb-4 grid grid-cols-2 gap-4">
                    <div>
                        <label for="date" class="mb-2 block text-sm font-medium text-gray-700"> Date </label>
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            :min="minDate"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>
                    <div>
                        <label for="time" class="mb-2 block text-sm font-medium text-gray-700"> Time </label>
                        <input
                            id="time"
                            v-model="form.time"
                            type="time"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>
                </div>

                <div class="mb-4">
                    <label for="duration" class="mb-2 block text-sm font-medium text-gray-700"> Duration </label>
                    <select
                        id="duration"
                        v-model="form.duration"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select duration</option>
                        <option value="15">15 minutes</option>
                        <option value="30">30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">60 minutes</option>
                        <option value="90">90 minutes</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="type" class="mb-2 block text-sm font-medium text-gray-700"> Call Type </label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select type</option>
                        <option value="mentorship">Mentorship</option>
                        <option value="career-advice">Career Advice</option>
                        <option value="networking">Networking</option>
                        <option value="project-discussion">Project Discussion</option>
                        <option value="interview-prep">Interview Preparation</option>
                        <option value="coffee-chat">Coffee Chat</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="agenda" class="mb-2 block text-sm font-medium text-gray-700"> Agenda/Topics to Discuss </label>
                    <textarea
                        id="agenda"
                        v-model="form.agenda"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="What would you like to discuss during this call?"
                        required
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label for="timezone" class="mb-2 block text-sm font-medium text-gray-700"> Timezone </label>
                    <select
                        id="timezone"
                        v-model="form.timezone"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select timezone</option>
                        <option value="America/New_York">Eastern Time (ET)</option>
                        <option value="America/Chicago">Central Time (CT)</option>
                        <option value="America/Denver">Mountain Time (MT)</option>
                        <option value="America/Los_Angeles">Pacific Time (PT)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input
                            v-model="form.sendReminder"
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <span class="ml-2 text-sm text-gray-700">Send reminder 24 hours before the call</span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" @click="close" class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-300">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="rounded-md bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ isSubmitting ? 'Scheduling...' : 'Schedule Call' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

interface Props {
    isOpen: boolean;
    participantId?: number;
    requestId?: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    submit: [data: any];
}>();

const isSubmitting = ref(false);

const form = reactive({
    title: '',
    date: '',
    time: '',
    duration: '',
    type: '',
    agenda: '',
    timezone: 'America/New_York',
    sendReminder: true,
});

const minDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
});

const close = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    form.title = '';
    form.date = '';
    form.time = '';
    form.duration = '';
    form.type = '';
    form.agenda = '';
    form.timezone = 'America/New_York';
    form.sendReminder = true;
};

const submitSchedule = async () => {
    isSubmitting.value = true;

    try {
        const scheduleData = {
            participantId: props.participantId,
            requestId: props.requestId,
            title: form.title,
            scheduledAt: `${form.date}T${form.time}:00`,
            duration: parseInt(form.duration),
            type: form.type,
            agenda: form.agenda,
            timezone: form.timezone,
            sendReminder: form.sendReminder,
        };

        emit('submit', scheduleData);
        close();
    } catch (error) {
        console.error('Error scheduling call:', error);
    } finally {
        isSubmitting.value = false;
    }
};
</script>
