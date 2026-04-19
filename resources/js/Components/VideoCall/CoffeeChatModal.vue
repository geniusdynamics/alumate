<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-md rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between border-b p-6">
                <h3 class="text-lg font-semibold text-gray-900">Request Coffee Chat</h3>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitRequest" class="p-6">
                <div class="mb-4">
                    <label for="topic" class="mb-2 block text-sm font-medium text-gray-700"> What would you like to discuss? </label>
                    <textarea
                        id="topic"
                        v-model="form.topic"
                        rows="3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Career advice, industry insights, networking tips..."
                        required
                    ></textarea>
                </div>

                <div class="mb-4">
                    <label for="duration" class="mb-2 block text-sm font-medium text-gray-700"> Preferred Duration </label>
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
                    </select>
                </div>

                <div class="mb-4">
                    <label for="timePreference" class="mb-2 block text-sm font-medium text-gray-700"> Time Preference </label>
                    <select
                        id="timePreference"
                        v-model="form.timePreference"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                        <option value="">Select time preference</option>
                        <option value="morning">Morning (9 AM - 12 PM)</option>
                        <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                        <option value="evening">Evening (5 PM - 8 PM)</option>
                        <option value="flexible">I'm flexible</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="message" class="mb-2 block text-sm font-medium text-gray-700"> Additional Message (Optional) </label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="2"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Any additional context or questions..."
                    ></textarea>
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
                        {{ isSubmitting ? 'Sending...' : 'Send Request' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';

interface Props {
    isOpen: boolean;
    recipientId: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    submit: [data: any];
}>();

const isSubmitting = ref(false);

const form = reactive({
    topic: '',
    duration: '',
    timePreference: '',
    message: '',
});

const close = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    form.topic = '';
    form.duration = '';
    form.timePreference = '';
    form.message = '';
};

const submitRequest = async () => {
    isSubmitting.value = true;

    try {
        const requestData = {
            recipientId: props.recipientId,
            topic: form.topic,
            duration: parseInt(form.duration),
            timePreference: form.timePreference,
            message: form.message,
            type: 'coffee-chat',
        };

        emit('submit', requestData);
        close();
    } catch (error) {
        console.error('Error submitting coffee chat request:', error);
    } finally {
        isSubmitting.value = false;
    }
};
</script>
