<?php

/**
 * Add behavior flow and funnel analysis types to analytics.ts
 */
$typesFile = 'resources/js/Types/analytics.ts';
$typesContent = file_get_contents($typesFile);

// New types to add
$newTypes = <<<'TYPES'

// Behavior Flow Analysis Types
export interface BehaviorFlowData {
    definition_id: number;
    definition_name: string;
    period: {
        start: string;
        end: string;
    };
    flow_graph: {
        nodes: FlowNode[];
        edges: FlowEdge[];
    };
    metrics: {
        total_users: number;
        total_paths: number;
        avg_path_length: number;
        unique_events: number;
    };
    common_paths: CommonPath[];
    optimization_suggestions: OptimizationSuggestion[];
}

export interface FlowNode {
    id: string;
    name: string;
    type: 'start' | 'event' | 'end';
    count: number;
    percentage: number;
}

export interface FlowEdge {
    source: string;
    target: string;
    count: number;
    percentage: number;
}

export interface CommonPath {
    path: string[];
    count: number;
    percentage: number;
    avg_time: number;
}

export interface OptimizationSuggestion {
    type: 'drop_off' | 'path_length' | 'engagement';
    priority: 'low' | 'medium' | 'high';
    description: string;
    recommendation: string;
    expected_impact: string;
}

export interface FunnelAnalysisData {
    definition_id: number;
    definition_name: string;
    period: {
        start: string;
        end: string;
    };
    funnel_steps: FunnelStep[];
    metrics: {
        total_users: number;
        overall_conversion_rate: number;
        avg_drop_off_rate: number;
    };
    drop_off_points: DropOffPoint[];
    optimization_suggestions: OptimizationSuggestion[];
}

export interface FunnelStep {
    step: number;
    event_name: string;
    users: number;
    conversion_rate: number;
    drop_off_rate: number;
}

export interface DropOffPoint {
    step: number;
    event_name: string;
    drop_off_rate: number;
    users_lost: number;
    potential_impact: string;
}

export interface BehaviorFlowResponse {
    success: boolean;
    data: BehaviorFlowData;
}

export interface FunnelAnalysisResponse {
    success: boolean;
    data: FunnelAnalysisData;
}
TYPES;

// Find the position to insert (after CustomEventAnalyticsResponse)
$insertPosition = strpos($typesContent, 'export interface CustomEventAnalyticsResponse');

if ($insertPosition !== false) {
    // Find the end of the CustomEventAnalyticsResponse interface
    $endPosition = strpos($typesContent, '}', $insertPosition);
    if ($endPosition !== false) {
        // Insert new types after the closing brace
        $newContent = substr_replace($typesContent, $newTypes, $endPosition + 1, 0);
        file_put_contents($typesFile, $newContent);
        echo "Successfully added behavior flow and funnel analysis types to analytics.ts\n";
    } else {
        echo "Error: Could not find closing brace for CustomEventAnalyticsResponse\n";
    }
} else {
    echo "Error: Could not find CustomEventAnalyticsResponse interface\n";
}
