<template>
    <div class="event-flow-view" role="region" aria-label="Custom event behavior flow visualization">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Behavior Flow Visualization</h2>
                <p class="text-sm text-gray-600">
                    {{ selectedEvent ? `Flow analysis for ${selectedEvent}` : 'Select an event to view behavior flows' }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <button
                    @click="refreshFlow"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    :disabled="isLoading"
                >
                    <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <div v-if="!selectedEvent" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No event selected</h3>
            <p class="mt-1 text-sm text-gray-500">Select an event from the list to view its behavior flow.</p>
        </div>

        <div v-else-if="isLoading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading flow data...</span>
        </div>

        <div v-else-if="flowData && flowData.nodes.length > 0" class="space-y-6">
            <!-- Flow Controls -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <label for="depth-select" class="block text-sm font-medium text-gray-700">Analysis Depth</label>
                        <select
                            id="depth-select"
                            v-model="analysisDepth"
                            @change="updateFlow"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option :value="2">2 levels</option>
                            <option :value="3">3 levels</option>
                            <option :value="4">4 levels</option>
                            <option :value="5">5 levels</option>
                        </select>
                    </div>
                    <div>
                        <label for="layout-select" class="block text-sm font-medium text-gray-700">Layout</label>
                        <select
                            id="layout-select"
                            v-model="layoutType"
                            @change="updateVisualization"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="force">Force Directed</option>
                            <option value="hierarchical">Hierarchical</option>
                            <option value="circular">Circular</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input
                            id="show-labels"
                            type="checkbox"
                            v-model="showLabels"
                            @change="updateVisualization"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="show-labels" class="ml-2 text-sm text-gray-700">Show Labels</label>
                    </div>
                </div>
            </div>

            <!-- Flow Visualization -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Event Flow Graph</h3>
                    <div class="text-sm text-gray-500">
                        {{ flowData.nodes.length }} nodes, {{ flowData.links.length }} connections
                    </div>
                </div>
                <div class="relative h-96 w-full overflow-hidden rounded border bg-gray-50">
                    <svg
                        ref="svgRef"
                        class="h-full w-full"
                        :aria-label="`Behavior flow visualization for ${selectedEvent}`"
                        role="img"
                    ></svg>
                    <div v-if="isRendering" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
                        <div class="flex items-center">
                            <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-blue-600"></div>
                            <span class="ml-2 text-gray-600">Rendering...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow Statistics -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h4 class="text-sm font-medium text-gray-600">Total Users</h4>
                    <p class="text-2xl font-bold text-gray-900">{{ flowData.nodes[0]?.user_count || 0 }}</p>
                    <p class="text-xs text-gray-500">Started with this event</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h4 class="text-sm font-medium text-gray-600">Flow Paths</h4>
                    <p class="text-2xl font-bold text-gray-900">{{ flowData.links.length }}</p>
                    <p class="text-xs text-gray-500">Unique transitions</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h4 class="text-sm font-medium text-gray-600">Max Depth</h4>
                    <p class="text-2xl font-bold text-gray-900">{{ Math.max(...flowData.nodes.map(n => n.level)) }}</p>
                    <p class="text-xs text-gray-500">Levels analyzed</p>
                </div>
            </div>

            <!-- Flow Details Table -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Flow Details</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Event
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Users
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Events
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Conversion Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="node in sortedNodes" :key="node.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ node.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ node.level }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ node.user_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ node.event_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ node.conversion_rate }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No flow data available</h3>
            <p class="mt-1 text-sm text-gray-500">This event doesn't have enough data to generate a behavior flow.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/Utils/logger';
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import * as d3 from 'd3';
import type { BehaviorFlowData } from '../../../Types/analytics';

// Props
const props = defineProps<{
    flowData: BehaviorFlowData | null;
    selectedEvent: string | null;
}>();

// Emits
defineEmits<{
    'event-selected': [eventId: string];
}>();

// Reactive state
const isLoading = ref(false);
const isRendering = ref(false);
const analysisDepth = ref(3);
const layoutType = ref('force');
const showLabels = ref(true);
const svgRef = ref<SVGSVGElement>();

// Computed properties
const sortedNodes = computed(() => {
    if (!props.flowData) return [];
    return [...props.flowData.nodes].sort((a, b) => {
        if (a.level !== b.level) return a.level - b.level;
        return b.user_count - a.user_count;
    });
});

// Methods
const refreshFlow = async () => {
    isLoading.value = true;
    try {
        // This would trigger a refresh in the parent component
        logger.log('Refreshing flow data...');
    } finally {
        isLoading.value = false;
    }
};

const updateFlow = () => {
    // This would update the flow with new depth
    logger.log('Updating flow with depth:', analysisDepth.value);
};

const updateVisualization = () => {
    if (props.flowData) {
        renderFlowVisualization();
    }
};

const renderFlowVisualization = async () => {
    if (!svgRef.value || !props.flowData) return;

    isRendering.value = true;

    await nextTick();

    const svg = d3.select(svgRef.value);
    svg.selectAll('*').remove(); // Clear previous content

    const width = svgRef.value.clientWidth;
    const height = svgRef.value.clientHeight;

    const { nodes, links } = props.flowData;

    // Create force simulation
    const simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id((d: any) => d.id).distance(100))
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('collision', d3.forceCollide().radius(30));

    // Create links
    const link = svg.append('g')
        .attr('class', 'links')
        .selectAll('line')
        .data(links)
        .enter().append('line')
        .attr('stroke', '#999')
        .attr('stroke-opacity', 0.6)
        .attr('stroke-width', (d: any) => Math.sqrt(d.value) * 2);

    // Create nodes
    const node = svg.append('g')
        .attr('class', 'nodes')
        .selectAll('circle')
        .data(nodes)
        .enter().append('circle')
        .attr('r', (d: any) => Math.sqrt(d.user_count) + 10)
        .attr('fill', (d: any) => d.level === 0 ? '#3B82F6' : '#10B981')
        .attr('stroke', '#fff')
        .attr('stroke-width', 2)
        .call(d3.drag()
            .on('start', (event, d: any) => {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x;
                d.fy = d.y;
            })
            .on('drag', (event, d: any) => {
                d.fx = event.x;
                d.fy = event.y;
            })
            .on('end', (event, d: any) => {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null;
                d.fy = null;
            }) as any
        );

    // Add labels
    if (showLabels.value) {
        svg.append('g')
            .attr('class', 'labels')
            .selectAll('text')
            .data(nodes)
            .enter().append('text')
            .text((d: any) => d.name.length > 15 ? d.name.substring(0, 15) + '...' : d.name)
            .attr('font-size', 10)
            .attr('dx', 15)
            .attr('dy', 4)
            .attr('fill', '#374151')
            .style('pointer-events', 'none');
    }

    // Update positions on simulation tick
    simulation.on('tick', () => {
        link
            .attr('x1', (d: any) => d.source.x)
            .attr('y1', (d: any) => d.source.y)
            .attr('x2', (d: any) => d.target.x)
            .attr('y2', (d: any) => d.target.y);

        node
            .attr('cx', (d: any) => d.x)
            .attr('cy', (d: any) => d.y);

        if (showLabels.value) {
            svg.selectAll('text')
                .attr('x', (d: any) => d.x)
                .attr('y', (d: any) => d.y);
        }
    });

    // Add tooltips
    node.on('mouseover', function(event, d: any) {
        const tooltip = d3.select('body').append('div')
            .attr('class', 'tooltip')
            .style('position', 'absolute')
            .style('background', 'rgba(0, 0, 0, 0.8)')
            .style('color', 'white')
            .style('padding', '5px 10px')
            .style('border-radius', '4px')
            .style('font-size', '12px')
            .style('pointer-events', 'none')
            .style('z-index', '1000')
            .html(`
                <strong>${d.name}</strong><br/>
                Users: ${d.user_count}<br/>
                Events: ${d.event_count}<br/>
                Level: ${d.level}
            `);

        tooltip
            .style('left', (event.pageX + 10) + 'px')
            .style('top', (event.pageY - 10) + 'px');
    });

    node.on('mouseout', () => {
        d3.selectAll('.tooltip').remove();
    });

    isRendering.value = false;
};

// Watchers
watch(() => props.flowData, () => {
    if (props.flowData) {
        renderFlowVisualization();
    }
}, { deep: true });

// Lifecycle
onMounted(() => {
    if (props.flowData) {
        renderFlowVisualization();
    }
});
</script>

<style scoped>
.event-flow-view {
    @apply w-full;
}

/* D3 tooltip styles */
.tooltip {
    @apply bg-gray-900 text-white px-2 py-1 rounded text-xs shadow-lg;
}

/* Custom focus styles for accessibility */
.event-flow-view select:focus,
.event-flow-view input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}
</style>