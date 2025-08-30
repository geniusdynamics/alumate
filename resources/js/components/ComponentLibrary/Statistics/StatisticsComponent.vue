<template>
  <StatisticsBase
    :statistics="processedStatistics"
    :config="baseConfig"
    :loading="isLoading"
    :error="hasErrors ? 'Failed to load some statistics data' : null"
    @data-load="handleDataLoad"
    @retry="handleRetry"
    @visibility-change="handleVisibilityChange"
    @animation-complete="handleAnimationComplete"
  >
    <template #default="{ statistics, isVisible, animate }">
      <!-- Counter Grid -->
      <div 
        v-if="config.displayType === 'counters' || config.displayType === 'mixed'"
        :class="[
          'statistics-counters',
          `grid-cols-${getGridColumns()}`
        ]"
      >
        <AnimatedCounter
          v-for="stat in counterStatistics"
          :key="stat.id"
          :value="stat.value"
          :label="stat.label"
          :description="stat.description"
          :prefix="stat.prefix"
          :suffix="stat.suffix"
          :format="stat.format"
          :size="config.counterSize"
          :theme="config.theme"
          :color="stat.color"
          :icon="stat.icon"
          :trend="stat.trend"
          :animate="animate && config.animation.enabled"
          :animation-duration="config.animation.duration"
          :animation-delay="config.animation.delay"
          :loading="isStatisticLoading(stat.id)"
          :error="getStatisticError(stat.id)"
          :respect-reduced-motion="config.accessibility.respectReducedMotion"
          @animation-complete="() => handleCounterComplete(stat)"
        />
      </div>

      <!-- Progress Bars -->
      <div 
        v-if="config.displayType === 'progress' || config.displayType === 'mixed'"
        class="statistics-progress"
      >
        <ProgressBar
          v-for="stat in progressStatistics"
          :key="stat.id"
          :value="typeof stat.value === 'number' ? stat.value : parseFloat(String(stat.value))"
          :target="stat.target"
          :label="stat.label"
          :description="stat.description"
          :size="config.progressSize"
          :theme="config.theme"
          :color="stat.color"
          :show-label="config.showLabels"
          :show-value="config.showValues"
          :show-target="config.showTargets"
          :format="stat.format"
          :animate="animate && config.animation.enabled"
          :animation-duration="config.animation.duration"
          :animation-delay="config.animation.delay"
          :segments="stat.segments"
          :milestones="stat.milestones"
          :loading="isStatisticLoading(stat.id)"
          :error="getStatisticError(stat.id)"
          :respect-reduced-motion="config.accessibility.respectReducedMotion"
          @animation-complete="() => handleProgressComplete(stat)"
          @milestone-reached="(milestone) => handleMilestoneReached(stat, milestone)"
        />
      </div>

      <!-- Comparison Charts -->
      <div 
        v-if="config.displayType === 'charts' || config.displayType === 'mixed'"
        class="statistics-charts"
      >
        <ComparisonChart
          v-for="chart in chartData"
          :key="chart.id"
          :data="chart.data"
          :type="chart.type"
          :title="chart.title"
          :description="chart.description"
          :size="config.chartSize"
          :theme="config.theme"
          :format="chart.format"
          :show-legend="chart.showLegend"
          :legend="chart.legend"
          :animate="animate && config.animation.enabled"
          :animation-duration="config.animation.duration"
          :animation-stagger="config.animation.stagger"
          :data-source="chart.dataSource"
          :loading="isChartLoading(chart.id)"
          :error="getChartError(chart.id)"
          :respect-reduced-motion="config.accessibility.respectReducedMotion"
          @retry="() => handleChartRetry(chart.id)"
          @animation-complete="() => handleChartComplete(chart)"
        />
      </div>
    </template>
  </StatisticsBase>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import StatisticsBase from './StatisticsBase.vue'
import AnimatedCounter from './AnimatedCounter.vue'
import ProgressBar from './ProgressBar.vue'
import ComparisonChart from './ComparisonChart.vue'
import { useStatisticsData, type StatisticsDataItem } from '@/composables/useStatisticsData'
import { useAnalytics } from '@/composables/useAnalytics'
import type { 
  StatisticItem, 
  StatisticsConfig 
} from './StatisticsBase.vue'
import type { 
  CounterTrend 
} from './AnimatedCounter.vue'
import type { 
  ProgressSegment, 
  ProgressMilestone 
} from './ProgressBar.vue'
import type { 
  ChartDataItem, 
  ChartLegendItem 
} from './ComparisonChart.vue'

export interface StatisticsComponentConfig extends Omit<StatisticsConfig, 'gridColumns'> {
  displayType: 'counters' | 'progress' | 'charts' | 'mixed'
  counterSize: 'sm' | 'md' | 'lg' | 'xl'
  progressSize: 'sm' | 'md' | 'lg'
  chartSize: 'sm' | 'md' | 'lg'
  showLabels: boolean
  showValues: boolean
  showTargets: boolean
  gridColumns: {
    desktop: number
    tablet: number
    mobile: number
  }
  realTimeData: {
    enabled: boolean
    sources: string[]
    refreshInterval: number
  }
}

export interface EnhancedStatisticItem extends StatisticItem {
  type?: 'counter' | 'progress' | 'chart'
  target?: number
  trend?: CounterTrend
  segments?: ProgressSegment[]
  milestones?: ProgressMilestone[]
  chartType?: 'bar' | 'before-after' | 'competitive'
  chartData?: ChartDataItem[]
  showLegend?: boolean
  legend?: ChartLegendItem[]
  dataSource?: string
}

export interface ChartConfig {
  id: string
  type: 'bar' | 'before-after' | 'competitive'
  title: string
  description?: string
  data: ChartDataItem[]
  format: 'number' | 'currency' | 'percentage'
  showLegend: boolean
  legend: ChartLegendItem[]
  dataSource?: string
}

interface Props {
  statistics: EnhancedStatisticItem[]
  config: StatisticsComponentConfig
  charts?: ChartConfig[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'data-updated': [data: StatisticsDataItem[]]
  'counter-complete': [statistic: EnhancedStatisticItem]
  'progress-complete': [statistic: EnhancedStatisticItem]
  'chart-complete': [chart: ChartConfig]
  'milestone-reached': [statistic: EnhancedStatisticItem, milestone: ProgressMilestone]
  'error': [error: string]
}>()

// Composables
const { trackEvent } = useAnalytics()
const {
  data: realTimeData,
  isLoading: dataLoading,
  hasErrors: dataHasErrors,
  addPlatformMetric,
  addCommonMetrics,
  refreshData,
  getDataBySource,
  getErrorBySource,
  isSourceLoading
} = useStatisticsData({
  autoRefresh: props.config.realTimeData.enabled,
  defaultRefreshInterval: props.config.realTimeData.refreshInterval,
  onError: (error) => {
    emit('error', error.message)
  }
})

// Refs
const isVisible = ref(false)

// Computed properties
const baseConfig = computed((): StatisticsConfig => ({
  title: props.config.title,
  description: props.config.description,
  layout: props.config.layout,
  theme: props.config.theme,
  spacing: props.config.spacing,
  gridColumns: props.config.gridColumns,
  animation: props.config.animation,
  accessibility: props.config.accessibility,
  dataRefresh: props.config.dataRefresh,
  errorHandling: props.config.errorHandling
}))

const processedStatistics = computed((): StatisticItem[] => {
  return props.statistics.map(stat => {
    // Try to get real-time data if available
    const realTimeValue = getRealTimeValue(stat.id)
    
    return {
      id: stat.id,
      value: realTimeValue !== undefined ? realTimeValue : stat.value,
      label: stat.label,
      suffix: stat.suffix,
      prefix: stat.prefix,
      format: stat.format,
      source: stat.source,
      apiEndpoint: stat.apiEndpoint,
      color: stat.color,
      icon: stat.icon,
      description: stat.description,
      trend: stat.trend
    }
  })
})

const counterStatistics = computed(() => {
  return processedStatistics.value.filter(stat => {
    const originalStat = props.statistics.find(s => s.id === stat.id)
    return !originalStat?.type || originalStat.type === 'counter'
  })
})

const progressStatistics = computed(() => {
  return props.statistics
    .filter(stat => stat.type === 'progress')
    .map(stat => {
      const realTimeValue = getRealTimeValue(stat.id)
      return {
        ...stat,
        value: realTimeValue !== undefined ? realTimeValue : stat.value
      }
    })
})

const chartData = computed((): ChartConfig[] => {
  const charts = props.charts || []
  
  // Add charts from statistics that have chartData
  const statisticCharts = props.statistics
    .filter(stat => stat.type === 'chart' && stat.chartData)
    .map(stat => ({
      id: stat.id,
      type: stat.chartType || 'bar' as const,
      title: stat.label,
      description: stat.description,
      data: stat.chartData || [],
      format: stat.format || 'number' as const,
      showLegend: stat.showLegend || false,
      legend: stat.legend || [],
      dataSource: stat.dataSource
    }))
  
  return [...charts, ...statisticCharts]
})

const isLoading = computed(() => {
  return dataLoading.value || props.statistics.some(stat => 
    stat.source === 'api' && isSourceLoading(stat.id)
  )
})

const hasErrors = computed(() => {
  return dataHasErrors.value || props.statistics.some(stat => 
    getErrorBySource(stat.id)
  )
})

// Methods
const getRealTimeValue = (statisticId: string): number | string | undefined => {
  const realTimeItem = realTimeData.value.find(item => item.id === statisticId)
  return realTimeItem?.value
}

const isStatisticLoading = (statisticId: string): boolean => {
  return isSourceLoading(statisticId)
}

const getStatisticError = (statisticId: string): string | null => {
  return getErrorBySource(statisticId) || null
}

const isChartLoading = (chartId: string): boolean => {
  return isSourceLoading(chartId)
}

const getChartError = (chartId: string): string | null => {
  return getErrorBySource(chartId) || null
}

const getGridColumns = (): string => {
  const { desktop, tablet, mobile } = props.config.gridColumns
  return `1 md:${tablet} lg:${desktop}`
}

// Event handlers
const handleDataLoad = () => {
  if (props.config.realTimeData.enabled) {
    refreshData()
  }
}

const handleRetry = () => {
  refreshData()
  trackEvent('statistics_retry', {
    component: 'StatisticsComponent',
    statistics_count: props.statistics.length
  })
}

const handleVisibilityChange = (visible: boolean) => {
  isVisible.value = visible
  
  if (visible) {
    trackEvent('statistics_view', {
      component: 'StatisticsComponent',
      display_type: props.config.displayType,
      statistics_count: props.statistics.length,
      charts_count: chartData.value.length
    })
  }
}

const handleAnimationComplete = () => {
  trackEvent('statistics_animation_complete', {
    component: 'StatisticsComponent',
    display_type: props.config.displayType
  })
}

const handleCounterComplete = (statistic: EnhancedStatisticItem) => {
  emit('counter-complete', statistic)
}

const handleProgressComplete = (statistic: EnhancedStatisticItem) => {
  emit('progress-complete', statistic)
}

const handleChartComplete = (chart: ChartConfig) => {
  emit('chart-complete', chart)
}

const handleMilestoneReached = (statistic: EnhancedStatisticItem, milestone: ProgressMilestone) => {
  emit('milestone-reached', statistic, milestone)
  
  trackEvent('statistics_milestone_reached', {
    component: 'StatisticsComponent',
    statistic_id: statistic.id,
    milestone_label: milestone.label,
    milestone_value: milestone.value
  })
}

const handleChartRetry = (chartId: string) => {
  refreshData(chartId)
}

// Lifecycle
onMounted(() => {
  // Setup real-time data sources if enabled
  if (props.config.realTimeData.enabled) {
    if (props.config.realTimeData.sources.length > 0) {
      props.config.realTimeData.sources.forEach(source => {
        addPlatformMetric(source)
      })
    } else {
      // Add common metrics if no specific sources are defined
      addCommonMetrics()
    }
  }
})

// Watchers
watch(() => realTimeData.value, (newData) => {
  emit('data-updated', newData)
}, { deep: true })
</script>

<style scoped>
.statistics-counters {
  @apply grid gap-6 mb-8;
}

.statistics-progress {
  @apply space-y-6 mb-8;
}

.statistics-charts {
  @apply space-y-8;
}

/* Responsive grid classes */
.grid-cols-1 {
  @apply grid-cols-1;
}

.grid-cols-2 {
  @apply md:grid-cols-2;
}

.grid-cols-3 {
  @apply md:grid-cols-2 lg:grid-cols-3;
}

.grid-cols-4 {
  @apply md:grid-cols-2 lg:grid-cols-4;
}

/* Mixed display type layout */
.statistics-component[data-display-type="mixed"] .statistics-counters {
  @apply mb-6;
}

.statistics-component[data-display-type="mixed"] .statistics-progress {
  @apply mb-6;
}
</style>