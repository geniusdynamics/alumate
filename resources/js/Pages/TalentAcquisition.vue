<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-emerald-100">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-green-900 via-emerald-900 to-teal-900 text-white">
      <div class="absolute inset-0 bg-black/20"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-green-200 to-emerald-200 bg-clip-text text-transparent">
            Talent Acquisition Hub
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-green-100 max-w-4xl mx-auto">
            Connect top talent with leading employers through advanced recruitment tools, multi-school job postings, and intelligent candidate matching.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
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
            Advanced Talent Acquisition Tools
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Streamline your recruitment process with powerful tools for job posting, candidate search, and talent pipeline management.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Multi-School Job Postings -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2-2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Multi-School Job Postings</h3>
            <p class="text-gray-600 mb-6">
              Post jobs across multiple institutions simultaneously, reaching a broader talent pool with unified management.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Cross-institutional posting
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Centralized job management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Automated distribution
              </li>
            </ul>
          </div>

          <!-- Candidate Search -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Advanced Candidate Search</h3>
            <p class="text-gray-600 mb-6">
              Powerful search and filtering tools to find the perfect candidates based on skills, experience, and preferences.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Skills-based filtering
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Experience matching
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Location preferences
              </li>
            </ul>
          </div>

          <!-- Promoted Postings -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Promoted Job Postings</h3>
            <p class="text-gray-600 mb-6">
              Boost visibility for premium positions with promoted listings that reach more qualified candidates faster.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Enhanced visibility
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Priority placement
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Targeted promotion
              </li>
            </ul>
          </div>

          <!-- Talent Pipeline -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Talent Pipeline Management</h3>
            <p class="text-gray-600 mb-6">
              Build and maintain talent pools for future opportunities with automated nurturing and engagement tools.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Talent pool creation
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Automated nurturing
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Engagement tracking
              </li>
            </ul>
          </div>

          <!-- Application Tracking -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Application Tracking System</h3>
            <p class="text-gray-600 mb-6">
              Streamlined application management with automated workflows and collaborative hiring tools.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Application management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Workflow automation
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Collaborative hiring
              </li>
            </ul>
          </div>

          <!-- Analytics & Insights -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Recruitment Analytics</h3>
            <p class="text-gray-600 mb-6">
              Data-driven insights into recruitment performance, candidate quality, and hiring success metrics.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Performance metrics
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Quality assessments
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Success tracking
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Metrics Section -->
    <section class="py-20 bg-gradient-to-r from-green-900 to-emerald-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold mb-4">Recruitment Success Metrics</h2>
          <p class="text-xl text-green-100">See how our Talent Acquisition Hub drives hiring success</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-5xl font-bold text-green-300 mb-2">75%</div>
            <div class="text-lg text-green-100">Faster Time-to-Hire</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-emerald-300 mb-2">4x</div>
            <div class="text-lg text-green-100">More Qualified Candidates</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-teal-300 mb-2">90%</div>
            <div class="text-lg text-green-100">Employer Satisfaction</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-cyan-300 mb-2">65%</div>
            <div class="text-lg text-green-100">Reduction in Hiring Costs</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recruitment Features Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Complete Recruitment Solution
          </h2>
          <p class="text-xl text-gray-600">
            Everything you need to connect top talent with leading employers
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Smart Matching</h3>
            <p class="text-gray-600">AI-powered candidate matching based on skills, experience, and preferences</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Communication Tools</h3>
            <p class="text-gray-600">Integrated messaging and interview scheduling for seamless communication</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Quality Assurance</h3>
            <p class="text-gray-600">Automated screening and verification to ensure candidate quality</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Promotion Tools</h3>
            <p class="text-gray-600">Boost job visibility with targeted promotion and enhanced listings</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance Analytics</h3>
            <p class="text-gray-600">Comprehensive reporting on recruitment metrics and success rates</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Workflow Automation</h3>
            <p class="text-gray-600">Streamline processes with automated workflows and smart notifications</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Revolutionize Your Talent Acquisition Process
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          Join leading organizations using Alumate to connect top talent with exceptional opportunities.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
            Schedule Your Demo
          </button>
          <button class="border-2 border-gray-300 hover:border-green-500 text-gray-700 hover:text-green-600 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300">
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