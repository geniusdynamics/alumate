<template>
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ mode === 'schedule' ? 'Schedule Session' : 'Availability Calendar' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ mode === 'schedule' ? 'Find available time slots for mentorship sessions' : 'Manage your availability for mentorship' }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="goToPreviousWeek" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatWeekRange(currentWeek) }}
                    </span>
                    <button @click="goToNextWeek" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Participants (for scheduling mode) -->
            <div v-if="mode === 'schedule' && participants.length > 0" class="mb-6">
                <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Participants</h4>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="participant in participants"
                        :key="participant.id"
                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                    >
                        <img v-if="participant.avatar_url" :src="participant.avatar_url" :alt="participant.name" class="mr-2 h-5 w-5 rounded-full" />
                        <div v-else class="mr-2 h-5 w-5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                        {{ participant.name }}
                    </div>
                </div>
            </div>

            <!-- Session Duration Selector -->
            <div v-if="mode === 'schedule'" class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Session Duration </label>
                <select
                    v-model="sessionDuration"
                    @change="fetchAvailableSlots"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="30">30 minutes</option>
                    <option value="60">1 hour</option>
                    <option value="90">1.5 hours</option>
                    <option value="120">2 hours</option>
                </select>
            </div>

            <!-- Calendar Grid -->
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                <!-- Header with days -->
                <div class="grid grid-cols-8 bg-gray-50 dark:bg-gray-700">
                    <div class="p-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Time</div>
                    <div v-for="day in weekDays" :key="day.date" class="border-l border-gray-200 p-3 text-center dark:border-gray-600">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ day.dayName }}
                        </div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ day.dayNumber }}
                        </div>
                    </div>
                </div>

                <!-- Time slots -->
                <div class="divide-y divide-gray-200 dark:divide-gray-600">
                    <div v-for="timeSlot in timeSlots" :key="timeSlot.time" class="grid grid-cols-8 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <!-- Time column -->
                        <div class="p-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ timeSlot.time }}
                        </div>

                        <!-- Day columns -->
                        <div v-for="day in weekDays" :key="`${day.date}-${timeSlot.time}`" class="border-l border-gray-200 p-1 dark:border-gray-600">
                            <div v-if="mode === 'schedule'" class="relative h-12">
                                <!-- Available slot -->
                                <button
                                    v-if="isSlotAvailable(day.date, timeSlot.time)"
                                    @click="selectTimeSlot(day.date, timeSlot.time)"
                                    class="flex h-full w-full items-center justify-center rounded border border-green-300 bg-green-100 text-xs font-medium text-green-800 transition-colors hover:bg-green-200 dark:border-green-700 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800"
                                >
                                    Available
                                </button>

                                <!-- Busy slot -->
                                <div
                                    v-else-if="isSlotBusy(day.date, timeSlot.time)"
                                    class="flex h-full w-full items-center justify-center rounded border border-red-300 bg-red-100 text-xs font-medium text-red-800 dark:border-red-700 dark:bg-red-900 dark:text-red-200"
                                >
                                    Busy
                                </div>

                                <!-- No data -->
                                <div
                                    v-else
                                    class="h-full w-full rounded border border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"
                                ></div>
                            </div>

                            <div v-else class="relative h-12">
                                <!-- Availability management mode -->
                                <button
                                    @click="toggleAvailability(day.date, timeSlot.time)"
                                    class="h-full w-full rounded border transition-colors"
                                    :class="{
                                        'border-green-300 bg-green-100 text-green-800 hover:bg-green-200 dark:border-green-700 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800':
                                            isUserAvailable(day.date, timeSlot.time),
                                        'border-gray-300 bg-gray-100 text-gray-600 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600':
                                            !isUserAvailable(day.date, timeSlot.time),
                                    }"
                                >
                                    <span class="text-xs font-medium">
                                        {{ isUserAvailable(day.date, timeSlot.time) ? 'Available' : 'Unavailable' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Time Slot (for scheduling mode) -->
            <div v-if="mode === 'schedule' && selectedSlot" class="mt-6 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <h4 class="mb-2 text-sm font-medium text-blue-900 dark:text-blue-100">Selected Time Slot</h4>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    {{ formatSelectedSlot(selectedSlot) }}
                </p>
                <div class="mt-4 flex items-center space-x-3">
                    <button
                        @click="scheduleSession"
                        :disabled="loading"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        <svg v-if="loading" class="-ml-1 mr-3 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Schedule Session
                    </button>
                    <button
                        @click="selectedSlot = null"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Working Hours Settings (for availability mode) -->
            <div v-if="mode === 'availability'" class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Working Hours</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Start Time</label>
                        <select
                            v-model="workingHours.start"
                            @change="updateWorkingHours"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                        >
                            <option v-for="time in availableTimes" :key="time" :value="time">{{ time }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">End Time</label>
                        <select
                            v-model="workingHours.end"
                            @change="updateWorkingHours"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                        >
                            <option v-for="time in availableTimes" :key="time" :value="time">{{ time }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
}

interface TimeSlot {
    date: string;
    time: string;
    available: boolean;
    participants?: User[];
}

interface SelectedSlot {
    date: string;
    time: string;
    datetime: string;
}

interface Props {
    mode?: 'availability' | 'schedule';
    participants?: User[];
    mentorId?: number;
    menteeId?: number;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'availability',
    participants: () => [],
});

const emit = defineEmits<{
    sessionScheduled: [session: any];
}>();

const loading = ref(false);
const currentWeek = ref(new Date());
const sessionDuration = ref(60);
const selectedSlot = ref<SelectedSlot | null>(null);
const availableSlots = ref<TimeSlot[]>([]);
const userAvailability = ref<Record<string, boolean>>({});

const workingHours = ref({
    start: '09:00',
    end: '17:00',
});

const timeSlots = computed(() => {
    const slots = [];
    const start = parseInt(workingHours.value.start.split(':')[0]);
    const end = parseInt(workingHours.value.end.split(':')[0]);

    for (let hour = start; hour < end; hour++) {
        slots.push({
            time: `${hour.toString().padStart(2, '0')}:00`,
            hour,
        });
        slots.push({
            time: `${hour.toString().padStart(2, '0')}:30`,
            hour,
        });
    }

    return slots;
});

const weekDays = computed(() => {
    const days = [];
    const startOfWeek = new Date(currentWeek.value);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek);
        date.setDate(startOfWeek.getDate() + i);

        days.push({
            date: date.toISOString().split('T')[0],
            dayName: date.toLocaleDateString('en-US', { weekday: 'short' }),
            dayNumber: date.getDate(),
            fullDate: date,
        });
    }

    return days;
});

const availableTimes = computed(() => {
    const times = [];
    for (let hour = 6; hour <= 22; hour++) {
        times.push(`${hour.toString().padStart(2, '0')}:00`);
        times.push(`${hour.toString().padStart(2, '0')}:30`);
    }
    return times;
});

const formatWeekRange = (date: Date): string => {
    const startOfWeek = new Date(date);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    return `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
};

const formatSelectedSlot = (slot: SelectedSlot): string => {
    const date = new Date(slot.datetime);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const goToPreviousWeek = (): void => {
    const newWeek = new Date(currentWeek.value);
    newWeek.setDate(newWeek.getDate() - 7);
    currentWeek.value = newWeek;
};

const goToNextWeek = (): void => {
    const newWeek = new Date(currentWeek.value);
    newWeek.setDate(newWeek.getDate() + 7);
    currentWeek.value = newWeek;
};

const isSlotAvailable = (date: string, time: string): boolean => {
    return availableSlots.value.some((slot) => slot.date === date && slot.time === time && slot.available);
};

const isSlotBusy = (date: string, time: string): boolean => {
    return availableSlots.value.some((slot) => slot.date === date && slot.time === time && !slot.available);
};

const isUserAvailable = (date: string, time: string): boolean => {
    const key = `${date}-${time}`;
    return userAvailability.value[key] || false;
};

const selectTimeSlot = (date: string, time: string): void => {
    const datetime = `${date}T${time}:00`;
    selectedSlot.value = {
        date,
        time,
        datetime,
    };
};

const toggleAvailability = (date: string, time: string): void => {
    const key = `${date}-${time}`;
    userAvailability.value[key] = !userAvailability.value[key];

    // Save to backend
    saveAvailability(date, time, userAvailability.value[key]);
};

const fetchAvailableSlots = async (): Promise<void> => {
    if (props.mode !== 'schedule' || props.participants.length === 0) return;

    try {
        loading.value = true;

        const startDate = weekDays.value[0].date;
        const endDate = weekDays.value[6].date;
        const userIds = props.participants.map((p) => p.id);

        const response = await fetch('/api/calendar/find-slots', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                user_ids: userIds,
                start_date: startDate,
                end_date: endDate,
                duration_minutes: sessionDuration.value,
                working_hours: [workingHours.value.start, workingHours.value.end],
            }),
        });

        if (response.ok) {
            const data = await response.json();
            availableSlots.value = data.available_slots.map((slot: any) => ({
                date: slot.date,
                time: slot.time,
                available: true,
            }));
        }
    } catch (error) {
        console.error('Failed to fetch available slots:', error);
    } finally {
        loading.value = false;
    }
};

const fetchUserAvailability = async (): Promise<void> => {
    if (props.mode !== 'availability') return;

    try {
        const startDate = weekDays.value[0].date;
        const endDate = weekDays.value[6].date;

        const response = await fetch(`/api/calendar/availability?start_date=${startDate}&end_date=${endDate}`);

        if (response.ok) {
            const data = await response.json();
            // Process availability data
            userAvailability.value = {};
            data.availability.forEach((slot: any) => {
                const date = slot.start.split('T')[0];
                const time = slot.start.split('T')[1].substring(0, 5);
                const key = `${date}-${time}`;
                userAvailability.value[key] = false; // Busy time
            });
        }
    } catch (error) {
        console.error('Failed to fetch user availability:', error);
    }
};

const saveAvailability = async (date: string, time: string, available: boolean): Promise<void> => {
    try {
        // This would save availability to the backend
        console.log(`Saving availability: ${date} ${time} = ${available}`);
    } catch (error) {
        console.error('Failed to save availability:', error);
    }
};

const updateWorkingHours = (): void => {
    // Update working hours and refresh data
    if (props.mode === 'schedule') {
        fetchAvailableSlots();
    } else {
        fetchUserAvailability();
    }
};

const scheduleSession = async (): Promise<void> => {
    if (!selectedSlot.value || !props.mentorId || !props.menteeId) return;

    try {
        loading.value = true;

        const response = await fetch('/api/calendar/schedule-mentorship', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                mentor_id: props.mentorId,
                mentee_id: props.menteeId,
                start_time: selectedSlot.value.datetime,
                duration_minutes: sessionDuration.value,
                topic: 'Mentorship Session',
            }),
        });

        if (response.ok) {
            const data = await response.json();
            emit('sessionScheduled', data.session);
            selectedSlot.value = null;
            await fetchAvailableSlots(); // Refresh available slots
        } else {
            console.error('Failed to schedule session');
        }
    } catch (error) {
        console.error('Error scheduling session:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for week changes
watch(currentWeek, () => {
    if (props.mode === 'schedule') {
        fetchAvailableSlots();
    } else {
        fetchUserAvailability();
    }
});

// Watch for participants changes
watch(
    () => props.participants,
    () => {
        if (props.mode === 'schedule') {
            fetchAvailableSlots();
        }
    },
    { deep: true },
);

onMounted(() => {
    if (props.mode === 'schedule') {
        fetchAvailableSlots();
    } else {
        fetchUserAvailability();
    }
});
</script>
