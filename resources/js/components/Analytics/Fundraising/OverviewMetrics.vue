<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <MetricCard
      title="Total Raised"
      :value="formatCurrency(metrics.total_raised)"
      icon="currency-dollar"
      color="green"
      :trend="calculateTrend('total_raised')"
    />
    
    <MetricCard
      title="Total Donations"
      :value="formatNumber(metrics.total_donations)"
      icon="gift"
      color="blue"
      :trend="calculateTrend('total_donations')"
    />
    
    <MetricCard
      title="Unique Donors"
      :value="formatNumber(metrics.unique_donors)"
      icon="users"
      color="purple"
      :trend="calculateTrend('unique_donors')"
    />
    
    <MetricCard
      title="Average Gift"
      :value="formatCurrency(metrics.average_gift)"
      icon="chart-bar"
      color="orange"
      :trend="calculateTrend('average_gift')"
    />
    
    <MetricCard
      title="Active Campaigns"
      :value="formatNumber(metrics.active_campaigns)"
      icon="megaphone"
      color="indigo"
      :trend="calculateTrend('active_campaigns')"
    />
  </div>
</template>

<script setup>
import MetricCard from '@/Components/Analytics/MetricCard.vue'

const props = defineProps({
  metrics: {
    type: Object,
    required: true
  },
  previousMetrics: {
    type: Object,
    default: null
  }
})

const formatCurrency = (value) => {
  if (!value) return '$0'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

const formatNumber = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('en-US').format(value)
}

const calculateTrend = (metric) => {
  if (!props.previousMetrics || !props.previousMetrics[metric] || !props.metrics[metric]) {
    return null
  }
  
  const current = props.metrics[metric]
  const previous = props.previousMetrics[metric]
  
  if (previous === 0) return null
  
  const change = ((current - previous) / previous) * 100
  
  return {
    value: Math.abs(change).toFixed(1),
    direction: change >= 0 ? 'up' : 'down',
    positive: change >= 0
  }
}
</script>