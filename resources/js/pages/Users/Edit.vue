<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import type { BreadcrumbItemType, Role, User } from '@/types/index.d';

const props = defineProps<{
    user: User;
    roles: Role[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Users', href: route('users.index') },
    { title: 'Edit' },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    roles: props.user.roles?.map((r) => r.id) ?? [],
});

const submit = () => {
    form.put(route('users.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="`Edit User: ${user.name}`" />
    <DefaultLayout title="Edit User" :breadcrumbs="breadcrumbs">
        <Card>
            <CardHeader>
                <CardTitle>Edit User: {{ user.name }}</CardTitle>
                <CardDescription>Update user details and assigned roles.</CardDescription>
            </CardHeader>
            <form @submit.prevent="submit">
                <CardContent class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="John Doe" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email" placeholder="john.doe@example.com" required />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input id="password" v-model="form.password" type="password" />
                        <p class="text-muted-foreground text-sm">Leave blank to keep the current password.</p>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                    </div>

                    <div>
                        <Label class="mb-2 block">Roles</Label>
                        <div class="grid grid-cols-2 gap-4 rounded-md border p-4 md:grid-cols-4">
                            <div v-for="role in props.roles" :key="role.id" class="flex items-center space-x-2">
                                <Checkbox :id="`role-${role.id}`" :value="role.id" v-model:checked="form.roles" />
                                <Label :for="`role-${role.id}`" class="font-normal capitalize">{{ role.name }}</Label>
                            </div>
                        </div>
                        <InputError :message="form.errors.roles" class="mt-2" />
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('users.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </DefaultLayout>
</template>
