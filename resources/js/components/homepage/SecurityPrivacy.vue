<template>
  <section class="security-privacy py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          {{ audience === 'institutional' ? 'Enterprise Security & Compliance' : 'Your Privacy & Security Matter' }}
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          {{ audience === 'institutional' 
            ? 'Enterprise-grade security and compliance standards to protect your institution and alumni data.'
            : 'We protect your professional information with industry-leading security measures and transparent privacy practices.'
          }}
        </p>
      </div>

      <!-- Privacy Highlights -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Privacy Protection</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div 
            v-for="highlight in privacyHighlights" 
            :key="highlight.id"
            class="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-300"
          >
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                <i :class="highlight.icon" class="text-blue-600 text-xl"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">{{ highlight.title }}</h4>
            </div>
            <p class="text-gray-600 mb-4">{{ highlight.description }}</p>
            <ul class="space-y-2">
              <li 
                v-for="detail in highlight.details" 
                :key="detail"
                class="flex items-start text-sm text-gray-600"
              >
                <i class="fas fa-check text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                {{ detail }}
              </li>
            </ul>
            <a 
              v-if="highlight.learnMoreUrl"
              :href="highlight.learnMoreUrl"
              class="inline-flex items-center text-blue-600 hover:text-blue-700 text-sm font-medium mt-4"
            >
              Learn more
              <i class="fas fa-arrow-right ml-1"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Security Certifications -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Security Certifications</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
          <div 
            v-for="cert in securityCertifications" 
            :key="cert.id"
            class="bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-300 text-center group cursor-pointer"
            @click="openCertificationModal(cert)"
          >
            <img 
              :src="cert.badge" 
              :alt="cert.name"
              class="w-16 h-16 mx-auto mb-3 object-contain"
            >
            <h5 class="text-sm font-semibold text-gray-900 mb-1">{{ cert.name }}</h5>
            <p class="text-xs text-gray-600">{{ cert.category }}</p>
            <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <i class="fas fa-external-link-alt text-blue-600 text-xs"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Alumni Verification Process -->
      <div class="mb-16">
        <div class="bg-white rounded-xl p-8 shadow-sm">
          <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ verificationProcess.title }}</h3>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ verificationProcess.description }}</p>
          </div>
          
          <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div 
              v-for="step in verificationProcess.steps" 
              :key="step.id"
              class="text-center"
            >
              <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-blue-600 font-bold text-lg">{{ step.stepNumber }}</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ step.title }}</h4>
              <p class="text-gray-600 text-sm mb-2">{{ step.description }}</p>
              <div class="flex items-center justify-center text-xs text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                {{ step.estimatedTime }}
              </div>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-4">Benefits of Verification</h4>
              <ul class="space-y-2">
                <li 
                  v-for="benefit in verificationProcess.benefits" 
                  :key="benefit"
                  class="flex items-start text-gray-600"
                >
                  <i class="fas fa-check-circle text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ benefit }}
                </li>
              </ul>
            </div>
            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h4>
              <ul class="space-y-2">
                <li 
                  v-for="requirement in verificationProcess.requirements" 
                  :key="requirement"
                  class="flex items-start text-gray-600"
                >
                  <i class="fas fa-info-circle text-blue-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ requirement }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Protection Information -->
      <div class="mb-16">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-8">
          <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ dataProtection.title }}</h3>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ dataProtection.description }}</p>
          </div>

          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div 
              v-for="principle in dataProtection.principles" 
              :key="principle.id"
              class="bg-white rounded-lg p-6"
            >
              <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                  <i :class="principle.icon" class="text-blue-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900">{{ principle.title }}</h4>
              </div>
              <p class="text-gray-600 mb-4">{{ principle.description }}</p>
              <ul class="space-y-1">
                <li 
                  v-for="impl in principle.implementation" 
                  :key="impl"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i class="fas fa-dot-circle text-blue-400 mr-2 mt-1.5 text-xs flex-shrink-0"></i>
                  {{ impl }}
                </li>
              </ul>
            </div>
          </div>

          <div class="bg-white rounded-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Your Rights</h4>
            <div class="grid md:grid-cols-2 gap-6">
              <div 
                v-for="right in dataProtection.userRights" 
                :key="right.id"
                class="border-l-4 border-blue-400 pl-4"
              >
                <h5 class="font-semibold text-gray-900 mb-1">{{ right.right }}</h5>
                <p class="text-gray-600 text-sm mb-2">{{ right.description }}</p>
                <p class="text-xs text-gray-500">
                  <strong>How to exercise:</strong> {{ right.howToExercise }}
                </p>
                <p class="text-xs text-gray-500">
                  <strong>Response time:</strong> {{ right.responseTime }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Compliance Information -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Compliance Standards</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="compliance in complianceInfo" 
            :key="compliance.id"
            class="bg-white rounded-lg p-6 shadow-sm"
          >
            <div class="flex items-center mb-4">
              <img 
                v-if="compliance.badge"
                :src="compliance.badge" 
                :alt="compliance.standard"
                class="w-12 h-12 mr-4 object-contain"
              >
              <div>
                <h4 class="text-lg font-semibold text-gray-900">{{ compliance.standard }}</h4>
                <p v-if="compliance.certificationDate" class="text-sm text-gray-500">
                  Certified: {{ formatDate(compliance.certificationDate) }}
                </p>
              </div>
            </div>
            <p class="text-gray-600 mb-4">{{ compliance.description }}</p>
            <div class="mb-4">
              <h5 class="font-semibold text-gray-900 mb-2">Scope:</h5>
              <ul class="space-y-1">
                <li 
                  v-for="scope in compliance.scope" 
                  :key="scope"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i class="fas fa-check text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ scope }}
                </li>
              </ul>
            </div>
            <p class="text-xs text-gray-500">
              <strong>Audit Frequency:</strong> {{ compliance.auditFrequency }}
            </p>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="text-center bg-white rounded-xl p-8 shadow-sm">
        <h3 class="text-2xl font-bold text-gray-900 mb-4">Questions About Privacy or Security?</h3>
        <p class="text-gray-600 mb-6">Our privacy and security team is here to help.</p>
        <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-8">
          <a 
            :href="`mailto:${dataProtection.contactInfo.email}`"
            class="flex items-center text-blue-600 hover:text-blue-700"
          >
            <i class="fas fa-envelope mr-2"></i>
            {{ dataProtection.contactInfo.email }}
          </a>
          <span v-if="dataProtection.contactInfo.phone" class="flex items-center text-gray-600">
            <i class="fas fa-phone mr-2"></i>
            {{ dataProtection.contactInfo.phone }}
          </span>
          <span class="flex items-center text-gray-600">
            <i class="fas fa-clock mr-2"></i>
            {{ dataProtection.contactInfo.hours }}
          </span>
        </div>
      </div>
    </div>

    <!-- Certification Modal -->
    <div 
      v-if="selectedCertification"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeCertificationModal"
    >
      <div 
        class="bg-white rounded-xl max-w-md w-full p-6"
        @click.stop
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-bold text-gray-900">{{ selectedCertification.name }}</h3>
          <button 
            @click="closeCertificationModal"
            class="text-gray-400 hover:text-gray-600"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="text-center mb-4">
          <img 
            :src="selectedCertification.badge" 
            :alt="selectedCertification.name"
            class="w-24 h-24 mx-auto object-contain"
          >
        </div>
        <p class="text-gray-600 mb-4">{{ selectedCertification.description }}</p>
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
          <span>Category: {{ selectedCertification.category }}</span>
          <span v-if="selectedCertification.expiryDate">
            Expires: {{ formatDate(selectedCertification.expiryDate) }}
          </span>
        </div>
        <a 
          v-if="selectedCertification.verificationUrl"
          :href="selectedCertification.verificationUrl"
          target="_blank"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center block"
        >
          Verify Certification
          <i class="fas fa-external-link-alt ml-2"></i>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { SecurityPrivacyProps, SecurityCertification } from '@/types/homepage'

interface Props extends /* @vue-ignore */ SecurityPrivacyProps {}

const props = defineProps<Props>()

const selectedCertification = ref<SecurityCertification | null>(null)

const openCertificationModal = (cert: SecurityCertification) => {
  selectedCertification.value = cert
}

const closeCertificationModal = () => {
  selectedCertification.value = null
}

const formatDate = (date: Date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(new Date(date))
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>
