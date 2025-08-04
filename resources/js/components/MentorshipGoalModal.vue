<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                aria-hidden="true"
                @click="closeModal"
            ></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Modal header -->
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <TrophyIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" aria-hidden="true" />
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                {{ isEditing ? 'Edit Goal' : 'Create New Goal' }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ isEditing ? 'Update your mentorship goal details' : 'Set a new goal for your mentorship journey' }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            class="ml-4 bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                </div>

                <!-- Modal body -->
                <form @submit.prevent="submitForm" class="px-4 pb-4 sm:px-6 sm:pb-6">
                    <div class="space-y-4">
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
                                placeholder="e.g., Improve public speaking skills"
                            />
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
                                placeholder="Describe what you want to achieve..."
                            ></textarea>
                        </div>

                        <!-- Goal Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Category
                            </label>
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                            >
                                <option value="">Select a category</option>
                                <option value="career_development">Career Development</option>
                                <option value="technical_skills">Technical Skills</option>
                                <option value="soft_skills">Soft Skills</option>
                                <option value="leadership">Leadership</option>
                                <option value="networking">Networking</option>
                                <option value="personal_growth">Personal Growth</option>
                                <option value="industry_knowledge">Industry Knowledge</option>
                            </select>
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
                            </select>
                        </div>

                        <!-- Skills Focus -->
                        <div>
                            <label for="skills_focus" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Skills Focus
                            </label>
                            <input
                                id="skills_focus"
                                v-model="skillsInput"
                                type="text"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="Enter skills separated by commas"
                                @input="updateSkillsFocus"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Separate multiple skills with commas
                            </p>
                            <div v-if="form.skills_focus && form.skills_focus.length > 0" class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="skill in form.skills_focus"
                                    :key="skill"
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                                >
                                    {{ skill }}
                                    <button
                                        type="button"
                                        @click="removeSkill(skill)"
                                        class="ml-1 text-blue-500 hover:text-blue-700"
                                    >
                                        <XMarkIcon class="w-3 h-3" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Milestones -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Milestones
                            </label>
                            <div class="space-y-2">
                                <div
                                    v-for="(milestone, index) in form.milestones"
                                    :key="index"
                                    class="flex items-center space-x-2"
                                >
                                    <input
                                        v-model="milestone.title"
                                        type="text"
                                        class="flex-1 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="Milestone description"
                                    />
                                    <input
                                        v-model="milestone.due_date"
                                        type="date"
                                        class="border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="removeMilestone(index)"
                                        class="text-red-500 hover:text-red-700"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addMilestone"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                >
                                    + Add Milestone
                                </button>
                            </div>
                        </div>

                        <!-- Success Metrics -->
                        <div>
                            <label for="success_metrics" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Success Metrics
                            </label>
                            <textarea
                                id="success_metrics"
                                v-model="form.success_metrics"
                                rows="2"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="How will you measure success?"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.title || isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isSubmitting ? 'Saving...' : (isEditing ? 'Update Goal' : 'Create Goal') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { TrophyIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    goal: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close', 'save'])

const isEditing = computed(() => !!props.goal)
const isSubmitting = ref(false)
const skillsInput = ref('')

const form = ref({
    title: '',
    description: '',
    category: '',
    target_date: '',
    priority: 'medium',
    skills_focus: [],
    milestones: [],
    success_metrics: ''
})

// Watch for goal prop changes to populate form
watch(() => props.goal, (newGoal) => {
    if (newGoal) {
        form.value = {
            title: newGoal.title || '',
            description: newGoal.description || '',
            category: newGoal.category || '',
            target_date: newGoal.target_date || '',
            priority: newGoal.priority || 'medium',
            skills_focus: newGoal.skills_focus || [],
            milestones: newGoal.milestones || [],
            success_metrics: newGoal.success_metrics || ''
        }
        skillsInput.value = (newGoal.skills_focus || []).join(', ')
    } else {
        resetForm()
    }
}, { immediate: true })

const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        category: '',
        target_date: '',
        priority: 'medium',
        skills_focus: [],
        milestones: [],
        success_metrics: ''
    }
    skillsInput.value = ''
}

const updateSkillsFocus = () => {
    const skills = skillsInput.value
        .split(',')
        .map(skill => skill.trim())
        .filter(skill => skill.length > 0)
    form.value.skills_focus = skills
}

const removeSkill = (skillToRemove) => {
    form.value.skills_focus = form.value.skills_focus.filter(skill => skill !== skillToRemove)
    skillsInput.value = form.value.skills_focus.join(', ')
}

const addMilestone = () => {
    form.value.milestones.push({
        title: '',
        due_date: '',
        completed: false
    })
}

const removeMilestone = (index) => {
    form.value.milestones.splice(index, 1)
}

const closeModal = () => {
    emit('close')
    resetForm()
}

const submitForm = async () => {
    if (!form.value.title) return

    isSubmitting.value = true
    
    try {
        const goalData = {
            ...form.value,
            id: props.goal?.id
        }
        
        emit('save', goalData)
        closeModal()
    } catch (error) {
        console.error('Error saving goal:', error)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<style scoped>
/* Modal animation styles */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
