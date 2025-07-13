<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    graduate: Object,
    profile: Object,
});

const form = useForm({
    bio: props.profile.bio,
    work_experience: props.profile.work_experience,
    skills: props.profile.skills,
    profile_picture: null,
});

const submit = () => {
    form.post(route('profile.update'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Profile</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your profile information.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="bio">Bio</label>
                <textarea id="bio" v-model="form.bio"></textarea>
            </div>
            <div>
                <label for="work_experience">Work Experience</label>
                <textarea id="work_experience" v-model="form.work_experience"></textarea>
            </div>
            <div>
                <label for="skills">Skills</label>
                <textarea id="skills" v-model="form.skills"></textarea>
            </div>
            <div>
                <label for="profile_picture">Profile Picture</label>
                <input type="file" @input="form.profile_picture = $event.target.files[0]" />
            </div>

            <div class="flex items-center gap-4">
                <button type="submit">Save</button>
            </div>
        </form>
    </section>
</template>
