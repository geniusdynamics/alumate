<template>
    <div class="analytics-chart">
        <canvas ref="chartCanvas" :height="height"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    type: {
        type: String,
        default: 'line'
    },
    height: {
        type: Number,
        default: 400
    },
    options: {
        type: Object,
        default: () => ({})
    }
})

const chartCanvas = ref(null)
let chartInstance = null

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
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
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
    }
}

const getChartConfig = () => {
    const config = {
        type: props.type,
        data: props.data,
        options: {
            ...defaultOptions,
            ...props.options
        }
    }

    // Customize based on chart type
    if (props.type === 'line') {
        config.options.elements = {
            line: {
                tension: 0.4
            },
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }

    if (props.type === 'bar') {
        config.options.scales.x.grid = {
            display: false
        }
    }

    if (props.type === 'area') {
        config.type = 'line'
        if (config.data.datasets) {
            config.data.datasets.forEach(dataset => {
                dataset.fill = true
                dataset.backgroundColor = dataset.backgroundColor || 'rgba(99, 102, 241, 0.1)'
            })
        }
    }

    if (props.type === 'funnel') {
        config.type = 'bar'
        config.options.indexAxis = 'y'
        config.options.scales = {
            x: {
                beginAtZero: true
            },
            y: {
                grid: {
                    display: false
                }
            }
        }
    }

    return config
}

const createChart = () => {
    if (chartInstance) {
        chartInstance.destroy()
    }

    if (!chartCanvas.value || !props.data) {
        return
    }

    const ctx = chartCanvas.value.getContext('2d')
    chartInstance = new Chart(ctx, getChartConfig())
}

const updateChart = () => {
    if (!chartInstance || !props.data) {
        return
    }

    chartInstance.data = props.data
    chartInstance.update('active')
}

onMounted(() => {
    nextTick(() => {
        createChart()
    })
})

watch(() => props.data, () => {
    if (chartInstance) {
        updateChart()
    } else {
        createChart()
    }
}, { deep: true })

watch(() => props.type, () => {
    createChart()
})
</script>

<style scoped>
.analytics-chart {
    position: relative;
    width: 100%;
}
</style>
</template>