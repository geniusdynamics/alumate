<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/components/TextInput.vue';

const props = defineProps({
    courses: Array,
});

const form = useForm({
    title: '',
    description: '',
    location: '',
    course_id: '',
    required_skills: [],
    preferred_qualifications: [],
    experience_level: 'entry',
    min_experience_years: 0,
    salary_min: '',
    salary_max: '',
    salary_type: 'monthly',
    job_type: 'full_time',
    work_arrangement: 'on_site',
    application_deadline: '',
    job_start_date: '',
    job_end_date: '',
    contact_email: '',
    contact_phone: '',
    contact_person: '',
    benefits: [],
    company_culture: '',
});

const newSkill = ref('');
const newQualification = ref('');
const newBenefit = ref('');

const selectedCourse = computed(() => {
    return props.courses.find(course => course.id == form.course_id);
});

const addSkill = () => {
    if (newSkill.value.trim() && !form.required_skills.includes(newSkill.value.trim())) {
        form.required_skills.push(newSkill.value.trim());
        newSkill.value = '';
    }
};

const removeSkill = (index) => {
    form.required_skills.splice(index, 1);
};

const addQualification = () => {
    if (newQualification.value.trim() && !form.preferred_qualifications.includes(newQualification.value.trim())) {
        form.preferred_qualifications.push(newQualification.value.trim());
        newQualification.value = '';
    }
};

const removeQualification = (index) => {
    form.preferred_qualifications.splice(index, 1);
};

const addBenefit = () => {
    if (newBenefit.value.trim() && !form.benefits.includes(newBenefit.value.trim())) {
        form.benefits.push(newBenefit.value.trim());
        newBenefit.value = '';
    }
};

const removeBenefit = (index) => {
    form.benefits.splice(index, 1);
};

const addCourseSkills = () => {
    if (selectedCourse.value && selectedCourse.value.skills_gained) {
        selectedCourse.value.skills_gained.forEach(skill => {
            if (!form.required_skills.includes(skill)) {
                form.required_skills.push(skill);
            }
        });
    }
};

const submit = () => {
    form.post(route('jobs.store'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Job Posting</h2>
            <p class="mt-1 text-sm text-gray-600">
                Create a comprehensive job posting with detailed requirements and benefits.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-8">
            <!-- Basic Information -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700">Job Title *</label>
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autofocus
                        />
                        <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Job Description *</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="6"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                            placeholder="Provide a detailed description of the role, responsibilities, and what the candidate will be doing..."
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">{{ form.errors.description }}</div>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location *</label>
                        <TextInput
                            id="location"
                            v-model="form.location"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            placeholder="e.g., Nairobi, Kenya"
                        />
                        <div v-if="form.errors.location" class="text-red-600 text-sm mt-1">{{ form.errors.location }}</div>
                    </div>

                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700">Target Course *</label>
                        <select
                            id="course_id"
                            v-model="form.course_id"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="">Select a course</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.course_id" class="text-red-600 text-sm mt-1">{{ form.errors.course_id }}</div>
                    </div>
                </div>
            </div>

            <!-- Job Requirements -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Job Requirements</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="experience_level" class="block text-sm font-medium text-gray-700">Experience Level *</label>
                        <select
                            id="experience_level"
                            v-model="form.experience_level"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="entry">Entry Level</option>
                            <option value="junior">Junior</option>
                            <option value="mid">Mid Level</option>
                            <option value="senior">Senior</option>
                            <option value="executive">Executive</option>
                        </select>
                        <div v-if="form.errors.experience_level" class="text-red-600 text-sm mt-1">{{ form.errors.experience_level }}</div>
                    </div>

                    <div>
                        <label for="min_experience_years" class="block text-sm font-medium text-gray-700">Minimum Experience (Years) *</label>
                        <TextInput
                            id="min_experience_years"
                            v-model="form.min_experience_years"
                            type="number"
                            min="0"
                            max="50"
                            class="mt-1 block w-full"
                            required
                        />
                        <div v-if="form.errors.min_experience_years" class="text-red-600 text-sm mt-1">{{ form.errors.min_experience_years }}</div>
                    </div>

                    <!-- Required Skills -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Required Skills</label>
                        <div class="mt-1 flex gap-2">
                            <TextInput
                                v-model="newSkill"
                                type="text"
                                class="flex-1"
                                placeholder="Add a required skill"
                                @keyup.enter="addSkill"
                            />
                            <button
                                type="button"
                                @click="addSkill"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            >
                                Add
                            </button>
                            <button
                                v-if="selectedCourse && selectedCourse.skills_gained"
                                type="button"
                                @click="addCourseSkills"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                            >
                                Add Course Skills
                            </button>
                        </div>
                        <div v-if="form.required_skills.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="(skill, index) in form.required_skills"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800"
                            >
                                {{ skill }}
                                <button
                                    type="button"
                                    @click="removeSkill(index)"
                                    class="ml-2 text-indigo-600 hover:text-indigo-800"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <div v-if="form.errors.required_skills" class="text-red-600 text-sm mt-1">{{ form.errors.required_skills }}</div>
                    </div>

                    <!-- Preferred Qualifications -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Preferred Qualifications</label>
                        <div class="mt-1 flex gap-2">
                            <TextInput
                                v-model="newQualification"
                                type="text"
                                class="flex-1"
                                placeholder="Add a preferred qualification"
                                @keyup.enter="addQualification"
                            />
                            <button
                                type="button"
                                @click="addQualification"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            >
                                Add
                            </button>
                        </div>
                        <div v-if="form.preferred_qualifications.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="(qualification, index) in form.preferred_qualifications"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800"
                            >
                                {{ qualification }}
                                <button
                                    type="button"
                                    @click="removeQualification(index)"
                                    class="ml-2 text-green-600 hover:text-green-800"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <div v-if="form.errors.preferred_qualifications" class="text-red-600 text-sm mt-1">{{ form.errors.preferred_qualifications }}</div>
                    </div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Job Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="job_type" class="block text-sm font-medium text-gray-700">Job Type *</label>
                        <select
                            id="job_type"
                            v-model="form.job_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="temporary">Temporary</option>
                        </select>
                        <div v-if="form.errors.job_type" class="text-red-600 text-sm mt-1">{{ form.errors.job_type }}</div>
                    </div>

                    <div>
                        <label for="work_arrangement" class="block text-sm font-medium text-gray-700">Work Arrangement *</label>
                        <select
                            id="work_arrangement"
                            v-model="form.work_arrangement"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="on_site">On Site</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                        <div v-if="form.errors.work_arrangement" class="text-red-600 text-sm mt-1">{{ form.errors.work_arrangement }}</div>
                    </div>

                    <div>
                        <label for="application_deadline" class="block text-sm font-medium text-gray-700">Application Deadline</label>
                        <TextInput
                            id="application_deadline"
                            v-model="form.application_deadline"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <div v-if="form.errors.application_deadline" class="text-red-600 text-sm mt-1">{{ form.errors.application_deadline }}</div>
                    </div>

                    <div>
                        <label for="job_start_date" class="block text-sm font-medium text-gray-700">Expected Start Date</label>
                        <TextInput
                            id="job_start_date"
                            v-model="form.job_start_date"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <div v-if="form.errors.job_start_date" class="text-red-600 text-sm mt-1">{{ form.errors.job_start_date }}</div>
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Compensation</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="salary_type" class="block text-sm font-medium text-gray-700">Salary Type *</label>
                        <select
                            id="salary_type"
                            v-model="form.salary_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option value="hourly">Hourly</option>
                            <option value="monthly">Monthly</option>
                            <option value="annually">Annually</option>
                        </select>
                        <div v-if="form.errors.salary_type" class="text-red-600 text-sm mt-1">{{ form.errors.salary_type }}</div>
                    </div>

                    <div>
                        <label for="salary_min" class="block text-sm font-medium text-gray-700">Minimum Salary</label>
                        <TextInput
                            id="salary_min"
                            v-model="form.salary_min"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full"
                            placeholder="0.00"
                        />
                        <div v-if="form.errors.salary_min" class="text-red-600 text-sm mt-1">{{ form.errors.salary_min }}</div>
                    </div>

                    <div>
                        <label for="salary_max" class="block text-sm font-medium text-gray-700">Maximum Salary</label>
                        <TextInput
                            id="salary_max"
                            v-model="form.salary_max"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full"
                            placeholder="0.00"
                        />
                        <div v-if="form.errors.salary_max" class="text-red-600 text-sm mt-1">{{ form.errors.salary_max }}</div>
                    </div>

                    <!-- Benefits -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Benefits & Perks</label>
                        <div class="mt-1 flex gap-2">
                            <TextInput
                                v-model="newBenefit"
                                type="text"
                                class="flex-1"
                                placeholder="Add a benefit or perk"
                                @keyup.enter="addBenefit"
                            />
                            <button
                                type="button"
                                @click="addBenefit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            >
                                Add
                            </button>
                        </div>
                        <div v-if="form.benefits.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="(benefit, index) in form.benefits"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800"
                            >
                                {{ benefit }}
                                <button
                                    type="button"
                                    @click="removeBenefit(index)"
                                    class="ml-2 text-purple-600 hover:text-purple-800"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <div v-if="form.errors.benefits" class="text-red-600 text-sm mt-1">{{ form.errors.benefits }}</div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                        <TextInput
                            id="contact_person"
                            v-model="form.contact_person"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Hiring Manager Name"
                        />
                        <div v-if="form.errors.contact_person" class="text-red-600 text-sm mt-1">{{ form.errors.contact_person }}</div>
                    </div>

                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <TextInput
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            class="mt-1 block w-full"
                            placeholder="hr@company.com"
                        />
                        <div v-if="form.errors.contact_email" class="text-red-600 text-sm mt-1">{{ form.errors.contact_email }}</div>
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact Phone</label>
                        <TextInput
                            id="contact_phone"
                            v-model="form.contact_phone"
                            type="tel"
                            class="mt-1 block w-full"
                            placeholder="+254 700 000 000"
                        />
                        <div v-if="form.errors.contact_phone" class="text-red-600 text-sm mt-1">{{ form.errors.contact_phone }}</div>
                    </div>
                </div>
            </div>

            <!-- Company Culture -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-md font-medium text-gray-900 mb-4">Company Culture</h3>
                <div>
                    <label for="company_culture" class="block text-sm font-medium text-gray-700">Company Culture & Environment</label>
                    <textarea
                        id="company_culture"
                        v-model="form.company_culture"
                        rows="4"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Describe your company culture, work environment, and what makes your company a great place to work..."
                    ></textarea>
                    <div v-if="form.errors.company_culture" class="text-red-600 text-sm mt-1">{{ form.errors.company_culture }}</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <button
                    type="button"
                    @click="$inertia.visit(route('jobs.index'))"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span v-if="form.processing">Creating...</span>
                    <span v-else>Create Job</span>
                </button>
            </div>
        </form>
    </section>
</template>
