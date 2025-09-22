<script setup lang="ts">
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import PostCreator from '@/Components/PostCreator.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import HelpButton from '@/Components/onboarding/HelpButton.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/Components/ui/breadcrumb';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTrigger } from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Separator } from '@/Components/ui/separator';
import { SidebarTrigger } from '@/Components/ui/sidebar';
import type { BreadcrumbItemType } from '@/Types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Plus, Settings, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const notifications = computed(() => page.props.auth?.notifications || []);
const showPostCreator = ref(false);

const logout = () => {
    router.post(route('logout'));
};

const getUserInitials = (name: string) => {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const handlePostCreated = () => {
    showPostCreator.value = false;
    // Refresh current page if on timeline
    if (route().current('social.timeline')) {
        router.reload();
    }
};
</script>

<template>
    <header class="flex h-16 shrink-0 items-center gap-2 border-b px-4">
        <SidebarTrigger class="-ml-1" />
        <Separator orientation="vertical" class="mr-2 h-4" />
        <Breadcrumb v-if="breadcrumbs.length > 0" class="flex-1">
            <BreadcrumbList>
                <template v-for="(item, index) in breadcrumbs" :key="index">
                    <BreadcrumbItem>
                        <BreadcrumbLink v-if="item.href && index < breadcrumbs.length - 1" :as="Link" :href="item.href">
                            {{ item.title }}
                        </BreadcrumbLink>
                        <BreadcrumbPage v-else>
                            {{ item.title }}
                        </BreadcrumbPage>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
                </template>
            </BreadcrumbList>
        </Breadcrumb>

        <!-- Global Search Bar -->
        <div class="mx-4 max-w-md flex-1">
            <GlobalSearch placeholder="Search alumni, jobs, events, and more..." />
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-2">
            <!-- Create Post Button -->
            <Dialog v-model:open="showPostCreator">
                <DialogTrigger as-child>
                    <Button variant="outline" size="sm">
                        <Plus class="mr-2 h-4 w-4" />
                        Post
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-w-2xl">
                    <PostCreator :user-circles="[]" :user-groups="[]" @post-created="handlePostCreated" />
                </DialogContent>
            </Dialog>

            <!-- Notifications -->
            <NotificationDropdown :notifications="notifications" />

            <!-- Help Button -->
            <HelpButton />
        </div>

        <!-- User Menu -->
        <div class="ml-auto">
            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="relative h-8 w-8 rounded-full">
                        <Avatar class="h-8 w-8">
                            <AvatarImage :src="user.avatar" :alt="user.name" />
                            <AvatarFallback>{{ getUserInitials(user.name) }}</AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56" align="end" :side-offset="5">
                    <DropdownMenuLabel class="font-normal">
                        <div class="flex flex-col space-y-1">
                            <p class="text-sm font-medium leading-none">{{ user.name }}</p>
                            <p class="text-xs leading-none text-muted-foreground">{{ user.email }}</p>
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link :href="route('profile.show')" class="flex items-center">
                            <User class="mr-2 h-4 w-4" />
                            <span>Profile</span>
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <Link href="#" class="flex items-center">
                            <Settings class="mr-2 h-4 w-4" />
                            <span>Settings</span>
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="logout" class="flex items-center text-red-600 focus:text-red-600">
                        <LogOut class="mr-2 h-4 w-4" />
                        <span>Log out</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>















