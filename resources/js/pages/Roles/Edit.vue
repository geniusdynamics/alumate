<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { groupBy } from 'lodash-es';

import type { BreadcrumbItemType, Permission, Role } from '@/types/index.d';

const props = defineProps<{
    role: Role;
    permissions: Permission[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Roles', href: route('roles.index') },
    { title: 'Edit' },
];

const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions?.map((p) => p.id) ?? [],
});

const groupedPermissions = computed(() => {
    return groupBy(props.permissions, (permission) => {
        const parts = permission.name.split(' ');
        return parts.length > 1 ? parts.slice(1).join(' ') : 'other';
    });
});

const submit = () => {
    form.put(route('roles.update', props.role.id));
};
</script>

<template>
    <Head :title="`Edit Role: ${role.name}`" />
    <DefaultLayout title="Edit Role" :breadcrumbs="breadcrumbs">
        <Card>
            <CardHeader>
                <CardTitle>Edit Role: {{ role.name }}</CardTitle>
                <CardDescription>Update the role details and assigned permissions.</CardDescription>
            </CardHeader>
            <form @submit.prevent="submit">
                <CardContent class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Role Name</Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="e.g., Editor" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label class="mb-2 block">Permissions</Label>
                        <div class="space-y-4">
                            <div v-for="(group, groupName) in groupedPermissions" :key="groupName" class="space-y-2 rounded-md border p-4">
                                <h3 class="font-medium capitalize">{{ groupName }}</h3>
                                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                    <div v-for="permission in group" :key="permission.id" class="flex items-center space-x-2">
                                        <Checkbox :id="`permission-${permission.id}`" :value="permission.id" v-model:checked="form.permissions" />
                                        <Label :for="`permission-${permission.id}`" class="font-normal capitalize">{{ permission.name }}</Label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.permissions" class="mt-2" />
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('roles.index')">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </DefaultLayout>
</template>
