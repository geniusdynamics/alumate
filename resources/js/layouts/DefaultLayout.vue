<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { Sidebar } from '@/components/ui/sidebar';
import AppHeader from '@/components/layout/AppHeader.vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import UserFlowIntegration from '@/components/UserFlowIntegration.vue';
import RealTimeUpdates from '@/components/RealTimeUpdates.vue';
import OnboardingSystem from '@/components/onboarding/OnboardingSystem.vue';
import MobileNavigation from '@/components/MobileNavigation.vue';
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue';
import { initializeTheme } from '@/composables/useTheme';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    title?: string;
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    title: 'Dashboard',
    breadcrumbs: () => [],
});

const page = usePage();
const flash = computed(() => page.props.flash);

// Initialize theme system
onMounted(() => {
    initializeTheme();
});
</script>

<template>
    <div>
        <Head :title="title" />
        
        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />
        
        <SidebarProvider>
            <AppSidebar class="hidden lg:block" />
            <SidebarInset>
                <AppHeader :breadcrumbs="breadcrumbs" class="hidden lg:block" />
                <div class="flex flex-1 flex-col gap-4 lg:p-4">
                    <!-- Real-time Updates Component -->
                    <RealTimeUpdates 
                        :show-activity-feed="false"
                        :show-post-updates="true"
                        :show-connection-status="true"
                        :show-event-updates="true"
                        :show-job-updates="true"
                        :show-mentorship-updates="true"
                    />
                    
                    <div v-if="flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Success!</strong>
                        <span class="block sm:inline">{{ flash.success }}</span>
                    </div>
                    <slot />
                </div>
                
                <!-- User Flow Integration Component -->
                <UserFlowIntegration />
                
                <!-- Onboarding System -->
                <OnboardingSystem />
            </SidebarInset>
        </SidebarProvider>

        <!-- Mobile Navigation -->
        <MobileNavigation />
    </div>
</template>

