<template>
    <section class="features-showcase relative overflow-hidden" ref="sectionRef">
        <!-- Enhanced Background with Multiple Gradients -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-900 via-blue-900 to-cyan-900"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-transparent to-indigo-600/20"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-0 left-1/4 w-72 h-72 bg-gradient-to-br from-cyan-400/30 to-blue-400/30 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-gradient-to-br from-indigo-400/20 to-cyan-400/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 3s"></div>
            <div class="absolute top-1/2 right-0 w-64 h-64 bg-gradient-to-br from-indigo-400/25 to-blue-400/25 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s"></div>
        </div>
        
        <!-- Floating Particles -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-10 w-2 h-2 bg-white/30 rounded-full animate-bounce" style="animation-delay: 0s; animation-duration: 3s"></div>
            <div class="absolute top-40 right-20 w-1 h-1 bg-blue-300/40 rounded-full animate-bounce" style="animation-delay: 1s; animation-duration: 4s"></div>
            <div class="absolute bottom-32 left-1/3 w-1.5 h-1.5 bg-indigo-300/30 rounded-full animate-bounce" style="animation-delay: 2s; animation-duration: 5s"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <!-- Section Header -->
            <div class="relative z-10 mb-20 text-center">
                <div class="mb-6 inline-flex items-center rounded-full bg-white/20 px-8 py-4 text-sm font-medium text-white backdrop-blur-md border border-white/30 shadow-xl hover:bg-white/30 hover:scale-105 transition-all duration-300 group">
                    <svg class="mr-3 h-5 w-5 transition-transform duration-300 group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="relative">
                        Powerful Features
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    </span>
                </div>
                <div class="relative inline-block">
                    <h2 class="mb-6 bg-gradient-to-r from-white via-blue-100 to-cyan-100 bg-clip-text text-4xl lg:text-6xl font-bold tracking-tight text-transparent drop-shadow-lg relative">
                        {{ title }}
                        <div class="absolute inset-0 bg-gradient-to-r from-white via-blue-100 to-cyan-100 bg-clip-text text-transparent opacity-50 blur-sm">
                            {{ title }}
                        </div>
                    </h2>
                    <div class="absolute -inset-4 bg-gradient-to-r from-blue-600/20 via-indigo-600/20 to-cyan-600/20 blur-xl opacity-75 animate-pulse"></div>
                </div>
                <p class="mx-auto max-w-4xl text-xl lg:text-2xl text-blue-100/90 leading-relaxed font-light">
                    {{ subtitle }}
                </p>
                <div class="mt-8 flex justify-center">
                    <div class="w-32 h-1 bg-gradient-to-r from-blue-400 via-indigo-400 to-cyan-400 rounded-full relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/60 to-white/0 translate-x-[-100%] animate-shimmer"></div>
                    </div>
                </div>
            </div>

            <!-- Persona Filters -->
            <div v-if="showPersonaFilters && personaFilters.length > 0" class="relative z-10 mb-12">
                <div class="flex flex-wrap justify-center gap-4">
                    <button
                        v-for="filter in personaFilters"
                        :key="filter.value"
                        @click="setActivePersona(filter.value)"
                        :class="[
                            'group relative overflow-hidden rounded-2xl px-8 py-4 text-sm font-semibold transition-all duration-300 hover:scale-105 hover:shadow-2xl border-2',
                            activePersona === filter.value
                                ? 'bg-gradient-to-r from-white via-blue-50 to-indigo-50 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 border-blue-300 shadow-xl'
                                : 'bg-white/10 text-white backdrop-blur-sm hover:bg-white/20 border-white/20 hover:border-white/40',
                        ]"
                        :aria-pressed="activePersona === filter.value"
                    >
                        <!-- Active state background gradient -->
                        <div v-if="activePersona === filter.value" class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-cyan-500/10 animate-pulse"></div>
                        <!-- Hover shimmer effect -->
                        <div v-if="activePersona !== filter.value" class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/30 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        <!-- Content -->
                        <div class="relative z-10 flex items-center space-x-2">
                            <span :class="activePersona === filter.value ? 'text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600' : ''">{{ filter.label }}</span>
                            <span v-if="filter.count" 
                                  class="inline-flex items-center justify-center min-w-[24px] h-6 px-2 rounded-full text-xs font-bold transition-all duration-300"
                                  :class="[
                                      activePersona === filter.value
                                          ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg'
                                          : 'bg-white/20 text-white/80 group-hover:bg-white/30 group-hover:text-white'
                                  ]">
                                {{ filter.count }}
                            </span>
                        </div>
                        <!-- Active state glow -->
                        <div v-if="activePersona === filter.value" class="absolute inset-0 rounded-2xl bg-gradient-to-r from-blue-400/20 via-indigo-400/20 to-cyan-400/20 blur-xl -z-10"></div>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="py-12 text-center">
                <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <p class="text-gray-600">Loading features...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="py-12 text-center">
                <div class="mb-4 text-red-500">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <p class="mb-4 text-gray-600">{{ error }}</p>
                <button @click="fetchFeatures" class="rounded bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">Try Again</button>
            </div>

            <!-- Features Content -->
            <div v-else-if="filteredFeatures.length > 0" class="relative z-10">
                <div class="mx-auto max-w-7xl rounded-2xl bg-white/5 p-8 backdrop-blur-sm">
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        <!-- Feature Tabs/Navigation -->
                        <div class="lg:col-span-4">
                            <div class="sticky top-8">
                                <div class="mb-6 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-white">{{ navigationTitle }}</h3>
                                    <button
                                        v-if="showComparisonMatrix"
                                        @click="toggleComparisonMatrix"
                                        class="group relative overflow-hidden rounded-lg bg-white/10 px-4 py-2 text-sm text-white backdrop-blur-sm transition-all duration-300 hover:bg-white/20 hover:scale-105"
                                    >
                                        <span class="relative z-10">Compare Features</span>
                                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                    </button>
                                </div>
                            </div>

                        <!-- Desktop: Vertical Tabs -->
                        <div class="hidden space-y-4 lg:block">
                            <button
                                v-for="(feature, index) in filteredFeatures"
                                :key="feature.id"
                                @click="setActiveFeature(index)"
                                :class="[
                                    'group relative w-full rounded-2xl p-6 text-left transition-all duration-500 hover:scale-[1.03] hover:shadow-2xl overflow-hidden border-2 transform',
                                    activeFeatureIndex === index
                                        ? 'bg-gradient-to-br from-white via-blue-50/80 to-indigo-50/60 border-blue-300 shadow-2xl scale-[1.02] ring-2 ring-blue-200/50'
                                        : 'bg-white/70 border-white/30 backdrop-blur-sm hover:bg-white/90 hover:border-blue-200/50',
                                ]"
                                :aria-selected="activeFeatureIndex === index"
                                role="tab"
                            >
                                <!-- Active state animated background -->
                                <div v-if="activeFeatureIndex === index" class="absolute inset-0 bg-gradient-to-br from-blue-500/8 via-indigo-500/8 to-cyan-500/8 animate-pulse"></div>
                                
                                <!-- Hover shimmer effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                
                                <!-- Active state glow -->
                                <div v-if="activeFeatureIndex === index" class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-blue-400/20 via-indigo-400/20 to-cyan-400/20 blur-xl -z-10"></div>
                                
                                <div class="flex items-start space-x-5 relative z-10">
                                    <div class="mt-1 flex-shrink-0">
                                        <div v-if="feature.icon" 
                                             class="h-14 w-14 flex items-center justify-center rounded-2xl transition-all duration-500 shadow-xl group-hover:scale-110 group-hover:rotate-6"
                                             :class="[
                                                 activeFeatureIndex === index 
                                                     ? 'bg-gradient-to-br from-blue-500 via-indigo-600 to-cyan-600 text-white shadow-blue-500/40 animate-iconBounce' 
                                                     : 'bg-gradient-to-br from-gray-100 via-gray-50 to-white text-gray-600 group-hover:from-blue-100 group-hover:via-indigo-100 group-hover:to-cyan-100 group-hover:text-blue-600 group-hover:shadow-blue-300/50',
                                             ]"
                                             v-html="getFeatureIcon(feature.icon)"></div>
                                    </div>
                                    <div class="min-w-0 flex-grow relative z-10">
                                        <h4 class="mb-3 font-bold text-lg transition-all duration-300 group-hover:translate-x-1"
                                            :class="[
                                                activeFeatureIndex === index ? 'text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600' : 'text-gray-900 group-hover:text-blue-700',
                                            ]">
                                            {{ feature.title }}
                                        </h4>
                                        <p class="line-clamp-2 text-sm leading-relaxed transition-all duration-300 group-hover:translate-x-1"
                                           :class="[
                                               activeFeatureIndex === index ? 'text-blue-700/80' : 'text-gray-600 group-hover:text-gray-700',
                                           ]">
                                            {{ feature.description }}
                                        </p>
                                        <div v-if="feature.usageStats && feature.usageStats.length > 0" class="mt-4 flex items-center space-x-3">
                                            <div v-for="stat in feature.usageStats.slice(0, 2)" :key="stat.metric" 
                                                 class="flex items-center space-x-2 px-3 py-1.5 rounded-full transition-all duration-300"
                                                 :class="[
                                                     activeFeatureIndex === index
                                                         ? 'bg-gradient-to-r from-blue-100/80 to-indigo-100/80 text-blue-700 shadow-sm'
                                                         : 'bg-gray-100/80 text-gray-500 group-hover:bg-blue-50 group-hover:text-blue-600'
                                                 ]">
                                                <i class="fas fa-chart-line text-xs"></i>
                                                <span class="text-xs font-semibold">{{ formatUsageStatValue(stat) }}</span>
                                                <span class="text-xs opacity-75">{{ stat.label }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Mobile: Horizontal Tabs -->
                        <div class="lg:hidden">
                            <div class="flex space-x-4 overflow-x-auto pb-8 scrollbar-hide">
                                <button
                                    v-for="(feature, index) in filteredFeatures"
                                    :key="feature.id"
                                    @click="setActiveFeature(index)"
                                    :class="[
                                        'group relative flex-shrink-0 rounded-2xl px-8 py-5 text-sm font-bold transition-all duration-500 hover:scale-105 hover:shadow-2xl whitespace-nowrap overflow-hidden border-2 transform hover:-translate-y-1',
                                        activeFeatureIndex === index
                                            ? 'bg-gradient-to-br from-blue-600 via-indigo-600 to-cyan-600 text-white shadow-2xl border-transparent animate-pulse'
                                            : 'bg-white/90 text-gray-700 border-white/30 backdrop-blur-sm hover:bg-white hover:text-blue-600 hover:border-blue-300',
                                    ]"
                                >
                                    <!-- Active state glow -->
                                    <div v-if="activeFeatureIndex === index" class="absolute inset-0 bg-gradient-to-br from-blue-400/20 via-indigo-400/20 to-cyan-400/20 rounded-2xl blur-xl"></div>
                                    
                                    <!-- Hover shimmer effect -->
                                    <div v-if="activeFeatureIndex !== index" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 animate-shimmer"></div>
                                    
                                    <div class="flex items-center space-x-3 relative z-10">
                                        <div v-if="feature.icon" 
                                             :class="[
                                                'h-6 w-6 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6',
                                                activeFeatureIndex === index ? 'text-white animate-iconBounce' : 'text-blue-600 group-hover:text-blue-700'
                                             ]"
                                             v-html="getFeatureIcon(feature.icon)"></div>
                                        <span class="transition-all duration-300 group-hover:translate-x-1 font-semibold">{{ feature.title }}</span>
                                        
                                        <!-- Usage stats for mobile -->
                                        <div v-if="feature.usageStats && feature.usageStats.length > 0 && activeFeatureIndex === index" 
                                             class="ml-2 px-2 py-1 rounded-full text-xs font-medium"
                                             :class="activeFeatureIndex === index ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-700'">
                                            {{ formatUsageStatValue(feature.usageStats[0]) }}
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Feature Comparison Matrix Toggle -->
                        <div v-if="showComparisonMatrix" class="mt-6 border-t border-gray-200 pt-6">
                            <button @click="toggleComparisonMatrix" class="flex items-center space-x-2 font-medium text-blue-600 hover:text-blue-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"
                                    ></path>
                                </svg>
                                <span>{{ showMatrix ? 'Hide' : 'Show' }} Comparison Matrix</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Feature Content -->
                <div class="lg:col-span-8">
                    <div v-if="activeFeature" class="overflow-hidden rounded-3xl bg-gradient-to-br from-white via-blue-50/30 to-indigo-50/30 shadow-2xl border border-white/40 backdrop-blur-sm hover:shadow-3xl transition-all duration-500 transform hover:scale-[1.01]">
                        <!-- Feature Header -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-white/90 via-blue-50/60 to-indigo-50/60 p-10 backdrop-blur-lg border-b border-white/30 shadow-xl">
                            <!-- Animated background elements -->
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/8 via-indigo-600/8 to-cyan-600/8 animate-backgroundShift"></div>
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/10 to-indigo-400/10 rounded-full blur-3xl animate-overlayPulse"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-cyan-400/10 to-blue-400/10 rounded-full blur-2xl animate-overlayPulse" style="animation-delay: 1s"></div>
                            
                            <div class="flex items-start justify-between relative z-10">
                                <div class="flex-grow">
                                    <div class="mb-8 flex justify-start">
                                        <div class="relative group">
                                            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/30 via-indigo-400/30 to-cyan-400/30 rounded-3xl blur-xl group-hover:blur-2xl transition-all duration-300"></div>
                                            <div
                                                class="relative flex h-24 w-24 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-500 via-indigo-600 to-cyan-500 text-white shadow-2xl transform hover:scale-110 hover:rotate-3 transition-all duration-500 animate-iconBounce"
                                                v-html="getFeatureIcon(activeFeature.icon)"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Enhanced title with glow effect -->
                                    <div class="relative mb-6">
                                        <h3 class="text-5xl font-black bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-800 bg-clip-text text-transparent leading-tight">
                                            {{ activeFeature.title }}
                                        </h3>
                                        <div class="absolute inset-0 text-5xl font-black bg-gradient-to-r from-blue-600/20 via-indigo-600/20 to-cyan-600/20 bg-clip-text text-transparent blur-sm animate-pulse">
                                            {{ activeFeature.title }}
                                        </div>
                                    </div>
                                    
                                    <p class="mb-8 text-xl text-gray-700 leading-relaxed font-medium">
                                        {{ activeFeature.description }}
                                    </p>

                                    <!-- Enhanced Benefits List -->
                                    <div v-if="activeFeature.benefits && activeFeature.benefits.length > 0" class="space-y-4">
                                        <div v-for="(benefit, index) in activeFeature.benefits.slice(0, 3)" :key="benefit" 
                                             class="group flex items-center space-x-4 p-3 rounded-xl bg-white/60 backdrop-blur-sm border border-white/40 hover:bg-white/80 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <span class="text-base text-gray-700 font-medium group-hover:text-gray-900 transition-colors duration-300">{{ benefit }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced Usage Statistics -->
                                <div v-if="activeFeature.usageStats && activeFeature.usageStats.length > 0" class="ml-8 flex-shrink-0">
                                    <div class="relative group">
                                        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 via-indigo-400/20 to-cyan-400/20 rounded-2xl blur-xl group-hover:blur-2xl transition-all duration-300"></div>
                                        <div class="relative rounded-2xl bg-white/90 backdrop-blur-sm border border-white/50 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
                                            <div class="text-center">
                                                <div class="text-3xl font-black bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                                                    {{ formatUsageStatValue(activeFeature.usageStats[0]) }}
                                                </div>
                                                <div class="text-sm text-gray-600 font-medium">
                                                    {{ activeFeature.usageStats[0].label }}
                                                </div>
                                                <div class="mt-3 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-full animate-pulse"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Demo -->
                        <div class="p-6">
                            <!-- Demo Iframe -->
                            <div v-if="activeFeature.demoUrl && showInteractiveDemo" class="mb-6">
                                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50/80 to-white/80 p-8 backdrop-blur-sm border border-white/30 shadow-xl">
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-indigo-500/5 to-cyan-500/5"></div>
                                    <div class="relative">
                                        <div class="mb-6 flex items-center justify-between">
                                            <h4 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-blue-800 bg-clip-text text-transparent">Interactive Demo</h4>
                                            <div class="flex items-center space-x-2">
                                                <div class="h-3 w-3 rounded-full bg-red-400 animate-pulse"></div>
                                                <div class="h-3 w-3 rounded-full bg-yellow-400 animate-pulse" style="animation-delay: 0.2s"></div>
                                                <div class="h-3 w-3 rounded-full bg-green-400 animate-pulse" style="animation-delay: 0.4s"></div>
                                            </div>
                                        </div>
                                        <div class="aspect-video overflow-hidden rounded-xl bg-white shadow-2xl border border-gray-200 hover:shadow-3xl transition-shadow duration-300">
                                            <iframe
                                                :src="activeFeature.demoUrl"
                                                class="absolute inset-0 h-full w-full"
                                                frameborder="0"
                                                :title="`Interactive demo of ${activeFeature.title}`"
                                                loading="lazy"
                                                sandbox="allow-scripts allow-same-origin allow-forms"
                                                @load="handleDemoLoad"
                                                @error="handleDemoError"
                                            ></iframe>

                                            <!-- Demo Loading Overlay -->
                                            <div v-if="demoLoading" class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                                <div class="text-center">
                                                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center">
                                                        <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-white"></div>
                                                    </div>
                                                    <p class="text-lg font-medium text-gray-700">Loading interactive demo...</p>
                                                    <p class="text-sm text-gray-500 mt-1">Please wait while we prepare your experience</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Screenshot with Hotspots -->
                            <div v-else-if="activeFeature.screenshot" class="mb-6">
                                <div class="relative">
                                    <img
                                        :src="activeFeature.screenshot"
                                        :alt="`Screenshot of ${activeFeature.title}`"
                                        class="w-full rounded-lg shadow-md"
                                        @error="handleImageError"
                                    />

                                    <!-- Interactive Hotspots -->
                                    <div
                                        v-for="(hotspot, index) in activeFeature.hotspots"
                                        :key="index"
                                        class="absolute -translate-x-1/2 -translate-y-1/2 transform cursor-pointer"
                                        :style="{ left: `${hotspot.x}%`, top: `${hotspot.y}%` }"
                                        @click="showHotspotTooltip(hotspot, index)"
                                        @mouseenter="showHotspotTooltip(hotspot, index)"
                                        @mouseleave="hideHotspotTooltip"
                                    >
                                        <div class="h-6 w-6 animate-pulse rounded-full border-2 border-white bg-blue-600 shadow-lg">
                                            <div class="h-full w-full animate-ping rounded-full bg-blue-600"></div>
                                        </div>

                                        <!-- Hotspot Tooltip -->
                                        <div
                                            v-if="activeHotspot === index"
                                            class="absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform"
                                        >
                                            <div class="max-w-xs rounded-lg bg-gray-900 px-3 py-2 text-sm text-white shadow-lg">
                                                <div class="mb-1 font-medium">{{ hotspot.title }}</div>
                                                <div class="text-xs text-gray-300">{{ hotspot.description }}</div>

                                                <!-- Tooltip Arrow -->
                                                <div class="absolute left-1/2 top-full -translate-x-1/2 transform">
                                                    <div class="border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Demo Video -->
                            <div v-if="activeFeature.demoVideo && !showInteractiveDemo" class="mb-6">
                                <div class="relative overflow-hidden rounded-lg bg-black" style="aspect-ratio: 16/9">
                                    <video
                                        :src="activeFeature.demoVideo"
                                        class="h-full w-full object-cover"
                                        controls
                                        preload="metadata"
                                        :poster="activeFeature.screenshot"
                                    >
                                        <track
                                            v-if="activeFeature.subtitles"
                                            kind="subtitles"
                                            :src="activeFeature.subtitles"
                                            srclang="en"
                                            label="English"
                                            default
                                        />
                                    </video>
                                </div>
                            </div>

                            <!-- Feature Details -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Target Personas -->
                                <div v-if="activeFeature.targetPersona && activeFeature.targetPersona.length > 0" class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50/80 to-indigo-50/80 p-6 backdrop-blur-sm border border-blue-100/50 shadow-lg hover:shadow-xl transition-all duration-300">
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-indigo-500/5 to-purple-500/5"></div>
                                    <div class="relative z-10">
                                        <h4 class="mb-4 text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Perfect For</h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="persona in activeFeature.targetPersona"
                                                :key="persona.id"
                                                class="group flex items-center space-x-4 rounded-lg bg-white/60 backdrop-blur-sm border border-white/30 p-4 hover:bg-white/80 hover:shadow-md transition-all duration-300 hover:scale-[1.02]"
                                            >
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors duration-300">{{ persona.name }}</div>
                                                    <div class="text-sm text-gray-600 group-hover:text-gray-700 transition-colors duration-300">{{ persona.description }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Benefits -->
                                <div v-if="activeFeature.benefits && activeFeature.benefits.length > 3" class="relative overflow-hidden rounded-xl bg-gradient-to-br from-green-50/80 to-emerald-50/80 p-6 backdrop-blur-sm border border-green-100/50 shadow-lg hover:shadow-xl transition-all duration-300">
                                    <div class="absolute inset-0 bg-gradient-to-r from-green-500/5 via-emerald-500/5 to-teal-500/5"></div>
                                    <div class="relative z-10">
                                        <h4 class="mb-4 text-lg font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Additional Benefits</h4>
                                        <ul class="space-y-3">
                                            <li v-for="(benefit, index) in activeFeature.benefits.slice(3)" :key="benefit" class="group flex items-start space-x-3 p-3 rounded-lg bg-white/60 backdrop-blur-sm border border-white/30 hover:bg-white/80 hover:shadow-md transition-all duration-300 hover:scale-[1.02]">
                                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-emerald-500 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                                    <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd"
                                                        ></path>
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-medium text-gray-800 group-hover:text-green-700 transition-colors duration-300">{{ benefit }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Section -->
                            <div class="mt-8 relative overflow-hidden rounded-xl bg-gradient-to-br from-gray-50/80 to-blue-50/80 p-6 backdrop-blur-sm border border-gray-200/50 shadow-lg hover:shadow-xl transition-all duration-300">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
                                <div class="relative z-10 flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0">
                                    <div>
                                        <h4 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-blue-700 bg-clip-text text-transparent">Ready to try {{ activeFeature.title }}?</h4>
                                        <p class="text-sm text-gray-600 mt-1">Join thousands of alumni already using this feature</p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <button
                                            @click="handleFeatureCTA('demo')"
                                            class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-white font-semibold shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl"
                                        >
                                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                            <span class="relative z-10">Try Demo</span>
                                        </button>
                                        <button
                                            @click="handleFeatureCTA('learn_more')"
                                            class="group relative overflow-hidden rounded-xl border-2 border-gray-300 bg-white/80 backdrop-blur-sm px-6 py-3 text-gray-700 font-semibold transition-all duration-300 hover:scale-105 hover:shadow-lg hover:border-blue-300 hover:text-blue-700"
                                        >
                                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-blue-500/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                            <span class="relative z-10">Learn More</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison Matrix Modal -->
            <div v-if="showMatrix" class="fixed inset-0 z-50 overflow-y-auto" @click="closeComparisonMatrix">
                <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div
                        class="my-8 inline-block w-full max-w-6xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all"
                        @click.stop
                    >
                        <FeatureComparisonMatrix :features="filteredFeatures" :audience="audience" @close="closeComparisonMatrix" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!loading && filteredFeatures.length === 0" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50/80 to-blue-50/80 p-12 text-center backdrop-blur-sm border border-gray-200/50 shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 via-purple-500/5 to-pink-500/5"></div>
                <div class="relative z-10">
                    <div class="mb-6 flex justify-center">
                        <div class="rounded-full bg-gradient-to-r from-gray-100 to-blue-100 p-4 shadow-lg">
                            <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="mb-3 text-xl font-bold bg-gradient-to-r from-gray-900 to-blue-700 bg-clip-text text-transparent">No features available</h3>
                    <p class="text-gray-600 max-w-md mx-auto">No features match the selected criteria. Try adjusting your filters or check back later for new features.</p>
                </div>
            </div>
        </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { logger } from '@/Utils/logger';
import type { AudienceType, FeatureStatistic, InstitutionalFeature, PlatformFeature } from '@/Types/homepage';
import { computed, onMounted, ref, watch } from 'vue';
import FeatureComparisonMatrix from './FeatureComparisonMatrix.vue';

interface PersonaFilter {
    value: string;
    label: string;
    count?: number;
}

interface Hotspot {
    x: number;
    y: number;
    title: string;
    description: string;
}

interface Props {
    audience: AudienceType;
    title?: string;
    subtitle?: string;
    showPersonaFilters?: boolean;
    showComparisonMatrix?: boolean;
    showInteractiveDemo?: boolean;
    demoMode?: 'carousel' | 'tabs' | 'accordion';
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Powerful Features for Alumni Success',
    subtitle: 'Discover the tools and capabilities that will accelerate your professional growth and networking',
    showPersonaFilters: true,
    showComparisonMatrix: true,
    showInteractiveDemo: true,
    demoMode: 'tabs',
});

// Reactive state
const features = ref<(PlatformFeature | InstitutionalFeature)[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const activePersona = ref<string>('all');
const activeFeatureIndex = ref(0);
const showMatrix = ref(false);
const demoLoading = ref(false);
const activeHotspot = ref<number | null>(null);
const sectionRef = ref<HTMLElement>();

// Computed properties
const title = computed(() => {
    if (props.audience === 'institutional') {
        return 'Enterprise Alumni Engagement Solutions';
    }
    return props.title;
});

const subtitle = computed(() => {
    if (props.audience === 'institutional') {
        return 'Comprehensive tools and analytics to transform your alumni community engagement';
    }
    return props.subtitle;
});

const navigationTitle = computed(() => {
    return props.audience === 'institutional' ? 'Enterprise Features' : 'Platform Features';
});

const personaFilters = computed((): PersonaFilter[] => {
    if (!props.showPersonaFilters) return [];

    const filters: PersonaFilter[] = [{ value: 'all', label: 'All Features', count: features.value.length }];

    if (props.audience === 'individual') {
        const individualFeatures = features.value as PlatformFeature[];
        const personas = new Set<string>();

        individualFeatures.forEach((feature) => {
            feature.targetPersona?.forEach((persona) => {
                personas.add(persona.name);
            });
        });

        personas.forEach((persona) => {
            const count = individualFeatures.filter((feature) => feature.targetPersona?.some((p) => p.name === persona)).length;

            filters.push({
                value: persona.toLowerCase().replace(/\s+/g, '_'),
                label: persona,
                count,
            });
        });
    } else {
        const institutionalFeatures = features.value as InstitutionalFeature[];
        const types = new Set<string>();

        institutionalFeatures.forEach((feature) => {
            types.add(feature.targetInstitution);
        });

        types.forEach((type) => {
            const count = institutionalFeatures.filter((feature) => feature.targetInstitution === type).length;

            filters.push({
                value: type,
                label: formatInstitutionType(type),
                count,
            });
        });
    }

    return filters;
});

const filteredFeatures = computed(() => {
    if (activePersona.value === 'all') {
        return features.value;
    }

    if (props.audience === 'individual') {
        const individualFeatures = features.value as PlatformFeature[];
        const personaName = activePersona.value.replace(/_/g, ' ');

        return individualFeatures.filter((feature) =>
            feature.targetPersona?.some((persona) => persona.name.toLowerCase() === personaName.toLowerCase()),
        );
    } else {
        const institutionalFeatures = features.value as InstitutionalFeature[];
        return institutionalFeatures.filter((feature) => feature.targetInstitution === activePersona.value);
    }
});

const activeFeature = computed(() => {
    return filteredFeatures.value[activeFeatureIndex.value] || null;
});

// Methods
const fetchFeatures = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/homepage/features?audience=${props.audience}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            features.value = data.data;
        } else {
            throw new Error(data.message || 'Failed to fetch features');
        }
    } catch (err) {
        console.error('Error fetching features:', err);
        error.value = err instanceof Error ? err.message : 'Failed to load features';

        // Fallback to mock data for development
        if (process.env.NODE_ENV === 'development') {
            features.value = getMockFeatures();
        }
    } finally {
        loading.value = false;
    }
};

const getMockFeatures = (): (PlatformFeature | InstitutionalFeature)[] => {
    if (props.audience === 'institutional') {
        return [
            {
                id: 'admin_dashboard',
                title: 'Comprehensive Admin Dashboard',
                description: 'Manage your entire alumni community with powerful analytics and engagement tools.',
                benefits: [
                    'Real-time engagement analytics',
                    'Event management tools',
                    'Communication campaign builder',
                    'Alumni directory management',
                    'Custom reporting and insights',
                ],
                targetInstitution: 'university',
                screenshot: '/images/features/admin-dashboard.png',
                demoVideo: '/videos/features/admin-dashboard-demo.mp4',
                demoUrl: '/demo/admin-dashboard',
                pricingTier: 'enterprise',
                customizationLevel: 'advanced',
                icon: 'dashboard',
                usageStats: [
                    {
                        metric: 'institutions_using',
                        value: 150,
                        label: 'Institutions Using',
                        trend: 'up',
                    },
                ],
                hotspots: [
                    {
                        x: 25,
                        y: 30,
                        title: 'Analytics Overview',
                        description: 'Real-time engagement metrics and trends',
                    },
                    {
                        x: 75,
                        y: 45,
                        title: 'Event Management',
                        description: 'Create and manage alumni events with RSVP tracking',
                    },
                ],
            },
            {
                id: 'branded_mobile_app',
                title: 'Custom Branded Mobile Apps',
                description: 'Deploy your own branded alumni app with full customization and white-label solutions.',
                benefits: [
                    'Complete white-label solution',
                    'Custom branding and features',
                    'App store deployment included',
                    'Push notification campaigns',
                    'Offline functionality',
                ],
                targetInstitution: 'university',
                screenshot: '/images/features/branded-app-showcase.png',
                demoVideo: '/videos/features/branded-app-demo.mp4',
                pricingTier: 'enterprise',
                customizationLevel: 'full',
                icon: 'mobile',
                usageStats: [
                    {
                        metric: 'apps_deployed',
                        value: 45,
                        label: 'Apps Deployed',
                        trend: 'up',
                    },
                ],
            },
        ] as InstitutionalFeature[];
    }

    return [
        {
            id: 'networking',
            title: 'Smart Alumni Networking',
            description: 'Connect with alumni based on shared interests, career goals, and industry focus using AI-powered recommendations.',
            benefits: [
                'AI-powered connection recommendations',
                'Industry-specific networking groups',
                'Professional conversation starters',
                'Mutual connection discovery',
                'Privacy-controlled networking',
            ],
            screenshot: '/images/features/networking-dashboard.png',
            demoVideo: '/videos/features/networking-demo.mp4',
            demoUrl: '/demo/networking',
            usageStats: [
                {
                    metric: 'connections_made_monthly',
                    value: 12000,
                    label: 'Monthly Connections',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'recent_grad',
                    name: 'Recent Graduates',
                    description: 'New alumni looking to build professional networks',
                    careerStage: 'recent_grad',
                    primaryGoals: ['Find mentors', 'Job opportunities'],
                    painPoints: ['Limited network', 'Career uncertainty'],
                },
                {
                    id: 'mid_career',
                    name: 'Mid-Career Professionals',
                    description: 'Experienced professionals seeking career advancement',
                    careerStage: 'mid_career',
                    primaryGoals: ['Career advancement', 'Industry connections'],
                    painPoints: ['Career plateau', 'Limited industry connections'],
                },
            ],
            category: 'networking',
            icon: 'network',
            hotspots: [
                {
                    x: 30,
                    y: 25,
                    title: 'Connection Recommendations',
                    description: 'AI-powered suggestions based on your profile and goals',
                },
                {
                    x: 70,
                    y: 60,
                    title: 'Industry Groups',
                    description: 'Join groups specific to your industry and interests',
                },
            ],
        },
        {
            id: 'mentorship',
            title: 'Career Mentorship Matching',
            description: 'Get paired with experienced alumni mentors in your field through our intelligent matching system.',
            benefits: [
                'Personalized mentor matching',
                'Structured mentorship programs',
                'Goal tracking and progress monitoring',
                'Video call integration',
                'Mentorship resource library',
            ],
            screenshot: '/images/features/mentorship-matching.png',
            demoVideo: '/videos/features/mentorship-demo.mp4',
            demoUrl: '/demo/mentorship',
            usageStats: [
                {
                    metric: 'active_mentorships',
                    value: 1800,
                    label: 'Active Mentorships',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'recent_grad',
                    name: 'Recent Graduates',
                    description: 'New alumni seeking career guidance',
                    careerStage: 'recent_grad',
                    primaryGoals: ['Career guidance', 'Skill development'],
                    painPoints: ['Lack of experience', 'Career direction'],
                },
            ],
            category: 'mentorship',
            icon: 'users',
        },
        {
            id: 'job_board',
            title: 'Exclusive Alumni Job Board',
            description: 'Access job opportunities shared exclusively within your alumni network, with referral tracking.',
            benefits: [
                'Alumni-exclusive job postings',
                'Referral tracking system',
                'Application status updates',
                'Salary insights and benchmarks',
                'Interview preparation resources',
            ],
            screenshot: '/images/features/job-board.png',
            demoVideo: '/videos/features/job-board-demo.mp4',
            usageStats: [
                {
                    metric: 'job_placements',
                    value: 3200,
                    label: 'Job Placements',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'job_seeker',
                    name: 'Job Seekers',
                    description: 'Alumni actively looking for new opportunities',
                    careerStage: 'mid_career',
                    primaryGoals: ['Find new job', 'Career change'],
                    painPoints: ['Limited opportunities', 'Competition'],
                },
            ],
            category: 'jobs',
            icon: 'briefcase',
        },
    ] as PlatformFeature[];
};

const setActivePersona = (persona: string): void => {
    activePersona.value = persona;
    activeFeatureIndex.value = 0; // Reset to first feature when filter changes
};

const setActiveFeature = (index: number): void => {
    activeFeatureIndex.value = index;
    activeHotspot.value = null; // Hide any active hotspots
};

const toggleComparisonMatrix = (): void => {
    showMatrix.value = !showMatrix.value;
};

const closeComparisonMatrix = (): void => {
    showMatrix.value = false;
};

const showHotspotTooltip = (hotspot: Hotspot, index: number): void => {
    activeHotspot.value = index;
};

const hideHotspotTooltip = (): void => {
    activeHotspot.value = null;
};

const handleDemoLoad = (): void => {
    demoLoading.value = false;
};

const handleDemoError = (): void => {
    demoLoading.value = false;
    console.error('Failed to load interactive demo');
};

const handleImageError = (event: Event): void => {
    const img = event.target as HTMLImageElement;
    img.src = '/images/placeholder-feature.png'; // Fallback image
};

const handleFeatureCTA = (action: 'demo' | 'learn_more'): void => {
    if (!activeFeature.value) return;

    // Track the CTA click
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'feature_cta_click', {
            feature_id: activeFeature.value.id,
            action: action,
            audience: props.audience,
        });
    }

    if (action === 'demo') {
        // Open demo in new tab or modal
        if (activeFeature.value.demoUrl) {
            window.open(activeFeature.value.demoUrl, '_blank');
        }
    } else {
        // Navigate to feature detail page or show more info
        logger.log('Learn more about feature:', activeFeature.value.id);
    }
};

const getFeatureIcon = (iconName: string): string => {
    const icons: Record<string, string> = {
        network: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M15 9H9v6h6V9zm-2 4h-2v-2h2v2zm8-2V9h-2V7c0-1.1-.9-2-2-2h-2V3h-2v2h-2V3H9v2H7c-1.1 0-2 .9-2 2v2H3v2h2v2H3v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2z"/></svg>`,
        users: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>`,
        briefcase: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1zM6 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1zM14 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1z"/></svg>`,
        dashboard: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>`,
        mobile: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM7 4h10v12H7V4zm5 15c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
    };

    return icons[iconName] || icons.network;
};

const formatInstitutionType = (type: string): string => {
    const typeMap: Record<string, string> = {
        university: 'Universities',
        college: 'Colleges',
        corporate: 'Corporations',
        nonprofit: 'Non-Profits',
    };
    return typeMap[type] || type;
};

const formatUsageStat = (stat: FeatureStatistic): string => {
    return `${stat.value.toLocaleString()} ${stat.label}`;
};

const formatUsageStatValue = (stat: FeatureStatistic): string => {
    return stat.value.toLocaleString();
};

// Watchers
watch(activePersona, () => {
    // Reset to first feature when persona changes
    activeFeatureIndex.value = 0;
});

watch(
    () => props.audience,
    () => {
        // Refetch features when audience changes
        fetchFeatures();
    },
);

// Lifecycle hooks
onMounted(() => {
    fetchFeatures();
});

// Expose methods for parent Components
defineExpose({
    fetchFeatures,
    refresh: fetchFeatures,
    setActiveFeature,
    setActivePersona,
});
</script>

<style scoped>
.features-showcase {
    padding-top: 6rem;
    padding-bottom: 6rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #8b5cf6 100%),
                radial-gradient(circle at 30% 70%, rgba(102, 126, 234, 0.4) 0%, transparent 60%),
                radial-gradient(circle at 70% 30%, rgba(240, 147, 251, 0.3) 0%, transparent 60%);
    background-size: 100% 100%, 150% 150%, 120% 120%;
    background-attachment: fixed;
    position: relative;
    overflow: hidden;
    animation: backgroundShift 20s ease-in-out infinite;
}

.features-showcase::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(240, 147, 251, 0.3) 0%, transparent 50%),
                linear-gradient(45deg, rgba(102, 126, 234, 0.1) 0%, transparent 30%, rgba(240, 147, 251, 0.1) 100%);
    pointer-events: none;
    animation: overlayPulse 15s ease-in-out infinite;
}

.features-showcase::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(255, 255, 255, 0.05) 90deg, transparent 180deg, rgba(102, 126, 234, 0.05) 270deg, transparent 360deg);
    animation: rotate 30s linear infinite;
    pointer-events: none;
}

/* Background animations */
@keyframes backgroundShift {
    0%, 100% {
        background-position: 0% 50%, 0% 0%, 100% 100%;
    }
    25% {
        background-position: 100% 50%, 25% 25%, 75% 75%;
    }
    50% {
        background-position: 50% 100%, 50% 50%, 50% 50%;
    }
    75% {
        background-position: 0% 0%, 75% 75%, 25% 25%;
    }
}

@keyframes overlayPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 2s ease-in-out infinite;
}

@keyframes cardHover {
    0% {
        transform: translateY(0) scale(1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    100% {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
}

@keyframes iconBounce {
    0%, 100% {
        transform: scale(1) rotate(0deg);
    }
    50% {
        transform: scale(1.1) rotate(5deg);
    }
}

@keyframes backgroundShift {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

@keyframes overlayPulse {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.1);
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    50% {
        box-shadow: 0 0 40px rgba(59, 130, 246, 0.6), 0 0 60px rgba(147, 51, 234, 0.3);
    }
}

.animate-backgroundShift {
    animation: backgroundShift 8s ease-in-out infinite;
    background-size: 200% 200%;
}

.animate-overlayPulse {
    animation: overlayPulse 4s ease-in-out infinite;
}

.animate-glow {
    animation: glow 3s ease-in-out infinite;
}

.animate-iconBounce {
    animation: iconBounce 2s ease-in-out infinite;
}

/* Smooth transitions */
.features-showcase * {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform, box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Loading animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Hotspot animations */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes ping {
    75%,
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Sticky positioning for navigation */
.sticky {
    position: sticky;
}

/* Focus styles for accessibility */
.features-showcase button:focus,
.features-showcase iframe:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .features-showcase .lg\:col-span-4 {
        @apply col-span-1;
    }

    .features-showcase .lg\:col-span-8 {
        @apply col-span-1;
    }
}

@media (max-width: 768px) {
    .features-showcase {
        @apply py-12;
    }

    .features-showcase .sticky {
        position: static;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .features-showcase *,
    .animate-spin,
    .animate-pulse,
    .animate-ping {
        animation: none;
        transition: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .features-showcase .bg-gray-50 {
        background-color: #ffffff;
    }

    .features-showcase .text-gray-600 {
        color: #000000;
    }
}

/* Print styles */
@media print {
    .features-showcase {
        break-inside: avoid;
    }

    .features-showcase .sticky {
        position: static;
    }

    .features-showcase iframe,
    .features-showcase video {
        display: none;
    }
}
</style>














