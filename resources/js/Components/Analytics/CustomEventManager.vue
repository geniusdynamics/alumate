<template>
    <div class="custom-event-manager" role="region" aria-label="Custom event management dashboard">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading custom events...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else class="custom-event-manager-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Custom Event Manager</h1>
                    <p class="text-sm text-gray-600">
                        Define, track, and analyze custom events for advanced analytics
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="trackSampleEvent"
                        class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        aria-label="Track sample custom event"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Track Sample Event
                    </button>
                    <button
                        @click="refreshData"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isLoading"
                        aria-label="Refresh custom event data"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <nav class="flex space-x-1" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        :aria-current="activeTab === tab.id ? 'page' : undefined"
                    >
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- List View -->
                <div v-if="activeTab === 'list'" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Event Definitions</h2>
                        <button
                            @click="activeTab = 'create'"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                        >
                            Create New Event
                        </button>
                    </div>

                    <!-- Search and Filter -->
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-4">
                            <div class="flex-1">
                                <label for="search-events" class="sr-only">Search events</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        id="search-events"
                                        v-model="searchQuery"
                                        type="text"
                                        class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Search events..."
                                    />
                                </div>
                            </div>
                            <div>
                                <label for="status-filter" class="sr-only">Filter by status</label>
                                <select
                                    id="status-filter"
                                    v-model="statusFilter"
                                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <div v-for="definition in filteredDefinitions" :key="definition.id"
                             class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ definition.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ definition.description }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-if="definition.category"
                                              :class="getCategoryBadgeClass(definition.category)"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ definition.category }}
                                        </span>
                                        <span v-for="param in definition.parameters_json"
                                              :key="param.name"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ param.name }}: {{ param.type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="definition.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ definition.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Aggregates -->
                            <div v-if="definition.aggregates" class="mt-4 grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-blue-600">{{ definition.aggregates.total_events || 0 }}</div>
                                    <div class="text-sm text-gray-600">Total Events</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ definition.aggregates.unique_users || 0 }}</div>
                                    <div class="text-sm text-gray-600">Unique Users</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-purple-600">{{ definition.aggregates.time_series?.length || 0 }}</div>
                                    <div class="text-sm text-gray-600">Data Points</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex items-center space-x-4">
                                <button
                                    @click="viewAnalytics(definition.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    View Analytics
                                </button>
                                <button
                                    @click="viewFlow(definition.id)"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm"
                                >
                                    View Flow
                                </button>
                                <button
                                    @click="viewOptimization(definition.id)"
                                    class="text-green-600 hover:text-green-800 text-sm"
                                >
                                    Optimization
                                </button>
                                <button
                                    @click="editDefinition(definition)"
                                    class="text-gray-600 hover:text-gray-800 text-sm"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteDefinition(definition.id)"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredDefinitions.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No custom events found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new custom event.</p>
                            <div class="mt-6">
                                <button
                                    @click="activeTab = 'create'"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                >
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Create Event
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create View -->
                <div v-if="activeTab === 'create'" class="max-w-2xl">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Event Definition</h2>
                    <form @submit.prevent="createDefinition" class="space-y-4">
                        <div>
                            <label for="event-name" class="block text-sm font-medium text-gray-700">Event Name *</label>
                            <input
                                id="event-name"
                                v-model="newDefinition.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="e.g., purchase_completed"
                            />
                        </div>

                        <div>
                            <label for="event-category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select
                                id="event-category"
                                v-model="newDefinition.category"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="conversion">Conversion</option>
                                <option value="engagement">Engagement</option>
                                <option value="error">Error</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <div>
                            <label for="event-description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                id="event-description"
                                v-model="newDefinition.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Describe what this event tracks..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Parameters</label>
                            <div v-for="(param, index) in newDefinition.parameters_json" :key="index"
                                 class="flex items-center space-x-2 mb-2">
                                <input
                                    v-model="param.name"
                                    type="text"
                                    placeholder="Parameter name"
                                    required
                                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <select
                                    v-model="param.type"
                                    required
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="string">String</option>
                                    <option value="number">Number</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                                <button
                                    @click="removeParameter(index)"
                                    type="button"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                @click="addParameter"
                                type="button"
                                class="mt-2 text-blue-600 hover:text-blue-800 text-sm"
                            >
                                + Add Parameter
                            </button>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button
                                @click="activeTab = 'list'"
                                type="button"
                                class="rounded bg-gray-600 px-4 py-2 text-sm text-white hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                                :disabled="isSubmitting"
                            >
                                {{ isSubmitting ? 'Creating...' : 'Create Event' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Analytics View -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Event Analytics</h2>
                        <div class="flex items-center space-x-4">
                            <select
                                v-model="selectedDefinitionId"
                                @change="loadAnalytics"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">Select Event Definition</option>
                                <option v-for="definition in definitions" :key="definition.id" :value="definition.id">
                                    {{ definition.name }}
                                </option>
                            </select>
                            <button
                                @click="loadAnalytics"
                                class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                                :disabled="!selectedDefinitionId"
                            >
                                Refresh Analytics
                            </button>
                        </div>
                    </div>

                    <div v-if="analyticsData" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Summary Cards -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Events:</span>
                                    <span class="font-semibold">{{ analyticsData.total_events?.toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Unique Users:</span>
                                    <span class="font-semibold">{{ analyticsData.unique_users?.toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between" v-if="analyticsData.avg_events_per_user">
                                    <span class="text-gray-600">Avg per User:</span>
                                    <span class="font-semibold">{{ analyticsData.avg_events_per_user.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Aggregates Chart -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aggregates</h3>
                            <div class="h-64">
                                <canvas ref="aggregatesChartRef" aria-label="Event aggregates chart" role="img"></canvas>
                            </div>
                        </div>

                        <!-- Time Series -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4 lg:col-span-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Timeline</h3>
                            <div class="h-64">
                                <canvas ref="timelineChartRef" aria-label="Event timeline chart" role="img"></canvas>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="selectedDefinitionId" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No analytics data available</h3>
                        <p class="mt-1 text-sm text-gray-500">Try tracking some events first.</p>
                    </div>
                </div>

                <!-- Event Flow View -->
                <div v-if="activeTab === 'flow'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Event Flow Visualization</h2>
                        <select
                            v-model="selectedDefinitionId"
                            @change="loadFlowData"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Event Definition</option>
                            <option v-for="definition in definitions" :key="definition.id" :value="definition.id">
                                {{ definition.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="flowData" class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="h-96 flex items-center justify-center bg-gray-50 rounded">
                            <p class="text-gray-600">Flow visualization for {{ flowData.definition_name }}</p>
                            <!-- Placeholder for Sankey diagram or flow chart -->
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">{{ flowData.nodes?.length || 0 }} nodes, {{ flowData.edges?.length || 0 }} connections</p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="selectedDefinitionId" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No flow data available</h3>
                        <p class="mt-1 text-sm text-gray-500">Try tracking more events to generate flow data.</p>
                    </div>
                </div>

                <!-- Optimization View -->
                <div v-if="activeTab === 'optimization'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Optimization Recommendations</h2>
                            <p class="text-sm text-gray-600">AI-powered suggestions to improve your event tracking</p>
                        </div>
                        <select
                            v-model="selectedDefinitionId"
                            @change="loadOptimizationData"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Event Definition</option>
                            <option v-for="definition in definitions" :key="definition.id" :value="definition.id">
                                {{ definition.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isLoadingOptimization" class="flex items-center justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                        <span class="ml-2 text-gray-600">Loading optimization suggestions...</span>
                    </div>

                    <!-- Optimization Suggestions -->
                    <div v-else-if="optimizationData && optimizationData.suggestions?.length > 0" class="space-y-4">
                        <div v-for="(suggestion, index) in optimizationData.suggestions" :key="index"
                             :class="[
                                 'rounded-lg border p-4',
                                 suggestion.priority === 'high' ? 'border-red-200 bg-red-50' :
                                 suggestion.priority === 'medium' ? 'border-yellow-200 bg-yellow-50' :
                                 'border-blue-200 bg-blue-50'
                             ]">
                            <div class="flex items-start">
                                <div :class="[
                                    'mr-3 flex-shrink-0 rounded-full p-1',
                                    suggestion.priority === 'high' ? 'bg-red-100' :
                                    suggestion.priority === 'medium' ? 'bg-yellow-100' :
                                    'bg-blue-100'
                                ]">
                                    <svg v-if="suggestion.type === 'improvement'" class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <svg v-else-if="suggestion.type === 'insight'" class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900">{{ suggestion.title }}</h4>
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            suggestion.priority === 'high' ? 'bg-red-100 text-red-800' :
                                            suggestion.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-blue-100 text-blue-800'
                                        ]">
                                            {{ suggestion.priority }} priority
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">{{ suggestion.description }}</p>
                                    <div v-if="suggestion.action" class="mt-3">
                                        <button
                                            @click="applyOptimization(suggestion)"
                                            class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ suggestion.action }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-gray-500 text-right">
                            Generated at: {{ optimizationData.generated_at ? new Date(optimizationData.generated_at).toLocaleString() : 'N/A' }}
                        </div>
                    </div>

                    <div v-else-if="selectedDefinitionId && !isLoadingOptimization" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No optimization suggestions available</h3>
                        <p class="mt-1 text-sm text-gray-500">Track more events to get AI-powered optimization recommendations.</p>
                    </div>

                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Select an event</h3>
                        <p class="mt-1 text-sm text-gray-500">Choose an event definition to view optimization recommendations.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useCustomEventStore } from '../../Stores/useCustomEventStore';
import { useCustomEvent } from '../../Composables/useCustomEvent';
import axios from 'axios';
import type { CustomEventDefinition, CustomEventAnalytics, OptimizationSuggestion } from '../../types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    initialTab?: 'list' | 'create' | 'analytics' | 'flow' | 'optimization';
}>(), {
    initialTab: 'list',
});

// Store and composable
const customEventStore = useCustomEventStore();
const { validateEventData } = useCustomEvent();

// Reactive state
const activeTab = ref<'list' | 'create' | 'analytics' | 'flow' | 'optimization'>(props.initialTab);
const selectedDefinitionId = ref<number | ''>('');
const selectedDefinition = ref<CustomEventDefinition | null>(null);
const newDefinition = ref({
    name: '',
    description: '',
    category: 'custom' as 'conversion' | 'engagement' | 'error' | 'custom',
    parameters_json: [{ name: '', type: 'string' as 'string' | 'number' | 'boolean' }],
});
const isSubmitting = ref(false);
const searchQuery = ref('');
const statusFilter = ref('');

// Optimization data
const optimizationData = ref<{
    definition_id: number;
    suggestions: OptimizationSuggestion[];
    generated_at: string;
} | null>(null);
const isLoadingOptimization = ref(false);
const flowData = ref<any>(null);

// Chart refs
const aggregatesChartRef = ref<HTMLCanvasElement>();
const timelineChartRef = ref<HTMLCanvasElement>();
let aggregatesChart: Chart | null = null;
let timelineChart: Chart | null = null;

// Tabs configuration
interface TabItem {
    id: 'list' | 'create' | 'analytics' | 'flow' | 'optimization';
    name: string;
}

const tabs: TabItem[] = [
    { id: 'list', name: 'Event Definitions' },
    { id: 'create', name: 'Create Event' },
    { id: 'analytics', name: 'Analytics' },
    { id: 'flow', name: 'Event Flow' },
    { id: 'optimization', name: 'Optimization' },
];

// Computed properties
const isLoading = computed(() => customEventStore.isLoading);
const error = computed(() => customEventStore.error);
const definitions = computed(() => customEventStore.definitions);
const analyticsData = computed(() => customEventStore.analyticsData);

const filteredDefinitions = computed(() => {
    let filtered = [...definitions.value];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(def =>
            def.name.toLowerCase().includes(query) ||
            def.description?.toLowerCase().includes(query)
        );
    }

    // Status filter
    if (statusFilter.value) {
        filtered = filtered.filter(def => def.status === statusFilter.value);
    }

    return filtered;
});

// Methods
const addParameter = () => {
    newDefinition.value.parameters_json.push({ name: '', type: 'string' });
};

const removeParameter = (index: number) => {
    if (newDefinition.value.parameters_json.length > 1) {
        newDefinition.value.parameters_json.splice(index, 1);
    }
};

const createDefinition = async () => {
    try {
        isSubmitting.value = true;

        // Validate parameters
        const validParams = newDefinition.value.parameters_json.filter(p => p.name.trim());
        if (validParams.length === 0) {
            throw new Error('At least one parameter is required');
        }

        await customEventStore.defineEvent({
            ...newDefinition.value,
            parameters_json: validParams,
        });

        // Reset form
        newDefinition.value = {
            name: '',
            description: '',
            category: 'custom',
            parameters_json: [{ name: '', type: 'string' }],
        };

        activeTab.value = 'list';
        await refreshData();
    } catch (err: any) {
        console.error('Failed to create definition:', err);
    } finally {
        isSubmitting.value = false;
    }
};

const viewAnalytics = (definitionId: number) => {
    selectedDefinitionId.value = definitionId;
    activeTab.value = 'analytics';
    loadAnalytics();
};

const viewFlow = (definitionId: number) => {
    selectedDefinitionId.value = definitionId;
    activeTab.value = 'flow';
    loadFlowData();
};

const viewOptimization = (definitionId: number) => {
    selectedDefinitionId.value = definitionId;
    activeTab.value = 'optimization';
    loadOptimizationData();
};

const editDefinition = (definition: CustomEventDefinition) => {
    selectedDefinition.value = definition;
    newDefinition.value = {
        name: definition.name,
        description: definition.description,
        category: definition.category || 'custom',
        parameters_json: [...definition.parameters_json],
    };
    activeTab.value = 'create';
};

const deleteDefinition = async (definitionId: number) => {
    if (confirm('Are you sure you want to delete this event definition?')) {
        try {
            await axios.delete(`/api/analytics/custom-events/definitions/${definitionId}`);
            await refreshData();
        } catch (err) {
            console.error('Failed to delete definition:', err);
        }
    }
};

const loadAnalytics = async () => {
    if (selectedDefinitionId.value) {
        await customEventStore.loadAnalytics(selectedDefinitionId.value);
        await nextTick();
        updateCharts();
    }
};

const loadFlowData = async () => {
    if (selectedDefinitionId.value) {
        try {
            const response = await axios.get(`/api/analytics/custom-events/${selectedDefinitionId.value}/behavior-flow`);
            if (response.data.success) {
                flowData.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to load flow data:', err);
            flowData.value = null;
        }
    }
};

const loadOptimizationData = async () => {
    if (!selectedDefinitionId.value) {
        optimizationData.value = null;
        return;
    }

    isLoadingOptimization.value = true;
    try {
        const response = await axios.get(`/api/analytics/custom-events/${selectedDefinitionId.value}/optimization`);
        if (response.data.success) {
            optimizationData.value = response.data.data;
        }
    } catch (err) {
        console.error('Failed to load optimization data:', err);
        optimizationData.value = null;
    } finally {
        isLoadingOptimization.value = false;
    }
};

const trackSampleEvent = async () => {
    if (definitions.value.length === 0) {
        // TODO-toast: alert('Please create an event definition first');
        return;
    }

    const sampleEvent = {
        definition_id: definitions.value[0]?.id,
        user_id: 1,
        data_json: {
            sample_param: 'test_value',
            count: Math.floor(Math.random() * 100),
        },
    };

    try {
        await customEventStore.trackEvent(sampleEvent);
        await refreshData();
    } catch (err) {
        console.error('Failed to track sample event:', err);
    }
};

const applyOptimization = (suggestion: OptimizationSuggestion) => {
    // Handle optimization action
    logger.log('Applying optimization:', suggestion);
    // TODO-toast: alert(`Applying: ${suggestion.action}`);
};

const updateCharts = () => {
    updateAggregatesChart();
    updateTimelineChart();
};

const updateAggregatesChart = () => {
    if (!aggregatesChartRef.value || !analyticsData.value?.aggregates) return;

    const ctx = aggregatesChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (aggregatesChart) {
        aggregatesChart.destroy();
    }

    const aggregates = analyticsData.value.aggregates;
    const labels = Object.keys(aggregates);
    const data = Object.values(aggregates).map((agg: any) => agg?.count || 0);

    aggregatesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Event Count',
                data,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
};

const updateTimelineChart = () => {
    if (!timelineChartRef.value || !analyticsData.value?.time_series) return;

    const ctx = timelineChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (timelineChart) {
        timelineChart.destroy();
    }

    const timeSeries = analyticsData.value.time_series;
    const labels = timeSeries.map((point: any) => point.date);
    const data = timeSeries.map((point: any) => point.count);

    timelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Events per Day',
                data,
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
};

const refreshData = async () => {
    try {
        await customEventStore.loadDefinitions();
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

const getCategoryBadgeClass = (category: string) => {
    const classes: Record<string, string> = {
        conversion: 'bg-blue-100 text-blue-800',
        engagement: 'bg-green-100 text-green-800',
        error: 'bg-red-100 text-red-800',
        custom: 'bg-purple-100 text-purple-800',
    };
    return classes[category] || 'bg-gray-100 text-gray-800';
};

// Watchers
watch(() => customEventStore.definitions, () => {
    updateCharts();
}, { deep: true });

// Lifecycle
onMounted(async () => {
    await refreshData();
});
</script>

<style scoped>
.custom-event-manager {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.custom-event-manager button:focus,
.custom-event-manager select:focus,
.custom-event-manager input:focus,
.custom-event-manager textarea:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Tab styles */
.tab-content {
    @apply min-h-96;
}

/* Chart responsive design */
@media (max-width: 768px) {
    .custom-event-manager-container {
        @apply px-2;
    }

    .grid-cols-1.lg\:grid-cols-2 {
        @apply grid-cols-1;
    }
}
</style>
