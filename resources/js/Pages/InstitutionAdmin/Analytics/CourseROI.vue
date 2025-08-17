<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';

const loading = ref(true);
const roiData = ref([]);

async function fetchData() {
  try {
    loading.value = true;
    const response = await axios.get(route('institution-admin.api.analytics.course-roi'));
    roiData.value = response.data;
  } catch (error) {
    console.error('Failed to fetch course ROI analytics:', error);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchData();
});
</script>

<template>
  <AdminLayout title="Course ROI Analytics">
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Course & Program ROI</CardTitle>
          <CardDescription>
            Return on investment analysis for your institution's courses, based on graduate salaries.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="text-center">
            <p>Loading ROI data...</p>
          </div>
          <div v-else-if="!roiData || roiData.length === 0" class="text-center">
            <p>No course ROI data available yet.</p>
          </div>
          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Course</TableHead>
                  <TableHead>Total Graduates</TableHead>
                  <TableHead>Average Salary</TableHead>
                  <TableHead>Estimated 5-Year ROI</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="course in roiData" :key="course.course_name">
                  <TableCell class="font-medium">{{ course.course_name }}</TableCell>
                  <TableCell>{{ course.total_graduates }}</TableCell>
                  <TableCell>${{ course.average_salary.toLocaleString() }}</TableCell>
                  <TableCell>
                    <span :class="{
                      'text-green-600': course.estimated_roi_percentage > 100,
                      'text-yellow-600': course.estimated_roi_percentage >= 0 && course.estimated_roi_percentage <= 100,
                      'text-red-600': course.estimated_roi_percentage < 0,
                    }">
                      {{ course.estimated_roi_percentage }}%
                    </span>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
