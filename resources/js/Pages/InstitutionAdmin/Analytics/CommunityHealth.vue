<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import axios from 'axios';

const loading = ref(true);
const healthData = ref(null);

async function fetchData() {
  try {
    loading.value = true;
    const response = await axios.get(route('institution-admin.api.analytics.community-health'));
    healthData.value = response.data;
  } catch (error) {
    console.error('Failed to fetch community health analytics:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchData();
});
</script>

<template>
  <AdminLayout title="Community Health Analytics">
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Community Health</CardTitle>
          <CardDescription>
            An overview of user activity and engagement in your community.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="text-center">
            <p>Loading community health data...</p>
          </div>
          <div v-else-if="!healthData" class="text-center">
            <p>No community health data available yet.</p>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Daily Active Users -->
            <Card>
              <CardHeader><CardTitle>Daily Active Users</CardTitle></CardHeader>
              <CardContent>
                <!-- Chart would go here -->
                <p class="text-2xl font-bold">{{ healthData.daily_active_users.reduce((acc, curr) => acc + curr.count, 0) }}</p>
                <p class="text-sm text-muted-foreground">Total active users in last 30 days</p>
              </CardContent>
            </Card>

            <!-- Post Activity -->
            <Card>
              <CardHeader><CardTitle>Post Activity</CardTitle></CardHeader>
              <CardContent>
                 <p class="text-2xl font-bold">{{ healthData.post_activity.reduce((acc, curr) => acc + curr.count, 0) }}</p>
                 <p class="text-sm text-muted-foreground">Total posts in last 30 days</p>
              </CardContent>
            </Card>

            <!-- Connections Made -->
             <Card>
              <CardHeader><CardTitle>New Connections</CardTitle></CardHeader>
              <CardContent>
                 <p class="text-2xl font-bold">{{ healthData.connections_made }}</p>
                 <p class="text-sm text-muted-foreground">Connections made in last 30 days</p>
              </CardContent>
            </Card>

          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
