<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background overlay -->
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-gray-800"
            >
                <!-- Modal header -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-gray-800">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10 dark:bg-blue-900/20"
                        >
                            <TrophyIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" aria-hidden="true" />
                        </div>
                        <div class="mt-3 flex-1 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white" id="modal-title">
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
                            class="ml-4 rounded-md bg-white text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800"
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
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Goal Title * </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="e.g., Improve public speaking skills"
                            />
                        </div>

                        <!-- Goal Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Description </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Describe what you want to achieve..."
                            ></textarea>
                        </div>

                        <!-- Goal Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Category </label>
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                            <label for="target_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Target Date </label>
                            <input
                                id="target_date"
                                v-model="form.target_date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Priority Level -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Priority Level </label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <!-- Skills Focus -->
                        <div>
                            <label for="skills_focus" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Skills Focus </label>
                            <input
                                id="skills_focus"
                                v-model="skillsInput"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Enter skills separated by commas"
                                @input="updateSkillsFocus"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Separate multiple skills with commas</p>
                            <div v-if="form.skills_focus && form.skills_focus.length > 0" class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="skill in form.skills_focus"
                                    :key="skill"
                                    class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                                >
                                    {{ skill }}
                                    <button type="button" @click="removeSkill(skill)" class="ml-1 text-blue-500 hover:text-blue-700">
                                        <XMarkIcon class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Milestones -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Milestones </label>
                            <div class="space-y-2">
                                <div v-for="(milestone, index) in form.milestones" :key="index" class="flex items-center space-x-2">
                                    <input
                                        v-model="milestone.title"
                                        type="text"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Milestone description"
                                    />
                                    <input
                                        v-model="milestone.due_date"
                                        type="date"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <button type="button" @click="removeMilestone(index)" class="text-red-500 hover:text-red-700">
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>
                                <button type="button" @click="addMilestone" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    + Add Milestone
                                </button>
                            </div>
                        </div>

                        <!-- Success Metrics -->
                        <div>
                            <label for="success_metrics" class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Success Metrics </label>
                            <textarea
                                id="success_metrics"
                                v-model="form.success_metrics"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="How will you measure success?"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.title || isSubmitting"
                            class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Saving...' : isEditing ? 'Update Goal' : 'Create Goal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { TrophyIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    goal: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'save']);

const isEditing = computed(() => !!props.goal);
const isSubmitting = ref(false);
const skillsInput = ref('');

const form = ref({
    title: '',
    description: '',
    category: '',
    target_date: '',
    priority: 'medium',
    skills_focus: [],
    milestones: [],
    success_metrics: '',
});

// Watch for goal prop changes to populate form
watch(
    () => props.goal,
    (newGoal) => {
        if (newGoal) {
            form.value = {
                title: newGoal.title || '',
                description: newGoal.description || '',
                category: newGoal.category || '',
                target_date: newGoal.target_date || '',
                priority: newGoal.priority || 'medium',
                skills_focus: newGoal.skills_focus || [],
                milestones: newGoal.milestones || [],
                success_metrics: newGoal.success_metrics || '',
            };
            skillsInput.value = (newGoal.skills_focus || []).join(', ');
        } else {
            resetForm();
        }
    },
    { immediate: true },
);

const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        category: '',
        target_date: '',
        priority: 'medium',
        skills_focus: [],
        milestones: [],
        success_metrics: '',
    };
    skillsInput.value = '';
};

const updateSkillsFocus = () => {
    const skills = skillsInput.value
        .split(',')
        .map((skill) => skill.trim())
        .filter((skill) => skill.length > 0);
    form.value.skills_focus = skills;
};

const removeSkill = (skillToRemove) => {
    form.value.skills_focus = form.value.skills_focus.filter((skill) => skill !== skillToRemove);
    skillsInput.value = form.value.skills_focus.join(', ');
};

const addMilestone = () => {
    form.value.milestones.push({
        title: '',
        due_date: '',
        completed: false,
    });
};

const removeMilestone = (index) => {
    form.value.milestones.splice(index, 1);
};

const closeModal = () => {
    emit('close');
    resetForm();
};

const submitForm = async () => {
    if (!form.value.title) return;

    isSubmitting.value = true;

    try {
        const goalData = {
            ...form.value,
            id: props.goal?.id,
        };

        emit('save', goalData);
        closeModal();
    } catch (error) {
        console.error('Error saving goal:', error);
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<style scoped>
/* Modal animation styles */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
