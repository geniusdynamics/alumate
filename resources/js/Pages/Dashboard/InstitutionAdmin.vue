<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

interface InstitutionStats {
    graduates?: number;
    courses?: number;
    employers?: number;
    active_jobs?: number;
    pending_applications?: number;
    staff_members?: number;
    employed_graduates?: number;
    employment_rate?: number;
    [key: string]: unknown;
}

const props = defineProps<{
    stats?: InstitutionStats;
}>();

const safeStats = {
    graduates: props.stats?.graduates ?? 0,
    courses: props.stats?.courses ?? 0,
    employers: props.stats?.employers ?? 0,
    active_jobs: props.stats?.active_jobs ?? 0,
    pending_applications: props.stats?.pending_applications ?? 0,
    staff_members: props.stats?.staff_members ?? 0,
    employed_graduates: props.stats?.employed_graduates ?? 0,
    employment_rate: props.stats?.employment_rate ?? 0,
};
</script>

<template>
    <Head title="Institution Admin Dashboard" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">Institution Admin Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Graduates</h3>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ safeStats.graduates.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Courses</h3>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ safeStats.courses.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Jobs</h3>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ safeStats.active_jobs.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Staff Members</h3>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ safeStats.staff_members.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Employed Graduates</h3>
                            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ safeStats.employed_graduates.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Employment Rate</h3>
                            <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ safeStats.employment_rate }}%</p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Applications</h3>
                            <p class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ safeStats.pending_applications.toLocaleString() }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Employers</h3>
                            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ safeStats.employers.toLocaleString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
