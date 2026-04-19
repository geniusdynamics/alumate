<template>
  <div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100">
    <!-- Header Section -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <h1 class="text-4xl font-bold text-gray-900 mb-4">
            Fundraising Excellence
          </h1>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Maximize your fundraising potential with data-driven strategies and comprehensive donor management tools.
          </p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Key Features Section -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Donor Management</h3>
          <p class="text-gray-600">Comprehensive donor database with detailed profiles, giving history, and engagement tracking.</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Campaign Analytics</h3>
          <p class="text-gray-600">Real-time campaign performance metrics and predictive analytics for fundraising success.</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Automated Workflows</h3>
          <p class="text-gray-600">Streamline donor communications and follow-ups with intelligent automation tools.</p>
        </div>
      </div>

      <!-- Statistics Section -->
      <div class="bg-white rounded-lg shadow-md p-8 mb-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Fundraising Impact</h2>
        <div class="grid md:grid-cols-3 gap-8">
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">250%</div>
            <div class="text-gray-600">Average increase in donor engagement</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">$2.5M+</div>
            <div class="text-gray-600">Additional funds raised annually</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">85%</div>
            <div class="text-gray-600">Donor retention rate improvement</div>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
          Supercharge Your Fundraising Efforts
        </h2>
        <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
          Transform your fundraising strategy with our comprehensive platform designed to maximize donor engagement and campaign success.
        </p>
        <button class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
          Start Fundraising
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

// Component logic can be added here
</script>

<style scoped>
/* Additional component-specific styles can be added here */
</style>