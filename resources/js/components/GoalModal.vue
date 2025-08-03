<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                aria-hidden="true"
                @click="$emit('close')"
            ></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveGoal">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    {{ goal?.id ? 'Edit Goal' : 'Add New Goal' }}
                                </h3>
                                
                                <div class="mt-6 space-y-4">
                                    <!-- Goal Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Goal Title *
                                        </label>
                                        <input
                                            id="title"
                                            v-model="form.title"
                                            type="text"
                                            required
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                            placeholder="e.g., Get promoted to Senior Developer"
                                        />
                                        <div v-if="errors.title" class="mt-1 text-sm text-red-600">
                                            {{ errors.title }}
                                        </div>
                                    </div>

                                    <!-- Goal Description -->
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Description
                                        </label>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                            placeholder="Describe your goal and what achieving it means to you..."
                                        ></textarea>
                                        <div v-if="errors.description" class="mt-1 text-sm text-red-600">
                                            {{ errors.description }}
                                        </div>
                                    </div>

                                    <!-- Goal Category -->
                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Category *
                                        </label>
                                        <select
                                            id="category"
                                            v-model="form.category"
                                            required
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        >
                                            <option value="">Select a category</option>
                                            <option value="career_advancement">Career Advancement</option>
                                            <option value="skill_development">Skill Development</option>
                                            <option value="networking">Networking</option>
                                            <option value="compensation">Compensation</option>
                                            <option value="education">Education</option>
                                            <option value="entrepreneurship">Entrepreneurship</option>
                                            <option value="giving_back">Giving Back</option>
                                            <option value="personal_growth">Personal Growth</option>
                                        </select>
                                        <div v-if="errors.category" class="mt-1 text-sm text-red-600">
                                            {{ errors.category }}
                                        </div>
                                    </div>

                                    <!-- Target Date -->
                                    <div>
                                        <label for="target_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Target Date
                                        </label>
                                        <input
                                            id="target_date"
                                            v-model="form.target_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        />
                                        <div v-if="errors.target_date" class="mt-1 text-sm text-red-600">
                                            {{ errors.target_date }}
                                        </div>
                                    </div>

                                    <!-- Priority Level -->
                                    <div>
                                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Priority Level
                                        </label>
                                        <select
                                            id="priority"
                                            v-model="form.priority"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        >
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                        <div v-if="errors.priority" class="mt-1 text-sm text-red-600">
                                            {{ errors.priority }}
                                        </div>
                                    </div>

                                    <!-- Measurable Outcome -->
                                    <div>
                                        <label for="success_criteria" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Success Criteria
                                        </label>
                                        <textarea
                                            id="success_criteria"
                                            v-model="form.success_criteria"
                                            rows="2"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                            placeholder="How will you know when you've achieved this goal?"
                                        ></textarea>
                                        <div v-if="errors.success_criteria" class="mt-1 text-sm text-red-600">
                                            {{ errors.success_criteria }}
                                        </div>
                                    </div>

                                    <!-- Initial Progress -->
                                    <div v-if="!goal?.id">
                                        <label for="progress" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Initial Progress (%)
                                        </label>
                                        <input
                                            id="progress"
                                            v-model.number="form.progress"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        />
                                        <div v-if="errors.progress" class="mt-1 text-sm text-red-600">
                                            {{ errors.progress }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ processing ? 'Saving...' : (goal?.id ? 'Update Goal' : 'Create Goal') }}
                        </button>
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500"
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
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    goal: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'saved'])

const processing = ref(false)
const errors = ref({})

const form = reactive({
    title: props.goal?.title || '',
    description: props.goal?.description || '',
    category: props.goal?.category || '',
    target_date: props.goal?.target_date || '',
    priority: props.goal?.priority || 'medium',
    success_criteria: props.goal?.success_criteria || '',
    progress: props.goal?.progress || 0
})

const saveGoal = async () => {
    processing.value = true
    errors.value = {}

    try {
        const url = props.goal?.id 
            ? route('api.career.goals.update', props.goal.id)
            : route('api.career.goals.store')
        
        const method = props.goal?.id ? 'put' : 'post'

        await router[method](url, form, {
            preserveState: true,
            onSuccess: () => {
                emit('saved')
            },
            onError: (responseErrors) => {
                errors.value = responseErrors
            },
            onFinish: () => {
                processing.value = false
            }
        })
    } catch (error) {
        console.error('Error saving goal:', error)
        processing.value = false
    }
}
</script>
