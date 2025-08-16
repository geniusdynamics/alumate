<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { graduateMenuItems, employerMenuItems, institutionAdminMenuItems, superAdminMenuItems, personalMenuItems } from '@/lib/navigation'
import { HomeIcon, UsersIcon, BriefcaseIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'

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
    const baseItems = [
        { name: 'Home', href: route('dashboard'), icon: HomeIcon, active: route().current('dashboard') },
    ];

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
        <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-t-lg">
            <div class="grid grid-cols-4 h-16" role="list">
                <div v-for="item in bottomNavItems" :key="item.name" class="flex items-center justify-center">
                    <button v-if="item.action" @click="item.action" class="flex flex-col items-center justify-center w-full h-full text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                        <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
                        <span class="text-xs truncate">{{ item.name }}</span>
                    </button>
                    <Link v-else :href="item.href" class="flex flex-col items-center justify-center w-full h-full" :class="[item.active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400']">
                        <component :is="item.icon" class="h-6 w-6" aria-hidden="true" />
                        <span class="text-xs truncate">{{ item.name }}</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Full Screen Menu Overlay -->
        <Dialog :open="isMenuOpen" @update:open="isMenuOpen = $event">
            <DialogContent class="sm:max-w-[425px] h-full flex flex-col p-0">
                <DialogHeader class="p-6 pb-0">
                    <DialogTitle class="flex justify-between items-center">
                        <span>Menu</span>
                         <button @click="closeMenu" class="p-2 -mr-2">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </DialogTitle>
                </DialogHeader>
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                     <!-- Role-specific Menu -->
                    <div v-if="currentRoleMenu.length > 0" class="space-y-1">
                         <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">My Tools</h3>
                        <Link v-for="item in renderableMenu(currentRoleMenu)" :key="item.title" :href="item.href" @click="closeMenu" class="flex items-center px-2 py-2 text-base font-medium rounded-md" :class="[item.active ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                            <component :is="item.icon" class="mr-3 h-6 w-6" />
                            {{ item.title }}
                        </Link>
                    </div>
                    <!-- Personal Menu -->
                     <div class="space-y-1">
                        <h3 class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</h3>
                        <Link v-for="item in renderableMenu(personalMenuItems)" :key="item.title" :href="item.href" @click="closeMenu" class="flex items-center px-2 py-2 text-base font-medium rounded-md" :class="[item.active ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                             <component :is="item.icon" class="mr-3 h-6 w-6" />
                            {{ item.title }}
                        </Link>
                    </div>
                     <!-- Logout -->
                    <div class="space-y-1">
                         <Link :href="route('logout')" method="post" as="button" @click="closeMenu" class="w-full flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                           Logout
                        </Link>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</script>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}

.mobile-navigation {
    /* Ensure proper z-index stacking */
}
</style>
