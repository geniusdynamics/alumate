<template>
    <div class="audience-selector-container">
        <div class="audience-selector">
            <div class="selector-container">
                <div class="selector-tabs">
                    <button
                        @click="selectAudience('individual')"
                        :class="[
                            'selector-tab',
                            {
                                'active': audience === 'individual',
                                'detecting': isDetecting && audience === 'individual'
                            }
                        ]"
                        :disabled="isDetecting"
                    >
                        <div class="tab-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="tab-content">
                            <div class="tab-title">I'm an Alumnus</div>
                            <div class="tab-subtitle">Individual professional</div>
                        </div>
                    </button>

                    <button
                        @click="selectAudience('institutional')"
                        :class="[
                            'selector-tab',
                            {
                                'active': audience === 'institutional',
                                'detecting': isDetecting && audience === 'institutional'
                            }
                        ]"
                        :disabled="isDetecting"
                    >
                        <div class="tab-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="tab-content">
                            <div class="tab-title">I'm an Administrator</div>
                            <div class="tab-subtitle">Institution or organization</div>
                        </div>
                    </button>

                    <button
                        @click="selectAudience('employer')"
                        :class="[
                            'selector-tab',
                            {
                                'active': audience === 'employer',
                                'detecting': isDetecting && audience === 'employer'
                            }
                        ]"
                        :disabled="isDetecting"
                    >
                        <div class="tab-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6m8 0H8m0 10h8a2 2 0 002-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="tab-content">
                            <div class="tab-title">I'm an Employer</div>
                            <div class="tab-subtitle">Hiring & recruiting</div>
                        </div>
                    </button>
                </div>

                <!-- Audience Descriptions -->
                <div v-if="showDescription" class="audience-description">
                    <div v-if="audience === 'individual'" class="description-content">
                        <h3 class="description-title">For Alumni Professionals</h3>
                        <p class="description-text">
                            Connect with fellow alumni, advance your career, and access exclusive opportunities through our professional networking platform.
                        </p>
                        <ul class="description-features">
                            <li>Career advancement opportunities</li>
                            <li>Professional networking</li>
                            <li>Mentorship programs</li>
                            <li>Industry insights and resources</li>
                        </ul>
                    </div>

                    <div v-if="audience === 'institutional'" class="description-content">
                        <h3 class="description-title">For Institutions & Organizations</h3>
                        <p class="description-text">
                            Strengthen alumni engagement, increase donations, and build lasting relationships with comprehensive alumni management tools.
                        </p>
                        <ul class="description-features">
                            <li>Alumni engagement analytics</li>
                            <li>Fundraising campaign management</li>
                            <li>Event planning and coordination</li>
                            <li>Communication and outreach tools</li>
                        </ul>
                    </div>

                    <div v-if="audience === 'employer'" class="description-content">
                        <h3 class="description-title">For Employers & Recruiters</h3>
                        <p class="description-text">
                            Access top talent from leading institutions, streamline your recruitment process, and build lasting partnerships with universities.
                        </p>
                        <ul class="description-features">
                            <li>Access to verified alumni talent pool</li>
                            <li>Advanced candidate filtering and search</li>
                            <li>University partnership opportunities</li>
                            <li>Recruitment analytics and insights</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { AudienceDetectionResult, AudiencePreference, AudienceType, DetectionFactor } from '@/types/homepage';
import { onMounted, ref, watch } from 'vue';

// Props
interface Props {
    audience: AudienceType;
    autoDetect?: boolean;
    showDescription?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    audience: 'individual',
    autoDetect: true,
    showDescription: true,
});

// Emits
const emit = defineEmits<{
    'update:audience': [audience: AudienceType];
    'audience-changed': [audience: AudienceType, preference: AudiencePreference];
}>();

// Reactive data
const audience = ref<AudienceType>(props.audience);
const isDetecting = ref(false);

// Session storage key
const STORAGE_KEY = 'homepage_audience_preference';

// Methods
const selectAudience = (newAudience: AudienceType, source: 'manual' | 'auto_detected' | 'url_param' = 'manual') => {
    if (audience.value !== newAudience) {
        const previousAudience = audience.value;
        audience.value = newAudience;

        // Create preference object
        const preference: AudiencePreference = {
            type: newAudience,
            timestamp: new Date(),
            source,
            sessionId: getSessionId(),
        };

        // Store preference in session storage
        storeAudiencePreference(preference);

        // Emit events
        emit('update:audience', newAudience);
        emit('audience-changed', newAudience, preference);

        // Track analytics
        trackAudienceChange(newAudience, previousAudience, source);
    }
};

const detectAudience = (): AudienceDetectionResult => {
    const factors: DetectionFactor[] = [];
    let totalWeight = 0;
    let institutionalScore = 0;
    let employerScore = 0;

    // Check URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('audience')) {
        const audienceParam = urlParams.get('audience');
        if (audienceParam === 'institutional' || audienceParam === 'admin') {
            factors.push({
                type: 'url_param',
                value: audienceParam,
                weight: 0.8,
                contribution: 0.8,
            });
            institutionalScore += 0.8;
            totalWeight += 0.8;
        } else if (audienceParam === 'employer') {
            factors.push({
                type: 'url_param',
                value: audienceParam,
                weight: 0.8,
                contribution: 0.8,
            });
            employerScore += 0.8;
            totalWeight += 0.8;
        }
    }

    // Check referrer
    if (document.referrer) {
        const referrer = new URL(document.referrer);
        const institutionalDomains = ['.edu', '.ac.', 'university', 'college', 'admin'];
        const employerDomains = ['recruit', 'hiring', 'employer', 'jobs', 'careers'];
        const isInstitutional = institutionalDomains.some((domain) => referrer.hostname.includes(domain));
        const isEmployer = employerDomains.some((domain) => referrer.hostname.includes(domain));

        if (isInstitutional) {
            factors.push({
                type: 'referrer',
                value: referrer.hostname,
                weight: 0.6,
                contribution: 0.6,
            });
            institutionalScore += 0.6;
            totalWeight += 0.6;
        } else if (isEmployer) {
            factors.push({
                type: 'referrer',
                value: referrer.hostname,
                weight: 0.6,
                contribution: 0.6,
            });
            employerScore += 0.6;
            totalWeight += 0.6;
        }
    }

    // Check stored preference
    const storedPreference = getStoredAudiencePreference();
    if (storedPreference) {
        const weight = storedPreference.source === 'manual' ? 0.9 : 0.5;
        factors.push({
            type: 'session_history',
            value: storedPreference.type,
            weight,
            contribution: storedPreference.type === 'institutional' ? weight : (storedPreference.type === 'employer' ? weight : 0),
        });

        if (storedPreference.type === 'institutional') {
            institutionalScore += weight;
        } else if (storedPreference.type === 'employer') {
            employerScore += weight;
        }
        totalWeight += weight;
    }

    // Calculate confidence and determine audience
    const institutionalConfidence = totalWeight > 0 ? Math.min(institutionalScore / totalWeight, 1) : 0;
    const employerConfidence = totalWeight > 0 ? Math.min(employerScore / totalWeight, 1) : 0;
    
    let detectedAudience: AudienceType = 'individual';
    let confidence = 0;
    
    if (employerConfidence > 0.5 && employerConfidence >= institutionalConfidence) {
        detectedAudience = 'employer';
        confidence = employerConfidence;
    } else if (institutionalConfidence > 0.5) {
        detectedAudience = 'institutional';
        confidence = institutionalConfidence;
    }

    return {
        detectedAudience,
        confidence,
        factors,
        fallback: 'individual',
    };
};

const storeAudiencePreference = (preference: AudiencePreference) => {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(preference));
    } catch (error) {
        console.warn('Failed to store audience preference:', error);
    }
};

const getStoredAudiencePreference = (): AudiencePreference | null => {
    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (stored) {
            const preference = JSON.parse(stored) as AudiencePreference;
            // Convert timestamp back to Date object
            preference.timestamp = new Date(preference.timestamp);
            return preference;
        }
    } catch (error) {
        console.warn('Failed to retrieve audience preference:', error);
    }
    return null;
};

const getSessionId = (): string => {
    let sessionId = sessionStorage.getItem('homepage_session_id');
    if (!sessionId) {
        sessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        sessionStorage.setItem('homepage_session_id', sessionId);
    }
    return sessionId;
};

const trackAudienceChange = (newAudience: AudienceType, previousAudience: AudienceType, source: string) => {
    // Analytics tracking
    if (typeof window !== 'undefined' && 'gtag' in window) {
        (window as any).gtag('event', 'audience_change', {
            new_audience: newAudience,
            previous_audience: previousAudience,
            change_source: source,
            session_id: getSessionId(),
        });
    }

    // Custom analytics event
    const event = new CustomEvent('homepage:audience-changed', {
        detail: {
            newAudience,
            previousAudience,
            source,
            timestamp: new Date(),
        },
    });
    window.dispatchEvent(event);
};

// Initialize audience detection on mount
onMounted(() => {
    if (props.autoDetect) {
        isDetecting.value = true;

        // Small delay to ensure DOM is ready
        setTimeout(() => {
            const detection = detectAudience();

            // Use detected audience if confidence is high enough
            if (detection.confidence > 0.7 && detection.detectedAudience !== audience.value) {
                selectAudience(detection.detectedAudience, 'auto_detected');
            }

            isDetecting.value = false;
        }, 100);
    }
});

// Watch for prop changes
watch(
    () => props.audience,
    (newAudience) => {
        if (audience.value !== newAudience) {
            audience.value = newAudience;
        }
    },
);
</script>

<style scoped>
.audience-selector-container {
    @apply w-full max-w-6xl mx-auto mb-8;
}

.audience-selector {
    @apply bg-white rounded-2xl shadow-lg border border-gray-100 p-6;
}

.selector-container {
    @apply w-full;
}

.selector-tabs {
    @apply grid grid-cols-1 md:grid-cols-3 gap-4 mb-6;
}

.selector-tab {
    @apply flex flex-col items-center p-6 rounded-xl border-2 border-gray-200 bg-white transition-all duration-300 hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer;
}

.selector-tab.active {
    @apply border-blue-500 bg-blue-50 text-blue-700 shadow-md;
}

.selector-tab.detecting {
    @apply border-blue-300 bg-blue-50 animate-pulse;
}

.selector-tab:disabled {
    @apply cursor-not-allowed opacity-75;
}

.tab-icon {
    @apply mb-3 p-3 rounded-full bg-gray-100 transition-colors duration-300;
}

.selector-tab.active .tab-icon {
    @apply bg-blue-100 text-blue-600;
}

.tab-content {
    @apply text-center;
}

.tab-title {
    @apply font-semibold text-gray-900 mb-1;
}

.selector-tab.active .tab-title {
    @apply text-blue-700;
}

.tab-subtitle {
    @apply text-sm text-gray-500;
}

.selector-tab.active .tab-subtitle {
    @apply text-blue-600;
}

.audience-description {
    @apply mt-6 p-6 bg-gray-50 rounded-xl;
}

.description-content {
    @apply text-left;
}

.description-title {
    @apply text-lg font-semibold text-gray-900 mb-3;
}

.description-text {
    @apply text-gray-600 mb-4 leading-relaxed;
}

.description-features {
    @apply list-disc list-inside space-y-1 text-sm text-gray-600;
}

.description-features li {
    @apply flex items-start;
}

.description-features li::before {
    content: '✓';
    @apply text-green-500 font-bold mr-2 mt-0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .selector-tabs {
        @apply grid-cols-1 gap-3;
    }
    
    .selector-tab {
        @apply flex-row p-4 text-left;
    }
    
    .tab-icon {
        @apply mb-0 mr-4 p-2;
    }
    
    .tab-content {
        @apply text-left flex-1;
    }
    
    .audience-description {
        @apply p-4;
    }
}

@media (max-width: 480px) {
    .audience-selector {
        @apply p-4;
    }
    
    .selector-tab {
        @apply p-3;
    }
    
    .tab-icon {
        @apply p-2;
    }
}
</style>















