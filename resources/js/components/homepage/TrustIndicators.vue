<template>
  <div>
    <!-- Security and Privacy Section -->
    <SecurityPrivacy 
      :audience="audience"
      :privacy-highlights="privacyHighlights"
      :security-certifications="securityCertifications"
      :verification-process="verificationProcess"
      :data-protection="dataProtection"
      :compliance-info="complianceInfo"
    />
    
    <!-- Integration and Ecosystem Section -->
    <IntegrationEcosystem 
      :audience="audience"
      :integrations="integrations"
      :api-documentation="apiDocumentation"
      :migration-support="migrationSupport"
      :training-programs="trainingPrograms"
      :scalability-info="scalabilityInfo"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import SecurityPrivacy from './SecurityPrivacy.vue'
import IntegrationEcosystem from './IntegrationEcosystem.vue'
import type { 
  AudienceType,
  PrivacyHighlight,
  SecurityCertification,
  VerificationProcess,
  DataProtectionInfo,
  ComplianceInfo,
  PlatformIntegration,
  APIDocumentation,
  MigrationSupport,
  TrainingProgram,
  ScalabilityInfo
} from '@/types/homepage'

interface Props {
  audience: AudienceType
}

const props = defineProps<Props>()

// Mock data for security and privacy
const privacyHighlights = computed<PrivacyHighlight[]>(() => [
  {
    id: '1',
    title: 'Data Minimization',
    description: 'We only collect data necessary for platform functionality and your professional networking goals.',
    icon: 'fas fa-shield-alt',
    details: [
      'No unnecessary personal information collected',
      'Optional data fields for enhanced features',
      'Regular data audits and cleanup',
      'Transparent data usage policies'
    ],
    learnMoreUrl: '/privacy-policy'
  },
  {
    id: '2',
    title: 'User Control',
    description: 'Complete control over your profile visibility, data sharing, and communication preferences.',
    icon: 'fas fa-user-cog',
    details: [
      'Granular privacy settings',
      'Easy data export and deletion',
      'Control over profile visibility',
      'Opt-out options for all communications'
    ],
    learnMoreUrl: '/privacy-settings'
  },
  {
    id: '3',
    title: 'Secure Communications',
    description: 'All communications are encrypted and protected with industry-standard security measures.',
    icon: 'fas fa-lock',
    details: [
      'End-to-end encryption for messages',
      'Secure file sharing capabilities',
      'Protected video conferencing',
      'No third-party access to conversations'
    ]
  }
])

const securityCertifications = computed<SecurityCertification[]>(() => [
  {
    id: '1',
    name: 'SOC 2 Type II',
    badge: '/images/certifications/soc2.png',
    description: 'Audited security controls for data protection and availability.',
    verificationUrl: 'https://verify.soc2.com/example',
    category: 'security'
  },
  {
    id: '2',
    name: 'ISO 27001',
    badge: '/images/certifications/iso27001.png',
    description: 'International standard for information security management.',
    verificationUrl: 'https://verify.iso.com/example',
    category: 'security'
  },
  {
    id: '3',
    name: 'GDPR Compliant',
    badge: '/images/certifications/gdpr.png',
    description: 'Full compliance with European data protection regulations.',
    category: 'privacy'
  },
  {
    id: '4',
    name: 'CCPA Compliant',
    badge: '/images/certifications/ccpa.png',
    description: 'California Consumer Privacy Act compliance certification.',
    category: 'privacy'
  },
  {
    id: '5',
    name: 'FERPA Compliant',
    badge: '/images/certifications/ferpa.png',
    description: 'Educational records privacy protection for academic institutions.',
    category: 'compliance'
  },
  {
    id: '6',
    name: 'TrustArc Certified',
    badge: '/images/certifications/trustarc.png',
    description: 'Third-party privacy and data governance certification.',
    category: 'privacy'
  }
])

const verificationProcess = computed<VerificationProcess>(() => ({
  title: 'Alumni Verification Process',
  description: 'Our multi-step verification ensures authentic connections and maintains the integrity of your professional network.',
  steps: [
    {
      id: '1',
      stepNumber: 1,
      title: 'Institution Verification',
      description: 'Verify your educational background through official records.',
      icon: 'fas fa-university',
      estimatedTime: '2-3 days',
      required: true
    },
    {
      id: '2',
      stepNumber: 2,
      title: 'Identity Confirmation',
      description: 'Confirm your identity through secure document verification.',
      icon: 'fas fa-id-card',
      estimatedTime: '1-2 days',
      required: true
    },
    {
      id: '3',
      stepNumber: 3,
      title: 'Professional Validation',
      description: 'Optional validation of current professional status.',
      icon: 'fas fa-briefcase',
      estimatedTime: '1-2 days',
      required: false
    },
    {
      id: '4',
      stepNumber: 4,
      title: 'Profile Activation',
      description: 'Complete profile setup and network access activation.',
      icon: 'fas fa-check-circle',
      estimatedTime: 'Instant',
      required: true
    }
  ],
  benefits: [
    'Access to verified alumni network',
    'Enhanced credibility and trust',
    'Priority in search results',
    'Exclusive verified-only events',
    'Advanced networking features'
  ],
  requirements: [
    'Valid educational credentials',
    'Government-issued photo ID',
    'Current email address',
    'Professional references (optional)'
  ]
}))

const dataProtection = computed<DataProtectionInfo>(() => ({
  title: 'Data Protection & Privacy',
  description: 'We implement comprehensive data protection measures based on privacy-by-design principles and international best practices.',
  principles: [
    {
      id: '1',
      title: 'Transparency',
      description: 'Clear, understandable privacy policies and data usage explanations.',
      icon: 'fas fa-eye',
      implementation: [
        'Plain language privacy notices',
        'Regular policy updates and notifications',
        'Data usage dashboards',
        'Audit logs available to users'
      ]
    },
    {
      id: '2',
      title: 'Purpose Limitation',
      description: 'Data is only used for specified, legitimate purposes.',
      icon: 'fas fa-target',
      implementation: [
        'Clear purpose statements for data collection',
        'No secondary use without consent',
        'Regular purpose reviews and updates',
        'Automated purpose compliance checks'
      ]
    },
    {
      id: '3',
      title: 'Data Security',
      description: 'Robust technical and organizational security measures.',
      icon: 'fas fa-shield-alt',
      implementation: [
        'AES-256 encryption at rest and in transit',
        'Multi-factor authentication',
        'Regular security audits and penetration testing',
        'Incident response and breach notification procedures'
      ]
    }
  ],
  userRights: [
    {
      id: '1',
      right: 'Right to Access',
      description: 'Request a copy of all personal data we hold about you.',
      howToExercise: 'Submit request through privacy settings or contact privacy team',
      responseTime: 'Within 30 days'
    },
    {
      id: '2',
      right: 'Right to Rectification',
      description: 'Correct inaccurate or incomplete personal data.',
      howToExercise: 'Update directly in profile settings or contact support',
      responseTime: 'Immediate for self-service, 7 days for assisted'
    },
    {
      id: '3',
      right: 'Right to Erasure',
      description: 'Request deletion of your personal data under certain conditions.',
      howToExercise: 'Submit deletion request through account settings',
      responseTime: 'Within 30 days'
    },
    {
      id: '4',
      right: 'Right to Portability',
      description: 'Receive your data in a structured, machine-readable format.',
      howToExercise: 'Use data export feature in account settings',
      responseTime: 'Immediate download available'
    }
  ],
  contactInfo: {
    email: 'privacy@alumniplatform.com',
    phone: '+1-800-PRIVACY',
    hours: 'Monday-Friday, 9 AM - 6 PM EST'
  }
}))

const complianceInfo = computed<ComplianceInfo[]>(() => [
  {
    id: '1',
    standard: 'GDPR',
    description: 'Full compliance with European General Data Protection Regulation for all EU users.',
    badge: '/images/compliance/gdpr-badge.png',
    certificationDate: new Date('2023-05-25'),
    scope: [
      'Data processing activities',
      'User consent management',
      'Data subject rights',
      'Cross-border data transfers',
      'Breach notification procedures'
    ],
    auditFrequency: 'Annual third-party audit'
  },
  {
    id: '2',
    standard: 'CCPA',
    description: 'California Consumer Privacy Act compliance for all California residents.',
    badge: '/images/compliance/ccpa-badge.png',
    certificationDate: new Date('2023-01-01'),
    scope: [
      'Consumer rights implementation',
      'Data sale opt-out mechanisms',
      'Privacy policy disclosures',
      'Third-party data sharing controls'
    ],
    auditFrequency: 'Semi-annual compliance review'
  },
  {
    id: '3',
    standard: 'FERPA',
    description: 'Family Educational Rights and Privacy Act compliance for educational institutions.',
    badge: '/images/compliance/ferpa-badge.png',
    certificationDate: new Date('2023-08-15'),
    scope: [
      'Educational record protection',
      'Directory information handling',
      'Consent management for disclosures',
      'Audit trail maintenance'
    ],
    auditFrequency: 'Annual educational compliance audit'
  }
])

// Mock data for integrations and ecosystem
const integrations = computed<PlatformIntegration[]>(() => [
  {
    id: '1',
    name: 'Salesforce',
    category: 'crm',
    logo: '/images/integrations/salesforce.png',
    description: 'Sync alumni data with your Salesforce CRM for comprehensive relationship management.',
    features: [
      'Bi-directional data synchronization',
      'Custom field mapping',
      'Automated lead scoring',
      'Event attendance tracking',
      'Donation history integration'
    ],
    setupComplexity: 'medium',
    documentation: '/docs/integrations/salesforce',
    supportLevel: 'premium',
    pricing: {
      type: 'paid',
      cost: 99,
      billingPeriod: 'monthly',
      setupFee: 500
    },
    screenshots: [
      '/images/integrations/salesforce-1.png',
      '/images/integrations/salesforce-2.png'
    ]
  },
  {
    id: '2',
    name: 'Mailchimp',
    category: 'email',
    logo: '/images/integrations/mailchimp.png',
    description: 'Seamlessly integrate with Mailchimp for targeted alumni email campaigns.',
    features: [
      'Automated list synchronization',
      'Segmentation based on alumni data',
      'Event-triggered campaigns',
      'Engagement analytics',
      'Template customization'
    ],
    setupComplexity: 'easy',
    documentation: '/docs/integrations/mailchimp',
    supportLevel: 'standard',
    pricing: {
      type: 'free'
    }
  },
  {
    id: '3',
    name: 'Zoom',
    category: 'events',
    logo: '/images/integrations/zoom.png',
    description: 'Integrate Zoom for virtual events and networking sessions.',
    features: [
      'Automatic meeting creation',
      'Registration synchronization',
      'Recording management',
      'Attendance tracking',
      'Breakout room automation'
    ],
    setupComplexity: 'easy',
    documentation: '/docs/integrations/zoom',
    supportLevel: 'standard',
    pricing: {
      type: 'free'
    }
  }
])

const apiDocumentation = computed<APIDocumentation>(() => ({
  title: 'Developer API',
  description: 'Comprehensive REST API for custom integrations and third-party applications.',
  version: 'v2.1',
  baseUrl: 'https://api.alumniplatform.com/v2',
  authentication: [
    {
      type: 'api_key',
      description: 'Simple API key authentication for server-to-server requests.',
      implementation: 'Include API key in Authorization header',
      security: ['Rate limiting', 'IP whitelisting', 'Key rotation']
    },
    {
      type: 'oauth2',
      description: 'OAuth 2.0 for user-authorized applications.',
      implementation: 'Standard OAuth 2.0 authorization code flow',
      security: ['PKCE support', 'Refresh tokens', 'Scope-based permissions']
    }
  ],
  endpoints: [
    {
      id: '1',
      method: 'GET',
      path: '/alumni',
      description: 'Retrieve alumni profiles with filtering and pagination.',
      parameters: [
        { name: 'page', type: 'integer', required: false, description: 'Page number for pagination' },
        { name: 'limit', type: 'integer', required: false, description: 'Number of results per page' }
      ],
      responses: [
        { status: 200, description: 'Success', schema: 'AlumniList', example: '{"data": [...], "meta": {...}}' }
      ],
      examples: ['curl -H "Authorization: Bearer token" /api/v2/alumni']
    }
  ],
  sdks: [
    {
      language: 'JavaScript',
      name: '@alumni-platform/sdk',
      version: '2.1.0',
      documentation: '/docs/sdk/javascript',
      repository: 'https://github.com/alumni-platform/js-sdk',
      examples: ['npm install @alumni-platform/sdk']
    },
    {
      language: 'Python',
      name: 'alumni-platform-python',
      version: '2.1.0',
      documentation: '/docs/sdk/python',
      repository: 'https://github.com/alumni-platform/python-sdk',
      examples: ['pip install alumni-platform']
    }
  ],
  examples: [
    {
      id: '1',
      title: 'Get Alumni List',
      description: 'Retrieve a paginated list of alumni profiles.',
      language: 'javascript',
      code: 'const alumni = await client.alumni.list({ page: 1, limit: 10 });',
      explanation: ['Initialize the client', 'Call the alumni list endpoint', 'Handle pagination']
    }
  ],
  rateLimits: [
    {
      endpoint: '/alumni',
      limit: 1000,
      period: 'hour',
      headers: ['X-RateLimit-Limit', 'X-RateLimit-Remaining']
    }
  ]
}))

const migrationSupport = computed<MigrationSupport>(() => ({
  title: 'Migration Support',
  description: 'Comprehensive support for migrating from existing alumni platforms with minimal disruption.',
  supportedPlatforms: [
    {
      id: '1',
      name: 'Legacy Alumni Database',
      logo: '/images/platforms/legacy.png',
      description: 'Custom migration from spreadsheets and legacy databases.',
      migrationComplexity: 'medium',
      dataMapping: [
        { sourceField: 'name', targetField: 'full_name' },
        { sourceField: 'email', targetField: 'email_address' }
      ],
      estimatedTime: '2-4 weeks'
    }
  ],
  migrationProcess: [
    {
      id: '1',
      stepNumber: 1,
      title: 'Data Assessment',
      description: 'Analyze existing data structure and quality.',
      duration: '1 week',
      deliverables: ['Data audit report', 'Migration plan'],
      prerequisites: ['Data export access', 'System documentation']
    }
  ],
  timeline: '4-8 weeks',
  support: {
    type: 'full_service',
    description: 'Complete migration handled by our team.',
    included: ['Data migration', 'User training', 'Go-live support'],
    timeline: '4-8 weeks'
  },
  tools: [
    {
      id: '1',
      name: 'Data Migration Tool',
      description: 'Automated tool for common data formats.',
      type: 'automated',
      supportedFormats: ['CSV', 'Excel', 'JSON'],
      limitations: ['Max 10,000 records per batch']
    }
  ]
}))

const trainingPrograms = computed<TrainingProgram[]>(() => [
  {
    id: '1',
    title: 'Administrator Training',
    description: 'Comprehensive training for platform administrators and staff.',
    audience: 'administrators',
    format: 'hybrid',
    duration: '2 days',
    modules: [
      {
        id: '1',
        title: 'Platform Overview',
        description: 'Introduction to platform features and capabilities.',
        duration: '2 hours',
        topics: ['Dashboard navigation', 'User management', 'Basic configuration'],
        materials: ['Video tutorials', 'Quick reference guide'],
        assessment: true
      }
    ],
    certification: true,
    cost: {
      type: 'included'
    },
    schedule: [
      {
        id: '1',
        date: new Date('2024-03-15'),
        time: '9:00 AM',
        timezone: 'EST',
        capacity: 20,
        registrationUrl: '/training/register/admin-1'
      }
    ]
  }
])

const scalabilityInfo = computed<ScalabilityInfo[]>(() => [
  {
    id: '1',
    institutionSize: 'small',
    alumniRange: '1,000-5,000',
    features: [
      { name: 'Basic networking', availability: true },
      { name: 'Event management', availability: true },
      { name: 'Advanced analytics', availability: false }
    ],
    performance: [
      { metric: 'Response time', value: '<200ms', description: 'Average API response time', benchmark: 'Industry standard' }
    ],
    support: {
      type: 'Standard support',
      description: 'Email and chat support during business hours.',
      responseTime: '24 hours',
      channels: ['Email', 'Chat'],
      dedicatedManager: false
    },
    pricing: {
      model: 'tiered',
      basePrice: 299,
      volumeDiscounts: [
        { minUsers: 1000, discountPercentage: 10, description: '10% off for 1000+ alumni' }
      ]
    },
    caseStudies: ['Small College Success Story']
  },
  {
    id: '2',
    institutionSize: 'enterprise',
    alumniRange: '50,000+',
    features: [
      { name: 'Advanced networking', availability: true },
      { name: 'Custom integrations', availability: true },
      { name: 'White-label solution', availability: true }
    ],
    performance: [
      { metric: 'Concurrent users', value: '10,000+', description: 'Simultaneous active users', benchmark: 'Enterprise grade' }
    ],
    support: {
      type: 'Enterprise support',
      description: '24/7 support with dedicated account manager.',
      responseTime: '1 hour',
      channels: ['Phone', 'Email', 'Chat', 'Dedicated Slack'],
      dedicatedManager: true
    },
    pricing: {
      model: 'custom',
      volumeDiscounts: [
        { minUsers: 50000, discountPercentage: 25, description: 'Volume pricing available' }
      ]
    },
    caseStudies: ['Major University Implementation', 'Global Corporate Alumni Network']
  }
])
</script>

<style scoped>
/* Component styles handled by child components */
</style>