<script setup lang="ts">
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Separator } from '@/components/ui/separator';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItemType } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { User, LogOut, Settings, Bell, Plus } from 'lucide-vue-next';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import PostCreator from '@/Components/PostCreator.vue';
import HelpButton from '@/components/onboarding/HelpButton.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';

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
        .map(word => word.charAt(0))
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
        <div class="flex-1 max-w-md mx-4">
            <GlobalSearch placeholder="Search alumni, jobs, events, and more..." />
        </div>
        
        <!-- Header Actions -->
        <div class="flex items-center gap-2">
            <!-- Create Post Button -->
            <Dialog v-model:open="showPostCreator">
                <DialogTrigger as-child>
                    <Button variant="outline" size="sm">
                        <Plus class="h-4 w-4 mr-2" />
                        Post
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-w-2xl">
                    <PostCreator 
                        :user-circles="[]"
                        :user-groups="[]"
                        @post-created="handlePostCreated"
                    />
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

