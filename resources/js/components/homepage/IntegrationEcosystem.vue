<template>
  <section class="integration-ecosystem py-16 bg-white">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          {{ audience === 'institutional' ? 'Enterprise Integration Ecosystem' : 'Seamless Integrations' }}
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          {{ audience === 'institutional' 
            ? 'Connect with your existing systems and scale across your entire institution with our comprehensive integration platform.'
            : 'Connect your favorite tools and platforms to create a unified professional networking experience.'
          }}
        </p>
      </div>

      <!-- Platform Integrations -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Platform Integrations</h3>
        
        <!-- Integration Categories -->
        <div class="flex flex-wrap justify-center mb-8 gap-2">
          <button
            v-for="category in integrationCategories"
            :key="category"
            @click="selectedCategory = category"
            :class="[
              'px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200',
              selectedCategory === category
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ formatCategoryName(category) }}
          </button>
        </div>

        <!-- Integration Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="integration in filteredIntegrations" 
            :key="integration.id"
            class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow duration-300 group"
          >
            <div class="flex items-center mb-4">
              <img 
                :src="integration.logo" 
                :alt="integration.name"
                class="w-12 h-12 mr-4 object-contain"
              >
              <div>
                <h4 class="text-lg font-semibold text-gray-900">{{ integration.name }}</h4>
                <span class="text-sm text-gray-500 capitalize">{{ integration.category }}</span>
              </div>
            </div>
            
            <p class="text-gray-600 mb-4">{{ integration.description }}</p>
            
            <div class="mb-4">
              <h5 class="font-semibold text-gray-900 mb-2">Key Features:</h5>
              <ul class="space-y-1">
                <li 
                  v-for="feature in integration.features.slice(0, 3)" 
                  :key="feature"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i class="fas fa-check text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ feature }}
                </li>
              </ul>
            </div>

            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <span class="text-sm text-gray-500 mr-2">Setup:</span>
                <span :class="[
                  'text-xs px-2 py-1 rounded-full',
                  integration.setupComplexity === 'easy' ? 'bg-green-100 text-green-800' :
                  integration.setupComplexity === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-red-100 text-red-800'
                ]">
                  {{ integration.setupComplexity }}
                </span>
              </div>
              <div class="text-sm">
                <span :class="[
                  'font-semibold',
                  integration.pricing.type === 'free' ? 'text-green-600' :
                  integration.pricing.type === 'paid' ? 'text-blue-600' :
                  'text-purple-600'
                ]">
                  {{ integration.pricing.type === 'free' ? 'Free' : 
                     integration.pricing.type === 'paid' ? `$${integration.pricing.cost}/${integration.pricing.billingPeriod}` :
                     'Enterprise' }}
                </span>
              </div>
            </div>

            <div class="flex space-x-2">
              <button 
                @click="openIntegrationModal(integration)"
                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm"
              >
                View Details
              </button>
              <a 
                :href="integration.documentation"
                target="_blank"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 text-sm text-gray-700"
              >
                <i class="fas fa-book"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- API Documentation -->
      <div class="mb-16">
        <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-8">
          <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ apiDocumentation.title }}</h3>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ apiDocumentation.description }}</p>
          </div>

          <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 text-center">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-code text-blue-600 text-xl"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">REST API</h4>
              <p class="text-gray-600 text-sm">{{ apiDocumentation.version }}</p>
            </div>
            
            <div class="bg-white rounded-lg p-6 text-center">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-green-600 text-xl"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Secure Auth</h4>
              <p class="text-gray-600 text-sm">{{ apiDocumentation.authentication.length }} methods</p>
            </div>
            
            <div class="bg-white rounded-lg p-6 text-center">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-puzzle-piece text-purple-600 text-xl"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">SDKs</h4>
              <p class="text-gray-600 text-sm">{{ apiDocumentation.sdks.length }} languages</p>
            </div>
            
            <div class="bg-white rounded-lg p-6 text-center">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-book-open text-orange-600 text-xl"></i>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Examples</h4>
              <p class="text-gray-600 text-sm">{{ apiDocumentation.examples.length }} code samples</p>
            </div>
          </div>

          <div class="text-center">
            <button 
              @click="openApiDocsModal"
              class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-200 mr-4"
            >
              View API Documentation
            </button>
            <a 
              :href="apiDocumentation.baseUrl"
              target="_blank"
              class="bg-white text-blue-600 py-3 px-6 rounded-lg border border-blue-600 hover:bg-blue-50 transition-colors duration-200"
            >
              Try API Console
            </a>
          </div>
        </div>
      </div>

      <!-- Migration Support -->
      <div class="mb-16">
        <div class="bg-white rounded-xl border border-gray-200 p-8">
          <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ migrationSupport.title }}</h3>
            <p class="text-gray-600 max-w-2xl mx-auto">{{ migrationSupport.description }}</p>
          </div>

          <!-- Supported Platforms -->
          <div class="mb-8">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Supported Platforms</h4>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="platform in migrationSupport.supportedPlatforms" 
                :key="platform.id"
                class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors duration-200"
              >
                <img 
                  :src="platform.logo" 
                  :alt="platform.name"
                  class="w-10 h-10 mr-3 object-contain"
                >
                <div class="flex-1">
                  <h5 class="font-semibold text-gray-900">{{ platform.name }}</h5>
                  <div class="flex items-center mt-1">
                    <span :class="[
                      'text-xs px-2 py-1 rounded-full mr-2',
                      platform.migrationComplexity === 'low' ? 'bg-green-100 text-green-800' :
                      platform.migrationComplexity === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    ]">
                      {{ platform.migrationComplexity }}
                    </span>
                    <span class="text-xs text-gray-500">{{ platform.estimatedTime }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Migration Process -->
          <div class="mb-8">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Migration Process</h4>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div 
                v-for="step in migrationSupport.migrationProcess" 
                :key="step.id"
                class="text-center"
              >
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                  <span class="text-blue-600 font-bold">{{ step.stepNumber }}</span>
                </div>
                <h5 class="font-semibold text-gray-900 mb-2">{{ step.title }}</h5>
                <p class="text-gray-600 text-sm mb-2">{{ step.description }}</p>
                <span class="text-xs text-gray-500">{{ step.duration }}</span>
              </div>
            </div>
          </div>

          <!-- Support Levels -->
          <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center p-6 border border-gray-200 rounded-lg">
              <h5 class="font-semibold text-gray-900 mb-2">{{ migrationSupport.support.type }}</h5>
              <p class="text-gray-600 text-sm mb-4">{{ migrationSupport.support.description }}</p>
              <ul class="space-y-1 text-sm text-gray-600">
                <li v-for="item in migrationSupport.support.included" :key="item">
                  <i class="fas fa-check text-green-500 mr-2"></i>{{ item }}
                </li>
              </ul>
            </div>
            <div class="text-center p-6 border border-gray-200 rounded-lg">
              <h5 class="font-semibold text-gray-900 mb-2">Timeline</h5>
              <p class="text-2xl font-bold text-blue-600 mb-2">{{ migrationSupport.timeline }}</p>
              <p class="text-gray-600 text-sm">Average migration time</p>
            </div>
            <div class="text-center p-6 border border-gray-200 rounded-lg">
              <h5 class="font-semibold text-gray-900 mb-2">Migration Tools</h5>
              <p class="text-2xl font-bold text-green-600 mb-2">{{ migrationSupport.tools.length }}</p>
              <p class="text-gray-600 text-sm">Automated tools available</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Training Programs -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Training & Support</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="program in trainingPrograms" 
            :key="program.id"
            class="bg-gray-50 rounded-lg p-6 hover:shadow-md transition-shadow duration-300"
          >
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-lg font-semibold text-gray-900">{{ program.title }}</h4>
              <span :class="[
                'text-xs px-2 py-1 rounded-full',
                program.cost.type === 'free' ? 'bg-green-100 text-green-800' :
                program.cost.type === 'included' ? 'bg-blue-100 text-blue-800' :
                'bg-purple-100 text-purple-800'
              ]">
                {{ program.cost.type }}
              </span>
            </div>
            
            <p class="text-gray-600 mb-4">{{ program.description }}</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-users mr-2"></i>
                <span class="capitalize">{{ program.audience }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-clock mr-2"></i>
                <span>{{ program.duration }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-desktop mr-2"></i>
                <span class="capitalize">{{ program.format }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-certificate mr-2"></i>
                <span>{{ program.certification ? 'Certificate included' : 'No certificate' }}</span>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="font-semibold text-gray-900 mb-2">Modules ({{ program.modules.length }}):</h5>
              <ul class="space-y-1">
                <li 
                  v-for="module in program.modules.slice(0, 3)" 
                  :key="module.id"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i class="fas fa-play-circle text-blue-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ module.title }}
                </li>
              </ul>
              <p v-if="program.modules.length > 3" class="text-xs text-gray-500 mt-2">
                +{{ program.modules.length - 3 }} more modules
              </p>
            </div>

            <button 
              @click="openTrainingModal(program)"
              class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200"
            >
              View Program Details
            </button>
          </div>
        </div>
      </div>

      <!-- Scalability Information -->
      <div class="mb-16">
        <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Scalability for Every Institution Size</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div 
            v-for="scale in scalabilityInfo" 
            :key="scale.id"
            class="bg-white rounded-lg border-2 border-gray-200 p-6 hover:border-blue-300 transition-colors duration-300"
            :class="{ 'border-blue-500 bg-blue-50': scale.institutionSize === 'enterprise' }"
          >
            <div class="text-center mb-4">
              <h4 class="text-lg font-semibold text-gray-900 capitalize mb-2">{{ scale.institutionSize }}</h4>
              <p class="text-sm text-gray-600">{{ scale.alumniRange }} alumni</p>
            </div>

            <div class="mb-4">
              <h5 class="font-semibold text-gray-900 mb-2">Features:</h5>
              <ul class="space-y-1">
                <li 
                  v-for="feature in scale.features.slice(0, 4)" 
                  :key="feature.name"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i :class="[
                    'mr-2 mt-1 flex-shrink-0',
                    feature.availability ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500'
                  ]"></i>
                  {{ feature.name }}
                </li>
              </ul>
            </div>

            <div class="mb-4">
              <h5 class="font-semibold text-gray-900 mb-2">Performance:</h5>
              <div class="space-y-1">
                <div 
                  v-for="metric in scale.performance.slice(0, 2)" 
                  :key="metric.metric"
                  class="flex justify-between text-sm"
                >
                  <span class="text-gray-600">{{ metric.metric }}:</span>
                  <span class="font-medium text-gray-900">{{ metric.value }}</span>
                </div>
              </div>
            </div>

            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600 mb-1">
                {{ scale.pricing.model === 'custom' ? 'Custom' : 
                   scale.pricing.basePrice ? `$${scale.pricing.basePrice}` : 'Contact' }}
              </div>
              <p class="text-xs text-gray-500">
                {{ scale.pricing.model === 'per_user' ? 'per user/month' : 
                   scale.pricing.model === 'tiered' ? 'starting price' : 'pricing' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="text-center bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-8 text-white">
        <h3 class="text-2xl font-bold mb-4">Ready to Integrate?</h3>
        <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
          {{ audience === 'institutional' 
            ? 'Schedule a technical consultation to discuss your integration requirements and migration timeline.'
            : 'Connect your favorite tools and start building your professional network today.'
          }}
        </p>
        <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
          <button class="bg-white text-blue-600 py-3 px-6 rounded-lg hover:bg-gray-100 transition-colors duration-200 font-semibold">
            {{ audience === 'institutional' ? 'Schedule Technical Demo' : 'Start Free Trial' }}
          </button>
          <button class="border border-white text-white py-3 px-6 rounded-lg hover:bg-white hover:text-blue-600 transition-colors duration-200">
            View Documentation
          </button>
        </div>
      </div>
    </div>

    <!-- Integration Detail Modal -->
    <div 
      v-if="selectedIntegration"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeIntegrationModal"
    >
      <div 
        class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-6">
            <div class="flex items-center">
              <img 
                :src="selectedIntegration.logo" 
                :alt="selectedIntegration.name"
                class="w-12 h-12 mr-4 object-contain"
              >
              <div>
                <h3 class="text-xl font-bold text-gray-900">{{ selectedIntegration.name }}</h3>
                <span class="text-sm text-gray-500 capitalize">{{ selectedIntegration.category }}</span>
              </div>
            </div>
            <button 
              @click="closeIntegrationModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>

          <p class="text-gray-600 mb-6">{{ selectedIntegration.description }}</p>

          <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Features</h4>
              <ul class="space-y-2">
                <li 
                  v-for="feature in selectedIntegration.features" 
                  :key="feature"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <i class="fas fa-check text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                  {{ feature }}
                </li>
              </ul>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Details</h4>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Setup Complexity:</span>
                  <span :class="[
                    'text-sm px-2 py-1 rounded-full',
                    selectedIntegration.setupComplexity === 'easy' ? 'bg-green-100 text-green-800' :
                    selectedIntegration.setupComplexity === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-red-100 text-red-800'
                  ]">
                    {{ selectedIntegration.setupComplexity }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Support Level:</span>
                  <span class="font-medium text-gray-900 capitalize">{{ selectedIntegration.supportLevel }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Pricing:</span>
                  <span class="font-medium text-gray-900">
                    {{ selectedIntegration.pricing.type === 'free' ? 'Free' : 
                       selectedIntegration.pricing.type === 'paid' ? `$${selectedIntegration.pricing.cost}/${selectedIntegration.pricing.billingPeriod}` :
                       'Enterprise' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedIntegration.screenshots && selectedIntegration.screenshots.length > 0" class="mb-6">
            <h4 class="font-semibold text-gray-900 mb-3">Screenshots</h4>
            <div class="grid grid-cols-2 gap-4">
              <img 
                v-for="screenshot in selectedIntegration.screenshots" 
                :key="screenshot"
                :src="screenshot" 
                :alt="`${selectedIntegration.name} screenshot`"
                class="w-full h-32 object-cover rounded-lg border border-gray-200"
              >
            </div>
          </div>

          <div class="flex space-x-4">
            <a 
              :href="selectedIntegration.documentation"
              target="_blank"
              class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center"
            >
              View Documentation
            </a>
            <button class="flex-1 border border-gray-300 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors duration-200">
              Contact Support
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- API Documentation Modal -->
    <div 
      v-if="showApiDocsModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeApiDocsModal"
    >
      <div 
        class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-900">API Documentation</h3>
            <button 
              @click="closeApiDocsModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>

          <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Authentication Methods</h4>
              <div class="space-y-3">
                <div 
                  v-for="auth in apiDocumentation.authentication" 
                  :key="auth.type"
                  class="border border-gray-200 rounded-lg p-3"
                >
                  <h5 class="font-medium text-gray-900 mb-1">{{ auth.type.toUpperCase() }}</h5>
                  <p class="text-sm text-gray-600">{{ auth.description }}</p>
                </div>
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Available SDKs</h4>
              <div class="space-y-2">
                <div 
                  v-for="sdk in apiDocumentation.sdks" 
                  :key="sdk.language"
                  class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                >
                  <div>
                    <span class="font-medium text-gray-900">{{ sdk.language }}</span>
                    <span class="text-sm text-gray-500 ml-2">v{{ sdk.version }}</span>
                  </div>
                  <a 
                    :href="sdk.repository"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-700"
                  >
                    <i class="fab fa-github"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-6">
            <h4 class="font-semibold text-gray-900 mb-3">Sample Endpoints</h4>
            <div class="space-y-3">
              <div 
                v-for="endpoint in apiDocumentation.endpoints.slice(0, 5)" 
                :key="endpoint.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-center mb-2">
                  <span :class="[
                    'text-xs px-2 py-1 rounded mr-3 font-mono',
                    endpoint.method === 'GET' ? 'bg-green-100 text-green-800' :
                    endpoint.method === 'POST' ? 'bg-blue-100 text-blue-800' :
                    endpoint.method === 'PUT' ? 'bg-yellow-100 text-yellow-800' :
                    endpoint.method === 'DELETE' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ endpoint.method }}
                  </span>
                  <code class="text-sm text-gray-700">{{ endpoint.path }}</code>
                </div>
                <p class="text-sm text-gray-600">{{ endpoint.description }}</p>
              </div>
            </div>
          </div>

          <div class="text-center">
            <a 
              :href="apiDocumentation.baseUrl"
              target="_blank"
              class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-200 mr-4"
            >
              View Full Documentation
            </a>
            <button class="border border-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-50 transition-colors duration-200">
              Download Postman Collection
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Training Program Modal -->
    <div 
      v-if="selectedTrainingProgram"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeTrainingModal"
    >
      <div 
        class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <div class="p-6">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-xl font-bold text-gray-900">{{ selectedTrainingProgram.title }}</h3>
              <p class="text-gray-600">{{ selectedTrainingProgram.description }}</p>
            </div>
            <button 
              @click="closeTrainingModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>

          <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Program Details</h4>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Audience:</span>
                  <span class="font-medium text-gray-900 capitalize">{{ selectedTrainingProgram.audience }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Format:</span>
                  <span class="font-medium text-gray-900 capitalize">{{ selectedTrainingProgram.format }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Duration:</span>
                  <span class="font-medium text-gray-900">{{ selectedTrainingProgram.duration }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Certification:</span>
                  <span class="font-medium text-gray-900">{{ selectedTrainingProgram.certification ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Cost:</span>
                  <span class="font-medium text-gray-900 capitalize">{{ selectedTrainingProgram.cost.type }}</span>
                </div>
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Upcoming Sessions</h4>
              <div class="space-y-2">
                <div 
                  v-for="schedule in selectedTrainingProgram.schedule.slice(0, 3)" 
                  :key="schedule.id"
                  class="border border-gray-200 rounded-lg p-3"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="font-medium text-gray-900">{{ formatDate(schedule.date) }}</div>
                      <div class="text-sm text-gray-600">{{ schedule.time }} {{ schedule.timezone }}</div>
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ schedule.capacity }} spots
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-6">
            <h4 class="font-semibold text-gray-900 mb-3">Training Modules</h4>
            <div class="space-y-3">
              <div 
                v-for="module in selectedTrainingProgram.modules" 
                :key="module.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex justify-between items-start mb-2">
                  <h5 class="font-medium text-gray-900">{{ module.title }}</h5>
                  <span class="text-sm text-gray-500">{{ module.duration }}</span>
                </div>
                <p class="text-sm text-gray-600 mb-2">{{ module.description }}</p>
                <div class="flex flex-wrap gap-2">
                  <span 
                    v-for="topic in module.topics.slice(0, 3)" 
                    :key="topic"
                    class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"
                  >
                    {{ topic }}
                  </span>
                  <span v-if="module.topics.length > 3" class="text-xs text-gray-500">
                    +{{ module.topics.length - 3 }} more
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="text-center">
            <button class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-200 mr-4">
              Register for Training
            </button>
            <button class="border border-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-50 transition-colors duration-200">
              Download Syllabus
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { 
  IntegrationEcosystemProps, 
  PlatformIntegration, 
  TrainingProgram 
} from '@/types/homepage'

interface Props extends IntegrationEcosystemProps {}

const props = defineProps<Props>()

const selectedCategory = ref<string>('all')
const selectedIntegration = ref<PlatformIntegration | null>(null)
const selectedTrainingProgram = ref<TrainingProgram | null>(null)
const showApiDocsModal = ref(false)

const integrationCategories = computed(() => {
  const categories = ['all', ...new Set(props.integrations.map(i => i.category))]
  return categories
})

const filteredIntegrations = computed(() => {
  if (selectedCategory.value === 'all') {
    return props.integrations
  }
  return props.integrations.filter(i => i.category === selectedCategory.value)
})

const formatCategoryName = (category: string) => {
  return category.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const openIntegrationModal = (integration: PlatformIntegration) => {
  selectedIntegration.value = integration
}

const closeIntegrationModal = () => {
  selectedIntegration.value = null
}

const openApiDocsModal = () => {
  showApiDocsModal.value = true
}

const closeApiDocsModal = () => {
  showApiDocsModal.value = false
}

const openTrainingModal = (program: TrainingProgram) => {
  selectedTrainingProgram.value = program
}

const closeTrainingModal = () => {
  selectedTrainingProgram.value = null
}

const formatDate = (date: Date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(date))
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>