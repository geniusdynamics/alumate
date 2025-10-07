<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showBanner"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="consent-title"
                aria-describedby="consent-description"
            >
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <div class="mb-4">
                        <h2
                            id="consent-title"
                            class="text-lg font-semibold text-gray-900"
                        >
                            Analytics Consent
                        </h2>
                        <p
                            id="consent-description"
                            class="mt-2 text-sm text-gray-600"
                        >
                            We use analytics to improve your experience. Your data helps us understand how our platform works and make it better for everyone.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <button
                            @click="grantConsent"
                            :disabled="loading"
                            class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                            aria-describedby="consent-description"
                        >
                            <span v-if="loading">Processing...</span>
                            <span v-else>Accept Analytics</span>
                        </button>

                        <button
                            @click="revokeConsent"
                            :disabled="loading"
                            class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                            aria-describedby="consent-description"
                        >
                            <span v-if="loading">Processing...</span>
                            <span v-else>Decline Analytics</span>
                        </button>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        <p>
                            You can change your consent preferences at any time in your account settings.
                        </p>
                        <p class="mt-1">
                            Learn more about our
                            <a
                                href="/privacy"
                                target="_blank"
                                class="text-blue-600 hover:text-blue-800 underline"
                            >
                                privacy policy
                            </a>.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
    forceShow?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    forceShow: false,
});

const showBanner = ref(false);
const loading = ref(false);

// Check if user has already made a consent decision
const hasConsentDecision = (): boolean => {
    const stored = localStorage.getItem('analytics_consent_decision');
    return stored !== null;
};

// Check if banner should be shown
const shouldShowBanner = (): boolean => {
    if (props.forceShow) return true;
    return !hasConsentDecision();
};

const grantConsent = async (): Promise<void> => {
    loading.value = true;

    try {
        await router.post('/api/consent/grant', {
            type: 'analytics',
        });

        localStorage.setItem('analytics_consent_decision', 'granted');
        showBanner.value = false;

        // Emit event for other components to react
        window.dispatchEvent(new CustomEvent('analytics-consent-granted'));
    } catch (error) {
        console.error('Failed to grant consent:', error);
    } finally {
        loading.value = false;
    }
};

const revokeConsent = async (): Promise<void> => {
    loading.value = true;

    try {
        await router.post('/api/consent/revoke', {
            type: 'analytics',
        });

        localStorage.setItem('analytics_consent_decision', 'revoked');
        showBanner.value = false;

        // Emit event for other components to react
        window.dispatchEvent(new CustomEvent('analytics-consent-revoked'));
    } catch (error) {
        console.error('Failed to revoke consent:', error);
    } finally {
        loading.value = false;
    }
};

// Watch for forceShow prop changes
watch(() => props.forceShow, (newValue) => {
    if (newValue) {
        showBanner.value = true;
    }
});

// Initialize on mount
onMounted(() => {
    showBanner.value = shouldShowBanner();
});
</script>

<style scoped>
/* Additional styles if needed */
</style>