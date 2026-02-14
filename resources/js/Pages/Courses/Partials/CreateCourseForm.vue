<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    code: '',
    description: '',
    level: '',
    duration_months: '',
    study_mode: '',
    department: '',
    required_skills: [],
    skills_gained: [],
    career_paths: [],
    prerequisites: [],
    learning_outcomes: [],
    is_active: true,
    is_featured: false,
});

const newRequiredSkill = ref('');
const newSkillGained = ref('');
const newCareerPath = ref('');
const newPrerequisite = ref('');
const newLearningOutcome = ref('');

const levels = [
    { value: 'certificate', label: 'Certificate' },
    { value: 'diploma', label: 'Diploma' },
    { value: 'advanced_diploma', label: 'Advanced Diploma' },
    { value: 'degree', label: 'Degree' },
    { value: 'other', label: 'Other' },
];

const studyModes = [
    { value: 'full_time', label: 'Full Time' },
    { value: 'part_time', label: 'Part Time' },
    { value: 'online', label: 'Online' },
    { value: 'hybrid', label: 'Hybrid' },
];

const addRequiredSkill = () => {
    if (newRequiredSkill.value.trim()) {
        form.required_skills.push(newRequiredSkill.value.trim());
        newRequiredSkill.value = '';
    }
};

const removeRequiredSkill = (index) => {
    form.required_skills.splice(index, 1);
};

const addSkillGained = () => {
    if (newSkillGained.value.trim()) {
        form.skills_gained.push(newSkillGained.value.trim());
        newSkillGained.value = '';
    }
};

const removeSkillGained = (index) => {
    form.skills_gained.splice(index, 1);
};

const addCareerPath = () => {
    if (newCareerPath.value.trim()) {
        form.career_paths.push(newCareerPath.value.trim());
        newCareerPath.value = '';
    }
};

const removeCareerPath = (index) => {
    form.career_paths.splice(index, 1);
};

const addPrerequisite = () => {
    if (newPrerequisite.value.trim()) {
        form.prerequisites.push(newPrerequisite.value.trim());
        newPrerequisite.value = '';
    }
};

const removePrerequisite = (index) => {
    form.prerequisites.splice(index, 1);
};

const addLearningOutcome = () => {
    if (newLearningOutcome.value.trim()) {
        form.learning_outcomes.push(newLearningOutcome.value.trim());
        newLearningOutcome.value = '';
    }
};

const removeLearningOutcome = (index) => {
    form.learning_outcomes.splice(index, 1);
};

const submit = () => {
    form.post(route('courses.store'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Course</h2>
            <p class="mt-1 text-sm text-gray-600">Create a new course with comprehensive information.</p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-8">
            <!-- Basic Information -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Basic Information</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Course Name *</label>
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Course Code *</label>
                        <input
                            id="code"
                            type="text"
                            v-model="form.code"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                    </div>
                </div>
            </div>

            <!-- Course Details -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Course Details</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">Level *</label>
                        <select
                            id="level"
                            v-model="form.level"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select a level</option>
                            <option v-for="level in levels" :key="level.value" :value="level.value">
                                {{ level.label }}
                            </option>
                        </select>
                        <div v-if="form.errors.level" class="mt-1 text-sm text-red-600">{{ form.errors.level }}</div>
                    </div>
                    <div>
                        <label for="duration_months" class="block text-sm font-medium text-gray-700">Duration (months) *</label>
                        <input
                            id="duration_months"
                            type="number"
                            min="1"
                            max="120"
                            v-model="form.duration_months"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.duration_months" class="mt-1 text-sm text-red-600">{{ form.errors.duration_months }}</div>
                    </div>
                    <div>
                        <label for="study_mode" class="block text-sm font-medium text-gray-700">Study Mode *</label>
                        <select
                            id="study_mode"
                            v-model="form.study_mode"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Select a study mode</option>
                            <option v-for="mode in studyModes" :key="mode.value" :value="mode.value">
                                {{ mode.label }}
                            </option>
                        </select>
                        <div v-if="form.errors.study_mode" class="mt-1 text-sm text-red-600">{{ form.errors.study_mode }}</div>
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                        <input
                            id="department"
                            type="text"
                            v-model="form.department"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div v-if="form.errors.department" class="mt-1 text-sm text-red-600">{{ form.errors.department }}</div>
                    </div>
                </div>
            </div>

            <!-- Required Skills -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Required Skills (Prerequisites)</h3>
                <div class="mb-3 flex gap-2">
                    <input
                        type="text"
                        v-model="newRequiredSkill"
                        placeholder="Add a required skill"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="button" @click="addRequiredSkill" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(skill, index) in form.required_skills"
                        :key="index"
                        class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm text-red-800"
                    >
                        {{ skill }}
                        <button type="button" @click="removeRequiredSkill(index)" class="ml-2 text-red-600 hover:text-red-800">Ã—</button>
                    </span>
                </div>
            </div>

            <!-- Skills Gained -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Skills Gained</h3>
                <div class="mb-3 flex gap-2">
                    <input
                        type="text"
                        v-model="newSkillGained"
                        placeholder="Add a skill gained"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="button" @click="addSkillGained" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(skill, index) in form.skills_gained"
                        :key="index"
                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm text-green-800"
                    >
                        {{ skill }}
                        <button type="button" @click="removeSkillGained(index)" class="ml-2 text-green-600 hover:text-green-800">Ã—</button>
                    </span>
                </div>
            </div>

            <!-- Career Paths -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Career Paths</h3>
                <div class="mb-3 flex gap-2">
                    <input
                        type="text"
                        v-model="newCareerPath"
                        placeholder="Add a career path"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="button" @click="addCareerPath" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(path, index) in form.career_paths"
                        :key="index"
                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
                    >
                        {{ path }}
                        <button type="button" @click="removeCareerPath(index)" class="ml-2 text-blue-600 hover:text-blue-800">Ã—</button>
                    </span>
                </div>
            </div>

            <!-- Prerequisites -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Prerequisites</h3>
                <div class="mb-3 flex gap-2">
                    <input
                        type="text"
                        v-model="newPrerequisite"
                        placeholder="Add a prerequisite"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="button" @click="addPrerequisite" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="(prerequisite, index) in form.prerequisites"
                        :key="index"
                        class="flex items-center justify-between rounded border bg-white p-2"
                    >
                        <span class="text-sm">{{ prerequisite }}</span>
                        <button type="button" @click="removePrerequisite(index)" class="text-red-600 hover:text-red-800">Remove</button>
                    </div>
                </div>
            </div>

            <!-- Learning Outcomes -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Learning Outcomes</h3>
                <div class="mb-3 flex gap-2">
                    <input
                        type="text"
                        v-model="newLearningOutcome"
                        placeholder="Add a learning outcome"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button type="button" @click="addLearningOutcome" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="(outcome, index) in form.learning_outcomes"
                        :key="index"
                        class="flex items-center justify-between rounded border bg-white p-2"
                    >
                        <span class="text-sm">{{ outcome }}</span>
                        <button type="button" @click="removeLearningOutcome(index)" class="text-red-600 hover:text-red-800">Remove</button>
                    </div>
                </div>
            </div>

            <!-- Course Settings -->
            <div class="rounded-lg bg-gray-50 p-4">
                <h3 class="text-md mb-4 font-semibold text-gray-800">Course Settings</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="ml-2 text-sm text-gray-700">Course is active and available for enrollment</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.is_featured"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="ml-2 text-sm text-gray-700">Feature this course (will be highlighted)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-md bg-indigo-600 px-6 py-2 text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Course' }}
                </button>
            </div>
        </form>
    </section>
</template>

