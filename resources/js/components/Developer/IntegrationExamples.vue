<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Integration Examples
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Common integration patterns and use cases with complete code examples
      </p>
    </div>

    <div class="p-6">
      <!-- Use Case Selection -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Select Integration Use Case
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <button
            v-for="useCase in useCases"
            :key="useCase.id"
            @click="selectedUseCase = useCase"
            :class="[
              'p-4 border rounded-lg text-left transition-colors',
              selectedUseCase?.id === useCase.id
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="flex items-center gap-3 mb-2">
              <component :is="useCase.icon" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
              <div class="font-medium text-gray-900 dark:text-white">
                {{ useCase.title }}
              </div>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              {{ useCase.description }}
            </div>
            <div class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="tech in useCase.technologies"
                :key="tech"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
              >
                {{ tech }}
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- Selected Use Case Details -->
      <div v-if="selectedUseCase" class="space-y-6">
        <!-- Overview -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
            {{ selectedUseCase.title }} Integration
          </h4>
          <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
            {{ selectedUseCase.overview }}
          </p>
          
          <!-- Requirements -->
          <div class="mb-3">
            <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">
              Requirements:
            </h5>
            <ul class="text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
              <li v-for="req in selectedUseCase.requirements" :key="req">{{ req }}</li>
            </ul>
          </div>
          
          <!-- Estimated Implementation Time -->
          <div class="flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Estimated implementation time: {{ selectedUseCase.estimatedTime }}</span>
          </div>
        </div>

        <!-- Implementation Steps -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
            Implementation Steps
          </h4>
          
          <div class="space-y-4">
            <div
              v-for="(step, index) in selectedUseCase.steps"
              :key="index"
              class="border border-gray-200 dark:border-gray-600 rounded-lg"
            >
              <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-xs font-medium rounded-full">
                      {{ index + 1 }}
                    </span>
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ step.title }}
                    </span>
                  </div>
                  <button
                    @click="step.expanded = !step.expanded"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                  >
                    {{ step.expanded ? 'Collapse' : 'Expand' }}
                  </button>
                </div>
              </div>
              
              <div v-if="step.expanded" class="p-4 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ step.description }}
                </p>
                
                <!-- Code Example -->
                <div v-if="step.code">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ step.codeLanguage || 'Code' }}
                    </span>
                    <button
                      @click="copyToClipboard(step.code)"
                      class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                    >
                      Copy Code
                    </button>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ step.code }}</code></pre>
                  </div>
                </div>
                
                <!-- Configuration -->
                <div v-if="step.config">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      Configuration
                    </span>
                    <button
                      @click="copyToClipboard(step.config)"
                      class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                    >
                      Copy Config
                    </button>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <pre class="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"><code>{{ step.config }}</code></pre>
                  </div>
                </div>
                
                <!-- Notes -->
                <div v-if="step.notes" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                  <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                      <strong>Note:</strong> {{ step.notes }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Testing & Validation -->
        <div v-if="selectedUseCase.testing">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
            Testing & Validation
          </h4>
          
          <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <h5 class="text-sm font-medium text-green-800 dark:text-green-200 mb-3">
              Test Your Integration
            </h5>
            
            <div class="space-y-3">
              <div v-for="test in selectedUseCase.testing" :key="test.name">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-green-700 dark:text-green-300">
                    {{ test.name }}
                  </span>
                  <button
                    @click="runTest(test)"
                    class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200 font-medium"
                  >
                    Run Test
                  </button>
                </div>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                  {{ test.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Common Issues & Troubleshooting -->
        <div v-if="selectedUseCase.troubleshooting">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
            Common Issues & Troubleshooting
          </h4>
          
          <div class="space-y-3">
            <div
              v-for="issue in selectedUseCase.troubleshooting"
              :key="issue.problem"
              class="border border-gray-200 dark:border-gray-600 rounded-lg p-4"
            >
              <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                {{ issue.problem }}
              </h5>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                {{ issue.cause }}
              </p>
              <div class="text-sm text-green-700 dark:text-green-300">
                <strong>Solution:</strong> {{ issue.solution }}
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Resources -->
        <div v-if="selectedUseCase.resources">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
            Additional Resources
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a
              v-for="resource in selectedUseCase.resources"
              :key="resource.title"
              :href="resource.url"
              target="_blank"
              class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <component :is="resource.icon" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              <div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ resource.title }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                  {{ resource.description }}
                </div>
              </div>
              <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { 
  CodeBracketIcon, 
  BellIcon, 
  ChartBarIcon, 
  UserGroupIcon,
  CogIcon,
  DocumentTextIcon,
  BookOpenIcon,
  VideoCameraIcon
} from '@heroicons/vue/24/outline'

const selectedUseCase = ref(null)

const useCases = [
  {
    id: 'real-time-notifications',
    title: 'Real-time Notifications',
    description: 'Implement real-time notifications for user activities',
    icon: BellIcon,
    technologies: ['WebSockets', 'Webhooks', 'Push API'],
    overview: 'Set up real-time notifications to keep users engaged with instant updates about connections, posts, events, and other activities.',
    estimatedTime: '2-4 hours',
    requirements: [
      'Alumni Platform API access',
      'Webhook endpoint capability',
      'Push notification service (optional)',
      'WebSocket support (for real-time updates)'
    ],
    steps: [
      {
        title: 'Set up Webhook Endpoint',
        description: 'Create a secure webhook endpoint to receive real-time events from the Alumni Platform.',
        expanded: false,
        codeLanguage: 'Node.js/Express',
        code: `const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json());

// Webhook endpoint
app.post('/webhooks/alumni-platform', (req, res) => {
  const signature = req.headers['x-signature'];
  const payload = JSON.stringify(req.body);
  
  // Verify webhook signature
  if (!verifySignature(payload, signature, process.env.WEBHOOK_SECRET)) {
    return res.status(401).send('Unauthorized');
  }
  
  const { event, data } = req.body;
  
  // Handle different event types
  switch (event) {
    case 'post.created':
      handlePostCreated(data);
      break;
    case 'user.connected':
      handleUserConnected(data);
      break;
    case 'event.registered':
      handleEventRegistration(data);
      break;
    default:
      console.log('Unknown event type:', event);
  }
  
  res.status(200).send('OK');
});

function verifySignature(payload, signature, secret) {
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(payload, 'utf8')
    .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}

function handlePostCreated(data) {
  // Send notification to relevant users
  console.log('New post created:', data.content);
  
  // Example: Send push notification
  sendPushNotification({
    title: 'New Post',
    body: \`\${data.user.name} shared an update\`,
    data: { postId: data.id }
  });
}

function handleUserConnected(data) {
  // Notify users about new connections
  console.log('Users connected:', data.user.name, data.connected_user.name);
}

function handleEventRegistration(data) {
  // Send confirmation and reminders
  console.log('Event registration:', data.event.title);
}

app.listen(3000, () => {
  console.log('Webhook server running on port 3000');
});`,
        notes: 'Make sure to use HTTPS in production and store your webhook secret securely.'
      },
      {
        title: 'Register Webhook with Alumni Platform',
        description: 'Register your webhook endpoint with the Alumni Platform API.',
        expanded: false,
        codeLanguage: 'JavaScript',
        code: `const alumniApi = require('@alumni-platform/api-client');

const api = new alumniApi({
  baseURL: 'https://your-alumni-platform.com/api',
  token: process.env.ALUMNI_API_TOKEN
});

async function registerWebhook() {
  try {
    const webhook = await api.webhooks.create({
      url: 'https://your-app.com/webhooks/alumni-platform',
      events: [
        'post.created',
        'user.connected',
        'event.registered',
        'donation.completed'
      ],
      secret: process.env.WEBHOOK_SECRET
    });
    
    console.log('Webhook registered:', webhook.id);
    return webhook;
  } catch (error) {
    console.error('Failed to register webhook:', error);
  }
}

registerWebhook();`,
        notes: 'Store the webhook ID for future reference and management.'
      },
      {
        title: 'Implement Push Notifications',
        description: 'Set up push notifications for mobile and web clients.',
        expanded: false,
        codeLanguage: 'JavaScript',
        code: `const webpush = require('web-push');

// Configure web push
webpush.setVapidDetails(
  'mailto:your-email@example.com',
  process.env.VAPID_PUBLIC_KEY,
  process.env.VAPID_PRIVATE_KEY
);

// Store user subscriptions
const subscriptions = new Map();

// Subscribe endpoint
app.post('/api/push/subscribe', (req, res) => {
  const { userId, subscription } = req.body;
  subscriptions.set(userId, subscription);
  res.json({ success: true });
});

// Send push notification
async function sendPushNotification(notification) {
  const payload = JSON.stringify({
    title: notification.title,
    body: notification.body,
    icon: '/icon-192x192.png',
    badge: '/badge-72x72.png',
    data: notification.data
  });
  
  // Send to all subscribed users
  const promises = [];
  for (const [userId, subscription] of subscriptions) {
    promises.push(
      webpush.sendNotification(subscription, payload)
        .catch(error => {
          console.error('Push notification failed for user', userId, error);
          // Remove invalid subscriptions
          if (error.statusCode === 410) {
            subscriptions.delete(userId);
          }
        })
    );
  }
  
  await Promise.all(promises);
}

// Client-side service worker
// sw.js
self.addEventListener('push', event => {
  const data = event.data.json();
  
  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge,
    data: data.data,
    actions: [
      {
        action: 'view',
        title: 'View'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});`
      }
    ],
    testing: [
      {
        name: 'Webhook Delivery Test',
        description: 'Test that your webhook endpoint receives and processes events correctly'
      },
      {
        name: 'Push Notification Test',
        description: 'Verify push notifications are delivered to subscribed devices'
      },
      {
        name: 'Signature Verification Test',
        description: 'Ensure webhook signatures are properly validated for security'
      }
    ],
    troubleshooting: [
      {
        problem: 'Webhook not receiving events',
        cause: 'URL not reachable or returning non-200 status codes',
        solution: 'Verify your webhook URL is publicly accessible and returns 200 OK for POST requests'
      },
      {
        problem: 'Push notifications not working',
        cause: 'Invalid VAPID keys or subscription expired',
        solution: 'Check VAPID configuration and handle subscription renewal gracefully'
      }
    ],
    resources: [
      {
        title: 'Webhook Security Guide',
        description: 'Best practices for webhook security',
        url: '#',
        icon: DocumentTextIcon
      },
      {
        title: 'Push API Documentation',
        description: 'Complete guide to web push notifications',
        url: '#',
        icon: BookOpenIcon
      }
    ]
  },
  
  {
    id: 'analytics-dashboard',
    title: 'Analytics Dashboard',
    description: 'Build custom analytics dashboards with Alumni Platform data',
    icon: ChartBarIcon,
    technologies: ['React', 'Chart.js', 'REST API'],
    overview: 'Create comprehensive analytics dashboards to track alumni engagement, career outcomes, and platform usage metrics.',
    estimatedTime: '4-8 hours',
    requirements: [
      'Alumni Platform API access with analytics permissions',
      'Frontend framework (React, Vue, Angular)',
      'Charting library (Chart.js, D3.js, etc.)',
      'Data visualization components'
    ],
    steps: [
      {
        title: 'Set up API Client',
        description: 'Initialize the Alumni Platform API client with proper authentication.',
        expanded: false,
        codeLanguage: 'React',
        code: `import { AlumniPlatformAPI } from '@alumni-platform/api-client';
import { createContext, useContext, useEffect, useState } from 'react';

const ApiContext = createContext();

export function ApiProvider({ children }) {
  const [api, setApi] = useState(null);
  
  useEffect(() => {
    const apiClient = new AlumniPlatformAPI({
      baseURL: process.env.REACT_APP_API_URL,
      token: localStorage.getItem('api_token')
    });
    
    setApi(apiClient);
  }, []);
  
  return (
    <ApiContext.Provider value={api}>
      {children}
    </ApiContext.Provider>
  );
}

export const useApi = () => {
  const api = useContext(ApiContext);
  if (!api) {
    throw new Error('useApi must be used within ApiProvider');
  }
  return api;
};`
      },
      {
        title: 'Create Analytics Hook',
        description: 'Build a custom React hook to fetch and manage analytics data.',
        expanded: false,
        codeLanguage: 'React Hook',
        code: `import { useState, useEffect } from 'react';
import { useApi } from './ApiProvider';

export function useAnalytics(period = '30d', refreshInterval = 300000) {
  const api = useApi();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  const fetchAnalytics = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const [engagement, careers, fundraising] = await Promise.all([
        api.analytics.getEngagement({ period }),
        api.analytics.getCareerOutcomes({ period }),
        api.analytics.getFundraisingMetrics({ period })
      ]);
      
      setData({
        engagement: engagement.data,
        careers: careers.data,
        fundraising: fundraising.data,
        lastUpdated: new Date()
      });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };
  
  useEffect(() => {
    fetchAnalytics();
    
    // Set up auto-refresh
    const interval = setInterval(fetchAnalytics, refreshInterval);
    return () => clearInterval(interval);
  }, [period, refreshInterval]);
  
  return { data, loading, error, refresh: fetchAnalytics };
}`
      },
      {
        title: 'Build Dashboard Components',
        description: 'Create reusable dashboard components for different metrics.',
        expanded: false,
        codeLanguage: 'React Component',
        code: `import { Line, Bar, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

export function EngagementChart({ data }) {
  const chartData = {
    labels: data.trends.daily_active_users.map(d => d.date),
    datasets: [
      {
        label: 'Daily Active Users',
        data: data.trends.daily_active_users.map(d => d.count),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      }
    ]
  };
  
  const options = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top'
      },
      title: {
        display: true,
        text: 'User Engagement Trends'
      }
    },
    scales: {
      y: {
        beginAtZero: true
      }
    }
  };
  
  return <Line data={chartData} options={options} />;
}

export function MetricCard({ title, value, change, icon: Icon }) {
  const isPositive = change > 0;
  
  return (
    <div className="bg-white p-6 rounded-lg shadow">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-3xl font-bold text-gray-900">{value}</p>
          <div className="flex items-center mt-2">
            <span className={\`text-sm font-medium \${
              isPositive ? 'text-green-600' : 'text-red-600'
            }\`}>
              {isPositive ? '+' : ''}{change}%
            </span>
            <span className="text-sm text-gray-500 ml-1">vs last period</span>
          </div>
        </div>
        <div className="p-3 bg-blue-50 rounded-full">
          <Icon className="w-6 h-6 text-blue-600" />
        </div>
      </div>
    </div>
  );
}

export function AnalyticsDashboard() {
  const { data, loading, error } = useAnalytics('30d');
  
  if (loading) return <div>Loading analytics...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!data) return null;
  
  return (
    <div className="space-y-6">
      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <MetricCard
          title="Active Users"
          value={data.engagement.summary.total_active_users.toLocaleString()}
          change={12.5}
          icon={UserGroupIcon}
        />
        <MetricCard
          title="Posts Created"
          value={data.engagement.summary.posts_created}
          change={8.3}
          icon={DocumentTextIcon}
        />
        <MetricCard
          title="Connections Made"
          value={data.engagement.summary.connections_made}
          change={15.7}
          icon={UserGroupIcon}
        />
        <MetricCard
          title="Events Attended"
          value={data.engagement.summary.events_attended}
          change={-2.1}
          icon={CalendarIcon}
        />
      </div>
      
      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <EngagementChart data={data.engagement} />
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <CareerOutcomesChart data={data.careers} />
        </div>
      </div>
    </div>
  );
}`
      }
    ],
    testing: [
      {
        name: 'API Connection Test',
        description: 'Verify API credentials and data access permissions'
      },
      {
        name: 'Chart Rendering Test',
        description: 'Test chart components with sample data'
      },
      {
        name: 'Real-time Updates Test',
        description: 'Verify dashboard updates with fresh data'
      }
    ]
  },
  
  {
    id: 'user-sync',
    title: 'User Data Synchronization',
    description: 'Sync user data between Alumni Platform and external systems',
    icon: UserGroupIcon,
    technologies: ['REST API', 'Webhooks', 'Cron Jobs'],
    overview: 'Keep user data synchronized between the Alumni Platform and your existing systems like CRM, HR platforms, or student information systems.',
    estimatedTime: '3-6 hours',
    requirements: [
      'Alumni Platform API access',
      'External system API access',
      'Database or data storage',
      'Scheduled job capability'
    ],
    steps: [
      {
        title: 'Set up Data Mapping',
        description: 'Define how data fields map between systems.',
        expanded: false,
        codeLanguage: 'JavaScript',
        code: `// Data mapping configuration
const fieldMapping = {
  // Alumni Platform -> External System
  'name': 'full_name',
  'email': 'email_address',
  'graduation_year': 'grad_year',
  'current_company': 'employer',
  'current_title': 'job_title',
  'location': 'city_state',
  'industry': 'industry_sector',
  'skills': 'skill_tags',
  'bio': 'biography',
  'avatar_url': 'profile_image_url'
};

// Reverse mapping for external -> Alumni Platform
const reverseMapping = Object.fromEntries(
  Object.entries(fieldMapping).map(([k, v]) => [v, k])
);

function mapAlumniToExternal(alumniData) {
  const mapped = {};
  
  for (const [alumniField, externalField] of Object.entries(fieldMapping)) {
    if (alumniData[alumniField] !== undefined) {
      mapped[externalField] = alumniData[alumniField];
    }
  }
  
  // Handle special transformations
  if (alumniData.skills && Array.isArray(alumniData.skills)) {
    mapped.skill_tags = alumniData.skills.join(',');
  }
  
  return mapped;
}

function mapExternalToAlumni(externalData) {
  const mapped = {};
  
  for (const [externalField, alumniField] of Object.entries(reverseMapping)) {
    if (externalData[externalField] !== undefined) {
      mapped[alumniField] = externalData[externalField];
    }
  }
  
  // Handle special transformations
  if (externalData.skill_tags && typeof externalData.skill_tags === 'string') {
    mapped.skills = externalData.skill_tags.split(',').map(s => s.trim());
  }
  
  return mapped;
}`
      },
      {
        title: 'Implement Sync Service',
        description: 'Create a service to handle bidirectional data synchronization.',
        expanded: false,
        codeLanguage: 'Node.js',
        code: `const { AlumniPlatformAPI } = require('@alumni-platform/api-client');
const ExternalAPI = require('./external-api-client');

class UserSyncService {
  constructor() {
    this.alumniApi = new AlumniPlatformAPI({
      baseURL: process.env.ALUMNI_API_URL,
      token: process.env.ALUMNI_API_TOKEN
    });
    
    this.externalApi = new ExternalAPI({
      baseURL: process.env.EXTERNAL_API_URL,
      apiKey: process.env.EXTERNAL_API_KEY
    });
    
    this.lastSyncTime = null;
  }
  
  async syncFromAlumniPlatform() {
    console.log('Starting sync from Alumni Platform...');
    
    try {
      // Get updated users since last sync
      const params = this.lastSyncTime ? 
        { updated_since: this.lastSyncTime } : {};
      
      const response = await this.alumniApi.users.getUpdated(params);
      const users = response.data;
      
      console.log(\`Found \${users.length} updated users\`);
      
      for (const user of users) {
        await this.syncUserToExternal(user);
      }
      
      this.lastSyncTime = new Date().toISOString();
      console.log('Sync from Alumni Platform completed');
      
    } catch (error) {
      console.error('Sync from Alumni Platform failed:', error);
      throw error;
    }
  }
  
  async syncUserToExternal(alumniUser) {
    try {
      const externalData = mapAlumniToExternal(alumniUser);
      
      // Check if user exists in external system
      const existingUser = await this.externalApi.users.findByEmail(
        alumniUser.email
      );
      
      if (existingUser) {
        // Update existing user
        await this.externalApi.users.update(existingUser.id, externalData);
        console.log(\`Updated user \${alumniUser.email} in external system\`);
      } else {
        // Create new user
        await this.externalApi.users.create(externalData);
        console.log(\`Created user \${alumniUser.email} in external system\`);
      }
      
    } catch (error) {
      console.error(\`Failed to sync user \${alumniUser.email}:\`, error);
    }
  }
  
  async syncFromExternal() {
    console.log('Starting sync from external system...');
    
    try {
      const response = await this.externalApi.users.getUpdated({
        since: this.lastSyncTime
      });
      
      const users = response.data;
      console.log(\`Found \${users.length} updated users in external system\`);
      
      for (const user of users) {
        await this.syncUserToAlumni(user);
      }
      
      console.log('Sync from external system completed');
      
    } catch (error) {
      console.error('Sync from external system failed:', error);
      throw error;
    }
  }
  
  async syncUserToAlumni(externalUser) {
    try {
      const alumniData = mapExternalToAlumni(externalUser);
      
      // Find user in Alumni Platform by email
      const existingUser = await this.alumniApi.users.findByEmail(
        externalUser.email_address
      );
      
      if (existingUser) {
        // Update existing user
        await this.alumniApi.users.update(existingUser.id, alumniData);
        console.log(\`Updated user \${externalUser.email_address} in Alumni Platform\`);
      } else {
        // Create new user (if allowed)
        await this.alumniApi.users.create(alumniData);
        console.log(\`Created user \${externalUser.email_address} in Alumni Platform\`);
      }
      
    } catch (error) {
      console.error(\`Failed to sync user \${externalUser.email_address}:\`, error);
    }
  }
  
  async performFullSync() {
    console.log('Starting full bidirectional sync...');
    
    await this.syncFromAlumniPlatform();
    await this.syncFromExternal();
    
    console.log('Full sync completed');
  }
}

module.exports = UserSyncService;`
      },
      {
        title: 'Schedule Sync Jobs',
        description: 'Set up automated synchronization using cron jobs or task schedulers.',
        expanded: false,
        codeLanguage: 'Node.js + Cron',
        code: `const cron = require('node-cron');
const UserSyncService = require('./user-sync-service');

const syncService = new UserSyncService();

// Run incremental sync every 15 minutes
cron.schedule('*/15 * * * *', async () => {
  console.log('Running incremental sync...');
  try {
    await syncService.performFullSync();
  } catch (error) {
    console.error('Scheduled sync failed:', error);
    // Send alert to monitoring system
    await sendAlert('User sync failed', error.message);
  }
});

// Run full sync daily at 2 AM
cron.schedule('0 2 * * *', async () => {
  console.log('Running daily full sync...');
  try {
    // Reset last sync time for full sync
    syncService.lastSyncTime = null;
    await syncService.performFullSync();
  } catch (error) {
    console.error('Daily full sync failed:', error);
    await sendAlert('Daily sync failed', error.message);
  }
});

// Health check endpoint
app.get('/sync/health', (req, res) => {
  res.json({
    status: 'healthy',
    lastSync: syncService.lastSyncTime,
    uptime: process.uptime()
  });
});

// Manual sync trigger
app.post('/sync/trigger', async (req, res) => {
  try {
    await syncService.performFullSync();
    res.json({ success: true, message: 'Sync completed' });
  } catch (error) {
    res.status(500).json({ 
      success: false, 
      error: error.message 
    });
  }
});

async function sendAlert(title, message) {
  // Implement your alerting mechanism
  // e.g., Slack, email, PagerDuty, etc.
  console.error(\`ALERT: \${title} - \${message}\`);
}`
      }
    ],
    testing: [
      {
        name: 'Data Mapping Test',
        description: 'Verify field mappings work correctly for sample data'
      },
      {
        name: 'Sync Direction Test',
        description: 'Test both directions of synchronization'
      },
      {
        name: 'Error Handling Test',
        description: 'Test behavior when APIs are unavailable or return errors'
      }
    ]
  }
];

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    // Show success message
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const runTest = (test) => {
  console.log('Running test:', test.name)
  // Implement test execution logic
}
</script>