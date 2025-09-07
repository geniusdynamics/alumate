<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <h1 class="text-3xl font-bold text-gray-900">Alumni Map</h1>
          <p class="mt-4 text-lg text-gray-600">
            Discover where our alumni are making an impact around the world
          </p>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
          <div class="text-3xl font-bold text-blue-600">{{ stats.total_alumni }}</div>
          <div class="text-sm text-gray-600">Alumni Worldwide</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
          <div class="text-3xl font-bold text-green-600">{{ Object.keys(stats.by_country).length }}</div>
          <div class="text-sm text-gray-600">Countries</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
          <div class="text-3xl font-bold text-purple-600">{{ Object.keys(stats.by_industry).length }}</div>
          <div class="text-sm text-gray-600">Industries</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
          <div class="text-3xl font-bold text-orange-600">{{ schools.length }}</div>
          <div class="text-sm text-gray-600">Institutions</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Map -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Global Alumni Distribution</h2>
            <div class="h-96 bg-gray-100 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                </svg>
                <p class="text-gray-600">Interactive map will be displayed here</p>
                <p class="text-sm text-gray-500 mt-2">{{ alumni.length }} alumni locations shown</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Top Countries -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Countries</h3>
            <div class="space-y-3">
              <div
                v-for="(count, country) in Object.entries(stats.by_country).slice(0, 5)"
                :key="country"
                class="flex justify-between items-center"
              >
                <span class="text-sm text-gray-700">{{ country }}</span>
                <span class="text-sm font-medium text-gray-900">{{ count }}</span>
              </div>
            </div>
          </div>

          <!-- Top Industries -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Industries</h3>
            <div class="space-y-3">
              <div
                v-for="(count, industry) in Object.entries(stats.by_industry).slice(0, 5)"
                :key="industry"
                class="flex justify-between items-center"
              >
                <span class="text-sm text-gray-700">{{ industry }}</span>
                <span class="text-sm font-medium text-gray-900">{{ count }}</span>
              </div>
            </div>
          </div>

          <!-- Featured Alumni -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Alumni</h3>
            <div class="space-y-4">
              <div
                v-for="alumnus in alumni.slice(0, 3)"
                :key="alumnus.id"
                class="flex items-center space-x-3"
              >
                <img
                  :src="alumnus.avatar_url || '/default-avatar.png'"
                  :alt="alumnus.name"
                  class="w-10 h-10 rounded-full object-cover"
                >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ alumnus.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ alumnus.current_title }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ alumnus.location }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alumni List -->
      <div class="mt-8">
        <div class="bg-white rounded-lg shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Alumni Locations</h2>
          </div>
          <div class="divide-y divide-gray-200">
            <div
              v-for="alumnus in alumni"
              :key="alumnus.id"
              class="px-6 py-4 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <img
                    :src="alumnus.avatar_url || '/default-avatar.png'"
                    :alt="alumnus.name"
                    class="w-12 h-12 rounded-full object-cover"
                  >
                  <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ alumnus.name }}</h3>
                    <p class="text-sm text-gray-600">{{ alumnus.current_title }}</p>
                    <p class="text-sm text-gray-500">{{ alumnus.location }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm text-gray-600">{{ alumnus.current_company }}</p>
                  <Link
                    :href="route('login')"
                    class="text-sm text-blue-600 hover:text-blue-800"
                  >
                    Login to Connect
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-blue-600 py-12 mt-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Add Your Location</h2>
        <p class="text-xl text-blue-100 mb-8">
          Join our global alumni network and let others find you.
        </p>
        <div class="space-x-4">
          <Link
            :href="route('register')"
            class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
          >
            Join Now
          </Link>
          <Link
            :href="route('login')"
            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
          >
            Sign In
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { AlumniProfile, InstitutionOption } from '@/types'

interface Props {
  alumni: AlumniProfile[]
  stats: {
    total_alumni: number
    by_country: Record<string, number>
    by_region: Record<string, number>
    by_industry: Record<string, number>
  }
  schools: InstitutionOption[]
  industries: string[]
  countries: string[]
  graduationYears: number[]
  auth_required: boolean
}

const props = defineProps<Props>()
</script>