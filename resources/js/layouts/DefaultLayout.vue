<script setup lang="ts">
import AppHeader from '@/Components/layout/AppHeader.vue';
import AppSidebar from '@/Components/layout/AppSidebar.vue';
import MobileHamburgerMenu from '@/Components/MobileHamburgerMenu.vue';
import MobileNavigation from '@/Components/MobileNavigation.vue';
import OnboardingSystem from '@/Components/onboarding/OnboardingSystem.vue';
import PWAIntegration from '@/Components/PWA/PWAIntegration.vue';
import RealTimeStatus from '@/Components/RealTimeStatus.vue';
import RealTimeUpdates from '@/Components/RealTimeUpdates.vue';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import UserFlowIntegration from '@/Components/UserFlowIntegration.vue';
import { usePerformanceMonitoring } from '@/Composables/usePerformanceMonitoring';
import { initializeTheme } from '@/Composables/useTheme';
import type { BreadcrumbItemType } from '@/Types';
import { BellIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

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
const mobileNavigationRef = ref(null);

// Performance monitoring for layout
const { trackInteraction, trackNavigation } = usePerformanceMonitoring('DefaultLayout');

// Initialize theme system
onMounted(() => {
    initializeTheme();
});

// Mobile navigation methods
const openMobileSearch = () => {
    trackInteraction('mobile-search-open');
    if (mobileNavigationRef.value) {
        mobileNavigationRef.value.openSearch();
    }
};

const openMobileNotifications = () => {
    trackInteraction('mobile-notifications-open');
    if (mobileNavigationRef.value) {
        mobileNavigationRef.value.openNotifications();
    }
};

// PWA Event Handlers
const handlePWAReady = (status: any) => {
    console.log('PWA Ready:', status);
};

const handleAppInstalled = (event: any) => {
    console.log('App Installed:', event);
    // You could show a success message or update UI state
};

const handlePushSubscribed = (subscription: any) => {
    console.log('Push Notifications Enabled:', subscription);
    // You could update user preferences or show confirmation
};

const handleOfflineMode = () => {
    console.log('App is offline');
    // You could update UI to show offline state
};

const handleOnlineMode = (event: any) => {
    console.log('App is online:', event);
    // You could trigger data sync or update UI
};
</script>

<template>
    <div>
        <Head :title="title" />

        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />

        <!-- Mobile Header for smaller screens -->
        <div class="sticky-mobile lg:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center space-x-3">
                    <img
                        :src="$page.props.app?.logo || '/images/logo.png'"
                        :alt="$page.props.app?.name || 'Alumni Platform'"
                        class="h-8 w-8 rounded"
                    />
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ title }}
                    </h1>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Mobile search button -->
                    <button
                        @click="openMobileSearch"
                        class="touch-target rounded-lg p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                        aria-label="Search"
                    >
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>
                    <!-- Mobile notifications button -->
                    <button
                        @click="openMobileNotifications"
                        class="touch-target relative rounded-lg p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                        aria-label="Notifications"
                    >
                        <BellIcon class="h-5 w-5" />
                        <span
                            v-if="page.props.auth?.unreadNotifications"
                            class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500"
                        >
                            <span class="text-xs text-white">{{ page.props.auth.unreadNotifications }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

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

                    <div v-if="flash.success" class="relative rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700" role="alert">
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
        <MobileNavigation ref="mobileNavigationRef" />

        <!-- PWA Integration -->
        <PWAIntegration
            :enable-push-notifications="true"
            :enable-install-prompt="true"
            :enable-offline-indicator="true"
            @pwa-ready="handlePWAReady"
            @app-installed="handleAppInstalled"
            @push-subscribed="handlePushSubscribed"
            @offline-mode="handleOfflineMode"
            @online-mode="handleOnlineMode"
        />

        <!-- Real-time Connection Status -->
        <RealTimeStatus :position="'bottom-right'" :show-details="false" class="fixed" />
    </div>
</template>





