<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePage, Link } from '@inertiajs/vue3';
import AppLogo from '@/components/common/AppLogo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ChevronUp } from 'lucide-vue-next';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { computed } from 'vue';
import { graduateMenuItems, employerMenuItems, institutionAdminMenuItems, superAdminMenuItems, personalMenuItems } from '@/lib/navigation';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// --- Helper Functions for Role & Permission Checks ---
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);
const userPermissions = computed(() => user.value?.permissions || []);

const hasRole = (roleName: string) => userRoles.value.includes(roleName);
const hasPermission = (permissionName: string) => userPermissions.value.includes(permissionName);

const can = (item: any) => {
    if (item.permission) return hasPermission(item.permission);
    if (item.role) return hasRole(item.role);
    return true; // Public link
};

const renderableMenu = (items: any[]) => items.filter(can);

</script>

<template>
    <Sidebar>
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg">
                        <AppLogo :show-text="false" class="h-8 w-8" />
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">Alumni Platform</span>
                            <span class="truncate text-xs">Menu</span>
                        </div>
                        <NotificationDropdown />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        
        <SidebarContent class="space-y-4">
            <!-- Graduate Menu -->
            <SidebarGroup v-if="hasRole('graduate') && renderableMenu(graduateMenuItems).length > 0">
                <SidebarGroupLabel>My Portal</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in renderableMenu(graduateMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active" :data-tour="item.tourTarget">
                                <component :is="item.icon" class="h-5 w-5" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Employer Menu -->
            <SidebarGroup v-if="hasRole('employer') && renderableMenu(employerMenuItems).length > 0">
                <SidebarGroupLabel>Employer Tools</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in renderableMenu(employerMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" class="h-5 w-5" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Institution Admin Menu -->
            <SidebarGroup v-if="hasRole('institution-admin') && renderableMenu(institutionAdminMenuItems).length > 0">
                <SidebarGroupLabel>Admin Tools</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in renderableMenu(institutionAdminMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" class="h-5 w-5" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Super Admin Menu -->
            <SidebarGroup v-if="hasRole('super-admin') && renderableMenu(superAdminMenuItems).length > 0">
                <SidebarGroupLabel>Super Admin</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in renderableMenu(superAdminMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" class="h-5 w-5" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Personal Menu -->
            <SidebarGroup>
                <SidebarGroupLabel>Account</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in renderableMenu(personalMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" class="h-5 w-5" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>
        
        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton size="lg">
                                <Avatar class="h-8 w-8 rounded-lg">
                                    <AvatarFallback class="rounded-lg">
                                        {{ user?.name?.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ user?.name }}</span>
                                    <span class="truncate text-xs">{{ user?.email }}</span>
                                </div>
                                <ChevronUp class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56" align="end" side="top">
                            <DropdownMenuItem :as="Link" :href="route('settings.profile')">
                                Settings
                            </DropdownMenuItem>
                            <DropdownMenuItem :as="Link" :href="route('logout')" method="post">
                                Logout
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>

