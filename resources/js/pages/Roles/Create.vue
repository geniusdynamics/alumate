<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { groupBy } from 'lodash-es';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import type { BreadcrumbItemType, Permission } from '@/types/index.d';

const props = defineProps<{
    permissions: Permission[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Roles', href: route('roles.index') },
    { title: 'Create' },
];

const form = useForm({
    name: '',
    permissions: [] as number[],
});

const groupedPermissions = computed(() => {
    return groupBy(props.permissions, (permission) => {
        const parts = permission.name.split(' ');
        return parts.length > 1 ? parts.slice(1).join(' ') : 'other';
    });
});

const submit = () => {
    form.post(route('roles.store'));
};
</script>

<template>
    <Head title="Create Role" />
    <DefaultLayout title="Create Role" :breadcrumbs="breadcrumbs">
        <Card>
            <CardHeader>
                <CardTitle>Create New Role</CardTitle>
                <CardDescription>Fill in the details to create a new role and assign permissions.</CardDescription>
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
                        {{ form.processing ? 'Creating...' : 'Create Role' }}
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </DefaultLayout>
</template>
