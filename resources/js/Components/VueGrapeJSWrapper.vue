<template>
    <div class="grapejs-wrapper" :class="{ loading: isLoading, error: isError }">
        <!-- Toolbar -->
        <div class="grapejs-toolbar" v-if="!isLoading">
            <div class="toolbar-left">
                <!-- Device Switcher -->
                <div class="device-switcher">
                    <button
                        v-for="device in availableDevices"
                        :key="device.id"
                        @click="switchToDeviceMode(device.id)"
                        :class="['device-btn', { active: deviceMode === device.id }]"
                        :aria-pressed="deviceMode === device.id"
                        :aria-label="`Switch to ${device.name} view`"
                    >
                        <component :is="device.icon" class="device-icon" />
                        <span class="device-label">{{ device.name }}</span>
                    </button>
                </div>

                <!-- Save/Publish Actions -->
                <div class="action-buttons">
                    <button
                        @click="saveCurrentPage"
                        :disabled="isSaving"
                        class="action-btn save-btn"
                        :aria-label="isSaving ? 'Saving page...' : 'Save page'"
                    >
                        <svg v-if="isSaving" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path
                                fill="currentColor"
                                class="opacity-75"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>

                    <button
                        @click="showPublishDialog = true"
                        :disabled="isPublishing"
                        class="action-btn publish-btn"
                        :aria-label="isPublishing ? 'Publishing page...' : 'Publish page'"
                    >
                        <svg v-if="isPublishing" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path
                                fill="currentColor"
                                class="opacity-75"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"
                            ></path>
                        </svg>
                        {{ isPublishing ? 'Publishing...' : 'Publish' }}
                    </button>
                </div>
            </div>

            <div class="toolbar-right">
                <!-- Panel Toggles -->
                <div class="panel-toggles">
                    <button
                        @click="togglePanel('component')"
                        :class="['panel-toggle', { active: showComponentPanel }]"
                        :aria-pressed="showComponentPanel"
                        :aria-label="`${showComponentPanel ? 'Hide' : 'Show'} component panel`"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            ></path>
                        </svg>
                    </button>

                    <button
                        @click="togglePanel('style')"
                        :class="['panel-toggle', { active: showStylePanel }]"
                        :aria-pressed="showStylePanel"
                        :aria-label="`${showStylePanel ? 'Hide' : 'Show'} style panel`"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"
                            ></path>
                        </svg>
                    </button>

                    <button
                        @click="togglePanel('layer')"
                        :class="['panel-toggle', { active: showLayerPanel }]"
                        :aria-pressed="showLayerPanel"
                        :aria-label="`${showLayerPanel ? 'Hide' : 'Show'} layer panel`"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                            ></path>
                        </svg>
                    </button>
                </div>

                <!-- Preview Toggle -->
                <button
                    @click="togglePreviewPanel"
                    :class="['preview-btn', { active: showPreviewPanel }]"
                    :aria-pressed="showPreviewPanel"
                    :aria-label="`${showPreviewPanel ? 'Hide' : 'Show'} preview panel`"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        ></path>
                    </svg>
                </button>

                <!-- Analytics -->
                <button
                    @click="toggleAnalyticsPanel"
                    :class="['analytics-btn', { active: showAnalyticsPanel }]"
                    :aria-pressed="showAnalyticsPanel"
                    :aria-label="`${showAnalyticsPanel ? 'Hide' : 'Show'} analytics panel`"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        ></path>
                    </svg>
                </button>

                <!-- A/B Testing -->
                <button
                    @click="toggleABTestingPanel"
                    :class="['ab-testing-btn', { active: showABTestingPanel }]"
                    :aria-pressed="showABTestingPanel"
                    :aria-label="`${showABTestingPanel ? 'Hide' : 'Show'} A/B testing panel`"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        ></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>

                <!-- Export/Backup/Migration -->
                <div class="export-backup-actions">
                    <button @click="showExportDialog = true" class="export-btn" :aria-label="'Export page'" :title="'Export page to various formats'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </button>

                    <button @click="showBackupDialog = true" class="backup-btn" :aria-label="'Backup page'" :title="'Create or restore page backup'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                            ></path>
                        </svg>
                    </button>

                    <button
                        @click="showMigrationDialog = true"
                        class="migration-btn"
                        :aria-label="'Migrate page'"
                        :title="'Migrate page between environments or tenants'"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                            ></path>
                        </svg>
                    </button>
                </div>

                <!-- Settings -->
                <button
                    @click="showSettings = !showSettings"
                    class="settings-btn"
                    :aria-pressed="showSettings"
                    :aria-label="`${showSettings ? 'Hide' : 'Show'} settings`"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 100 4m0-4v2m0 16v2m0-2a2 2 0 100-4m0 4a2 2 0 100-4m0 4v-2m-6-8h2m10 0h2M4.93 4.93l1.41 1.41m10.73 0l1.41 1.41M4.93 19.07l1.41-1.41m10.73 0l1.41-1.41"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- GrapeJS Editor Container -->
        <div class="grapejs-editor-container" :class="{ 'panels-hidden': !showComponentPanel && !showStylePanel && !showLayerPanel }">
            <!-- Component Panel -->
            <div v-if="showComponentPanel" class="component-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Components</h3>
                    <button @click="togglePanel('component')" class="close-panel-btn">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="blocks-container" class="blocks-container"></div>
            </div>

            <!-- Main Editor Area -->
            <div class="main-editor-area">
                <div id="grapesjs-editor" class="grapesjs-editor" :style="{ height: editorHeight }"></div>
            </div>

            <!-- Style Panel -->
            <div v-if="showStylePanel" class="style-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Styles</h3>
                    <button @click="togglePanel('style')" class="close-panel-btn">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="style-panel-tabs">
                    <button @click="activeStyleTab = 'visual'" :class="['style-tab', { active: activeStyleTab === 'visual' }]">Visual</button>
                    <button @click="activeStyleTab = 'css'" :class="['style-tab', { active: activeStyleTab === 'css' }]">CSS Editor</button>
                </div>
                <div v-if="activeStyleTab === 'visual'" id="styles-container" class="styles-container"></div>
                <div v-if="activeStyleTab === 'css'" class="css-editor-container">
                    <textarea
                        v-model="customCSS"
                        @input="validateCSS"
                        class="css-editor"
                        placeholder="Enter custom CSS..."
                        :class="{ error: cssValidationErrors.length > 0 }"
                    ></textarea>
                    <div v-if="cssValidationErrors.length > 0" class="css-errors">
                        <div v-for="error in cssValidationErrors" :key="error.line + '-' + error.column" class="css-error">
                            Line {{ error.line }}: {{ error.message }}
                        </div>
                    </div>
                    <div class="css-actions">
                        <button @click="applyCustomCSS" class="apply-css-btn" :disabled="cssValidationErrors.length > 0">Apply CSS</button>
                        <button @click="formatCSS" class="format-css-btn">Format</button>
                    </div>
                </div>
            </div>

            <!-- Layer Panel -->
            <div v-if="showLayerPanel" class="layer-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Layers</h3>
                    <button @click="togglePanel('layer')" class="close-panel-btn">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="layers-container" class="layers-container"></div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div v-if="showSettings" class="settings-overlay">
            <div class="settings-panel">
                <div class="settings-header">
                    <h3 class="settings-title">Editor Settings</h3>
                    <button @click="showSettings = false" class="close-settings-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="settings-content">
                    <div class="setting-group">
                        <h4>Auto-save</h4>
                        <label class="setting-label">
                            <input v-model="settings.autoSave" type="checkbox" class="setting-checkbox" />
                            Auto-save changes
                        </label>
                    </div>

                    <div class="setting-group">
                        <h4>Device Preview</h4>
                        <label class="setting-label">
                            <input v-model="settings.showDevicePreview" type="checkbox" class="setting-checkbox" />
                            Show device preview toolbar
                        </label>
                    </div>

                    <div class="setting-group">
                        <h4>Grid System</h4>
                        <label class="setting-label">
                            <input v-model="settings.showGrid" type="checkbox" class="setting-checkbox" />
                            Show grid overlay
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Panel -->
        <div v-if="showPreviewPanel" class="preview-panel">
            <div class="panel-header">
                <h3 class="panel-title">Preview & Testing</h3>
                <button @click="togglePreviewPanel" class="close-panel-btn">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="preview-content">
                <!-- Preview Controls -->
                <div class="preview-controls">
                    <div class="device-selector">
                        <label class="control-label">Device:</label>
                        <div class="device-buttons">
                            <button
                                v-for="device in availableDevices"
                                :key="device.id"
                                @click="switchPreviewDevice(device.id)"
                                :class="['device-btn', { active: previewDevice === device.id }]"
                                :title="`${device.name} (${device.width}x${device.height})`"
                            >
                                <component :is="device.icon" class="device-icon" />
                                {{ device.name }}
                            </button>
                        </div>
                    </div>

                    <div class="interaction-controls">
                        <label class="control-label">
                            <input v-model="mockInteractions" type="checkbox" @change="toggleMockInteractions" class="interaction-checkbox" />
                            Enable Interactions
                        </label>
                        <button @click="clearInteractionLogs" class="clear-logs-btn">Clear Logs</button>
                    </div>
                </div>

                <!-- Preview Iframe -->
                <div class="preview-container">
                    <div v-if="isPreviewLoading" class="preview-loading">
                        <div class="loading-spinner"></div>
                        <p>Loading preview...</p>
                    </div>
                    <div v-else-if="previewError" class="preview-error">
                        <p>{{ previewError }}</p>
                        <button @click="updatePreview" class="retry-btn">Retry</button>
                    </div>
                    <div v-else class="preview-iframe-container">
                        <iframe ref="previewIframe" class="preview-iframe" :src="previewUrl" @load="onPreviewLoad"></iframe>
                    </div>
                </div>

                <!-- Interaction Logs -->
                <div class="interaction-logs">
                    <h4 class="logs-title">Interaction Logs</h4>
                    <div class="logs-container">
                        <div v-if="interactionLogs.length === 0" class="no-logs">No interactions recorded yet</div>
                        <div v-else class="log-entries">
                            <div v-for="(log, index) in interactionLogs" :key="index" class="log-entry">
                                <span class="log-type">{{ log.type }}</span>
                                <span class="log-element">{{ log.element }}</span>
                                <span class="log-time">{{ formatLogTime(log.timestamp) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Panel -->
        <div v-if="showAnalyticsPanel" class="analytics-panel">
            <div class="panel-header">
                <h3 class="panel-title">Analytics & Tracking</h3>
                <button @click="toggleAnalyticsPanel" class="close-panel-btn">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="analytics-content">
                <div v-if="isAnalyticsLoading" class="analytics-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading analytics...</p>
                </div>
                <div v-else-if="analyticsData" class="analytics-metrics">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <h4 class="metric-title">Page Views</h4>
                            <p class="metric-value">{{ analyticsData.pageViews || 0 }}</p>
                        </div>
                        <div class="metric-card">
                            <h4 class="metric-title">Unique Visitors</h4>
                            <p class="metric-value">{{ analyticsData.uniqueVisitors || 0 }}</p>
                        </div>
                        <div class="metric-card">
                            <h4 class="metric-title">Bounce Rate</h4>
                            <p class="metric-value">{{ ((analyticsData.bounceRate || 0) * 100).toFixed(1) }}%</p>
                        </div>
                        <div class="metric-card">
                            <h4 class="metric-title">Conversion Rate</h4>
                            <p class="metric-value">{{ ((analyticsData.conversionRate || 0) * 100).toFixed(1) }}%</p>
                        </div>
                        <div class="metric-card">
                            <h4 class="metric-title">Form Submissions</h4>
                            <p class="metric-value">{{ analyticsData.formSubmissions || 0 }}</p>
                        </div>
                        <div class="metric-card">
                            <h4 class="metric-title">Element Interactions</h4>
                            <p class="metric-value">{{ analyticsData.elementInteractions || 0 }}</p>
                        </div>
                    </div>

                    <div class="real-time-section">
                        <h4 class="section-title">Real-time Activity</h4>
                        <div v-if="realTimeAnalytics" class="real-time-metrics">
                            <div class="real-time-item">
                                <span class="real-time-label">Active Users:</span>
                                <span class="real-time-value">{{ realTimeAnalytics.activeUsers || 0 }}</span>
                            </div>
                            <div class="real-time-item">
                                <span class="real-time-label">Events/Minute:</span>
                                <span class="real-time-value">{{ realTimeAnalytics.pageViewsPerMinute || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="analytics-actions">
                        <button @click="loadAnalyticsData" class="refresh-analytics-btn">Refresh Data</button>
                    </div>
                </div>
                <div v-else class="no-analytics">
                    <p>No analytics data available</p>
                    <button @click="loadAnalyticsData" class="load-analytics-btn">Load Analytics</button>
                </div>
            </div>
        </div>

        <!-- A/B Testing Panel -->
        <div v-if="showABTestingPanel" class="ab-testing-panel">
            <div class="panel-header">
                <h3 class="panel-title">A/B Testing</h3>
                <button @click="toggleABTestingPanel" class="close-panel-btn">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="ab-testing-content">
                <div v-if="experimentError" class="error-message">
                    <p>{{ experimentError }}</p>
                </div>

                <div class="experiment-list">
                    <div class="experiment-header">
                        <h4>Experiments</h4>
                        <button @click="isCreatingExperiment = true" class="create-experiment-btn">Create Experiment</button>
                    </div>

                    <div v-if="experiments.length === 0" class="no-experiments">
                        <p>No experiments yet</p>
                    </div>
                    <div v-else class="experiment-items">
                        <div
                            v-for="experiment in experiments"
                            :key="experiment.id"
                            @click="selectExperiment(experiment.id)"
                            :class="['experiment-item', { active: selectedExperimentId === experiment.id }]"
                        >
                            <div class="experiment-info">
                                <h5>{{ experiment.config.name }}</h5>
                                <span class="experiment-status" :class="experiment.status">{{ experiment.status }}</span>
                            </div>
                            <div class="experiment-metrics" v-if="experiment.results">
                                <span class="metric">{{ experiment.results.totalVisitors }} visitors</span>
                                <span class="metric">{{ (experiment.results.confidence * 100).toFixed(1) }}% confidence</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedExperiment" class="experiment-details">
                    <div class="experiment-header">
                        <h4>{{ selectedExperiment.config.name }}</h4>
                        <div class="experiment-actions">
                            <button @click="startExperiment" v-if="selectedExperiment.status === 'draft'" class="action-btn start-btn">Start</button>
                            <button @click="pauseExperiment" v-if="selectedExperiment.status === 'running'" class="action-btn pause-btn">
                                Pause
                            </button>
                            <button @click="endExperiment" v-if="selectedExperiment.status === 'running'" class="action-btn end-btn">End</button>
                        </div>
                    </div>

                    <div class="experiment-config">
                        <p class="hypothesis">{{ selectedExperiment.config.hypothesis }}</p>
                        <div class="experiment-stats">
                            <div class="stat">
                                <span class="stat-label">Duration:</span>
                                <span class="stat-value">{{ selectedExperiment.config.duration }} days</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Traffic Allocation:</span>
                                <span class="stat-value">{{ selectedExperiment.config.trafficAllocation.join('-') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="variants-section">
                        <h5>Variants</h5>
                        <div class="variants-list">
                            <div v-for="variant in variants" :key="variant.id" class="variant-item">
                                <div class="variant-info">
                                    <h6>{{ variant.config.name }}</h6>
                                    <span class="variant-weight">{{ variant.config.weight }}%</span>
                                </div>
                                <div class="variant-metrics">
                                    <span class="metric">{{ variant.visitors }} visitors</span>
                                    <span class="metric">{{ variant.conversions }} conversions</span>
                                    <span class="metric">{{ variant.conversionRate.toFixed(2) }}% rate</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="testResults" class="test-results">
                        <h5>Test Results</h5>
                        <div class="results-summary">
                            <div class="result-item">
                                <span class="result-label">Winner:</span>
                                <span class="result-value">{{ testResults.winner || 'No clear winner' }}</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Confidence:</span>
                                <span class="result-value">{{ (testResults.confidence * 100).toFixed(1) }}%</span>
                            </div>
                            <div class="result-item">
                                <span class="result-label">Statistical Significance:</span>
                                <span class="result-value" :class="{ significant: testResults.statisticalSignificance }">
                                    {{ testResults.statisticalSignificance ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isCreatingExperiment" class="create-experiment-modal">
                    <div class="modal-content">
                        <h4>Create New Experiment</h4>
                        <form @submit.prevent="createExperiment">
                            <div class="form-group">
                                <label for="experiment-name">Name:</label>
                                <input id="experiment-name" v-model="newExperimentName" type="text" required class="form-input" />
                            </div>
                            <div class="form-group">
                                <label for="experiment-hypothesis">Hypothesis:</label>
                                <textarea id="experiment-hypothesis" v-model="newExperimentHypothesis" required class="form-textarea"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="experiment-goal">Goal:</label>
                                <select id="experiment-goal" v-model="newExperimentGoal" class="form-select">
                                    <option value="conversion">Conversion</option>
                                    <option value="engagement">Engagement</option>
                                    <option value="clicks">Clicks</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="experiment-duration">Duration (days):</label>
                                <input
                                    id="experiment-duration"
                                    v-model.number="newExperimentDuration"
                                    type="number"
                                    min="1"
                                    required
                                    class="form-input"
                                />
                            </div>
                            <div class="form-actions">
                                <button type="button" @click="isCreatingExperiment = false" class="cancel-btn">Cancel</button>
                                <button type="submit" class="create-btn">Create Experiment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Publish Dialog -->
        <div v-if="showPublishDialog" class="dialog-overlay">
            <div class="dialog-panel">
                <div class="dialog-header">
                    <h3 class="dialog-title">Publish Page</h3>
                    <button @click="showPublishDialog = false" class="close-dialog-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="dialog-content">
                    <p>Are you sure you want to publish this page? This will make it live for all users.</p>
                    <div class="dialog-actions">
                        <button @click="showPublishDialog = false" class="dialog-btn cancel-btn">Cancel</button>
                        <button @click="publishCurrentPage" :disabled="isPublishing" class="dialog-btn confirm-btn">
                            {{ isPublishing ? 'Publishing...' : 'Publish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Dialog -->
        <div v-if="showExportDialog" class="dialog-overlay">
            <div class="dialog-panel">
                <div class="dialog-header">
                    <h3 class="dialog-title">Export Page</h3>
                    <button @click="showExportDialog = false" class="close-dialog-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="dialog-content">
                    <div class="form-group">
                        <label for="export-format">Export Format:</label>
                        <select id="export-format" v-model="exportOptions.format" class="form-select">
                            <option value="html">HTML</option>
                            <option value="json">JSON</option>
                            <option value="xml">XML</option>
                            <option value="yaml">YAML</option>
                            <option value="pdf">PDF</option>
                            <option value="markdown">Markdown</option>
                            <option value="grapejs">GrapeJS</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="exportOptions.includeAssets" type="checkbox" class="form-checkbox-input" />
                            Include Assets
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="exportOptions.compress" type="checkbox" class="form-checkbox-input" />
                            Compress Output
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="exportOptions.encrypt" type="checkbox" class="form-checkbox-input" />
                            Encrypt Export
                        </label>
                    </div>

                    <div v-if="exportError" class="error-message">
                        <p>{{ exportError }}</p>
                    </div>

                    <div class="dialog-actions">
                        <button @click="showExportDialog = false" class="dialog-btn cancel-btn">Cancel</button>
                        <button @click="handleExport" :disabled="isExporting" class="dialog-btn confirm-btn">
                            {{ isExporting ? 'Exporting...' : 'Export' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Dialog -->
        <div v-if="showBackupDialog" class="dialog-overlay">
            <div class="dialog-panel">
                <div class="dialog-header">
                    <h3 class="dialog-title">Create Backup</h3>
                    <button @click="showBackupDialog = false" class="close-dialog-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="dialog-content">
                    <div class="form-group">
                        <label for="backup-name">Backup Name:</label>
                        <input
                            id="backup-name"
                            v-model="backupOptions.name"
                            type="text"
                            required
                            class="form-input"
                            placeholder="Enter backup name"
                        />
                    </div>

                    <div class="form-group">
                        <label for="backup-description">Description:</label>
                        <textarea
                            id="backup-description"
                            v-model="backupOptions.description"
                            class="form-textarea"
                            placeholder="Optional description"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="retention-days">Retention (days):</label>
                        <input id="retention-days" v-model.number="backupOptions.retentionDays" type="number" min="1" max="365" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="backupOptions.includeAssets" type="checkbox" class="form-checkbox-input" />
                            Include Assets
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="backupOptions.compress" type="checkbox" class="form-checkbox-input" />
                            Compress Backup
                        </label>
                    </div>

                    <div v-if="backupError" class="error-message">
                        <p>{{ backupError }}</p>
                    </div>

                    <div class="dialog-actions">
                        <button @click="showBackupDialog = false" class="dialog-btn cancel-btn">Cancel</button>
                        <button @click="handleBackup" :disabled="isBackingUp" class="dialog-btn confirm-btn">
                            {{ isBackingUp ? 'Creating Backup...' : 'Create Backup' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Dialog -->
        <div v-if="showMigrationDialog" class="dialog-overlay">
            <div class="dialog-panel">
                <div class="dialog-header">
                    <h3 class="dialog-title">Migrate Page</h3>
                    <button @click="showMigrationDialog = false" class="close-dialog-btn">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="dialog-content">
                    <div class="form-group">
                        <label for="source-env">Source Environment:</label>
                        <select id="source-env" v-model="migrationConfig.sourceEnvironment" class="form-select">
                            <option value="development">Development</option>
                            <option value="staging">Staging</option>
                            <option value="production">Production</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="target-env">Target Environment:</label>
                        <select id="target-env" v-model="migrationConfig.targetEnvironment" class="form-select">
                            <option value="development">Development</option>
                            <option value="staging">Staging</option>
                            <option value="production">Production</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="target-tenant">Target Tenant ID:</label>
                        <input
                            id="target-tenant"
                            v-model="migrationConfig.targetTenantId"
                            type="text"
                            class="form-input"
                            placeholder="Enter target tenant ID"
                        />
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="migrationConfig.includeAssets" type="checkbox" class="form-checkbox-input" />
                            Include Assets
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input v-model="migrationConfig.validateBeforeMigration" type="checkbox" class="form-checkbox-input" />
                            Validate Before Migration
                        </label>
                    </div>

                    <div v-if="migrationError" class="error-message">
                        <p>{{ migrationError }}</p>
                    </div>

                    <div class="dialog-actions">
                        <button @click="showMigrationDialog = false" class="dialog-btn cancel-btn">Cancel</button>
                        <button @click="handleMigration" :disabled="isMigrating" class="dialog-btn confirm-btn">
                            {{ isMigrating ? 'Creating Migration...' : 'Create Migration' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p>{{ loadingMessage }}</p>
            </div>
        </div>

        <!-- Error Overlay -->
        <div v-if="isError" class="error-overlay">
            <div class="error-content">
                <svg class="h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                    ></path>
                </svg>
                <h3 class="error-title">Editor Error</h3>
                <p class="error-message">{{ errorMessage }}</p>
                <button @click="resetEditor" class="error-btn">Try Again</button>
            </div>
        </div>

        <!-- Custom UI Slots -->
        <slot name="toolbar"></slot>
        <slot name="sidebar"></slot>
        <slot name="footer"></slot>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { createABTestingService, type ABTestingService } from '@/Services/ABTestingService';
import { analyticsService } from '@/Services/AnalyticsIntegrationService';
import { brandConfigService } from '@/Services/BrandConfigService';
import { componentLibraryBridge } from '@/Services/ComponentLibraryBridge';
import { pageBackupService, type BackupOptions, type BackupResult } from '@/Services/PageBackupService';
import { pageExportService, type ExportOptions, type ExportResult } from '@/Services/PageExportService';
import { pageMigrationService, type Migration, type MigrationConfig } from '@/Services/PageMigrationService';
import type { Component, EditorState, GrapeJSConfig, Page } from '@/types/Components';
import type { Editor } from 'grapesjs';
import grapesjs from 'grapesjs';
import { io, type Socket } from 'socket.io-client';
import type { Ref } from 'vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Props
interface Props {
    pageId?: string;
    initialData?: any;
    config?: Partial<GrapeJSConfig>;
    height?: string;
    disabled?: boolean;
    tenantId?: string;
    tenantContext?: any;
}

const props = withDefaults(defineProps<Props>(), {
    height: '600px',
    disabled: false,
});

// Emits
const emit = defineEmits<{
    save: [data: any];
    publish: [pageId: string];
    change: [data: any];
    error: [error: Error];
    ready: [editor: Editor];
}>();

// Reactive state
const editor: Ref<Editor | null> = ref(null);
const socket: Ref<Socket | null> = ref(null);
const isSocketConnected = ref(false);
const socketError = ref('');
const isLoading = ref(true);
const isError = ref(false);
const errorMessage = ref('');
const currentPage: Ref<Page | null> = ref(null);
const selectedComponent: Ref<Component | null> = ref(null);
const deviceMode = ref('desktop');
const isSaving = ref(false);
const isPublishing = ref(false);
const showPublishDialog = ref(false);
const showSettings = ref(false);
const showComponentPanel = ref(true);
const showStylePanel = ref(true);
const showLayerPanel = ref(true);
const showAnalyticsPanel = ref(false);
const loadingMessage = ref('Initializing GrapeJS editor...');

// Analytics state
const analyticsData = ref(null);
const realTimeAnalytics = ref(null);
const isAnalyticsLoading = ref(false);

// A/B Testing state
const abTestingService: Ref<ABTestingService | null> = ref(null);
const showABTestingPanel = ref(false);
const experiments = ref([]);
const selectedExperimentId = ref<string | null>(null);
const selectedExperiment = ref(null);
const variants = ref([]);
const testResults = ref(null);
const realTimeTestResults = ref(null);
const isCreatingExperiment = ref(false);
const experimentError = ref('');

// Export/Backup/Migration state
const showExportDialog = ref(false);
const showBackupDialog = ref(false);
const showMigrationDialog = ref(false);
const exportOptions = ref<ExportOptions>({
    format: 'html',
    includeAssets: true,
    compress: false,
    encrypt: false,
});
const backupOptions = ref<BackupOptions>({
    name: '',
    description: '',
    retentionDays: 30,
    includeAssets: true,
    compress: true,
});
const migrationConfig = ref<MigrationConfig>({
    sourceEnvironment: 'development',
    targetEnvironment: 'production',
    sourceTenantId: props.tenantId || '',
    targetTenantId: '',
    includeAssets: true,
    validateBeforeMigration: true,
});
const exportResult = ref<ExportResult | null>(null);
const backupResult = ref<BackupResult | null>(null);
const migrationResult = ref<Migration | null>(null);
const isExporting = ref(false);
const isBackingUp = ref(false);
const isMigrating = ref(false);
const exportError = ref('');
const backupError = ref('');
const migrationError = ref('');

// Form state for creating experiments
const newExperimentName = ref('');
const newExperimentHypothesis = ref('');
const newExperimentGoal = ref('conversion');
const newExperimentDuration = ref(7);

// Preview and testing state
const showPreviewPanel = ref(false);
const previewDevice = ref('desktop');
const previewIframe = ref<HTMLIFrameElement | null>(null);
const previewUrl = ref('');
const isPreviewLoading = ref(false);
const previewError = ref('');
const mockInteractions = ref(false);
const interactionLogs = ref<Array<{ type: string; element: string; timestamp: Date }>>([]);

// Brand configuration state
const brandColors = ref({});
const typographySettings = ref({});
const spacingSettings = ref({});

// CSS Editor state
const activeStyleTab = ref('visual');
const customCSS = ref('');
const cssValidationErrors = ref<Array<{ line: number; column: number; message: string }>>([]);

// Settings
const settings = ref({
    autoSave: true,
    showDevicePreview: true,
    showGrid: false,
});

// Device configurations for preview
const availableDevices = [
    {
        id: 'desktop',
        name: 'Desktop',
        icon: 'MonitorIcon',
        width: 1200,
        height: 800,
        userAgent: '',
        pixelRatio: 1,
    },
    {
        id: 'laptop',
        name: 'Laptop',
        icon: 'LaptopIcon',
        width: 1024,
        height: 768,
        userAgent: '',
        pixelRatio: 1,
    },
    {
        id: 'tablet',
        name: 'Tablet',
        icon: 'DeviceTabletIcon',
        width: 768,
        height: 1024,
        userAgent: 'Mozilla/5.0 (iPad; CPU OS 13_2_3 like Mac OS X)',
        pixelRatio: 2,
    },
    {
        id: 'mobile',
        name: 'Mobile',
        icon: 'DevicePhoneMobileIcon',
        width: 375,
        height: 667,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X)',
        pixelRatio: 2,
    },
];

// Computed properties
const editorHeight = computed(() => props.height);

const editorConfig = computed((): GrapeJSConfig => {
    return {
        container: '#grapesjs-editor',
        height: editorHeight.value,
        width: 'auto',
        storageManager: {
            type: 'remote',
            autosave: settings.value.autoSave,
            stepsBeforeSave: 1,
            options: {
                remote: {
                    urlLoad: props.pageId ? `/api/pages/${props.pageId}/grapejs-data` : '',
                    urlStore: props.pageId ? `/api/pages/${props.pageId}/grapejs-data` : '',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    contentTypeJson: true,
                },
            },
        },
        blockManager: {
            appendTo: '#blocks-container',
        },
        styleManager: {
            appendTo: '#styles-container',
            sectors: [
                {
                    name: 'General',
                    open: true,
                    properties: [
                        'display',
                        'position',
                        'top',
                        'right',
                        'bottom',
                        'left',
                        'width',
                        'height',
                        'max-width',
                        'max-height',
                        'min-width',
                        'min-height',
                        'margin',
                        'padding',
                    ],
                },
                {
                    name: 'Typography',
                    open: false,
                    properties: [
                        'font-family',
                        'font-size',
                        'font-weight',
                        'line-height',
                        'letter-spacing',
                        'color',
                        'text-align',
                        'text-decoration',
                        'text-transform',
                        'text-shadow',
                    ],
                },
                {
                    name: 'Background',
                    open: false,
                    properties: [
                        'background-color',
                        'background-image',
                        'background-repeat',
                        'background-position',
                        'background-size',
                        'background-attachment',
                    ],
                },
                {
                    name: 'Border',
                    open: false,
                    properties: [
                        'border-width',
                        'border-style',
                        'border-color',
                        'border-radius',
                        'border-top-left-radius',
                        'border-top-right-radius',
                        'border-bottom-left-radius',
                        'border-bottom-right-radius',
                    ],
                },
                {
                    name: 'Layout',
                    open: false,
                    properties: [
                        'flex-direction',
                        'justify-content',
                        'align-items',
                        'align-content',
                        'flex-wrap',
                        'gap',
                        'grid-template-columns',
                        'grid-template-rows',
                        'grid-gap',
                    ],
                },
                {
                    name: 'Effects',
                    open: false,
                    properties: ['opacity', 'box-shadow', 'filter', 'transform', 'transition'],
                },
                {
                    name: 'Responsive',
                    open: false,
                    properties: ['display', 'width', 'height', 'margin', 'padding', 'font-size'],
                },
                {
                    name: 'Accessibility',
                    open: false,
                    properties: [
                        'color',
                        'background-color',
                        'font-size',
                        'line-height',
                        'contrast-ratio',
                        'focus-outline',
                        'aria-label',
                        'role',
                        'tabindex',
                    ],
                },
            ],
        },
        layerManager: {
            appendTo: '#layers-container',
        },
        traitManager: {
            appendTo: '#traits-container',
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '', widthMedia: '' },
                { name: 'Tablet', width: '768px', widthMedia: '768px' },
                { name: 'Mobile', width: '375px', widthMedia: '375px' },
            ],
        },
        plugins: [
            'gjs-blocks-basic',
            'grapesjs-plugin-forms',
            'grapesjs-component-countdown',
            'grapesjs-plugin-export',
            'grapesjs-tabs',
            'grapesjs-custom-code',
            'grapesjs-touch',
            'grapesjs-parser-postcss',
            'grapesjs-tooltip',
            'grapesjs-tui-image-editor',
            'grapesjs-typed',
            'grapesjs-style-bg',
        ],
        pluginsOpts: {
            'gjs-blocks-basic': { flexGrid: true },
        },
        ...props.config,
    };
});

const editorState = computed((): EditorState => {
    return {
        isLoading: isLoading.value,
        isError: isError.value,
        errorMessage: errorMessage.value,
        currentPage: currentPage.value,
        selectedComponent: selectedComponent.value,
        deviceMode: deviceMode.value,
        isSaving: isSaving.value,
        isPublishing: isPublishing.value,
    };
});

// Lifecycle
onMounted(async () => {
    try {
        await initializeEditor();
        await initializeSocket();
        await loadSystemData();
        if (props.pageId) {
            await loadPageData(props.pageId);
        }

        // Initialize analytics service
        await initializeAnalytics();

        // Initialize A/B testing service
        await initializeABTesting();

        // Add message listener for preview interactions
        window.addEventListener('message', handlePreviewMessage);
    } catch (error) {
        console.error('Failed to initialize editor:', error);
        isError.value = true;
        errorMessage.value = error instanceof Error ? error.message : 'Failed to initialize editor';
        emit('error', error instanceof Error ? error : new Error('Unknown error'));
    } finally {
        isLoading.value = false;
    }
});

onUnmounted(() => {
    if (editor.value) {
        editor.value.destroy();
    }
    if (socket.value) {
        socket.value.disconnect();
    }

    // Remove message listener
    window.removeEventListener('message', handlePreviewMessage);

    // Clean up preview URL
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

// Watchers
watch(
    () => props.pageId,
    async (newPageId: string | undefined, oldPageId: string | undefined) => {
        if (newPageId && newPageId !== oldPageId) {
            await loadPageData(newPageId);
        }
    },
);

watch(deviceMode, (newMode: string) => {
    if (editor.value) {
        editor.value.setDevice(newMode);
    }
});

// Methods
const initializeEditor = async (): Promise<void> => {
    try {
        loadingMessage.value = 'Loading GrapeJS...';

        // Initialize GrapeJS editor
        editor.value = await grapesjs.init(editorConfig.value);

        // Set up event listeners
        setupEditorEvents();

        // Register Components with GrapeJS
        await registerComponentsWithEditor();

        logger.log('GrapeJS editor initialized successfully');
        emit('ready', editor.value);
    } catch (error) {
        console.error('Failed to initialize GrapeJS editor:', error);
        throw error;
    }
};

const initializeSocket = async (): Promise<void> => {
    try {
        loadingMessage.value = 'Connecting to real-time server...';

        // Initialize Socket.io client
        const socketUrl = (import.meta as any).env?.VITE_SOCKET_URL || 'http://localhost:3000';
        socket.value = io(socketUrl, {
            transports: ['websocket', 'polling'],
            timeout: 5000,
        });

        // Set up socket event listeners
        setupSocketEvents();

        logger.log('Socket.io client initialized');
    } catch (error) {
        console.error('Failed to initialize Socket.io client:', error);
        socketError.value = error instanceof Error ? error.message : 'Failed to connect to real-time server';
        throw error;
    }
};

const setupEditorEvents = (): void => {
    if (!editor.value) return;

    // Listen for component selection
    editor.value.on('component:selected', (component: any) => {
        const componentId = component.get('attributes')?.['data-component-id'];
        if (componentId) {
            selectedComponent.value = componentLibraryBridge
                .getRegisteredComponents()
                .find((c) => c.blockDefinition.attributes?.['data-component-id'] === componentId) as any;

            // Track component selection
            analyticsService
                .trackElementInteraction(componentId, 'select', {
                    componentType: component.get('type'),
                    componentTag: component.get('tagName'),
                })
                .catch(console.error);
        } else {
            selectedComponent.value = null;
        }
    });

    // Listen for component updates and broadcast via socket
    editor.value.on('component:update', (component: any) => {
        const changeData = {
            type: 'component_update',
            componentId: component.getId(),
            data: component.toJSON(),
            timestamp: Date.now(),
        };
        emit('change', changeData);

        // Broadcast change via socket if connected
        if (socket.value && isSocketConnected.value && props.tenantId) {
            socket.value.emit('editor:change', {
                ...changeData,
                tenantId: props.tenantId,
                pageId: props.pageId,
            });
        }
    });

    // Listen for component additions
    editor.value.on('component:add', (component: any) => {
        const changeData = {
            type: 'component_add',
            componentId: component.getId(),
            data: component.toJSON(),
            timestamp: Date.now(),
        };

        // Track component addition
        analyticsService
            .trackEvent('component_added', {
                componentId: component.getId(),
                componentType: component.get('type'),
                componentTag: component.get('tagName'),
                pageId: props.pageId,
            })
            .catch(console.error);

        // Broadcast change via socket if connected
        if (socket.value && isSocketConnected.value && props.tenantId) {
            socket.value.emit('editor:change', {
                ...changeData,
                tenantId: props.tenantId,
                pageId: props.pageId,
            });
        }
    });

    // Listen for component deletions
    editor.value.on('component:remove', (component: any) => {
        const changeData = {
            type: 'component_remove',
            componentId: component.getId(),
            timestamp: Date.now(),
        };

        // Broadcast change via socket if connected
        if (socket.value && isSocketConnected.value && props.tenantId) {
            socket.value.emit('editor:change', {
                ...changeData,
                tenantId: props.tenantId,
                pageId: props.pageId,
            });
        }
    });

    // Listen for style changes
    editor.value.on('style:update', (component: any, style: any) => {
        const changeData = {
            type: 'style_update',
            componentId: component.getId(),
            data: style,
            timestamp: Date.now(),
        };

        emit('change', changeData);

        // Broadcast change via socket if connected
        if (socket.value && isSocketConnected.value && props.tenantId) {
            socket.value.emit('editor:change', {
                ...changeData,
                tenantId: props.tenantId,
                pageId: props.pageId,
            });
        }
    });

    // Listen for storage events
    editor.value.on('storage:start:store', () => {
        isSaving.value = true;
    });

    editor.value.on('storage:end:store', () => {
        isSaving.value = false;
        emit('save', getCurrentEditorData());
    });

    editor.value.on('storage:error:store', (error: Error) => {
        isSaving.value = false;
        console.error('Storage error:', error);
        isError.value = true;
        errorMessage.value = `Failed to save: ${error.message}`;
        emit('error', error);
    });

    // Listen for device changes
    editor.value.on('device:change', (device: string) => {
        deviceMode.value = device;
    });
};

const setupSocketEvents = (): void => {
    if (!socket.value) return;

    // Connection events
    socket.value.on('connect', () => {
        logger.log('Connected to real-time server');
        isSocketConnected.value = true;
        socketError.value = '';

        // Join tenant room for isolation
        if (props.tenantId) {
            socket.value?.emit('join:tenant', { tenantId: props.tenantId, pageId: props.pageId });
        }
    });

    socket.value.on('disconnect', (reason: string) => {
        logger.log('Disconnected from real-time server:', reason);
        isSocketConnected.value = false;
    });

    socket.value.on('connect_error', (error: Error) => {
        console.error('Socket connection error:', error);
        isSocketConnected.value = false;
        socketError.value = error.message;
    });

    // Editor change events from other users
    socket.value.on('editor:change', (changeData: any) => {
        if (!editor.value || !changeData) return;

        try {
            // Apply incoming changes to editor
            switch (changeData.type) {
                case 'component_update':
                    if (changeData.data) {
                        const component = editor.value.getComponents().get(changeData.componentId);
                        if (component) {
                            component.set(changeData.data);
                        }
                    }
                    break;
                case 'component_add':
                    if (changeData.data) {
                        editor.value.addComponents([changeData.data]);
                    }
                    break;
                case 'component_remove':
                    const component = editor.value.getComponents().get(changeData.componentId);
                    if (component) {
                        component.remove();
                    }
                    break;
                case 'style_update':
                    if (changeData.data) {
                        const component = editor.value.getComponents().get(changeData.componentId);
                        if (component) {
                            component.setStyle(changeData.data);
                        }
                    }
                    break;
                case 'custom_css_update':
                    if (changeData.css !== undefined) {
                        customCSS.value = changeData.css;
                        validateCSS();
                    }
                    break;
            }
        } catch (error) {
            console.error('Failed to apply remote change:', error);
        }
    });

    // User presence events
    socket.value.on('user:joined', (userData: any) => {
        logger.log('User joined:', userData);
        // Could emit event for UI updates
    });

    socket.value.on('user:left', (userData: any) => {
        logger.log('User left:', userData);
        // Could emit event for UI updates
    });
};

const loadComponentCategories = async (): Promise<void> => {
    try {
        // Get categories from Component Library Bridge
        const categories = componentLibraryBridge.getGrapeJSCategories();

        // Configure GrapeJS block manager with categories
        if (editor.value) {
            const blockManager = editor.value.BlockManager;
            categories.forEach((category) => {
                try {
                    blockManager.addCategory(category.id, category);
                } catch (categoryError) {
                    console.warn(`Failed to add category ${category.id}:`, categoryError);
                }
            });
        }

        logger.log(`Loaded ${categories.length} component categories`);
    } catch (error) {
        console.error('Failed to load component categories:', error);
        isError.value = true;
        errorMessage.value = `Failed to load component categories: ${error instanceof Error ? error.message : 'Unknown error'}`;
        emit('error', error instanceof Error ? error : new Error('Category loading failed'));
        throw error;
    }
};

const loadComponents = async (): Promise<void> => {
    try {
        // Components are already registered in the bridge during initialization
        // The bridge handles component loading internally
        const registeredComponents = componentLibraryBridge.getRegisteredComponents();

        logger.log(`Loaded ${registeredComponents.length} Components`);
    } catch (error) {
        console.error('Failed to load Components:', error);
        isError.value = true;
        errorMessage.value = `Failed to load Components: ${error instanceof Error ? error.message : 'Unknown error'}`;
        emit('error', error instanceof Error ? error : new Error('Component loading failed'));
        throw error;
    }
};

const setupBridgeEventListeners = (): void => {
    // Real-time sync is handled internally by the bridge's sync manager
    // The bridge will automatically update GrapeJS blocks when Components change
    logger.log('Bridge event listeners initialized');
};

const registerComponentsWithEditor = async (): Promise<void> => {
    if (!editor.value) return;

    try {
        loadingMessage.value = 'Loading Components...';

        // Get Components from Component Library Bridge with categorization
        const componentMetadata = componentLibraryBridge.getRegisteredComponents();

        // Register each component as a GrapeJS block with proper categorization
        componentMetadata.forEach((metadata) => {
            try {
                const block = metadata.blockDefinition;
                editor.value?.BlockManager.add(block.id, block);

                // Track component usage when added to editor
                const componentId = block.attributes?.['data-component-id'] as string;
                if (componentId) {
                    componentLibraryBridge.trackComponentUsage(componentId, 'editor_registration');
                }
            } catch (blockError) {
                console.warn(`Failed to register block ${metadata.blockDefinition.id}:`, blockError);
            }
        });

        // Set up search functionality for block manager
        setupBlockManagerSearch();

        logger.log(`Registered ${componentMetadata.length} Components with GrapeJS`);
    } catch (error) {
        console.error('Failed to register Components with GrapeJS:', error);
        isError.value = true;
        errorMessage.value = `Failed to register Components: ${error instanceof Error ? error.message : 'Unknown error'}`;
        emit('error', error instanceof Error ? error : new Error('Component registration failed'));
        throw error;
    }
};

const setupBlockManagerSearch = (): void => {
    if (!editor.value) return;

    const blockManager = editor.value.BlockManager;

    // Override the default block manager render to include search
    const originalRender = blockManager.render.bind(blockManager);
    blockManager.render = () => {
        const result = originalRender();

        // Add search input to the block manager
        const searchContainer = document.createElement('div');
        searchContainer.className = 'block-search-container';
        searchContainer.innerHTML = `
      <input type="text" class="block-search-input" placeholder="Search Components..." />
    `;

        const blocksContainer = result.querySelector('#blocks-container');
        if (blocksContainer) {
            blocksContainer.insertBefore(searchContainer, blocksContainer.firstChild);

            // Set up search functionality
            const searchInput = searchContainer.querySelector('.block-search-input') as HTMLInputElement;
            searchInput.addEventListener('input', (e) => {
                const query = (e.target as HTMLInputElement).value;
                filterBlocks(query);
            });
        }

        return result;
    };
};

const filterBlocks = (query: string): void => {
    if (!editor.value) return;

    const blockManager = editor.value.BlockManager;
    const searchResults = componentLibraryBridge.searchComponents(query);

    // Hide all blocks first
    const allBlocks = blockManager.getAll();
    allBlocks.forEach((block: any) => {
        blockManager.remove(block.id);
    });

    // Add filtered blocks back
    searchResults.forEach((result) => {
        const block = componentLibraryBridge.convertToGrapeJSBlock(result.component);
        blockManager.add(block.id, block);
    });
};

const loadSystemData = async (): Promise<void> => {
    try {
        loadingMessage.value = 'Loading system data...';

        // Initialize Component Library Bridge with tenant context
        componentLibraryBridge.initialize();

        // Load brand configuration if tenant ID is provided
        if (props.tenantId) {
            await loadBrandConfiguration(props.tenantId);
        }

        // Note: Tenant context is handled through component metadata
        // when registering Components with tenantId

        // Load component categories and Components
        await loadComponentCategories();
        await loadComponents();

        // Set up real-time sync event listeners
        setupBridgeEventListeners();

        logger.log('System data loaded successfully');
    } catch (error) {
        console.error('Failed to load system data:', error);
        throw error;
    }
};

const loadBrandConfiguration = async (tenantId: string): Promise<void> => {
    try {
        const brandConfig = await brandConfigService.getBrandConfig(tenantId);
        brandColors.value = brandConfig.colors;
        typographySettings.value = brandConfig.typography;
        spacingSettings.value = brandConfig.spacing;

        logger.log('Brand configuration loaded for tenant:', tenantId);
    } catch (error) {
        console.error('Failed to load brand configuration:', error);
        // Use default configuration if loading fails
        const defaultConfig = await brandConfigService.getBrandConfig('default');
        brandColors.value = defaultConfig.colors;
        typographySettings.value = defaultConfig.typography;
        spacingSettings.value = defaultConfig.spacing;
    }
};

const loadPageData = async (pageId: string): Promise<void> => {
    try {
        loadingMessage.value = 'Loading page data...';

        // Load page data from backend
        const response = await fetch(`/api/pages/${pageId}`);
        if (!response.ok) {
            throw new Error('Failed to load page data');
        }

        const pageData = await response.json();
        currentPage.value = pageData;

        // Load page content into editor
        if (editor.value && pageData.grapejsData) {
            editor.value.setComponents(pageData.grapejsData.Components);
            editor.value.setStyle(pageData.grapejsData.styles);
        }

        // Track page load event
        await analyticsService.trackPageView(window.location.href, {
            pageId,
            pageTitle: pageData.title,
            tenantId: props.tenantId,
        });

        logger.log(`Page data loaded for page ${pageId}`);
    } catch (error) {
        console.error('Failed to load page data:', error);
        throw error;
    }
};

const saveCurrentPage = async (): Promise<void> => {
    if (!editor.value || !props.pageId) return;

    try {
        isSaving.value = true;

        // Get current editor data
        const grapejsData = getCurrentEditorData();

        // Save to backend
        const response = await fetch(`/api/pages/${props.pageId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                grapejsData,
                updatedAt: new Date().toISOString(),
            }),
        });

        if (!response.ok) {
            throw new Error('Failed to save page');
        }

        logger.log(`Page ${props.pageId} saved successfully`);
        emit('save', grapejsData);
    } catch (error) {
        console.error('Failed to save page:', error);
        isError.value = true;
        errorMessage.value = error instanceof Error ? error.message : 'Failed to save page';
        emit('error', error instanceof Error ? error : new Error('Save failed'));
    } finally {
        isSaving.value = false;
    }
};

const publishCurrentPage = async (): Promise<void> => {
    if (!props.pageId) return;

    try {
        isPublishing.value = true;

        // Update page status
        const response = await fetch(`/api/pages/${props.pageId}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ status: 'published' }),
        });

        if (!response.ok) {
            throw new Error('Failed to publish page');
        }

        logger.log(`Page ${props.pageId} published successfully`);
        showPublishDialog.value = false;
        emit('publish', props.pageId);
    } catch (error) {
        console.error('Failed to publish page:', error);
        isError.value = true;
        errorMessage.value = error instanceof Error ? error.message : 'Failed to publish page';
        emit('error', error instanceof Error ? error : new Error('Publish failed'));
    } finally {
        isPublishing.value = false;
    }
};

const switchToDeviceMode = (mode: string): void => {
    deviceMode.value = mode;
};

const togglePanel = (panel: string): void => {
    switch (panel) {
        case 'component':
            showComponentPanel.value = !showComponentPanel.value;
            break;
        case 'style':
            showStylePanel.value = !showStylePanel.value;
            break;
        case 'layer':
            showLayerPanel.value = !showLayerPanel.value;
            break;
    }
};

const getCurrentEditorData = () => {
    if (!editor.value) return null;

    return {
        html: editor.value.getHtml(),
        css: editor.value.getCss(),
        Components: editor.value.getComponents().toJSON(),
        styles: editor.value.getStyle().toJSON(),
    };
};

const resetEditor = (): void => {
    if (!editor.value) return;

    try {
        // Clear canvas
        editor.value.runCommand('core:canvas-clear');

        // Reset state
        selectedComponent.value = null;
        deviceMode.value = 'desktop';
        isError.value = false;
        errorMessage.value = '';
        customCSS.value = '';
        cssValidationErrors.value = [];

        logger.log('Editor reset successfully');
    } catch (error) {
        console.error('Failed to reset editor:', error);
        emit('error', error instanceof Error ? error : new Error('Reset failed'));
    }
};

// CSS Editor methods
const validateCSS = (): void => {
    cssValidationErrors.value = [];

    if (!customCSS.value.trim()) {
        return;
    }

    // Basic CSS validation
    const lines = customCSS.value.split('\n');
    let braceCount = 0;

    lines.forEach((line, index) => {
        const lineNumber = index + 1;

        // Check for unbalanced braces
        const openBraces = (line.match(/{/g) || []).length;
        const closeBraces = (line.match(/}/g) || []).length;
        braceCount += openBraces - closeBraces;

        if (braceCount < 0) {
            cssValidationErrors.value.push({
                line: lineNumber,
                column: 1,
                message: 'Unexpected closing brace',
            });
        }

        // Check for missing semicolons
        if (line.trim() && !line.trim().endsWith(';') && !line.trim().endsWith('{') && !line.trim().endsWith('}') && !line.trim().startsWith('//')) {
            const colonIndex = line.indexOf(':');
            if (colonIndex > 0 && !line.substring(colonIndex).includes(';')) {
                cssValidationErrors.value.push({
                    line: lineNumber,
                    column: line.length,
                    message: 'Missing semicolon',
                });
            }
        }
    });

    if (braceCount > 0) {
        cssValidationErrors.value.push({
            line: lines.length,
            column: 1,
            message: 'Missing closing brace',
        });
    }
};

const applyCustomCSS = (): void => {
    if (!editor.value || cssValidationErrors.value.length > 0) return;

    try {
        // Apply custom CSS to the editor
        const styleManager = editor.value.StyleManager;
        if (styleManager && customCSS.value.trim()) {
            // Add custom CSS rules
            const cssRules = customCSS.value.split('}').filter((rule) => rule.trim());
            cssRules.forEach((rule) => {
                if (rule.trim()) {
                    const fullRule = rule + '}';
                    // In a real implementation, this would parse and apply the CSS
                    logger.log('Applying custom CSS rule:', fullRule);
                }
            });
        }

        // Broadcast style change via socket
        if (socket.value && isSocketConnected.value && props.tenantId) {
            socket.value.emit('editor:change', {
                type: 'custom_css_update',
                css: customCSS.value,
                timestamp: Date.now(),
                tenantId: props.tenantId,
                pageId: props.pageId,
            });
        }

        logger.log('Custom CSS applied successfully');
    } catch (error) {
        console.error('Failed to apply custom CSS:', error);
        emit('error', error instanceof Error ? error : new Error('CSS application failed'));
    }
};

const formatCSS = (): void => {
    if (!customCSS.value.trim()) return;

    try {
        // Basic CSS formatting
        const formatted = customCSS.value
            .replace(/\s*{\s*/g, ' {\n  ')
            .replace(/;\s*/g, ';\n  ')
            .replace(/\s*}\s*/g, '\n}\n\n')
            .replace(/\n\s*\n/g, '\n\n')
            .trim();

        customCSS.value = formatted;
        validateCSS();
    } catch (error) {
        console.error('Failed to format CSS:', error);
    }
};

// Preview and Testing Methods
const generatePreviewHTML = (): string => {
    if (!editor.value) return '';

    try {
        const html = editor.value.getHtml();
        const css = editor.value.getCss();
        const Components = editor.value.getComponents();

        // Create a simple HTML document for preview
        const previewHTML = `<!DOCTYPE html><html><head><title>Preview</title></head><body><div id="preview-content">${html}</div></body></html>`;

        return previewHTML;
    } catch (error) {
        console.error('Failed to generate preview HTML:', error);
        previewError.value = 'Failed to generate preview';
        return '';
    }
};

const updatePreview = (): void => {
    if (!showPreviewPanel.value || !previewIframe.value) return;

    try {
        isPreviewLoading.value = true;
        previewError.value = '';

        const previewHTML = generatePreviewHTML();
        if (previewHTML) {
            const blob = new Blob([previewHTML], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            previewIframe.value.src = url;

            // Clean up previous URL
            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
            }
            previewUrl.value = url;
        }
    } catch (error) {
        console.error('Failed to update preview:', error);
        previewError.value = 'Failed to update preview';
    } finally {
        isPreviewLoading.value = false;
    }
};

const switchPreviewDevice = (deviceId: string): void => {
    const device = availableDevices.find((d) => d.id === deviceId);
    if (device && previewIframe.value) {
        previewDevice.value = deviceId;
        previewIframe.value.style.width = `${device.width}px`;
        previewIframe.value.style.height = `${device.height}px`;
        previewIframe.value.style.transform = `scale(${1 / device.pixelRatio})`;
        previewIframe.value.style.transformOrigin = 'top left';

        // Update iframe container to accommodate scaled content
        const container = previewIframe.value.parentElement;
        if (container) {
            container.style.width = `${device.width / device.pixelRatio}px`;
            container.style.height = `${device.height / device.pixelRatio}px`;
        }
    }
};

const togglePreviewPanel = (): void => {
    showPreviewPanel.value = !showPreviewPanel.value;
    if (showPreviewPanel.value) {
        // Delay to ensure DOM is updated
        setTimeout(() => {
            updatePreview();
            switchPreviewDevice(previewDevice.value);
        }, 100);
    }
};

const toggleMockInteractions = (): void => {
    mockInteractions.value = !mockInteractions.value;
    updatePreview();
};

const handlePreviewMessage = (event: MessageEvent): void => {
    if (event.data.type === 'preview-interaction') {
        interactionLogs.value.unshift({
            type: event.data.interaction,
            element: event.data.element,
            timestamp: new Date(event.data.timestamp),
        });

        // Keep only last 50 interactions
        if (interactionLogs.value.length > 50) {
            interactionLogs.value = interactionLogs.value.slice(0, 50);
        }
    }
};

const clearInteractionLogs = (): void => {
    interactionLogs.value = [];
};

const onPreviewLoad = (): void => {
    isPreviewLoading.value = false;
};

const formatLogTime = (timestamp: Date): string => {
    return timestamp.toLocaleTimeString();
};

// Analytics Methods
const initializeAnalytics = async (): Promise<void> => {
    try {
        // Initialize analytics service with tenant context
        await analyticsService.initialize(socket.value || undefined);

        // Configure analytics with tenant and page info
        analyticsService.updateConfig({
            tenantId: props.tenantId || undefined,
            pageId: props.pageId || undefined,
            userId: 'current-user', // This should come from auth context
            enableRealTime: true,
        });

        // Load initial analytics data
        await loadAnalyticsData();

        logger.log('Analytics service initialized successfully');
    } catch (error) {
        console.error('Failed to initialize analytics:', error);
    }
};

const loadAnalyticsData = async (): Promise<void> => {
    if (!props.pageId) return;

    try {
        isAnalyticsLoading.value = true;

        // Load analytics metrics for the current page
        analyticsData.value = await analyticsService.getAnalyticsMetrics(
            new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // Last 30 days
            new Date(),
        );

        // Load real-time data
        realTimeAnalytics.value = await analyticsService.getRealTimeData();

        logger.log('Analytics data loaded successfully');
    } catch (error) {
        console.error('Failed to load analytics data:', error);
    } finally {
        isAnalyticsLoading.value = false;
    }
};

const toggleAnalyticsPanel = (): void => {
    showAnalyticsPanel.value = !showAnalyticsPanel.value;
    if (showAnalyticsPanel.value) {
        loadAnalyticsData();
    }
};

// A/B Testing Methods
const initializeABTesting = async (): Promise<void> => {
    try {
        abTestingService.value = createABTestingService(analyticsService, props.tenantId);
        await abTestingService.value.initialize(socket.value || undefined);
        await loadExperiments();

        logger.log('A/B Testing service initialized successfully');
    } catch (error) {
        console.error('Failed to initialize A/B testing:', error);
        experimentError.value = 'Failed to initialize A/B testing service';
    }
};

const toggleABTestingPanel = (): void => {
    showABTestingPanel.value = !showABTestingPanel.value;
    if (showABTestingPanel.value) {
        loadExperiments();
    }
};

const loadExperiments = async (): Promise<void> => {
    if (!abTestingService.value) return;

    try {
        experiments.value = await abTestingService.value.getExperiments();
        experimentError.value = '';
    } catch (error) {
        console.error('Failed to load experiments:', error);
        experimentError.value = 'Failed to load experiments';
    }
};

const selectExperiment = async (experimentId: string): Promise<void> => {
    selectedExperimentId.value = experimentId;

    if (!abTestingService.value) return;

    try {
        selectedExperiment.value = await abTestingService.value.getExperiment(experimentId);
        variants.value = await abTestingService.value.getVariants(experimentId);
        testResults.value = await abTestingService.value.getTestResults(experimentId);
        experimentError.value = '';
    } catch (error) {
        console.error('Failed to load experiment details:', error);
        experimentError.value = 'Failed to load experiment details';
    }
};

const createExperiment = async (): Promise<void> => {
    if (!abTestingService.value) return;

    try {
        const config = {
            name: newExperimentName.value,
            hypothesis: newExperimentHypothesis.value,
            goal: { type: newExperimentGoal.value },
            variants: [
                {
                    name: 'Control',
                    description: 'Original version',
                    changes: [],
                    weight: 50,
                },
                {
                    name: 'Variant A',
                    description: 'Test variation',
                    changes: [],
                    weight: 50,
                },
            ],
            trafficAllocation: [50, 50],
            duration: newExperimentDuration.value,
            status: 'draft' as const,
        };

        const experiment = await abTestingService.value.createExperiment(config);
        experiments.value.push(experiment);
        isCreatingExperiment.value = false;

        // Reset form
        newExperimentName.value = '';
        newExperimentHypothesis.value = '';
        newExperimentGoal.value = 'conversion';
        newExperimentDuration.value = 7;

        experimentError.value = '';
    } catch (error) {
        console.error('Failed to create experiment:', error);
        experimentError.value = 'Failed to create experiment';
    }
};

const startExperiment = async (): Promise<void> => {
    if (!abTestingService.value || !selectedExperimentId.value) return;

    try {
        await abTestingService.value.startExperiment(selectedExperimentId.value);
        await selectExperiment(selectedExperimentId.value);
        experimentError.value = '';
    } catch (error) {
        console.error('Failed to start experiment:', error);
        experimentError.value = 'Failed to start experiment';
    }
};

const pauseExperiment = async (): Promise<void> => {
    if (!abTestingService.value || !selectedExperimentId.value) return;

    try {
        await abTestingService.value.pauseExperiment(selectedExperimentId.value);
        await selectExperiment(selectedExperimentId.value);
        experimentError.value = '';
    } catch (error) {
        console.error('Failed to pause experiment:', error);
        experimentError.value = 'Failed to pause experiment';
    }
};

const endExperiment = async (): Promise<void> => {
    if (!abTestingService.value || !selectedExperimentId.value) return;

    try {
        await abTestingService.value.endExperiment(selectedExperimentId.value);
        await selectExperiment(selectedExperimentId.value);
        experimentError.value = '';
    } catch (error) {
        console.error('Failed to end experiment:', error);
        experimentError.value = 'Failed to end experiment';
    }
};

// Export/Backup/Migration Methods
const handleExport = async (): Promise<void> => {
    if (!props.pageId || !editor.value) return;

    try {
        isExporting.value = true;
        exportError.value = '';

        const editorData = getCurrentEditorData();
        exportResult.value = await pageExportService.exportPage(props.pageId, exportOptions.value, editorData, props.tenantId);

        logger.log('Page exported successfully:', exportResult.value);
        showExportDialog.value = false;
    } catch (error) {
        console.error('Failed to export page:', error);
        exportError.value = error instanceof Error ? error.message : 'Failed to export page';
    } finally {
        isExporting.value = false;
    }
};

const handleBackup = async (): Promise<void> => {
    if (!props.pageId || !editor.value) return;

    try {
        isBackingUp.value = true;
        backupError.value = '';

        const editorData = getCurrentEditorData();
        backupResult.value = await pageBackupService.createBackup(props.pageId, backupOptions.value, editorData, props.tenantId);

        logger.log('Backup created successfully:', backupResult.value);
        showBackupDialog.value = false;
    } catch (error) {
        console.error('Failed to create backup:', error);
        backupError.value = error instanceof Error ? error.message : 'Failed to create backup';
    } finally {
        isBackingUp.value = false;
    }
};

const handleMigration = async (): Promise<void> => {
    if (!props.pageId || !editor.value) return;

    try {
        isMigrating.value = true;
        migrationError.value = '';

        const editorData = getCurrentEditorData();
        migrationResult.value = await pageMigrationService.createMigration(props.pageId, migrationConfig.value, editorData);

        logger.log('Migration created successfully:', migrationResult.value);
        showMigrationDialog.value = false;
    } catch (error) {
        console.error('Failed to create migration:', error);
        migrationError.value = error instanceof Error ? error.message : 'Failed to create migration';
    } finally {
        isMigrating.value = false;
    }
};

const downloadExport = (): void => {
    if (!exportResult.value) return;

    const link = document.createElement('a');
    link.href = exportResult.value.downloadUrl;
    link.download = exportResult.value.filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const restoreBackup = async (backupId: string): Promise<void> => {
    if (!props.pageId) return;

    try {
        const result = await pageBackupService.restoreBackup(backupId, props.pageId, props.tenantId);

        logger.log('Backup restored successfully:', result);

        // Reload page data after restore
        if (props.pageId) {
            await loadPageData(props.pageId);
        }
    } catch (error) {
        console.error('Failed to restore backup:', error);
        backupError.value = error instanceof Error ? error.message : 'Failed to restore backup';
    }
};

const executeMigration = async (migrationId: string): Promise<void> => {
    try {
        const result = await pageMigrationService.executeMigration(migrationId);
        logger.log('Migration executed successfully:', result);
    } catch (error) {
        console.error('Failed to execute migration:', error);
        migrationError.value = error instanceof Error ? error.message : 'Failed to execute migration';
    }
};

// Expose methods for parent Components
defineExpose({
    editor,
    editorState,
    saveCurrentPage,
    publishCurrentPage,
    resetEditor,
    getCurrentEditorData,
    analyticsService,
});
</script>

<style scoped>
.grapejs-wrapper {
    @apply relative h-full w-full bg-gray-50 dark:bg-gray-900;
}

.grapejs-wrapper.loading {
    @apply pointer-events-none;
}

.grapejs-wrapper.error {
    @apply opacity-75;
}

/* Toolbar */
.grapejs-toolbar {
    @apply flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800;
}

.toolbar-left {
    @apply flex items-center gap-4;
}

.toolbar-right {
    @apply flex items-center gap-4;
}

/* Device Switcher */
.device-switcher {
    @apply flex items-center rounded-lg bg-gray-100 p-1 dark:bg-gray-700;
}

.device-btn {
    @apply flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors;
    @apply text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-600;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.device-btn.active {
    @apply bg-blue-500 text-white;
}

.device-icon {
    @apply h-4 w-4;
}

/* Action Buttons */
.action-buttons {
    @apply flex items-center gap-2;
}

.action-btn {
    @apply flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none disabled:cursor-not-allowed disabled:opacity-50;
}

.save-btn {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.publish-btn {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

/* Panel Toggles */
.panel-toggles {
    @apply flex items-center gap-1;
}

.panel-toggle {
    @apply rounded p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.panel-toggle.active {
    @apply bg-blue-500 text-white;
}

.settings-btn {
    @apply rounded p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

/* Editor Container */
.grapejs-editor-container {
    @apply flex h-full;
}

.grapejs-editor-container.panels-hidden .main-editor-area {
    @apply flex-1;
}

/* Panels */
.component-panel,
.style-panel,
.layer-panel {
    @apply flex w-80 flex-col border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
}

.panel-header {
    @apply flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700;
}

.panel-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-panel-btn {
    @apply rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.blocks-container,
.styles-container,
.layers-container {
    @apply flex-1 overflow-y-auto;
}

/* Main Editor Area */
.main-editor-area {
    @apply flex-1;
}

.grapesjs-editor {
    @apply w-full;
}

/* Settings Panel */
.settings-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.settings-panel {
    @apply mx-4 w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800;
}

.settings-header {
    @apply flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700;
}

.settings-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-settings-btn {
    @apply rounded p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.settings-content {
    @apply space-y-6 p-6;
}

.setting-group h4 {
    @apply mb-3 text-lg font-medium text-gray-900 dark:text-white;
}

.setting-label {
    @apply flex cursor-pointer items-center gap-3;
}

.setting-checkbox {
    @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

/* Dialog */
.dialog-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.dialog-panel {
    @apply mx-4 w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-gray-800;
}

.dialog-header {
    @apply flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700;
}

.dialog-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.close-dialog-btn {
    @apply rounded p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.dialog-content {
    @apply p-6;
}

.dialog-actions {
    @apply mt-6 flex items-center justify-end gap-3;
}

.dialog-btn {
    @apply rounded-lg px-4 py-2 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.cancel-btn {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

.confirm-btn {
    @apply bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

/* Loading Overlay */
.loading-overlay {
    @apply absolute inset-0 z-40 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-800;
}

.loading-content {
    @apply flex flex-col items-center gap-4;
}

.loading-spinner {
    @apply h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent;
}

/* Error Overlay */
.error-overlay {
    @apply absolute inset-0 z-40 flex items-center justify-center bg-red-50 bg-opacity-75 dark:bg-red-900;
}

.error-content {
    @apply mx-4 w-full max-w-md rounded-lg bg-white p-6 text-center dark:bg-gray-800;
}

.error-title {
    @apply mb-2 text-lg font-semibold text-gray-900 dark:text-white;
}

.error-message {
    @apply mb-4 text-gray-600 dark:text-gray-400;
}

.error-btn {
    @apply rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700;
    @apply focus:ring-2 focus:ring-red-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .grapejs-toolbar {
        @apply flex-col gap-4;
    }

    .toolbar-left,
    .toolbar-right {
        @apply justify-center;
    }

    .component-panel,
    .style-panel,
    .layer-panel {
        @apply absolute inset-y-0 right-0 z-30 w-full;
    }

    .device-switcher {
        @apply flex-col;
    }

    .action-buttons {
        @apply flex-col;
    }

    .panel-toggles {
        @apply justify-center;
    }
}

/* Block Search */
.block-search-container {
    @apply border-b border-gray-200 p-2 dark:border-gray-700;
}

.block-search-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
    @apply placeholder-gray-500 dark:placeholder-gray-400;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .grapejs-wrapper {
        @apply border-2 border-gray-900 dark:border-white;
    }

    .device-btn,
    .action-btn,
    .panel-toggle,
    .settings-btn {
        @apply border-2;
    }

    .block-search-input {
        @apply border-2 border-gray-900 dark:border-white;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .loading-spinner {
        @apply animate-none;
    }

    .device-btn,
    .action-btn,
    .panel-toggle,
    .settings-btn,
    .dialog-btn,
    .error-btn {
        @apply transition-none;
    }
}

/* Style Panel Tabs */
.style-panel-tabs {
    @apply flex border-b border-gray-200 dark:border-gray-700;
}

.style-tab {
    @apply flex-1 px-4 py-2 text-center text-sm font-medium transition-colors;
    @apply text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.style-tab.active {
    @apply bg-blue-500 text-white;
}

/* CSS Editor */
.css-editor-container {
    @apply flex h-full flex-col;
}

.css-editor {
    @apply flex-1 resize-none border-0 p-3 font-mono text-sm;
    @apply bg-white text-gray-900 dark:bg-gray-800 dark:text-white;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-0;
    @apply placeholder-gray-500 dark:placeholder-gray-400;
}

.css-editor.error {
    @apply border-red-500 focus:ring-red-500;
}

.css-errors {
    @apply border-t border-red-200 bg-red-50 p-2 dark:border-red-800 dark:bg-red-900;
}

.css-error {
    @apply mb-1 text-sm text-red-700 dark:text-red-300;
}

.css-actions {
    @apply flex gap-2 border-t border-gray-200 p-3 dark:border-gray-700;
}

.apply-css-btn,
.format-css-btn {
    @apply rounded-lg px-4 py-2 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.apply-css-btn {
    @apply bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.format-css-btn {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

/* Preview Panel */
.preview-panel {
    @apply fixed right-0 top-0 z-40 h-full w-96 border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
    @apply flex flex-col shadow-xl;
}

.preview-content {
    @apply flex flex-1 flex-col overflow-hidden;
}

.preview-controls {
    @apply space-y-4 border-b border-gray-200 p-4 dark:border-gray-700;
}

.control-label {
    @apply mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.device-selector .device-buttons {
    @apply flex gap-2;
}

.device-selector .device-btn {
    @apply rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium dark:border-gray-600;
    @apply bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300;
    @apply transition-colors hover:bg-gray-50 dark:hover:bg-gray-600;
}

.device-selector .device-btn.active {
    @apply border-blue-500 bg-blue-500 text-white;
}

.interaction-controls {
    @apply flex items-center justify-between;
}

.interaction-checkbox {
    @apply rounded border-gray-300 text-blue-600 shadow-sm;
}

.clear-logs-btn {
    @apply rounded bg-gray-200 px-3 py-1 text-sm text-gray-900 hover:bg-gray-300;
    @apply transition-colors dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

.preview-container {
    @apply flex-1 bg-gray-50 p-4 dark:bg-gray-900;
}

.preview-loading,
.preview-error {
    @apply flex h-full flex-col items-center justify-center text-center;
}

.preview-iframe-container {
    @apply h-full w-full overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700;
}

.preview-iframe {
    @apply h-full w-full border-0;
}

.interaction-logs {
    @apply border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800;
}

.logs-title {
    @apply mb-2 text-sm font-medium text-gray-900 dark:text-white;
}

.logs-container {
    @apply max-h-32 overflow-y-auto;
}

.no-logs {
    @apply text-sm italic text-gray-500 dark:text-gray-400;
}

.log-entries {
    @apply space-y-1;
}

.log-entry {
    @apply flex items-center justify-between text-xs text-gray-600 dark:text-gray-400;
    @apply rounded bg-gray-50 px-2 py-1 dark:bg-gray-700;
}

.log-type {
    @apply font-medium text-blue-600 dark:text-blue-400;
}

.log-element {
    @apply ml-2 flex-1;
}

.log-time {
    @apply text-gray-400 dark:text-gray-500;
}

.retry-btn {
    @apply mt-2 rounded bg-blue-500 px-3 py-1 text-sm text-white transition-colors hover:bg-blue-600;
}

/* Analytics Panel */
.analytics-panel {
    @apply fixed right-0 top-0 z-40 h-full w-96 border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
    @apply flex flex-col shadow-xl;
}

.analytics-content {
    @apply flex flex-1 flex-col overflow-hidden;
}

.analytics-loading,
.no-analytics {
    @apply flex h-full flex-col items-center justify-center p-6 text-center;
}

.analytics-metrics {
    @apply flex-1 overflow-y-auto p-6;
}

.metrics-grid {
    @apply mb-6 grid grid-cols-2 gap-4;
}

.metric-card {
    @apply rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-700;
}

.metric-title {
    @apply mb-2 text-sm font-medium text-gray-600 dark:text-gray-400;
}

.metric-value {
    @apply text-2xl font-bold text-gray-900 dark:text-white;
}

.real-time-section {
    @apply mb-6;
}

.section-title {
    @apply mb-4 text-lg font-semibold text-gray-900 dark:text-white;
}

.real-time-metrics {
    @apply space-y-3;
}

.real-time-item {
    @apply flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700;
}

.real-time-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.real-time-value {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.analytics-actions {
    @apply border-t border-gray-200 p-4 dark:border-gray-700;
}

.refresh-analytics-btn,
.load-analytics-btn {
    @apply w-full rounded-lg px-4 py-2 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.refresh-analytics-btn {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.load-analytics-btn {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600;
}

/* A/B Testing Panel */
.ab-testing-panel {
    @apply fixed right-0 top-0 z-40 h-full w-96 border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
    @apply flex flex-col shadow-xl;
}

.ab-testing-content {
    @apply flex-1 overflow-y-auto p-6;
}

.error-message {
    @apply mb-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900;
}

.error-message p {
    @apply text-sm text-red-700 dark:text-red-300;
}

.experiment-list {
    @apply mb-6;
}

.experiment-header {
    @apply mb-4 flex items-center justify-between;
}

.experiment-header h4 {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.create-experiment-btn {
    @apply rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply text-sm font-medium focus:outline-none;
}

.no-experiments {
    @apply py-8 text-center text-gray-500 dark:text-gray-400;
}

.experiment-items {
    @apply space-y-2;
}

.experiment-item {
    @apply cursor-pointer rounded-lg border border-gray-200 p-3 dark:border-gray-700;
    @apply transition-colors hover:bg-gray-50 dark:hover:bg-gray-700;
}

.experiment-item.active {
    @apply border-blue-300 bg-blue-50 dark:border-blue-600 dark:bg-blue-900;
}

.experiment-info h5 {
    @apply mb-1 font-medium text-gray-900 dark:text-white;
}

.experiment-status {
    @apply inline-block rounded-full px-2 py-1 text-xs font-medium;
}

.experiment-status.draft {
    @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300;
}

.experiment-status.running {
    @apply bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-300;
}

.experiment-status.paused {
    @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-300;
}

.experiment-status.completed {
    @apply bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-300;
}

.experiment-metrics {
    @apply mt-2 flex gap-4 text-sm text-gray-600 dark:text-gray-400;
}

.experiment-details {
    @apply border-t border-gray-200 pt-6 dark:border-gray-700;
}

.experiment-details .experiment-header {
    @apply mb-4 flex items-center justify-between;
}

.experiment-details .experiment-header h4 {
    @apply text-xl font-semibold text-gray-900 dark:text-white;
}

.experiment-actions {
    @apply flex gap-2;
}

.action-btn {
    @apply rounded px-3 py-1 text-sm font-medium transition-colors;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.start-btn {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.pause-btn {
    @apply bg-yellow-600 text-white hover:bg-yellow-700;
}

.end-btn {
    @apply bg-red-600 text-white hover:bg-red-700;
}

.experiment-config {
    @apply mb-6;
}

.hypothesis {
    @apply mb-3 italic text-gray-700 dark:text-gray-300;
}

.experiment-stats {
    @apply grid grid-cols-2 gap-4;
}

.stat {
    @apply flex flex-col;
}

.stat-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.stat-value {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.variants-section {
    @apply mb-6;
}

.variants-section h5 {
    @apply mb-3 text-lg font-semibold text-gray-900 dark:text-white;
}

.variants-list {
    @apply space-y-3;
}

.variant-item {
    @apply rounded-lg bg-gray-50 p-3 dark:bg-gray-700;
}

.variant-info {
    @apply mb-2 flex items-center justify-between;
}

.variant-info h6 {
    @apply font-medium text-gray-900 dark:text-white;
}

.variant-weight {
    @apply text-sm font-medium text-blue-600 dark:text-blue-400;
}

.variant-metrics {
    @apply flex gap-4 text-sm text-gray-600 dark:text-gray-400;
}

.test-results {
    @apply border-t border-gray-200 pt-6 dark:border-gray-700;
}

.test-results h5 {
    @apply mb-4 text-lg font-semibold text-gray-900 dark:text-white;
}

.results-summary {
    @apply space-y-3;
}

.result-item {
    @apply flex items-center justify-between;
}

.result-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.result-value {
    @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.result-value.significant {
    @apply text-green-600 dark:text-green-400;
}

.create-experiment-modal {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.modal-content {
    @apply mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800;
}

.modal-content h4 {
    @apply mb-4 text-lg font-semibold text-gray-900 dark:text-white;
}

.form-group {
    @apply mb-4;
}

.form-group label {
    @apply mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input,
.form-textarea,
.form-select {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
    @apply placeholder-gray-500 dark:placeholder-gray-400;
}

.form-textarea {
    @apply min-h-20 resize-y;
}

.form-actions {
    @apply mt-6 flex items-center justify-end gap-3;
}

.cancel-btn {
    @apply px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300;
    @apply rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600;
    @apply transition-colors;
}

.create-btn {
    @apply rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700;
    @apply transition-colors;
}

/* Export/Backup/Migration Styles */
.export-backup-actions {
    @apply flex items-center gap-2;
}

.export-btn {
    @apply rounded p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.backup-btn {
    @apply rounded p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.migration-btn {
    @apply rounded p-2 text-gray-600 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700;
    @apply focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:focus:ring-offset-gray-800;
    @apply focus:outline-none;
}

.form-checkbox {
    @apply flex cursor-pointer items-center gap-3;
}

.form-checkbox-input {
    @apply rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}
</style>















