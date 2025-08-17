<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/components/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';

const props = defineProps<{
  institution: {
    id: number;
    name: string;
    logo_path: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    feature_flags: { [key: string]: boolean } | null;
  };
}>();

const form = useForm({
  logo: null as File | null,
  primary_color: props.institution.primary_color || '#000000',
  secondary_color: props.institution.secondary_color || '#ffffff',
  feature_flags: {
    enable_social_timeline: props.institution.feature_flags?.enable_social_timeline ?? true,
    enable_job_board: props.institution.feature_flags?.enable_job_board ?? true,
    enable_events: props.institution.feature_flags?.enable_events ?? true,
    enable_fundraising: props.institution.feature_flags?.enable_fundraising ?? false,
  },
});

const logoPreview = ref<string | null>(props.institution.logo_path ? `/storage/${props.institution.logo_path}` : null);

function handleLogoChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.logo = target.files[0];
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(form.logo);
  }
}

function submit() {
  form.post(route('institution-admin.settings.branding.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Maybe show a toast notification
    },
  });
}
</script>

<template>
  <AdminLayout title="Branding Settings">
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Branding &amp; Customization</CardTitle>
          <CardDescription>Customize the look and feel of your institution's portal.</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Branding Section -->
              <div class="space-y-6">
                <div>
                  <Label for="logo">Logo</Label>
                  <Input id="logo" type="file" @change="handleLogoChange" class="mt-1" />
                  <div v-if="logoPreview" class="mt-4">
                    <img :src="logoPreview" alt="Logo Preview" class="h-20 w-auto bg-gray-100 p-2 rounded-md" />
                  </div>
                </div>

                <div>
                  <Label for="primary_color">Primary Color</Label>
                  <div class="relative">
                    <Input v-model="form.primary_color" id="primary_color" type="text" class="mt-1 pl-12" />
                    <input type="color" v-model="form.primary_color" class="absolute left-2 top-1/2 -translate-y-1/2 h-8 w-8 p-0 border-none">
                  </div>
                </div>

                <div>
                  <Label for="secondary_color">Secondary Color</Label>
                   <div class="relative">
                    <Input v-model="form.secondary_color" id="secondary_color" type="text" class="mt-1 pl-12" />
                    <input type="color" v-model="form.secondary_color" class="absolute left-2 top-1/2 -translate-y-1/2 h-8 w-8 p-0 border-none">
                  </div>
                </div>
              </div>

              <!-- Feature Flags Section -->
              <div class="space-y-6">
                 <CardTitle>Feature Toggles</CardTitle>
                 <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <Label for="enable_social_timeline" class="font-medium">Social Timeline</Label>
                        <Switch id="enable_social_timeline" v-model:checked="form.feature_flags.enable_social_timeline" />
                    </div>
                     <div class="flex items-center justify-between rounded-lg border p-4">
                        <Label for="enable_job_board" class="font-medium">Job Board</Label>
                        <Switch id="enable_job_board" v-model:checked="form.feature_flags.enable_job_board" />
                    </div>
                     <div class="flex items-center justify-between rounded-lg border p-4">
                        <Label for="enable_events" class="font-medium">Events</Label>
                        <Switch id="enable_events" v-model:checked="form.feature_flags.enable_events" />
                    </div>
                     <div class="flex items-center justify-between rounded-lg border p-4">
                        <Label for="enable_fundraising" class="font-medium">Fundraising</Label>
                        <Switch id="enable_fundraising" v-model:checked="form.feature_flags.enable_fundraising" />
                    </div>
                 </div>
              </div>
            </div>

            <div class="flex justify-end">
              <Button type="submit" :disabled="form.processing">Save Changes</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
