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
    <Head title="Privacy Policy" />
    
    <div class="container mx-auto px-4 py-12 max-w-4xl">
      <h1 class="text-4xl font-bold mb-4">Privacy Policy</h1>
      <p class="text-gray-600 mb-8">Last Updated: {{ lastUpdated }}</p>
      
      <div class="prose prose-lg max-w-none">
        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">1. Introduction</h2>
          <p>
            Alumate ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains 
            how we collect, use, disclose, and safeguard your information when you use our alumni networking platform.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">2. Information We Collect</h2>
          
          <h3 class="text-xl font-medium mb-2">2.1 Personal Information</h3>
          <p>We may collect the following personal information:</p>
          <ul class="list-disc pl-6 mb-4">
            <li>Name and contact information (email, phone number)</li>
            <li>Educational background (institution, graduation year, degree)</li>
            <li>Professional information (employer, job title, industry)</li>
            <li>Profile information (bio, skills, interests)</li>
            <li>Location information</li>
            <li>Profile photos</li>
          </ul>

          <h3 class="text-xl font-medium mb-2">2.2 Usage Information</h3>
          <p>We automatically collect certain information about your use of the Service:</p>
          <ul class="list-disc pl-6 mb-4">
            <li>IP address and device information</li>
            <li>Browser type and operating system</li>
            <li>Pages visited and features used</li>
            <li>Time spent on the platform</li>
            <li>Click patterns and interactions</li>
          </ul>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">3. How We Use Your Information</h2>
          <p>We use your information to:</p>
          <ul class="list-disc pl-6 mb-4">
            <li>Provide, maintain, and improve our services</li>
            <li>Connect you with other alumni and networking opportunities</li>
            <li>Facilitate mentorship and career development</li>
            <li>Send you notifications and updates</li>
            <li>Analyze usage patterns to enhance user experience</li>
            <li>Ensure security and prevent fraud</li>
            <li>Comply with legal obligations</li>
          </ul>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">4. Information Sharing</h2>
          <p>We may share your information with:</p>
          <ul class="list-disc pl-6 mb-4">
            <li><strong>Your Institution:</strong> To facilitate alumni relations and verify your affiliation</li>
            <li><strong>Other Users:</strong> Information visible on your public profile</li>
            <li><strong>Service Providers:</strong> Third-party vendors who assist in operating our platform</li>
            <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
          </ul>
          <p>We do not sell your personal information to third parties.</p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">5. Your Rights</h2>
          <p>Depending on your location, you may have the following rights:</p>
          <ul class="list-disc pl-6 mb-4">
            <li><strong>Access:</strong> Request a copy of your personal information</li>
            <li><strong>Correction:</strong> Update or correct inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal information</li>
            <li><strong>Portability:</strong> Receive your data in a structured format</li>
            <li><strong>Restriction:</strong> Limit how we use your information</li>
            <li><strong>Objection:</strong> Object to certain processing activities</li>
          </ul>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">6. Data Security</h2>
          <p>
            We implement appropriate technical and organizational measures to protect your personal information, 
            including encryption, access controls, and regular security assessments. However, no method of 
            transmission over the internet is completely secure.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">7. Data Retention</h2>
          <p>
            We retain your personal information for as long as your account is active or as needed to provide 
            you services. We may also retain information as required by law or for legitimate business purposes.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">8. International Transfers</h2>
          <p>
            Your information may be transferred to and processed in countries other than your country of residence. 
            These countries may have different data protection laws. We ensure appropriate safeguards are in place 
            to protect your information.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">9. Children's Privacy</h2>
          <p>
            Our Service is not intended for individuals under the age of 13. We do not knowingly collect personal 
            information from children under 13. If you become aware that a child has provided us with personal 
            information, please contact us.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">10. Changes to This Policy</h2>
          <p>
            We may update this Privacy Policy from time to time. We will notify you of any significant changes 
            by posting the updated policy on our website with a new "Last Updated" date.
          </p>
        </section>

        <section class="mb-8">
          <h2 class="text-2xl font-semibold mb-4">11. Contact Us</h2>
          <p>
            If you have any questions about this Privacy Policy or our data practices, please contact us:
            <br />
            <strong>Email:</strong> privacy@alumate.com
            <br />
            <strong>Data Protection Officer:</strong> dpo@alumate.com
          </p>
        </section>
      </div>
    </div>
  </DefaultLayout>
</template>
