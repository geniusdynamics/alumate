<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

interface Props {
  lastUpdated: string;
}

defineProps<Props>();
</script>

<template>
  <DefaultLayout>
    <Head title="Terms of Service" />
    
    <div class="container mx-auto px-4 py-12 max-w-4xl">
      <h1 class="text-4xl font-bold mb-4">Terms of Service</h1>
      <p class="text-gray-600 mb-8">Last Updated: {{ lastUpdated }}</p>
      
      <div class="prose prose-lg max-w-none">
        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">1. Acceptance of Terms</h2>
          <p>
            By accessing or using Alumate ("the Service"), you agree to be bound by these Terms of Service ("Terms"). 
            If you disagree with any part of the terms, you may not access the Service.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">2. Description of Service</h2>
          <p>
            Alumate provides an alumni networking platform that enables educational institutions to connect with their 
            alumni, manage events, facilitate mentorship, and support career development. The Service includes web 
            applications, mobile applications, APIs, and related services.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">3. User Accounts</h2>
          <p>
            You must provide accurate and complete information when creating an account. You are responsible for 
            safeguarding your password and for all activities that occur under your account. You agree to notify us 
            immediately of any unauthorized access to your account.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">4. Acceptable Use</h2>
          <p>You agree not to use the Service to:</p>
          <ul class="list-disc pl-6 mb-4">
            <li>Violate any applicable laws or regulations</li>
            <li>Infringe on the rights of others</li>
            <li>Transmit any harmful, threatening, or offensive content</li>
            <li>Engage in unauthorized data collection or harvesting</li>
            <li>Interfere with the proper functioning of the Service</li>
            <li>Attempt to gain unauthorized access to any part of the Service</li>
          </ul>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">5. Content</h2>
          <p>
            You retain ownership of any content you post to the Service. By posting content, you grant us a 
            non-exclusive, worldwide, royalty-free license to use, reproduce, and display your content in connection 
            with providing the Service.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">6. Privacy</h2>
          <p>
            Your use of the Service is also governed by our Privacy Policy. Please review our Privacy Policy to 
            understand how we collect, use, and protect your personal information.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">7. Termination</h2>
          <p>
            We may terminate or suspend your account immediately, without prior notice or liability, for any reason 
            whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the 
            Service will immediately cease.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">8. Limitation of Liability</h2>
          <p>
            In no event shall Alumate be liable for any indirect, incidental, special, consequential, or punitive 
            damages, including without limitation, loss of profits, data, or goodwill, arising out of or in connection 
            with your access to or use of the Service.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">9. Changes to Terms</h2>
          <p>
            We reserve the right to modify these Terms at any time. We will provide notice of significant changes 
            by posting the updated Terms on the Service with a new "Last Updated" date. Your continued use of the 
            Service after such modifications constitutes your acceptance of the updated Terms.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">10. Contact Information</h2>
          <p>
            If you have any questions about these Terms, please contact us at:
            <br />
            <strong>Email:</strong> legal@alumate.com
            <br />
            <strong>Address:</strong> Alumate Inc., 123 Education Street, Tech City, TC 12345
          </p>
        </section>
      </div>
    </div>
  </DefaultLayout>
</template>
