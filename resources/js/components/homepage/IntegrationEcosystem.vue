<template>
    <section class="integration-ecosystem bg-white py-16">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ audience === 'institutional' ? 'Enterprise Integration Ecosystem' : 'Seamless Integrations' }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    {{
                        audience === 'institutional'
                            ? 'Connect with your existing systems and scale across your entire institution with our comprehensive integration platform.'
                            : 'Connect your favorite tools and platforms to create a unified professional networking experience.'
                    }}
                </p>
            </div>

            <!-- Platform Integrations -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Platform Integrations</h3>

                <!-- Integration Categories -->
                <div class="mb-8 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="category in integrationCategories"
                        :key="category"
                        @click="selectedCategory = category"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition-colors duration-200',
                            selectedCategory === category ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                        ]"
                    >
                        {{ formatCategoryName(category) }}
                    </button>
                </div>

                <!-- Integration Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="integration in filteredIntegrations"
                        :key="integration.id"
                        class="group rounded-lg bg-gray-50 p-6 transition-shadow duration-300 hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center">
                            <img :src="integration.logo" :alt="integration.name" class="mr-4 h-12 w-12 object-contain" />
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ integration.name }}</h4>
                                <span class="text-sm capitalize text-gray-500">{{ integration.category }}</span>
                            </div>
                        </div>

                        <p class="mb-4 text-gray-600">{{ integration.description }}</p>

                        <div class="mb-4">
                            <h5 class="mb-2 font-semibold text-gray-900">Key Features:</h5>
                            <ul class="space-y-1">
                                <li v-for="feature in integration.features.slice(0, 3)" :key="feature" class="flex items-start text-sm text-gray-600">
                                    <i class="fas fa-check mr-2 mt-1 flex-shrink-0 text-green-500"></i>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>

                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="mr-2 text-sm text-gray-500">Setup:</span>
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs',
                                        integration.setupComplexity === 'easy'
                                            ? 'bg-green-100 text-green-800'
                                            : integration.setupComplexity === 'medium'
                                              ? 'bg-yellow-100 text-yellow-800'
                                              : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    {{ integration.setupComplexity }}
                                </span>
                            </div>
                            <div class="text-sm">
                                <span
                                    :class="[
                                        'font-semibold',
                                        integration.pricing.type === 'free'
                                            ? 'text-green-600'
                                            : integration.pricing.type === 'paid'
                                              ? `$${integration.pricing.cost}/${integration.pricing.billingPeriod}`
                                              : 'text-purple-600',
                                    ]"
                                >
                                    {{
                                        integration.pricing.type === 'free'
                                            ? 'Free'
                                            : integration.pricing.type === 'paid'
                                              ? `$${integration.pricing.cost}/${integration.pricing.billingPeriod}`
                                              : 'Enterprise'
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <button
                                @click="openIntegrationModal(integration)"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm text-white transition-colors duration-200 hover:bg-blue-700"
                            >
                                View Details
                            </button>
                            <a
                                :href="integration.documentation"
                                target="_blank"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition-colors duration-200 hover:bg-gray-50"
                            >
                                <i class="fas fa-book"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Documentation -->
            <div class="mb-16">
                <div class="rounded-xl bg-gradient-to-r from-gray-50 to-blue-50 p-8">
                    <div class="mb-8 text-center">
                        <h3 class="mb-4 text-2xl font-bold text-gray-900">{{ apiDocumentation?.title || 'API Documentation' }}</h3>
                        <p class="mx-auto max-w-2xl text-gray-600">
                            {{ apiDocumentation?.description || 'Comprehensive API documentation for developers' }}
                        </p>
                    </div>

                    <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-white p-6 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                                <i class="fas fa-code text-xl text-blue-600"></i>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">REST API</h4>
                            <p class="text-sm text-gray-600">{{ apiDocumentation?.version || 'v1.0' }}</p>
                        </div>

                        <div class="rounded-lg bg-white p-6 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-100">
                                <i class="fas fa-shield-alt text-xl text-green-600"></i>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Secure Auth</h4>
                            <p class="text-sm text-gray-600">{{ apiDocumentation?.authentication?.length || 0 }} methods</p>
                        </div>

                        <div class="rounded-lg bg-white p-6 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100">
                                <i class="fas fa-puzzle-piece text-xl text-purple-600"></i>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">SDKs</h4>
                            <p class="text-sm text-gray-600">{{ apiDocumentation?.sdks?.length || 0 }} languages</p>
                        </div>

                        <div class="rounded-lg bg-white p-6 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100">
                                <i class="fas fa-book-open text-xl text-orange-600"></i>
                            </div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900">Examples</h4>
                            <p class="text-sm text-gray-600">{{ apiDocumentation?.examples?.length || 0 }} code samples</p>
                        </div>
                    </div>

                    <div class="text-center">
                        <button
                            @click="openApiDocsModal"
                            class="mr-4 rounded-lg bg-blue-600 px-6 py-3 text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            View API Documentation
                        </button>
                        <a
                            :href="apiDocumentation?.baseUrl || '#'"
                            target="_blank"
                            class="rounded-lg border border-blue-600 bg-white px-6 py-3 text-blue-600 transition-colors duration-200 hover:bg-blue-50"
                        >
                            Try API Console
                        </a>
                    </div>
                </div>
            </div>

            <!-- Migration Support -->
            <div class="mb-16">
                <div class="rounded-xl border border-gray-200 bg-white p-8">
                    <div class="mb-8 text-center">
                        <h3 class="mb-4 text-2xl font-bold text-gray-900">{{ migrationSupport?.title || 'Migration Support' }}</h3>
                        <p class="mx-auto max-w-2xl text-gray-600">
                            {{ migrationSupport?.description || 'Seamless migration assistance for your existing systems' }}
                        </p>
                    </div>

                    <!-- Supported Platforms -->
                    <div class="mb-8">
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Supported Platforms</h4>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="platform in migrationSupport?.supportedPlatforms || []"
                                :key="platform.id"
                                class="flex items-center rounded-lg border border-gray-200 p-4 transition-colors duration-200 hover:border-blue-300"
                            >
                                <img :src="platform.logo" :alt="platform.name" class="mr-3 h-10 w-10 object-contain" />
                                <div class="flex-1">
                                    <h5 class="font-semibold text-gray-900">{{ platform.name }}</h5>
                                    <div class="mt-1 flex items-center">
                                        <span
                                            :class="[
                                                'mr-2 rounded-full px-2 py-1 text-xs',
                                                platform.migrationComplexity === 'low'
                                                    ? 'bg-green-100 text-green-800'
                                                    : platform.migrationComplexity === 'medium'
                                                      ? 'bg-yellow-100 text-yellow-800'
                                                      : 'bg-red-100 text-red-800',
                                            ]"
                                        >
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
                        <h4 class="mb-4 text-lg font-semibold text-gray-900">Migration Process</h4>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div v-for="step in migrationSupport?.migrationProcess || []" :key="step.id" class="text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <span class="font-bold text-blue-600">{{ step?.stepNumber || '1' }}</span>
                                </div>
                                <h5 class="mb-2 font-semibold text-gray-900">{{ step?.title || 'Migration Step' }}</h5>
                                <p class="mb-2 text-sm text-gray-600">{{ step?.description || 'Step description' }}</p>
                                <span class="text-xs text-gray-500">{{ step?.duration || 'Duration TBD' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Support Levels -->
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 p-6 text-center">
                            <h5 class="mb-2 font-semibold text-gray-900">{{ migrationSupport?.support?.type }}</h5>
                            <p class="mb-4 text-sm text-gray-600">{{ migrationSupport?.support?.description }}</p>
                            <ul class="space-y-1 text-sm text-gray-600">
                                <li v-for="item in migrationSupport?.support?.included || []" :key="item">
                                    <i class="fas fa-check mr-2 text-green-500"></i>{{ item }}
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-6 text-center">
                            <h5 class="mb-2 font-semibold text-gray-900">Timeline</h5>
                            <p class="mb-2 text-2xl font-bold text-blue-600">{{ migrationSupport?.timeline }}</p>
                            <p class="text-sm text-gray-600">Average migration time</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-6 text-center">
                            <h5 class="mb-2 font-semibold text-gray-900">Migration Tools</h5>
                            <p class="mb-2 text-2xl font-bold text-green-600">{{ migrationSupport?.tools?.length || 0 }}</p>
                            <p class="text-sm text-gray-600">Automated tools available</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training Programs -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Training & Support</h3>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="program in trainingPrograms || []"
                        :key="program.id"
                        class="rounded-lg bg-gray-50 p-6 transition-shadow duration-300 hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-gray-900">{{ program.title }}</h4>
                            <span
                                :class="[
                                    'rounded-full px-2 py-1 text-xs',
                                    program.cost.type === 'free'
                                        ? 'bg-green-100 text-green-800'
                                        : program.cost.type === 'included'
                                          ? 'bg-blue-100 text-blue-800'
                                          : 'bg-purple-100 text-purple-800',
                                ]"
                            >
                                {{ program.cost.type }}
                            </span>
                        </div>

                        <p class="mb-4 text-gray-600">{{ program.description }}</p>

                        <div class="mb-4 space-y-2">
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
                            <h5 class="mb-2 font-semibold text-gray-900">Modules ({{ program.modules.length }}):</h5>
                            <ul class="space-y-1">
                                <li v-for="module in program.modules.slice(0, 3)" :key="module.id" class="flex items-start text-sm text-gray-600">
                                    <i class="fas fa-play-circle mr-2 mt-1 flex-shrink-0 text-blue-500"></i>
                                    {{ module.title }}
                                </li>
                            </ul>
                            <p v-if="program.modules.length > 3" class="mt-2 text-xs text-gray-500">+{{ program.modules.length - 3 }} more modules</p>
                        </div>

                        <button
                            @click="openTrainingModal(program)"
                            class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            View Program Details
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scalability Information -->
            <div class="mb-16">
                <h3 class="mb-8 text-center text-2xl font-bold text-gray-900">Scalability for Every Institution Size</h3>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="scale in (scalabilityInfo && scalabilityInfo.length > 0) ? scalabilityInfo : []"
                        :key="scale.id"
                        class="rounded-lg border-2 border-gray-200 bg-white p-6 transition-colors duration-300 hover:border-blue-300"
                        :class="{ 'border-blue-500 bg-blue-50': scale.institutionSize === 'enterprise' }"
                    >
                        <div class="mb-4 text-center">
                            <h4 class="mb-2 text-lg font-semibold capitalize text-gray-900">{{ scale.institutionSize }}</h4>
                            <p class="text-sm text-gray-600">{{ scale.alumniRange }} alumni</p>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-2 font-semibold text-gray-900">Features:</h5>
                            <ul class="space-y-1">
                                <li v-for="feature in scale.features.slice(0, 4)" :key="feature.name" class="flex items-start text-sm text-gray-600">
                                    <i
                                        :class="[
                                            'mr-2 mt-1 flex-shrink-0',
                                            feature.availability ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500',
                                        ]"
                                    ></i>
                                    {{ feature.name }}
                                </li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-2 font-semibold text-gray-900">Performance:</h5>
                            <div class="space-y-1">
                                <div v-for="metric in scale.performance.slice(0, 2)" :key="metric.metric" class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ metric.metric }}:</span>
                                    <span class="font-medium text-gray-900">{{ metric.value }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="mb-1 text-2xl font-bold text-blue-600">
                                {{
                                    scale.pricing.model === 'custom' ? 'Custom' : scale.pricing.basePrice ? `$${scale.pricing.basePrice}` : 'Contact'
                                }}
                            </div>
                            <p class="text-xs text-gray-500">
                                {{
                                    scale.pricing.model === 'per_user'
                                        ? 'per user/month'
                                        : scale.pricing.model === 'tiered'
                                          ? 'starting price'
                                          : 'pricing'
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-center text-white">
                <h3 class="mb-4 text-2xl font-bold">Ready to Integrate?</h3>
                <p class="mx-auto mb-6 max-w-2xl text-blue-100">
                    {{
                        audience === 'institutional'
                            ? 'Schedule a technical consultation to discuss your integration requirements and migration timeline.'
                            : 'Connect your favorite tools and start building your professional network today.'
                    }}
                </p>
                <div class="flex flex-col items-center justify-center space-y-4 md:flex-row md:space-x-4 md:space-y-0">
                    <button class="rounded-lg bg-white px-6 py-3 font-semibold text-blue-600 transition-colors duration-200 hover:bg-gray-100">
                        {{ audience === 'institutional' ? 'Schedule Technical Demo' : 'Start Free Trial' }}
                    </button>
                    <button class="rounded-lg border border-blue-600 px-6 py-3 text-blue-600 transition-colors duration-200 hover:bg-blue-50">
                        View Documentation
                    </button>
                </div>
            </div>
        </div>

        <!-- Integration Detail Modal -->
        <div
            v-if="selectedIntegration"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click="closeIntegrationModal"
        >
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white" @click.stop>
                <div class="p-6">
                    <div class="mb-6 flex items-start justify-between">
                        <div class="flex items-center">
                            <img :src="selectedIntegration.logo" :alt="selectedIntegration.name" class="mr-4 h-12 w-12 object-contain" />
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ selectedIntegration.name }}</h3>
                                <span class="text-sm capitalize text-gray-500">{{ selectedIntegration.category }}</span>
                            </div>
                        </div>
                        <button @click="closeIntegrationModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <p class="mb-6 text-gray-600">{{ selectedIntegration.description }}</p>

                    <div class="mb-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Features</h4>
                            <ul class="space-y-2">
                                <li v-for="feature in selectedIntegration.features" :key="feature" class="flex items-start text-sm text-gray-600">
                                    <i class="fas fa-check mr-2 mt-1 flex-shrink-0 text-green-500"></i>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Setup Complexity:</span>
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-1 text-sm',
                                            selectedIntegration.setupComplexity === 'easy'
                                                ? 'bg-green-100 text-green-800'
                                                : selectedIntegration.setupComplexity === 'medium'
                                                  ? 'bg-yellow-100 text-yellow-800'
                                                  : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ selectedIntegration.setupComplexity }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Support Level:</span>
                                    <span class="font-medium capitalize text-gray-900">{{ selectedIntegration.supportLevel }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pricing:</span>
                                    <span class="font-medium text-gray-900">
                                        {{
                                            selectedIntegration.pricing.type === 'free'
                                                ? 'Free'
                                                : selectedIntegration.pricing.type === 'paid'
                                                  ? `$${selectedIntegration.pricing.cost}/${selectedIntegration.pricing.billingPeriod}`
                                                  : 'Enterprise'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedIntegration.screenshots && selectedIntegration.screenshots.length > 0" class="mb-6">
                        <h4 class="mb-3 font-semibold text-gray-900">Screenshots</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <img
                                v-for="screenshot in selectedIntegration.screenshots"
                                :key="screenshot"
                                :src="screenshot"
                                :alt="`${selectedIntegration.name} screenshot`"
                                class="h-32 w-full rounded-lg border border-gray-200 object-cover"
                            />
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <a
                            :href="selectedIntegration.documentation"
                            target="_blank"
                            class="flex-1 rounded-lg bg-blue-600 px-4 py-3 text-center text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            View Documentation
                        </a>
                        <button
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-gray-700 transition-colors duration-200 hover:bg-gray-50"
                        >
                            Contact Support
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Documentation Modal -->
        <div
            v-if="showApiDocsModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click="closeApiDocsModal"
        >
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white" @click.stop>
                <div class="p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900">API Documentation</h3>
                        <button @click="closeApiDocsModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="mb-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Authentication Methods</h4>
                            <div class="space-y-3">
                                <div
                                    v-for="auth in apiDocumentation?.authentication || []"
                                    :key="auth.type"
                                    class="rounded-lg border border-gray-200 p-3"
                                >
                                    <h5 class="mb-1 font-medium text-gray-900">{{ auth.type.toUpperCase() }}</h5>
                                    <p class="text-sm text-gray-600">{{ auth.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Available SDKs</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="sdk in apiDocumentation?.sdks || []"
                                    :key="sdk.language"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 p-3"
                                >
                                    <div>
                                        <span class="font-medium text-gray-900">{{ sdk.language }}</span>
                                        <span class="ml-2 text-sm text-gray-500">v{{ sdk.version }}</span>
                                    </div>
                                    <a :href="sdk.repository" target="_blank" class="text-blue-600 hover:text-blue-700">
                                        <i class="fab fa-github"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="mb-3 font-semibold text-gray-900">Sample Endpoints</h4>
                        <div class="space-y-3">
                            <div
                                v-for="endpoint in (apiDocumentation?.endpoints || []).slice(0, 5)"
                                :key="endpoint.id"
                                class="rounded-lg border border-gray-200 p-4"
                            >
                                <div class="mb-2 flex items-center">
                                    <span
                                        :class="[
                                            'mr-3 rounded px-2 py-1 font-mono text-xs',
                                            endpoint.method === 'GET'
                                                ? 'bg-green-100 text-green-800'
                                                : endpoint.method === 'POST'
                                                  ? 'bg-blue-100 text-blue-800'
                                                  : endpoint.method === 'PUT'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : endpoint.method === 'DELETE'
                                                      ? 'bg-red-100 text-red-800'
                                                      : 'bg-gray-100 text-gray-800',
                                        ]"
                                    >
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
                            class="mr-4 rounded-lg bg-blue-600 px-6 py-3 text-white transition-colors duration-200 hover:bg-blue-700"
                        >
                            View Full Documentation
                        </a>
                        <button class="rounded-lg border border-blue-600 px-6 py-3 text-blue-600 transition-colors duration-200 hover:bg-blue-50">
                            Download Postman Collection
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Program Modal -->
        <div
            v-if="selectedTrainingProgram"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @click="closeTrainingModal"
        >
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white" @click.stop>
                <div class="p-6">
                    <div class="mb-6 flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ selectedTrainingProgram.title }}</h3>
                            <p class="text-gray-600">{{ selectedTrainingProgram.description }}</p>
                        </div>
                        <button @click="closeTrainingModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="mb-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Program Details</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Audience:</span>
                                    <span class="font-medium capitalize text-gray-900">{{ selectedTrainingProgram.audience }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Format:</span>
                                    <span class="font-medium capitalize text-gray-900">{{ selectedTrainingProgram.format }}</span>
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
                                    <span class="font-medium capitalize text-gray-900">{{ selectedTrainingProgram.cost.type }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-3 font-semibold text-gray-900">Upcoming Sessions</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="schedule in selectedTrainingProgram.schedule.slice(0, 3)"
                                    :key="schedule.id"
                                    class="rounded-lg border border-gray-200 p-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ formatDate(schedule.date) }}</div>
                                            <div class="text-sm text-gray-600">{{ schedule.time }} {{ schedule.timezone }}</div>
                                        </div>
                                        <div class="text-sm text-gray-500">{{ schedule.capacity }} spots</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="mb-3 font-semibold text-gray-900">Training Modules</h4>
                        <div class="space-y-3">
                            <div v-for="module in selectedTrainingProgram.modules" :key="module.id" class="rounded-lg border border-gray-200 p-4">
                                <div class="mb-2 flex items-start justify-between">
                                    <h5 class="font-medium text-gray-900">{{ module.title }}</h5>
                                    <span class="text-sm text-gray-500">{{ module.duration }}</span>
                                </div>
                                <p class="mb-2 text-sm text-gray-600">{{ module.description }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="topic in module.topics.slice(0, 3)"
                                        :key="topic"
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                    >
                                        {{ topic }}
                                    </span>
                                    <span v-if="module.topics.length > 3" class="text-xs text-gray-500"> +{{ module.topics.length - 3 }} more </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button class="mr-4 rounded-lg bg-blue-600 px-6 py-3 text-white transition-colors duration-200 hover:bg-blue-700">
                            Register Now
                        </button>
                        <button class="rounded-lg border border-blue-600 px-6 py-3 text-blue-600 transition-colors duration-200 hover:bg-blue-50">
                            Download Syllabus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { IntegrationEcosystemProps, PlatformIntegration, TrainingProgram } from '@/types/homepage';
import { computed, ref } from 'vue';

interface Props extends /* @vue-ignore */ IntegrationEcosystemProps {}

const props = withDefaults(defineProps<Props>(), {
    audience: 'individual',
    integrations: () => [],
    apiDocumentation: () => ({
        title: 'API Documentation',
        description: 'Comprehensive API documentation for developers',
        version: 'v1.0',
        baseUrl: 'https://api.alumate.com',
        authentication: [],
        endpoints: [],
        sdks: [],
        examples: [],
        rateLimits: [],
    }),
    migrationSupport: () => ({
        title: 'Migration Support',
        description: 'Seamless migration from your existing platform',
        supportedPlatforms: [],
        migrationProcess: [],
        timeline: '2-4 weeks',
        support: {
            type: 'assisted',
            description: 'Assisted migration support',
            included: [],
            timeline: '2-4 weeks',
        },
        tools: [],
    }),
    trainingPrograms: () => [],
    scalabilityInfo: () => [],
});

const selectedCategory = ref<string>('all');
const selectedIntegration = ref<PlatformIntegration | null>(null);
const selectedTrainingProgram = ref<TrainingProgram | null>(null);
const showApiDocsModal = ref(false);

const integrationCategories = computed(() => {
    console.log('props.integrations:', props.integrations);
    const categories = ['all'];
    if (props.integrations) {
        props.integrations.forEach((i) => {
            if (i && i.category) {
                categories.push(i.category);
            }
        });
    }
    return [...new Set(categories)];
});

const filteredIntegrations = computed(() => {
    if (selectedCategory.value === 'all') {
        return props.integrations;
    }
    return props.integrations.filter((i) => i.category === selectedCategory.value);
});

const formatCategoryName = (category: string) => {
    return category
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const openIntegrationModal = (integration: PlatformIntegration) => {
    selectedIntegration.value = integration;
};

const closeIntegrationModal = () => {
    selectedIntegration.value = null;
};

const openApiDocsModal = () => {
    showApiDocsModal.value = true;
};

const closeApiDocsModal = () => {
    showApiDocsModal.value = false;
};

const openTrainingModal = (program: TrainingProgram) => {
    selectedTrainingProgram.value = program;
};

const closeTrainingModal = () => {
    selectedTrainingProgram.value = null;
};

const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
};
</script>

<style scoped>
/* Additional custom styles if needed */
</style>














