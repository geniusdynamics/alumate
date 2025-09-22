<script setup>
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
<<<<<<< HEAD:resources/js/components/MobileNavigation.vue
import { employerMenuItems, graduateMenuItems, institutionAdminMenuItems, personalMenuItems, superAdminMenuItems } from '@/Lib/navigation';
=======
import { employerMenuItems, graduateMenuItems, institutionAdminMenuItems, personalMenuItems, superAdminMenuItems } from '@/lib/navigation';
>>>>>>> origin/db1:resources/js/Components/MobileNavigation.vue
import { Bars3Icon, BriefcaseIcon, HomeIcon, UsersIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isMenuOpen = ref(false);

// --- Role & Permission Helpers ---
const userRoles = computed(() => user.value?.roles?.map((role) => role.name) || []);
const userPermissions = computed(() => user.value?.permissions || []);

const hasRole = (roleName) => userRoles.value.includes(roleName);
const hasPermission = (permissionName) => userPermissions.value.includes(permissionName);

const can = (item) => {
    if (item.permission) return hasPermission(item.permission);
    if (item.role) return hasRole(item.role);
    return true;
};

const renderableMenu = (items) => items.filter(can);

// --- Determine Current Menu based on Role ---
const currentRoleMenu = computed(() => {
    if (hasRole('super-admin')) return superAdminMenuItems;
    if (hasRole('institution-admin')) return institutionAdminMenuItems;
    if (hasRole('employer')) return employerMenuItems;
    if (hasRole('graduate')) return graduateMenuItems;
    return [];
});

// --- Dynamic Bottom Navigation ---
const bottomNavItems = computed(() => {
    const baseItems = [{ name: 'Home', href: route('dashboard'), icon: HomeIcon, active: route().current('dashboard') }];

    if (hasRole('graduate')) {
        baseItems.push({ name: 'Jobs', href: route('jobs.dashboard'), icon: BriefcaseIcon, active: page.url.startsWith('/jobs') });
        baseItems.push({ name: 'Alumni', href: route('alumni.directory'), icon: UsersIcon, active: page.url.startsWith('/alumni') });
    } else if (hasRole('employer')) {
        baseItems.push({ name: 'Jobs', href: route('jobs.dashboard'), icon: BriefcaseIcon, active: page.url.startsWith('/jobs') });
        baseItems.push({ name: 'Graduates', href: route('graduates.search'), icon: UsersIcon, active: page.url.startsWith('/graduates') });
    } else if (hasRole('institution-admin')) {
        baseItems.push({ name: 'Graduates', href: route('graduates.index'), icon: UsersIcon, active: page.url.startsWith('/graduates') });
        baseItems.push({ name: 'Jobs', href: route('jobs.public.index'), icon: BriefcaseIcon, active: page.url.startsWith('/jobs') });
    }

    baseItems.push({ name: 'Menu', action: () => (isMenuOpen.value = true), icon: Bars3Icon, active: false });

    return baseItems.slice(0, 4);
});

const closeMenu = () => {
    isMenuOpen.value = false;
};
</script>

<template>
    <div class="lg:hidden">
        <!-- Bottom Navigation Bar -->
        <nav class="shadow-t-lg fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="grid h-16 grid-cols-4" role="list">
                <div v-for="item in bottomNavItems" :key="item.name" class="flex items-center justify-center">
                    <button
                        v-if="item.action"
                        @click="item.action"
                        class="flex h-full w-full flex-col items-center justify-center text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                    >
                        <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
                        <span class="truncate text-xs">{{ item.name }}</span>
                    </button>
                    <Link
                        v-else
                        :href="item.href"
                        class="flex h-full w-full flex-col items-center justify-center"
                        :class="[
                            item.active
                                ? 'text-blue-600 dark:text-blue-400'
                                : 'text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400',
                        ]"
                    >
                        <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
                        <span class="truncate text-xs">{{ item.name }}</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Full Screen Menu Overlay -->
        <Dialog :open="isMenuOpen" @update:open="isMenuOpen = $event">
            <DialogContent class="flex h-full flex-col p-0 sm:max-w-[425px]">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle class="flex items-center justify-between">
                        <span>Menu</span>
                        <button @click="closeMenu" class="-mr-2 p-2">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </DialogTitle>
                </DialogHeader>
                <div class="flex-1 space-y-4 overflow-y-auto p-6">
                    <!-- Role-specific Menu -->
                    <div v-if="currentRoleMenu.length > 0" class="space-y-1">
                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500">My Tools</h3>
                        <Link
                            v-for="item in renderableMenu(currentRoleMenu)"
                            :key="item.title"
                            :href="item.href"
                            @click="closeMenu"
                            class="flex items-center rounded-md px-2 py-2 text-base font-medium"
                            :class="[
                                item.active
                                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                                    : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                        >
                            <component :is="item.icon" class="mr-3 h-6 w-6" />
                            {{ item.title }}
                        </Link>
                    </div>
                    <!-- Personal Menu -->
                    <div class="space-y-1">
                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Account</h3>
                        <Link
                            v-for="item in renderableMenu(personalMenuItems)"
                            :key="item.title"
                            :href="item.href"
                            @click="closeMenu"
                            class="flex items-center rounded-md px-2 py-2 text-base font-medium"
                            :class="[
                                item.active
                                    ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
                                    : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                        >
                            <component :is="item.icon" class="mr-3 h-6 w-6" />
                            {{ item.title }}
                        </Link>
                    </div>
                    <!-- Logout -->
                    <div class="space-y-1">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            @click="closeMenu"
                            class="flex w-full items-center rounded-md px-2 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Logout
                        </Link>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}

.mobile-navigation {
    /* Ensure proper z-index stacking */
}
</style>











