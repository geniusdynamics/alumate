<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 text-white">
      <div class="absolute inset-0 bg-black/20"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-blue-200 to-purple-200 bg-clip-text text-transparent">
            Employer Relations Management
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-blue-100 max-w-4xl mx-auto">
            Build and maintain strong employer partnerships with comprehensive relationship management tools and strategic engagement features.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
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
            Comprehensive Employer Management
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Streamline employer relationships with powerful tools for contact management, engagement tracking, and strategic partnership development.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Employer Directory -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Employer Directory</h3>
            <p class="text-gray-600 mb-6">
              Centralized database of employer contacts with detailed company profiles, hiring history, and engagement metrics.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Company profiles & details
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Contact management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Hiring history tracking
              </li>
            </ul>
          </div>

          <!-- Contact Profiles -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Contact Profiles</h3>
            <p class="text-gray-600 mb-6">
              Detailed contact profiles with communication history, preferences, and relationship strength indicators.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Individual contact details
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Communication history
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Relationship scoring
              </li>
            </ul>
          </div>

          <!-- Task Management -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Task & Notes Management</h3>
            <p class="text-gray-600 mb-6">
              Organize follow-ups, track action items, and maintain detailed notes for every employer interaction.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Task scheduling & reminders
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Detailed interaction notes
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                Follow-up automation
              </li>
            </ul>
          </div>

          <!-- Engagement Tracking -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Engagement Analytics</h3>
            <p class="text-gray-600 mb-6">
              Track engagement levels, measure relationship health, and identify opportunities for deeper partnerships.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Engagement metrics
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Relationship health scores
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Partnership opportunities
              </li>
            </ul>
          </div>

          <!-- Event Coordination -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Event Coordination</h3>
            <p class="text-gray-600 mb-6">
              Plan and manage employer events, career fairs, and networking opportunities with integrated scheduling tools.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Event planning & management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Employer invitations
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
                Attendance tracking
              </li>
            </ul>
          </div>

          <!-- Partnership Development -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Partnership Development</h3>
            <p class="text-gray-600 mb-6">
              Identify and develop strategic partnerships with key employers through data-driven relationship building.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Strategic partnership planning
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Opportunity identification
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Relationship progression
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Key Metrics Section -->
    <section class="py-20 bg-gradient-to-r from-blue-900 to-indigo-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold mb-4">Relationship Success Metrics</h2>
          <p class="text-xl text-blue-100">See how our ERM platform strengthens employer partnerships</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-5xl font-bold text-blue-300 mb-2">85%</div>
            <div class="text-lg text-blue-100">Employer Retention Rate</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-indigo-300 mb-2">3x</div>
            <div class="text-lg text-blue-100">Increase in Partnerships</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-purple-300 mb-2">60%</div>
            <div class="text-lg text-blue-100">More Efficient Outreach</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-pink-300 mb-2">92%</div>
            <div class="text-lg text-blue-100">User Satisfaction</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Relationship Management Features -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Advanced Relationship Tools
          </h2>
          <p class="text-xl text-gray-600">
            Everything you need to build and maintain strong employer relationships
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Integration</h3>
            <p class="text-gray-600">Seamless email tracking and automated communication workflows</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance Analytics</h3>
            <p class="text-gray-600">Track relationship performance and identify growth opportunities</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Workflow Automation</h3>
            <p class="text-gray-600">Automate routine tasks and streamline relationship management</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Calendar Integration</h3>
            <p class="text-gray-600">Sync meetings and events with your existing calendar systems</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Document Management</h3>
            <p class="text-gray-600">Store and organize contracts, agreements, and partnership documents</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Smart Alerts</h3>
            <p class="text-gray-600">Intelligent notifications for important relationship milestones</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Strengthen Your Employer Partnerships Today
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          Join leading career services teams using Alumate to build lasting employer relationships and drive student success.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
            Schedule Your Demo
          </button>
          <button class="border-2 border-gray-300 hover:border-blue-500 text-gray-700 hover:text-blue-600 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300">
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
