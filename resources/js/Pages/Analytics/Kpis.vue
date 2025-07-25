<template>
    <AppLayout title="Key Performance Indicators">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Key Performance Indicators
                </h2>
                <div class="flex items-center space-x-4">
                    <select 
                        v-model="selectedCategory" 
                        @change="filterByCategory"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category" :value="category">
                            {{ category.charAt(0).toUpperCase() + category.slice(1) }}
                        </option>
                    </select>
                    <button
                        @click="refreshKpis"
                        :disabled="loading"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                    >
                        {{ loading ? 'Refreshing...' : 'Refresh KPIs' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- KPI Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="kpi in kpis" :key="kpi.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <!-- KPI Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ kpi.name }}</h3>
                                    <p class="text-sm text-gray-500">{{ kpi.description }}</p>
                                </div>
                                <span :class="getStatusBadgeClass(kpi.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ kpi.status }}
                                </span>
                            </div>

                            <!-- Current Value -->
                            <div class="mb-4">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold text-gray-900">{{ kpi.formatted_value || kpi.current_value || 'N/A' }}</span>
                                    <span v-if="kpi.target_value" class="ml-2 text-sm text-gray-500">
                                        / {{ formatTargetValue(kpi) }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    Category: {{ kpi.category }}
                                </div>
                            </div>

                            <!-- Progress Bar (if target exists) -->
                            <div v-if="kpi.target_value" class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Progress to Target</span>
                                    <span>{{ getProgressPercentage(kpi) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        :class="getProgressBarColor(kpi)"
                                        class="h-2 rounded-full transition-all duration-300"
                                        :style="{ width: Math.min(100, getProgressPercentage(kpi)) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Trend Chart -->
                            <div v-if="kpi.trend_data && kpi.trend_data.length > 0" class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">30-Day Trend</h4>
                                <div class="h-20">
                                    <AnalyticsChart
                                        :data="getTrendChartData(kpi)"
                                        type="line"
                                        :height="80"
                                        :options="getTrendChartOptions()"
                                    />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-between items-center">
                                <button
                                    @click="viewKpiDetails(kpi)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    View Details
                                </button>
                                <button
                                    @click="calculateKpi(kpi)"
                                    :disabled="calculatingKpis.includes(kpi.id)"
                                    class="text-green-600 hover:text-green-900 text-sm font-medium disabled:opacity-50"
                                >
                                    {{ calculatingKpis.includes(kpi.id) ? 'Calculating...' : 'Recalculate' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="kpis.length === 0" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No KPIs found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ selectedCategory ? 'No KPIs found for the selected category.' : 'No KPIs have been configured yet.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Details Modal -->
        <Modal :show="showDetailsModal" @close="showDetailsModal = false" max-width="4xl">
            <div class="p-6" v-if="selectedKpi">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ selectedKpi.name }} Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- KPI Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Information</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">{{ selectedKpi.description }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Category</dt>
                                <dd class="text-sm text-gray-900">{{ selectedKpi.category }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Current Value</dt>
                                <dd class="text-sm text-gray-900">{{ selectedKpi.formatted_value || selectedKpi.current_value || 'N/A' }}</dd>
                            </div>
                            <div v-if="selectedKpi.target_value">
                                <dt class="text-sm text-gray-500">Target Value</dt>
                                <dd class="text-sm text-gray-900">{{ formatTargetValue(selectedKpi) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Status</dt>
                                <dd>
                                    <span :class="getStatusBadgeClass(selectedKpi.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ selectedKpi.status }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Trend Chart -->
                    <div v-if="selectedKpi.trend_data && selectedKpi.trend_data.length > 0">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Historical Trend</h4>
                        <div class="h-64">
                            <AnalyticsChart
                                :data="getTrendChartData(selectedKpi)"
                                type="line"
                                :height="256"
                                :options="getDetailedTrendChartOptions()"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="showDetailsModal = false"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-md text-sm font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import AnalyticsChart from '@/Components/AnalyticsChart.vue'

const props = defineProps({
    kpis: Array,
    categories: Array,
    selectedCategory: String,
})

const loading = ref(false)
const calculatingKpis = ref([])
const showDetailsModal = ref(false)
const selectedKpi = ref(null)

const filterByCategory = () => {
    router.get(route('analytics.kpis'), { 
        category: selectedCategory.value 
    }, {
        preserveState: true
    })
}

const refreshKpis = () => {
    loading.value = true
    router.reload({
        onFinish: () => loading.value = false
    })
}

const calculateKpi = async (kpi) => {
    calculatingKpis.value.push(kpi.id)
    
    try {
        const response = await fetch(route('analytics.calculate-kpis'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Refresh the page to show updated values
            router.reload()
        } else {
            alert('Failed to calculate KPI: ' + result.message)
        }
    } catch (error) {
        alert('Error calculating KPI: ' + error.message)
    } finally {
        calculatingKpis.value = calculatingKpis.value.filter(id => id !== kpi.id)
    }
}

const viewKpiDetails = (kpi) => {
    selectedKpi.value = kpi
    showDetailsModal.value = true
}

const getStatusBadgeClass = (status) => {
    const classes = {
        good: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        poor: 'bg-red-100 text-red-800',
        unknown: 'bg-gray-100 text-gray-800',
    }
    return classes[status] || classes.unknown
}

const formatTargetValue = (kpi) => {
    if (!kpi.target_value) return 'N/A'
    
    // Format based on the KPI type or use a default format
    return kpi.target_value.toString()
}

const getProgressPercentage = (kpi) => {
    if (!kpi.target_value || !kpi.current_value) return 0
    
    return Math.round((kpi.current_value / kpi.target_value) * 100)
}

const getProgressBarColor = (kpi) => {
    const percentage = getProgressPercentage(kpi)
    
    if (percentage >= 100) return 'bg-green-500'
    if (percentage >= 80) return 'bg-blue-500'
    if (percentage >= 60) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getTrendChartData = (kpi) => {
    if (!kpi.trend_data || kpi.trend_data.length === 0) {
        return { labels: [], datasets: [] }
    }
    
    const labels = kpi.trend_data.map(item => {
        const date = new Date(item.measurement_date)
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    })
    
    const data = kpi.trend_data.map(item => parseFloat(item.value))
    
    return {
        labels,
        datasets: [{
            label: kpi.name,
            data,
            borderColor: kpi.status_color === 'green' ? '#10B981' : 
                        kpi.status_color === 'yellow' ? '#F59E0B' : 
                        kpi.status_color === 'red' ? '#EF4444' : '#6B7280',
            backgroundColor: kpi.status_color === 'green' ? 'rgba(16, 185, 129, 0.1)' : 
                           kpi.status_color === 'yellow' ? 'rgba(245, 158, 11, 0.1)' : 
                           kpi.status_color === 'red' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(107, 114, 128, 0.1)',
            fill: true,
            tension: 0.4,
        }]
    }
}

const getTrendChartOptions = () => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            x: {
                display: false,
                grid: {
                    display: false
                }
            },
            y: {
                display: false,
                grid: {
                    display: false
                }
            }
        },
        elements: {
            point: {
                radius: 0,
                hoverRadius: 4
            }
        }
    }
}

const getDetailedTrendChartOptions = () => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false
                }
            },
            y: {
                display: true,
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        },
        elements: {
            point: {
                radius: 3,
                hoverRadius: 6
            }
        }
    }
}
</script>
</template>