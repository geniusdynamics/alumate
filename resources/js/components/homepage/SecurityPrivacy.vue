<template>
    <section class="security-privacy bg-gray-50 py-16">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ audience === 'institutional' ? 'Enterprise Security & Compliance' : 'Your Privacy & Security Matter' }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{
                        audience === 'institutional'
                            ? 'Enterprise-grade security and compliance standards to protect your institution and alumni data.'
                            : 'We protect your professional information with industry-leading security measures and transparent privacy practices.'
                    }}
                </p>
            </div>

            <!-- Privacy Highlights -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Privacy Protection</h3>
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="highlight in privacyHighlights || []"
                        :key="highlight.id"
                        class="rounded-lg bg-white p-6 shadow-sm transition-shadow duration-300 hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center">
                            <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                                <i :class="highlight.icon" class="text-xl text-blue-600"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ highlight.title }}</h4>
                        </div>
                        <p class="mb-4 text-gray-600">{{ highlight.description }}</p>
                        <ul class="space-y-2">
                            <li v-for="detail in highlight.details" :key="detail" class="flex items-start text-sm text-gray-600">
                                <i class="fas fa-check mr-2 mt-1 flex-shrink-0 text-green-500"></i>
                                {{ detail }}
                            </li>
                        </ul>
                        <a
                            v-if="highlight.learnMoreUrl"
                            :href="highlight.learnMoreUrl"
                            class="mt-4 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700"
                        >
                            Learn more
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Security Certifications -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Security Certifications</h3>
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4 lg:grid-cols-6">
                    <div
                        v-for="cert in securityCertifications || []"
                        :key="cert.id"
                        class="group cursor-pointer rounded-lg bg-white p-4 text-center shadow-sm transition-shadow duration-300 hover:shadow-md"
                        @click="openCertificationModal(cert)"
                    >
                        <img :src="cert.badge" :alt="cert.name" class="mx-auto mb-3 h-16 w-16 object-contain" />
                        <h5 class="mb-1 text-sm font-semibold text-gray-900">{{ cert.name }}</h5>
                        <p class="text-xs text-gray-600">{{ cert.category }}</p>
                        <div class="mt-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                            <i class="fas fa-external-link-alt text-xs text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alumni Verification Process -->
            <div class="mb-16">
                <div class="rounded-xl bg-white p-8 shadow-sm">
                    <div class="mb-8 text-center">
                        <h3 class="mb-4 text-2xl font-bold text-gray-900">{{ verificationProcess?.title || 'Alumni Verification Process' }}</h3>
                        <p class="mx-auto max-w-2xl text-gray-600">
                            {{
                                verificationProcess?.description ||
                                'Our multi-step verification ensures authentic connections and maintains the integrity of your professional network.'
                            }}
                        </p>
                    </div>

                    <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div v-for="step in verificationProcess?.steps || []" :key="step.id" class="text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                                <span class="text-lg font-bold text-blue-600">{{ step.stepNumber }}</span>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">{{ step.title }}</h4>
                            <p class="mb-2 text-sm text-gray-600">{{ step.description }}</p>
                            <div class="flex items-center justify-center text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                {{ step.estimatedTime }}
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-8 md:grid-cols-2">
                        <div>
                            <h4 class="mb-4 text-lg font-semibold text-gray-900">Benefits of Verification</h4>
                            <ul class="space-y-2">
                                <li v-for="benefit in verificationProcess?.benefits || []" :key="benefit" class="flex items-start text-gray-600">
                                    <i class="fas fa-check-circle mr-2 mt-1 flex-shrink-0 text-green-500"></i>
                                    {{ benefit }}
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="mb-4 text-lg font-semibold text-gray-900">Requirements</h4>
                            <ul class="space-y-2">
                                <li
                                    v-for="requirement in verificationProcess?.requirements || []"
                                    :key="requirement"
                                    class="flex items-start text-gray-600"
                                >
                                    <i class="fas fa-info-circle mr-2 mt-1 flex-shrink-0 text-blue-500"></i>
                                    {{ requirement }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Protection Information -->
            <div class="mb-16">
                <div class="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-8">
                    <div class="mb-8 text-center">
                        <h3 class="mb-4 text-2xl font-bold text-gray-900">{{ dataProtection?.title || 'Data Protection & Privacy' }}</h3>
                        <p class="mx-auto max-w-2xl text-gray-600">
                            {{
                                dataProtection?.description ||
                                'We implement comprehensive data protection measures based on privacy-by-design principles and international best practices.'
                            }}
                        </p>
                    </div>

                    <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <div v-for="principle in dataProtection?.principles || []" :key="principle.id" class="rounded-lg bg-white p-6">
                            <div class="mb-4 flex items-center">
                                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                    <i :class="principle.icon" class="text-blue-600"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ principle.title }}</h4>
                            </div>
                            <p class="mb-4 text-gray-600">{{ principle.description }}</p>
                            <ul class="space-y-1">
                                <li v-for="impl in principle.implementation || []" :key="impl" class="flex items-start text-sm text-gray-600">
                                    <i class="fas fa-dot-circle mr-2 mt-1.5 flex-shrink-0 text-xs text-blue-400"></i>
                                    {{ impl }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Your Rights</h4>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div v-for="right in dataProtection?.userRights || []" :key="right.id" class="border-l-4 border-blue-400 pl-4">
                                <h5 class="mb-1 font-semibold text-gray-900">{{ right.right }}</h5>
                                <p class="mb-2 text-sm text-gray-600">{{ right.description }}</p>
                                <p class="text-xs text-gray-500"><strong>How to exercise:</strong> {{ right.howToExercise }}</p>
                                <p class="text-xs text-gray-500"><strong>Response time:</strong> {{ right.responseTime }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Information -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Compliance Standards</h3>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="compliance in complianceInfo || []" :key="compliance.id" class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center">
                            <img v-if="compliance.badge" :src="compliance.badge" :alt="compliance.standard" class="mr-4 h-12 w-12 object-contain" />
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ compliance.standard }}</h4>
                                <p v-if="compliance.certificationDate" class="text-sm text-gray-500">
                                    Certified: {{ formatDate(compliance.certificationDate) }}
                                </p>
                            </div>
                        </div>
                        <p class="mb-4 text-gray-600">{{ compliance.description }}</p>
                        <div class="mb-4">
                            <h5 class="mb-2 font-semibold text-gray-900">Scope:</h5>
                            <ul class="space-y-1">
                                <li v-for="scope in compliance.scope" :key="scope" class="flex items-start text-sm text-gray-600">
                                    <i class="fas fa-check mr-2 mt-1 flex-shrink-0 text-green-500"></i>
                                    {{ scope }}
                                </li>
                            </ul>
                        </div>
                        <p class="text-xs text-gray-500"><strong>Audit Frequency:</strong> {{ compliance.auditFrequency }}</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="rounded-xl bg-white p-8 text-center shadow-sm">
                <h3 class="mb-4 text-2xl font-bold text-gray-900">Questions About Privacy or Security?</h3>
                <p class="mb-6 text-gray-600">Our privacy and security team is here to help.</p>
                <div class="flex flex-col items-center justify-center space-y-4 md:flex-row md:space-x-8 md:space-y-0">
                    <a
                        :href="`mailto:${dataProtection?.contactInfo?.email || 'privacy@alumate.com'}`"
                        class="flex items-center text-blue-600 hover:text-blue-700"
                    >
                        <i class="fas fa-envelope mr-2"></i>
                        {{ dataProtection?.contactInfo?.email || 'privacy@alumate.com' }}
                    </a>
                    <span v-if="dataProtection?.contactInfo?.phone" class="flex items-center text-gray-600">
                        <i class="fas fa-phone mr-2"></i>
                        {{ dataProtection.contactInfo.phone }}
                    </span>
                    <span class="flex items-center text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        {{ dataProtection?.contactInfo?.hours || 'Monday - Friday, 9 AM - 5 PM EST' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Certification Modal -->
        <div
            v-if="selectedCertification"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click="closeCertificationModal"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6" @click.stop>
                <div class="mb-4 flex items-start justify-between">
                    <h3 class="text-xl font-bold text-gray-900">{{ selectedCertification.name }}</h3>
                    <button @click="closeCertificationModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4 text-center">
                    <img :src="selectedCertification.badge" :alt="selectedCertification.name" class="mx-auto h-24 w-24 object-contain" />
                </div>
                <p class="mb-4 text-gray-600">{{ selectedCertification.description }}</p>
                <div class="mb-4 flex items-center justify-between text-sm text-gray-500">
                    <span>Category: {{ selectedCertification.category }}</span>
                    <span v-if="selectedCertification.expiryDate"> Expires: {{ formatDate(selectedCertification.expiryDate) }} </span>
                </div>
                <a
                    v-if="selectedCertification.verificationUrl"
                    :href="selectedCertification.verificationUrl"
                    target="_blank"
                    class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-white transition-colors duration-200 hover:bg-blue-700"
                >
                    Verify Certification
                    <i class="fas fa-external-link-alt ml-2"></i>
                </a>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { SecurityCertification, SecurityPrivacyProps } from '@/types/homepage';
import { ref } from 'vue';

interface Props extends /* @vue-ignore */ SecurityPrivacyProps {}

const props = withDefaults(defineProps<Props>(), {
    audience: 'individual',
    privacyHighlights: () => [],
    securityCertifications: () => [],
    verificationProcess: () => ({
        title: '',
        description: '',
        steps: [],
        benefits: [],
        requirements: [],
    }),
    dataProtection: () => ({
        title: '',
        description: '',
        principles: [],
        userRights: [],
        contactInfo: {
            email: '',
            hours: '',
        },
    }),
    complianceInfo: () => [],
});

const selectedCertification = ref<SecurityCertification | null>(null);

const openCertificationModal = (cert: SecurityCertification) => {
    selectedCertification.value = cert;
};

const closeCertificationModal = () => {
    selectedCertification.value = null;
};

const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(date));
};
</script>

<style scoped>
/* Additional custom styles if needed */
</style>

