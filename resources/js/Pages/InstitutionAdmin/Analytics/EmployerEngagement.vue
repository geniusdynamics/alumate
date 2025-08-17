<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';

const loading = ref(true);
const engagementData = ref(null);

async function fetchData() {
  try {
    loading.value = true;
    const response = await axios.get(route('institution-admin.api.analytics.employer-engagement'));
    engagementData.value = response.data;
  } catch (error) {
    console.error('Failed to fetch employer engagement analytics:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchData();
});
</script>

<template>
  <AdminLayout title="Employer Engagement Analytics">
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Employer Engagement</CardTitle>
          <CardDescription>
            Insights into employer activity and in-demand skills on the platform.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="text-center">
            <p>Loading engagement data...</p>
          </div>
          <div v-else-if="!engagementData" class="text-center">
            <p>No employer engagement data available yet.</p>
          </div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Engaging Employers -->
            <Card>
              <CardHeader><CardTitle>Top Engaging Employers</CardTitle></CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Company</TableHead>
                      <TableHead>Jobs Posted</TableHead>
                      <TableHead>Hires</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="employer in engagementData.top_engaging_employers" :key="employer.company_name">
                      <TableCell>{{ employer.company_name }}</TableCell>
                      <TableCell>{{ employer.jobs_posted }}</TableCell>
                      <TableCell>{{ employer.total_hires }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </CardContent>
            </Card>

            <!-- Most In-Demand Skills -->
            <Card>
              <CardHeader><CardTitle>Most In-Demand Skills</CardTitle></CardHeader>
              <CardContent>
                 <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Skill</TableHead>
                      <TableHead>Times Listed in Job Posts</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="skill in engagementData.most_in_demand_skills" :key="skill.skill">
                      <TableCell>{{ skill.skill }}</TableCell>
                      <TableCell>{{ skill.count }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </CardContent>
            </Card>

            <!-- Hiring Trends by Industry -->
            <Card class="lg:col-span-2">
              <CardHeader><CardTitle>Hiring Trends by Industry</CardTitle></CardHeader>
              <CardContent>
                 <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Industry</TableHead>
                      <TableHead>Total Hires</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="industry in engagementData.hiring_trends_by_industry" :key="industry.industry">
                      <TableCell>{{ industry.industry }}</TableCell>
                      <TableCell>{{ industry.hires }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </CardContent>
            </Card>

          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
