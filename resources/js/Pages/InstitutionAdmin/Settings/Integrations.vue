<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
// import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
  settings: {
    email?: { apiKey?: string; fromEmail?: string; };
    calendar?: { type?: string; apiKey?: string; };
    sso?: { type?: string; clientId?: string; clientSecret?: string; };
    crm?: { type?: string; apiKey?: string; apiUrl?: string; };
  };
}>();

const form = useForm({
  integrations: {
    email: {
      apiKey: props.settings.email?.apiKey || '',
      fromEmail: props.settings.email?.fromEmail || '',
    },
    calendar: {
      type: props.settings.calendar?.type || 'google',
      apiKey: props.settings.calendar?.apiKey || '',
    },
    sso: {
      type: props.settings.sso?.type || 'saml',
      clientId: props.settings.sso?.clientId || '',
      clientSecret: props.settings.sso?.clientSecret || '',
    },
    crm: {
      type: props.settings.crm?.type || 'salesforce',
      apiKey: props.settings.crm?.apiKey || '',
      apiUrl: props.settings.crm?.apiUrl || '',
    },
  },
});

function submit() {
  form.post(route('institution-admin.settings.integrations.update'), {
    preserveScroll: true,
  });
}
</script>

<template>
  <AdminLayout title="Integration Settings">
    <form @submit.prevent="submit">
      <Card>
        <CardHeader>
          <CardTitle>External System Integrations</CardTitle>
          <CardDescription>Connect your institution's portal to other services.</CardDescription>
        </CardHeader>
        <CardContent>
          <div default-value="email" class="w-full">
            <div class="grid w-full grid-cols-4">
              <button value="email">Email</button>
              <button value="calendar">Calendar</button>
              <button value="sso">SSO</button>
              <button value="crm">CRM</button>
            </div>

            <div value="email" class="mt-6">
              <div class="space-y-4">
                <div>
                  <Label for="email-api-key">API Key (e.g., Mailgun, SendGrid)</Label>
                  <Input id="email-api-key" v-model="form.integrations.email.apiKey" class="mt-1" />
                </div>
                <div>
                  <Label for="email-from">"From" Email Address</Label>
                  <Input id="email-from" type="email" v-model="form.integrations.email.fromEmail" class="mt-1" />
                </div>
              </div>
            </div>

            <div value="calendar" class="mt-6">
               <div class="space-y-4">
                <div>
                  <Label for="calendar-type">Calendar Type</Label>
                  <Input id="calendar-type" v-model="form.integrations.calendar.type" class="mt-1" placeholder="e.g., Google, Outlook" />
                </div>
                <div>
                  <Label for="calendar-api-key">API Key</Label>
                  <Input id="calendar-api-key" v-model="form.integrations.calendar.apiKey" class="mt-1" />
                </div>
              </div>
            </div>

            <div value="sso" class="mt-6">
               <div class="space-y-4">
                 <div>
                  <Label for="sso-type">SSO Provider</Label>
                  <Input id="sso-type" v-model="form.integrations.sso.type" class="mt-1" placeholder="e.g., SAML, OAuth2" />
                </div>
                <div>
                  <Label for="sso-client-id">Client ID</Label>
                  <Input id="sso-client-id" v-model="form.integrations.sso.clientId" class="mt-1" />
                </div>
                 <div>
                  <Label for="sso-client-secret">Client Secret</Label>
                  <Input id="sso-client-secret" type="password" v-model="form.integrations.sso.clientSecret" class="mt-1" />
                </div>
              </div>
            </div>

            <div value="crm" class="mt-6">
               <div class="space-y-4">
                 <div>
                  <Label for="crm-type">CRM Provider</Label>
                  <Input id="crm-type" v-model="form.integrations.crm.type" class="mt-1" placeholder="e.g., Salesforce, HubSpot" />
                </div>
                <div>
                  <Label for="crm-api-key">API Key</Label>
                  <Input id="crm-api-key" v-model="form.integrations.crm.apiKey" class="mt-1" />
                </div>
                 <div>
                  <Label for="crm-api-url">API URL</Label>
                  <Input id="crm-api-url" v-model="form.integrations.crm.apiUrl" class="mt-1" />
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

       <div class="flex justify-end mt-6">
          <Button type="submit" :disabled="form.processing">Save Integration Settings</Button>
        </div>
    </form>
  </AdminLayout>
</template>