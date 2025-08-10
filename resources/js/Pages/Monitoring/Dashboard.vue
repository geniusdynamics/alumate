<template>
  <div class="monitoring-dashboard">
    <div class="dashboard-header">
      <h1>Homepage Monitoring Dashboard</h1>
      <div class="last-updated">
        Last updated: {{ formatTime(lastUpdated) }}
        <button @click="refreshData" :disabled="loading" class="refresh-btn">
          <span v-if="loading">Refreshing...</span>
          <span v-else>Refresh</span>
        </button>
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Uptime Status -->
      <div class="dashboard-card">
        <h2>Uptime Status</h2>
        <div class="uptime-grid">
          <div 
            v-for="(endpoint, name) in dashboardData.uptime" 
            :key="name"
            :class="['uptime-item', endpoint.status]"
          >
            <div class="endpoint-name">{{ formatEndpointName(name) }}</div>
            <div class="endpoint-status">
              <span :class="['status-badge', endpoint.status]">
                {{ endpoint.status.toUpperCase() }}
              </span>
              <span v-if="endpoint.response_time" class="response-time">
                {{ endpoint.response_time }}ms
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- System Health -->
      <div class="dashboard-card">
        <h2>System Health</h2>
        <div class="health-grid">
          <div 
            v-for="(check, name) in dashboardData.system_health" 
            :key="name"
            :class="['health-item', check.status || 'unknown']"
          >
            <div class="health-name">{{ formatHealthName(name) }}</div