<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    graduate: Object,
    courses: Array,
});

const form = useForm({
    name: props.graduate.name || '',
    email: props.graduate.email || '',
    phone: props.graduate.phone || '',
    address: props.graduate.address || '',
    graduation_year: props.graduate.graduation_year || '',
    course_id: props.graduate.course_id || '',
    student_id: props.graduate.student_id || '',
    gpa: props.graduate.gpa || '',
    academic_standing: props.graduate.academic_standing || '',
    employment_status: props.graduate.employment_status || 'unemployed',
    current_job_title: props.graduate.current_job_title || '',
    current_company: props.graduate.current_company || '',
    current_salary: props.graduate.current_salary || '',
    employment_start_date: props.graduate.employment_start_date || '',
    skills: props.graduate.skills || [],
    certifications: props.graduate.certifications || [],
    privacy_settings: props.graduate.privacy_settings || {
        profile_visible: true,
        contact_visible: true,
        employment_visible: true,
    },
    allow_employer_contact: props.graduate.allow_employer_contact ?? true,
    job_search_active: props.graduate.job_search_active ?? true,
});

const newSkill = ref('');
const newCertification = ref('');

const addSkill = () => {
    if (newSkill.value.trim()) {
        form.skills.push(newSkill.value.trim());
        newSkill.value = '';
    }
};

const removeSkill = (index) => {
    form.skills.splice(index, 1);
};

const addCertification = () => {
    if (newCertification.value.trim()) {
        form.certifications.push({
            name: newCertification.value.trim(),
            issuer: '',
            date_obtained: '',
        });
        newCertification.value = '';
    }
};

const removeCertification = (index) => {
    form.certifications.splice(index, 1);
};

const submit = () => {
    form.patch(route('graduates.update', props.graduate.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Graduate</h2>
            <p class="mt-1 text-sm text-gray-600">
                Update the graduate's comprehensive profile information.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-8">
            <!-- Personal Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                        <input id="name" type="text" v-model="form.name" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                        <input id="email" type="email" v-model="form.email" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div v-if="form.errors.email" class="text-red-600 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input id="phone" type="text" v-model="form.phone" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Student ID</label>
                        <input id="student_id" type="text" v-model="form.student_id" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" v-model="form.address" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Academic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700">Course *</label>
                        <select id="course_id" v-model="form.course_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a course</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.course_id" class="text-red-600 text-sm mt-1">{{ form.errors.course_id }}</div>
                    </div>
                    <div>
                        <label for="graduation_year" class="block text-sm font-medium text-gray-700">Graduation Year *</label>
                        <input id="graduation_year" type="number" v-model="form.graduation_year" required 
                               :min="1900" :max="new Date().getFullYear() + 1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div v-if="form.errors.graduation_year" class="text-red-600 text-sm mt-1">{{ form.errors.graduation_year }}</div>
                    </div>
                    <div>
                        <label for="gpa" class="block text-sm font-medium text-gray-700">GPA</label>
                        <input id="gpa" type="number" step="0.01" min="0" max="4" v-model="form.gpa" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="academic_standing" class="block text-sm font-medium text-gray-700">Academic Standing</label>
                        <select id="academic_standing" v-model="form.academic_standing"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select standing</option>
                            <option value="excellent">Excellent</option>
                            <option value="very_good">Very Good</option>
                            <option value="good">Good</option>
                            <option value="satisfactory">Satisfactory</option>
                            <option value="pass">Pass</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Employment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status *</label>
                        <select id="employment_status" v-model="form.employment_status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="unemployed">Unemployed</option>
                            <option value="employed">Employed</option>
                            <option value="self_employed">Self Employed</option>
                            <option value="further_studies">Further Studies</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div v-if="form.employment_status === 'employed' || form.employment_status === 'self_employed'">
                        <label for="current_job_title" class="block text-sm font-medium text-gray-700">Job Title</label>
                        <input id="current_job_title" type="text" v-model="form.current_job_title" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.employment_status === 'employed'">
                        <label for="current_company" class="block text-sm font-medium text-gray-700">Company</label>
                        <input id="current_company" type="text" v-model="form.current_company" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.employment_status === 'employed' || form.employment_status === 'self_employed'">
                        <label for="current_salary" class="block text-sm font-medium text-gray-700">Salary (Annual)</label>
                        <input id="current_salary" type="number" min="0" v-model="form.current_salary" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="form.employment_status === 'employed' || form.employment_status === 'self_employed'">
                        <label for="employment_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input id="employment_start_date" type="date" v-model="form.employment_start_date" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
            </div>

            <!-- Skills -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Skills</h3>
                <div class="flex gap-2 mb-3">
                    <input type="text" v-model="newSkill" placeholder="Add a skill" 
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" @click="addSkill" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(skill, index) in form.skills" :key="index" 
                          class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 text-indigo-800">
                        {{ skill }}
                        <button type="button" @click="removeSkill(index)" class="ml-2 text-indigo-600 hover:text-indigo-800">
                            ×
                        </button>
                    </span>
                </div>
            </div>

            <!-- Certifications -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Certifications</h3>
                <div class="flex gap-2 mb-3">
                    <input type="text" v-model="newCertification" placeholder="Add a certification" 
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" @click="addCertification" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Add
                    </button>
                </div>
                <div class="space-y-2">
                    <div v-for="(cert, index) in form.certifications" :key="index" 
                         class="flex items-center justify-between p-3 bg-white rounded border">
                        <div class="flex-1">
                            <input type="text" v-model="cert.name" placeholder="Certification name" 
                                   class="w-full mb-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" v-model="cert.issuer" placeholder="Issuer" 
                                       class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <input type="date" v-model="cert.date_obtained" 
                                       class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                        </div>
                        <button type="button" @click="removeCertification(index)" 
                                class="ml-3 text-red-600 hover:text-red-800">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <!-- Privacy Settings -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800 mb-4">Privacy & Contact Settings</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.profile_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700">Make profile visible to employers</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.contact_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700">Show contact information to employers</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.privacy_settings.employment_visible" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700">Show employment status to employers</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.allow_employer_contact" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700">Allow employers to contact me</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.job_search_active" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700">Currently looking for job opportunities</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" :disabled="form.processing" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    {{ form.processing ? 'Updating...' : 'Update Graduate' }}
                </button>
            </div>
        </form>
    </section>
</template>
