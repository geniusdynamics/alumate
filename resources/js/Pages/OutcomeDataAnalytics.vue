<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-100">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-orange-900 via-amber-900 to-yellow-900 text-white">
      <div class="absolute inset-0 bg-black/20"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-orange-200 to-yellow-200 bg-clip-text text-transparent">
            Outcome Data & Analytics
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-orange-100 max-w-4xl mx-auto">
            Transform career outcomes data into actionable insights with comprehensive collection, analysis, and reporting tools.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
              Schedule Demo
            </button>
            <button class="border-2 border-white/30 hover:bg-white/10 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300">
              Learn More
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Features Section -->
    <section class="py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Comprehensive Outcome Tracking
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Collect, analyze, and report on career outcomes with powerful data management and visualization tools.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Data Collection -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Automated Data Collection</h3>
            <p class="text-gray-600 mb-6">
              Streamlined systems for collecting employment outcomes, salary data, and career progression information.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Employment status tracking
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Salary benchmarking
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Career progression monitoring
              </li>
            </ul>
          </div>

          <!-- Survey Management -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Survey Management</h3>
            <p class="text-gray-600 mb-6">
              Create, distribute, and manage outcome surveys with customizable templates and automated follow-ups.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Custom survey builder
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Automated reminders
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Response tracking
              </li>
            </ul>
          </div>

          <!-- Analytics Dashboard -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Analytics Dashboard</h3>
            <p class="text-gray-600 mb-6">
              Interactive dashboards with real-time analytics, trend analysis, and predictive insights for career outcomes.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Real-time analytics
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Trend analysis
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Predictive modeling
              </li>
            </ul>
          </div>

          <!-- Compliance Reporting -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Compliance Reporting</h3>
            <p class="text-gray-600 mb-6">
              Generate reports that meet accreditation requirements and regulatory standards with automated compliance checks.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                NACE standards compliance
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Accreditation reporting
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Automated validation
              </li>
            </ul>
          </div>

          <!-- Data Visualization -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Data Visualization</h3>
            <p class="text-gray-600 mb-6">
              Create compelling visual reports and presentations with interactive charts, graphs, and infographics.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-3"></span>
                Interactive charts
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-3"></span>
                Custom dashboards
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-3"></span>
                Export capabilities
              </li>
            </ul>
          </div>

          <!-- Benchmarking Tools -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Benchmarking Tools</h3>
            <p class="text-gray-600 mb-6">
              Compare your outcomes against peer institutions and industry standards with comprehensive benchmarking data.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Peer comparisons
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Industry benchmarks
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Performance metrics
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Metrics Section -->
    <section class="py-20 bg-gradient-to-r from-orange-900 to-amber-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold mb-4">Measurable Impact</h2>
          <p class="text-xl text-orange-100">See how our Outcome Data & Analytics platform drives results</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-5xl font-bold text-orange-300 mb-2">95%</div>
            <div class="text-lg text-orange-100">Data Collection Rate</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-amber-300 mb-2">80%</div>
            <div class="text-lg text-orange-100">Time Savings on Reporting</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-yellow-300 mb-2">100%</div>
            <div class="text-lg text-orange-100">Compliance Achievement</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-red-300 mb-2">5x</div>
            <div class="text-lg text-orange-100">Faster Report Generation</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Reporting Features Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Comprehensive Reporting Suite
          </h2>
          <p class="text-xl text-gray-600">
            Generate insights that drive strategic decision-making and program improvement
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Employment Outcomes</h3>
            <p class="text-gray-600">Track employment rates, job placement success, and career progression</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Salary Analysis</h3>
            <p class="text-gray-600">Comprehensive salary tracking and benchmarking by program and industry</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Program Effectiveness</h3>
            <p class="text-gray-600">Measure and compare the success of different academic programs</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Accreditation Reports</h3>
            <p class="text-gray-600">Automated generation of reports for accreditation bodies</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Trend Analysis</h3>
            <p class="text-gray-600">Identify patterns and trends in career outcomes over time</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Alumni Tracking</h3>
            <p class="text-gray-600">Long-term career tracking and alumni success monitoring</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Transform Your Data into Strategic Insights
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          Join leading institutions using Alumate to make data-driven decisions and improve career outcomes.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button class="bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
            Schedule Your Demo
          </button>
          <button class="border-2 border-gray-300 hover:border-orange-500 text-gray-700 hover:text-orange-600 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300">
            Download Brochure
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
  layout: 'AppLayout'
});
</script>