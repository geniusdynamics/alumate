<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle dark:bg-gray-800"
            >
                <form @submit.prevent="saveMentorProfile">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-gray-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white" id="modal-title">Become a Mentor</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Share your expertise and help fellow alumni grow in their careers
                                </p>

                                <div class="mt-6 space-y-6">
                                    <!-- Bio -->
                                    <div>
                                        <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Bio * </label>
                                        <textarea
                                            id="bio"
                                            v-model="form.bio"
                                            rows="4"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Tell potential mentees about your background, experience, and what you can offer as a mentor..."
                                        ></textarea>
                                        <div v-if="errors.bio" class="mt-1 text-sm text-red-600">
                                            {{ errors.bio }}
                                        </div>
                                    </div>

                                    <!-- Expertise Areas -->
                                    <div>
                                        <label for="expertise_areas" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Areas of Expertise *
                                        </label>
                                        <div class="mt-2 space-y-2">
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="(area, index) in form.expertise_areas"
                                                    :key="index"
                                                    class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                                >
                                                    {{ area }}
                                                    <button
                                                        type="button"
                                                        @click="removeExpertiseArea(index)"
                                                        class="ml-2 text-blue-600 hover:text-blue-800"
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                            </div>
                                            <div class="flex space-x-2">
                                                <input
                                                    v-model="newExpertiseArea"
                                                    type="text"
                                                    placeholder="Add expertise area"
                                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    @keyup.enter="addExpertiseArea"
                                                />
                                                <button
                                                    type="button"
                                                    @click="addExpertiseArea"
                                                    class="rounded-md bg-blue-600 px-3 py-2 text-white transition-colors hover:bg-blue-700"
                                                >
                                                    Add
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="errors.expertise_areas" class="mt-1 text-sm text-red-600">
                                            {{ errors.expertise_areas }}
                                        </div>
                                    </div>

                                    <!-- Years of Experience -->
                                    <div>
                                        <label for="years_experience" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Years of Experience *
                                        </label>
                                        <select
                                            id="years_experience"
                                            v-model="form.years_experience"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">Select experience level</option>
                                            <option value="1-3">1-3 years</option>
                                            <option value="4-7">4-7 years</option>
                                            <option value="8-12">8-12 years</option>
                                            <option value="13-20">13-20 years</option>
                                            <option value="20+">20+ years</option>
                                        </select>
                                        <div v-if="errors.years_experience" class="mt-1 text-sm text-red-600">
                                            {{ errors.years_experience }}
                                        </div>
                                    </div>

                                    <!-- Mentoring Preferences -->
                                    <div>
                                        <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300"> Mentoring Preferences </label>
                                        <div class="space-y-3">
                                            <div>
                                                <label for="max_mentees" class="block text-sm text-gray-600 dark:text-gray-400">
                                                    Maximum number of mentees
                                                </label>
                                                <select
                                                    id="max_mentees"
                                                    v-model="form.max_mentees"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                >
                                                    <option value="1">1 mentee</option>
                                                    <option value="2">2 mentees</option>
                                                    <option value="3">3 mentees</option>
                                                    <option value="5">5 mentees</option>
                                                    <option value="10">10 mentees</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm text-gray-600 dark:text-gray-400"> Preferred session types </label>
                                                <div class="space-y-2">
                                                    <label class="flex items-center">
                                                        <input
                                                            v-model="form.session_types"
                                                            type="checkbox"
                                                            value="video_call"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                        />
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Video calls</span>
                                                    </label>
                                                    <label class="flex items-center">
                                                        <input
                                                            v-model="form.session_types"
                                                            type="checkbox"
                                                            value="phone_call"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                        />
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Phone calls</span>
                                                    </label>
                                                    <label class="flex items-center">
                                                        <input
                                                            v-model="form.session_types"
                                                            type="checkbox"
                                                            value="in_person"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                        />
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">In-person meetings</span>
                                                    </label>
                                                    <label class="flex items-center">
                                                        <input
                                                            v-model="form.session_types"
                                                            type="checkbox"
                                                            value="messaging"
                                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                        />
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Text messaging</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Availability -->
                                    <div>
                                        <label for="availability" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Availability
                                        </label>
                                        <textarea
                                            id="availability"
                                            v-model="form.availability"
                                            rows="2"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="e.g., Weekday evenings, Weekend mornings, Flexible schedule..."
                                        ></textarea>
                                        <div v-if="errors.availability" class="mt-1 text-sm text-red-600">
                                            {{ errors.availability }}
                                        </div>
                                    </div>

                                    <!-- Accepting New Mentees -->
                                    <div>
                                        <label class="flex items-center">
                                            <input
                                                v-model="form.accepting_mentees"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"> I'm currently accepting new mentees </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-700">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ processing ? 'Creating Profile...' : 'Become a Mentor' }}
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm dark:border-gray-500 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const emit = defineEmits(['close', 'saved']);

const processing = ref(false);
const errors = ref({});
const newExpertiseArea = ref('');

const form = reactive({
    bio: '',
    expertise_areas: [],
    years_experience: '',
    max_mentees: 3,
    session_types: [],
    availability: '',
    accepting_mentees: true,
});

const addExpertiseArea = () => {
    if (newExpertiseArea.value.trim() && !form.expertise_areas.includes(newExpertiseArea.value.trim())) {
        form.expertise_areas.push(newExpertiseArea.value.trim());
        newExpertiseArea.value = '';
    }
};

const removeExpertiseArea = (index) => {
    form.expertise_areas.splice(index, 1);
};

const saveMentorProfile = async () => {
    processing.value = true;
    errors.value = {};

    try {
        await router.post(route('api.mentorship.become-mentor'), form, {
            preserveState: true,
            onSuccess: () => {
                emit('saved');
            },
            onError: (responseErrors) => {
                errors.value = responseErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } catch (error) {
        console.error('Error creating mentor profile:', error);
        processing.value = false;
    }
};
</script>
