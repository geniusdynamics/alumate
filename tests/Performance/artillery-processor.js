/**
 * Artillery Processor for Analytics Load Testing
 *
 * Provides custom functions and logic for Artillery load testing
 * of analytics endpoints with tenant isolation and proper data handling.
 */

const moment = require('moment');

module.exports = {
  // Custom function to format dates for API queries
  dateFormat: function(context, events, done) {
    const date = context.vars.date || '2024-01-01';
    const formatted = moment(date).format('YYYY-MM-DD');
    context.vars.formattedDate = formatted;
    return done();
  },

  // Custom function to generate tenant-specific headers
  setTenantHeader: function(context, events, done) {
    const tenantId = context.vars.tenant_id || 'default';
    context.vars.tenantHeader = {
      'X-Tenant-ID': tenantId,
      'X-API-Key': `test-key-${tenantId}`
    };
    return done();
  },

  // Custom function to simulate realistic user behavior
  simulateUserBehavior: function(context, events, done) {
    // Randomly decide user actions based on typical analytics usage patterns
    const behaviors = [
      { action: 'view_dashboard', weight: 40 },
      { action: 'view_insights', weight: 30 },
      { action: 'view_metrics', weight: 20 },
      { action: 'export_data', weight: 10 }
    ];

    const random = Math.random() * 100;
    let cumulativeWeight = 0;

    for (const behavior of behaviors) {
      cumulativeWeight += behavior.weight;
      if (random <= cumulativeWeight) {
        context.vars.userAction = behavior.action;
        break;
      }
    }

    return done();
  },

  // Custom function to generate realistic query parameters
  generateQueryParams: function(context, events, done) {
    const periods = ['daily', 'weekly', 'monthly', 'quarterly'];
    const groupBys = ['day', 'week', 'month'];
    const metrics = [
      'engagement',
      'completion',
      'certification',
      'engagement,completion',
      'engagement,completion,certification'
    ];

    context.vars.queryParams = {
      period: periods[Math.floor(Math.random() * periods.length)],
      group_by: groupBys[Math.floor(Math.random() * groupBys.length)],
      metrics: metrics[Math.floor(Math.random() * metrics.length)],
      include_trends: Math.random() > 0.5,
      include_anomalies: Math.random() > 0.7,
      date_from: moment().subtract(Math.floor(Math.random() * 90), 'days').format('YYYY-MM-DD'),
      date_to: moment().format('YYYY-MM-DD')
    };

    return done();
  },

  // Custom function to validate response data structure
  validateResponseStructure: function(context, events, done) {
    const response = context.vars.response || {};

    // Basic validation of expected response structure
    const requiredFields = ['data', 'meta'];
    const hasRequiredFields = requiredFields.every(field => response.hasOwnProperty(field));

    if (!hasRequiredFields) {
      events.emit('error', new Error('Response missing required fields'));
      return done();
    }

    // Validate data integrity
    if (response.data && typeof response.data === 'object') {
      context.vars.responseValid = true;
    } else {
      context.vars.responseValid = false;
      events.emit('counter', 'invalid_responses', 1);
    }

    return done();
  },

  // Custom function to track performance metrics
  trackPerformanceMetrics: function(context, events, done) {
    const responseTime = context.vars.responseTime || 0;
    const statusCode = context.vars.statusCode || 0;

    // Track response time distribution
    if (responseTime < 100) {
      events.emit('counter', 'fast_responses', 1);
    } else if (responseTime < 500) {
      events.emit('counter', 'normal_responses', 1);
    } else if (responseTime < 1000) {
      events.emit('counter', 'slow_responses', 1);
    } else {
      events.emit('counter', 'very_slow_responses', 1);
    }

    // Track status codes
    if (statusCode >= 200 && statusCode < 300) {
      events.emit('counter', 'success_responses', 1);
    } else if (statusCode >= 400 && statusCode < 500) {
      events.emit('counter', 'client_error_responses', 1);
    } else if (statusCode >= 500) {
      events.emit('counter', 'server_error_responses', 1);
    }

    return done();
  },

  // Custom function to simulate tenant switching
  simulateTenantSwitching: function(context, events, done) {
    const tenants = ['tenant_1', 'tenant_2', 'tenant_3', 'default'];
    const currentTenant = context.vars.current_tenant || 'default';

    // 10% chance to switch tenants
    if (Math.random() < 0.1) {
      const newTenant = tenants[Math.floor(Math.random() * tenants.length)];
      context.vars.current_tenant = newTenant;
      context.vars.tenant_switched = true;
      events.emit('counter', 'tenant_switches', 1);
    } else {
      context.vars.tenant_switched = false;
    }

    return done();
  },

  // Custom function to generate cache-busting parameters
  generateCacheBuster: function(context, events, done) {
    context.vars.cache_buster = Date.now() + Math.random();
    return done();
  },

  // Custom function to simulate realistic think times
  realisticThinkTime: function(context, events, done) {
    // Simulate user think time based on typical analytics usage
    const thinkTimes = [1000, 2000, 3000, 5000, 8000]; // milliseconds
    const weights = [0.4, 0.3, 0.15, 0.1, 0.05]; // probability weights

    const random = Math.random();
    let cumulativeWeight = 0;

    for (let i = 0; i < thinkTimes.length; i++) {
      cumulativeWeight += weights[i];
      if (random <= cumulativeWeight) {
        context.vars.think_time = thinkTimes[i];
        break;
      }
    }

    return done();
  },

  // Custom function to handle rate limiting simulation
  simulateRateLimiting: function(context, events, done) {
    const requestCount = context.vars.request_count || 0;
    context.vars.request_count = requestCount + 1;

    // Simulate rate limiting after 100 requests per minute
    if (requestCount > 100 && Math.random() < 0.1) {
      context.vars.rate_limited = true;
      events.emit('counter', 'rate_limited_requests', 1);
    } else {
      context.vars.rate_limited = false;
    }

    return done();
  },

  // Custom function to validate analytics data integrity
  validateAnalyticsData: function(context, events, done) {
    const response = context.vars.response || {};
    const data = response.data || {};

    // Validate common analytics data patterns
    const validations = {
      total_users: (val) => typeof val === 'number' && val >= 0,
      total_events: (val) => typeof val === 'number' && val >= 0,
      avg_session_duration: (val) => typeof val === 'number' && val >= 0,
      completion_rate: (val) => typeof val === 'number' && val >= 0 && val <= 100,
    };

    let dataValid = true;
    for (const [field, validator] of Object.entries(validations)) {
      if (data.hasOwnProperty(field) && !validator(data[field])) {
        dataValid = false;
        events.emit('counter', `invalid_${field}`, 1);
        break;
      }
    }

    context.vars.analytics_data_valid = dataValid;

    if (!dataValid) {
      events.emit('counter', 'invalid_analytics_data', 1);
    }

    return done();
  }
};