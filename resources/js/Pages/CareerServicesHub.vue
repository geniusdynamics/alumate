<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-emerald-100">
    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-teal-900 to-cyan-900 text-white">
      <div class="absolute inset-0 bg-black/20"></div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-emerald-200 to-cyan-200 bg-clip-text text-transparent">
            Career Services Hub
          </h1>
          <p class="text-xl md:text-2xl mb-8 text-emerald-100 max-w-4xl mx-auto">
            Streamline career services operations with comprehensive tools for events, job postings, appointments, and on-campus interviewing.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
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
            Comprehensive Career Services Management
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Everything you need to support student and alumni career development in one integrated platform.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Event Management -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Event Management</h3>
            <p class="text-gray-600 mb-6">
              Create, promote, and manage career fairs, workshops, networking events, and employer information sessions.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3"></span>
                Career fair coordination
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3"></span>
                Workshop scheduling
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-3"></span>
                Registration management
              </li>
            </ul>
          </div>

          <!-- Job Board & Listings -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Job Board & Listings</h3>
            <p class="text-gray-600 mb-6">
              Centralized job posting platform with advanced search, filtering, and application tracking capabilities.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Advanced job search
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Application tracking
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                Employer posting tools
              </li>
            </ul>
          </div>

          <!-- Appointment Scheduling -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Appointment Scheduling</h3>
            <p class="text-gray-600 mb-6">
              Streamlined booking system for career counseling, resume reviews, and mock interviews with automated reminders.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Online booking system
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Automated reminders
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-purple-500 rounded-full mr-3"></span>
                Calendar integration
              </li>
            </ul>
          </div>

          <!-- On-Campus Interviewing -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">On-Campus Interviewing (OCI)</h3>
            <p class="text-gray-600 mb-6">
              Complete OCI management system with room scheduling, interview coordination, and employer relationship tools.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Interview scheduling
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Room management
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                Employer coordination
              </li>
            </ul>
          </div>

          <!-- Student Tracking -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-teal-500 to-cyan-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Student Tracking</h3>
            <p class="text-gray-600 mb-6">
              Comprehensive student engagement tracking with career development milestones and progress monitoring.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-teal-500 rounded-full mr-3"></span>
                Engagement analytics
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-teal-500 rounded-full mr-3"></span>
                Progress tracking
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-teal-500 rounded-full mr-3"></span>
                Outcome reporting
              </li>
            </ul>
          </div>

          <!-- Resource Library -->
          <div class="bg-white rounded-2xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Resource Library</h3>
            <p class="text-gray-600 mb-6">
              Centralized repository of career resources, templates, guides, and industry insights for students and staff.
            </p>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Resume templates
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Career guides
              </li>
              <li class="flex items-center">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span>
                Industry insights
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-gradient-to-r from-emerald-900 to-teal-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold mb-4">Transform Your Career Services</h2>
          <p class="text-xl text-emerald-100">Streamline operations and improve student outcomes with our comprehensive platform</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="text-5xl font-bold text-emerald-300 mb-2">75%</div>
            <div class="text-lg text-emerald-100">Reduction in Admin Time</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-teal-300 mb-2">90%</div>
            <div class="text-lg text-emerald-100">Student Satisfaction Rate</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-cyan-300 mb-2">2x</div>
            <div class="text-lg text-emerald-100">More Employer Partnerships</div>
          </div>
          <div class="text-center">
            <div class="text-5xl font-bold text-green-300 mb-2">95%</div>
            <div class="text-lg text-emerald-100">Event Attendance Rate</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Integration Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Seamless Integration
          </h2>
          <p class="text-xl text-gray-600">
            Connect with your existing systems and popular career services tools
          </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-white rounded-xl p-6 shadow-lg text-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Student Information Systems</h3>
            <p class="text-gray-600">Sync with Banner, PeopleSoft, and other SIS platforms</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg text-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Calendar Systems</h3>
            <p class="text-gray-600">Integrate with Google Calendar, Outlook, and more</p>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-lg text-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Communication Tools</h3>
            <p class="text-gray-600">Connect with email platforms and messaging systems</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Ready to Revolutionize Your Career Services?
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          Join leading institutions using Alumate to streamline career services and improve student outcomes.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-xl">
            Schedule Your Demo
          </button>
          <button class="border-2 border-gray-300 hover:border-emerald-500 text-gray-700 hover:text-emerald-600 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300">
            Download Brochure
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

defineOptions({
  layout: 'AppLayout'
});
</script>