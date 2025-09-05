<?php

namespace Database\Factories;

use App\Models\BrandConfig;
use App\Models\BrandGuidelines;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandGuidelines>
 */
class BrandGuidelinesFactory extends Factory
{
    protected $model = BrandGuidelines::class;

    public function definition(): array
    {
        $institutionType = fake()->randomElement([
            'university', 'college', 'corporate', 'nonprofit', 'hospital', 'school', 'foundation'
        ]);

        $name = $this->generateGuidelinesName($institutionType);
        $description = $this->generateDescription($institutionType);

        return [
            'tenant_id' => Tenant::factory(),
            'brand_config_id' => BrandConfig::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->randomNumber(3)),
            'description' => $description,
            'usage_rules' => $this->generateUsageRules($institutionType),
            'color_guidelines' => $this->generateColorGuidelines($institutionType),
            'typography_guidelines' => $this->generateTypographyGuidelines($institutionType),
            'logo_guidelines' => $this->generateLogoGuidelines($institutionType),
            'dos_and_donts' => $this->generateDosAndDonts($institutionType),
            'brand_voice_tone' => $this->generateBrandVoice($institutionType),
            'brand_personality' => $this->generateBrandPersonality($institutionType),
            'target_audience' => $this->generateTargetAudience($institutionType),
            'brand_values' => $this->generateBrandValues($institutionType),
            'legal_restrictions' => $this->generateLegalRestrictions($institutionType),
            'contact_information' => $this->generateContactInformation($institutionType),
            'review_process' => $this->generateReviewProcess($institutionType),
            'version' => 1,
            'effective_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'is_active' => true,
            'requires_approval' => fake()->boolean(80), // 80% chance of requiring approval
            'created_by' => null,
            'updated_by' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    private function generateGuidelinesName(string $institutionType): string
    {
        $templates = [
            'university' => [
                'Brand Guidelines - Excellence in Education',
                'University Brand Standards Manual',
                'Academic Identity Guidelines',
                'Campus Branding Guidelines'
            ],
            'corporate' => [
                'Corporate Brand Guidelines',
                'Company Brand Standards',
                'Enterprise Brand Manual',
                'Corporate Identity Guidelines'
            ],
            'nonprofit' => [
                'Mission-Driven Brand Guidelines',
                'Nonprofit Brand Standards',
                'Impact Organization Guidelines',
                'Community Focus Brand Manual'
            ],
            'hospital' => [
                'Healthcare Brand Guidelines',
                'Medical Center Brand Standards',
                'Hospital Branding Manual',
                'Healthcare Identity Guidelines'
            ],
            'foundation' => [
                'Foundation Brand Guidelines',
                'Philanthropic Brand Standards',
                'Giving Organization Manual',
                'Foundation Identity Guidelines'
            ],
        ];

        return fake()->randomElement($templates[$institutionType] ?? [
            'Brand Guidelines Manual',
            'Brand Standards Guide',
            'Identity Guidelines',
            'Brand Guidelines and Standards'
        ]);
    }

    private function generateDescription(string $institutionType): string
    {
        $descriptions = [
            'university' => 'Comprehensive brand guidelines ensuring consistent application of university identity across all communications and touchpoints. These standards protect the integrity of the institution\'s reputation and brand equity.',
            'corporate' => 'Corporate brand standards that establish clear guidelines for brand application, ensuring consistent messaging and visual identity across all business units and communications.',
            'nonprofit' => 'Brand guidelines designed to support the mission while maintaining clear, impactful communication that resonates with stakeholders and donors.',
            'hospital' => 'Medical institution branding standards that prioritize trust, professionalism, and clear communication while maintaining compliance with healthcare regulations.',
            'foundation' => 'Foundation brand guidelines that communicate philanthropic values while ensuring consistent representation of the organization\'s mission and impact.',
        ];

        return $descriptions[$institutionType] ??
               'Comprehensive brand guidelines ensuring consistent application of organizational identity across all communications and touchpoints.';
    }

    private function generateUsageRules(string $institutionType): array
    {
        $commonRules = [
            'Maintain at least 1 inch clear space around logo',
            'Never stretch or distort logo proportions',
            'Use approved color palette only',
            'Maintain consistent typography hierarchy',
            'Respect trademark usage guidelines',
        ];

        $specificRules = [
            'university' => [
                'Include university seal only on official documents',
                'Use athletics branding only for sports-related communications',
                'Academic department names should follow naming standards',
                'Publications must include institutional disclaimers',
            ],
            'corporate' => [
                'Product branding must align with overall corporate identity',
                'Regional adaptations require approval from global branding team',
                'Use of stock photography must comply with company policies',
                'Sustainability claims must be verified',
            ],
            'nonprofit' => [
                'Impact messaging must be supported by data',
                'Donor communication standards must be followed',
                'Partnership mentions require legal review',
                'Volunteer recruitment materials must be approved',
            ],
            'hospital' => [
                'HIPAA compliance must be maintained in all communications',
                'Medical claims must be approved by medical advisory team',
                'Patient privacy considerations in all branding',
                'Emergency communication protocols must be observed',
            ],
        ];

        return array_merge(
            $commonRules,
            fake()->randomElements($specificRules[$institutionType] ?? [], 3)
        );
    }

    private function generateColorGuidelines(string $institutionType): array
    {
        $palettes = [
            'university' => [
                'primary' => '#1E40AF', // Deep blue for tradition
                'secondary' => '#7C3AED', // Purple for innovation
                'accent' => '#059669', // Green for growth
                'neutral' => '#6B7280', // Gray for professionalism
            ],
            'corporate' => [
                'primary' => '#DC2626', // Red for energy
                'secondary' => '#2563EB', // Blue for trust
                'accent' => '#F59E0B', // Orange for innovation
                'neutral' => '#4B5563', // Gray for balance
            ],
            'nonprofit' => [
                'primary' => '#059669', // Green for hope
                'secondary' => '#3B82F6', // Blue for trust
                'accent' => '#EC4899', // Pink for compassion
                'neutral' => '#6B7280', // Gray for professionalism
            ],
            'hospital' => [
                'primary' => '#DC2626', // Red for urgent care
                'secondary' => '#16A34A', // Green for health
                'accent' => '#2563EB', // Blue for calmness
                'neutral' => '#374151', // Gray for sterility
            ],
        ];

        $colors = $palettes[$institutionType] ?? $palettes['university'];

        return [
            [
                'name' => 'Primary Color',
                'hex' => $colors['primary'],
                'usage' => 'Main brand color for headlines, logos, and primary elements',
                'accessibility' => 'WCAG AA compliant when used with white text',
            ],
            [
                'name' => 'Secondary Color',
                'hex' => $colors['secondary'],
                'usage' => 'Support primary messaging and secondary brand elements',
                'accessibility' => 'Accessible when used appropriately',
            ],
            [
                'name' => 'Accent Color',
                'hex' => $colors['accent'],
                'usage' => 'Call-to-action buttons, highlights, and accent elements',
                'accessibility' => 'Use sparingly and test for readability',
            ],
            [
                'name' => 'Neutral Color',
                'hex' => $colors['neutral'],
                'usage' => 'Body text, secondary information, and neutral backgrounds',
                'accessibility' => 'Ensures optimal text contrast ratios',
            ],
        ];
    }

    private function generateTypographyGuidelines(string $institutionType): array
    {
        $fonts = [
            'university' => [
                'primary' => 'Times New Roman',
                'secondary' => 'Calibri',
                'accent' => 'Helvetica',
            ],
            'corporate' => [
                'primary' => 'Arial',
                'secondary' => 'Segoe UI',
                'accent' => 'Montserrat',
            ],
            'nonprofit' => [
                'primary' => 'Georgia',
                'secondary' => 'Inter',
                'accent' => 'Nunito',
            ],
        ];

        $chosenFonts = $fonts[$institutionType] ?? $fonts['university'];

        return [
            [
                'font_family' => $chosenFonts['primary'],
                'usage' => 'Headlines, primary messaging, and official documents',
                'weight_options' => [400, 700],
                'size_range' => '18px-48px',
            ],
            [
                'font_family' => $chosenFonts['secondary'],
                'usage' => 'Body text, paragraphs, and secondary content',
                'weight_options' => [400, 600],
                'size_range' => '14px-16px',
            ],
            [
                'font_family' => $chosenFonts['accent'],
                'usage' => 'Logos, banners, and special elements',
                'weight_options' => [300, 500, 700],
                'size_range' => '16px-36px',
            ],
            'hierarchy_rules' => [
                'H1: 32px, line-height 1.2, font-weight 700',
                'H2: 24px, line-height 1.3, font-weight 600',
                'H3: 18px, line-height 1.4, font-weight 600',
                'Body: 16px, line-height 1.6, font-weight 400',
                'Caption: 14px, line-height 1.5, font-weight 400',
            ],
        ];
    }

    private function generateLogoGuidelines(string $institutionType): array
    {
        return [
            [
                'version' => 'Primary Logo',
                'usage' => 'Full color on white backgrounds',
                'minimum_size' => '100px wide',
                'clearance' => 'Height of letter "O" in logo',
                'approved_file' => 'logo_primary.svg',
            ],
            [
                'version' => 'Black Logo',
                'usage' => 'Single color applications',
                'minimum_size' => '85px wide',
                'clearance' => 'Half height of logo',
                'approved_file' => 'logo_black.svg',
            ],
            [
                'version' => 'White Logo',
                'usage' => 'Dark background applications',
                'minimum_size' => '100px wide',
                'clearance' => 'Height of letter "O" in logo',
                'approved_file' => 'logo_white.svg',
            ],
            [
                'version' => 'Icon Only',
                'usage' => 'Small spaces, favicons',
                'minimum_size' => '32px wide',
                'clearance' => '25% of icon width',
                'approved_file' => 'logo_icon.svg',
            ],
            'restrictions' => [
                'Never modify logo colors without approval',
                'Never use drop shadows without permission',
                'Never place logo on busy backgrounds',
                'Never rotate or distort logo proportions',
                'Never combine with other institutional logos',
            ],
        ];
    }

    private function generateDosAndDonts(string $institutionType): array
    {
        $specificRules = [
            'university' => [
                'dos' => [
                    'Use university seal for official academic documents',
                    'Include "Est. [YEAR]" on appropriate materials',
                    'Mention accreditation when relevant',
                    'Highlight academic achievements',
                ],
                'donts' => [
                    'Claim rankings without data',
                    'Misrepresent academic programs',
                    'Use athletics branding inappropriately',
                    'Create department logos without approval',
                ],
            ],
            'corporate' => [
                'dos' => [
                    'Emphasize company values',
                    'Highlight innovation and expertise',
                    'Use company taglines consistently',
                    'Show corporate social responsibility',
                ],
                'donts' => [
                    'Make unsubstantiated claims',
                    'Use competitor names in messaging',
                    'Promise unrealistic outcomes',
                    'Create individual department brands',
                ],
            ],
            'nonprofit' => [
                'dos' => [
                    'Focus on impact and mission',
                    'Share success stories',
                    'Highlight volunteer involvement',
                    'Communicate donation impact',
                ],
                'donts' => [
                    'Guarantee specific outcomes',
                    'Use emotional manipulation',
                    'Misrepresentation of needs',
                    'Create competitive messaging',
                ],
            ],
        ];

        $rules = $specificRules[$institutionType] ?? $specificRules['university'];

        return [
            'dos' => $rules['dos'],
            'donts' => $rules['donts'],
            'general_guidance' => [
                'Always prioritize clear communication',
                'Maintain professionalism in all interactions',
                'Respect legal and compliance requirements',
                'Consider accessibility for all audiences',
                'Test messaging across different channels',
            ],
        ];
    }

    private function generateBrandVoice(string $institutionType): array
    {
        $voices = [
            'university' => [
                'formal' => 80,
                'professional' => 90,
                'educational' => 95,
                'encouraging' => 70,
                'inspirational' => 75,
                'authoritative' => 85,
            ],
            'corporate' => [
                'professional' => 95,
                'confident' => 85,
                'innovative' => 80,
                'authoritative' => 90,
                'solution-oriented' => 85,
                'results-driven' => 90,
            ],
            'nonprofit' => [
                'compassionate' => 90,
                'hopeful' => 95,
                'mission-driven' => 100,
                'empathetic' => 85,
                'authentic' => 90,
                'community-focused' => 95,
            ],
        ];

        $currentVoice = $voices[$institutionType] ?? $voices['corporate'];

        return [
            'tone_characteristics' => $currentVoice,
            'communication_style' => [
                'language_level' => 'Formal to semi-formal',
                'sentence_structure' => 'Clear, concise, and purposeful',
                'vocabulary_choice' => 'Professional, accessible, and appropriate for audience',
            ],
            'voice_examples' => [
                'slogan' => fake()->randomElement([
                    'Excellence in Education',
                    'Innovating Tomorrow',
                    'Making a Difference',
                    'Leading with Purpose',
                ]),
                'tagline' => fake()->randomElement([
                    'Where Tradition Meets Innovation',
                    'Empowering Communities',
                    'Advancing Humanity',
                    'Building Better Futures',
                ]),
            ],
        ];
    }

    private function generateBrandPersonality(string $institutionType): array
    {
        $personalities = [
            'university' => [
                'trustworthy' => 95,
                'knowledgeable' => 100,
                'traditional' => 80,
                'innovative' => 75,
                'approachable' => 85,
                'prestigious' => 90,
            ],
            'corporate' => [
                'efficient' => 90,
                'reliable' => 95,
                'innovative' => 85,
                'professional' => 100,
                'ambitious' => 80,
                'solution-oriented' => 90,
            ],
            'nonprofit' => [
                'compassionate' => 100,
                'hopeful' => 95,
                'authentic' => 90,
                'community-focused' => 95,
                'empathetic' => 90,
                'mission-driven' => 100,
            ],
        ];

        $currentPersonality = $personalities[$institutionType] ?? $personalities['corporate'];

        return [
            'core_traits' => $currentPersonality,
            'archetype' => $this->getBrandArchetype($institutionType),
            'behavioral_guidelines' => [
                'decision_making' => 'Data-informed, ethical, and strategic',
                'stakeholder_interaction' => 'Respectful, transparent, and collaborative',
                'crisis_response' => 'Calm, responsible, and proactive',
            ],
        ];
    }

    private function getBrandArchetype(string $institutionType): string
    {
        $archetypes = [
            'university' => 'The Sage - Wisdom, knowledge, and guidance',
            'corporate' => 'The Creator - Innovation, vision, and transformation',
            'nonprofit' => 'The Caregiver - Compassion, service, and nurturing',
            'hospital' => 'The Guardian - Protection, healing, and safety',
        ];

        return $archetypes[$institutionType] ?? 'The Professional - Expertise, reliability, and service';
    }

    private function generateTargetAudience(string $institutionType): array
    {
        $audiences = [
            'university' => [
                ['segment' => 'Prospective Students', 'demographics' => 'High school seniors, 17-19 years, diverse backgrounds', 'interests' => 'Higher education, career development, personal growth'],
                ['segment' => 'Current Students', 'demographics' => 'Undergraduates and graduates, 18-35 years', 'interests' => 'Academic success, campus life, career preparation'],
                ['segment' => 'Alumni', 'demographics' => 'Young professionals, 22-45 years, alumni network', 'interests' => 'Networking, career advancement, giving back'],
                ['segment' => 'Faculty & Staff', 'demographics' => 'Educators and administrators, 25-65 years', 'interests' => 'Professional development, institutional pride'],
                ['segment' => 'Community Partners', 'demographics' => 'Local businesses and organizations', 'interests' => 'Student talent, community relations'],
            ],
            'corporate' => [
                ['segment' => 'B2B Clients', 'demographics' => 'Business decision-makers, 35-55 years', 'interests' => 'Solutions, ROI, trust'],
                ['segment' => 'Industry Partners', 'demographics' => 'Executives and influencers', 'interests' => 'Strategic alliances, market intelligence'],
                ['segment' => 'Job Seekers', 'demographics' => 'Professionals 25-45 years', 'interests' => 'Career growth, company culture'],
                ['segment' => 'Investors', 'demographics' => 'Financial professionals', 'interests' => 'Financial performance, leadership'],
            ],
            'nonprofit' => [
                ['segment' => 'Donors', 'demographics' => 'High-net-worth individuals, philanthropists', 'interests' => 'Impact, causes, tax benefits'],
                ['segment' => 'Volunteers', 'demographics' => 'Community members, 25-75 years', 'interests' => 'Service, engagement, purpose'],
                ['segment' => 'Beneficiaries', 'demographics' => 'Varies by cause', 'interests' => 'Help, hope, change'],
                ['segment' => 'Partner Organizations', 'demographics' => 'Similar mission organizations', 'interests' => 'Collaboration, resources'],
            ],
        ];

        return $audiences[$institutionType] ?? [
            ['segment' => 'General Audience', 'demographics' => 'Broad demographic range', 'interests' => 'Information, engagement'],
        ];
    }

    private function generateBrandValues(string $institutionType): array
    {
        $values = [
            'university' => [
                'Excellence - Striving for the highest standards',
                'Knowledge - Advancing learning and discovery',
                'Integrity - Upholding ethical principles',
                'Innovation - Embracing new ideas and approaches',
                'Community - Building connections and belonging',
                'Service - Contributing to society through education',
            ],
            'corporate' => [
                'Innovation - Driving progress and advancement',
                'Excellence - Delivering superior results',
                'Integrity - Maintaining ethical business practices',
                'Collaboration - Working together for success',
                'Sustainability - Responsible environmental stewardship',
                'Growth - Fostering professional development',
            ],
            'nonprofit' => [
                'Compassion - Showing care and understanding',
                'Justice - Advocating for equality and fairness',
                'Hope - Inspiring positive change',
                'Collaboration - Partnering for greater impact',
                'Accountability - Responsible stewardship of resources',
                'Empowerment - Building capacity and opportunity',
            ],
        ];

        return $values[$institutionType] ?? $this->generateGenericBrandValues();
    }

    private function generateGenericBrandValues(): array
    {
        return [
            'Integrity - Honest and ethical practices',
            'Excellence - Highest quality standards',
            'Innovation - Creative problem-solving',
            'Respect - Valuing diversity and inclusion',
            'Responsibility - Accountable to stakeholders',
            'Collaboration - Working together effectively',
        ];
    }

    private function generateLegalRestrictions(string $institutionType): array
    {
        $restrictions = [
            'university' => [
                'Never claim accreditation without verification',
                'Academic freedom statements must comply with laws',
                'Student data privacy protected by FERPA',
                'Research claims must be evidenced',
                'Title IX compliance in all communications',
            ],
            'corporate' => [
                'Securities regulations prohibit misleading claims',
                'Intellectual property rights must be respected',
                'Employment communications must comply with laws',
                'Environmental claims require substantiation',
                'Data privacy must follow GDPR/CCPA standards',
            ],
            'nonprofit' => [
                'Tax-exempt status messaging must be careful',
                'Donation solicitations must comply with state laws',
                'Program impact claims must be verifiable',
                'Partnership communications must be approved',
                'Volunteers are not employees - legal status clarity',
            ],
            'hospital' => [
                'Medical advice must be from licensed professionals',
                'HIPAA compliance in all patient communications',
                'Medical claims require FDA approval if applicable',
                'Emergency communications must be coordinated',
                'Research results must be peer-reviewed',
            ],
        ];

        return $restrictions[$institutionType] ?? [
            'Compliance with relevant industry regulations',
            'Accuracy in all claims and statements',
            'Respect for intellectual property rights',
            'Protection of confidential information',
            'Fair and ethical business practices',
        ];
    }

    private function generateContactInformation(string $institutionType): array
    {
        $contacts = [
            'branding_team' => [
                'title' => 'Brand Manager',
                'email' => 'brand@university.edu',
                'phone' => '+1 (555) 123-4567',
                'hours' => 'Mon-Fri 9AM-5PM EST',
            ],
            'legal_team' => [
                'title' => 'Legal Counsel',
                'email' => 'legal@university.edu',
                'phone' => '+1 (555) 123-4568',
                'hours' => 'Mon-Fri 9AM-5PM EST',
            ],
            'compliance_officer' => [
                'title' => 'Compliance Officer',
                'email' => 'compliance@university.edu',
                'phone' => '+1 (555) 123-4569',
                'hours' => 'Mon-Fri 9AM-5PM EST',
            ],
        ];

        return [
            'primary_contact' => $contacts['branding_team'],
            'additional_contacts' => [
                'legal' => $contacts['legal_team'],
                'compliance' => $contacts['compliance_officer'],
            ],
            'emergency_contacts' => [
                'crisis_communications' => 'crisis@university.edu',
                'media_relations' => 'media@university.edu',
            ],
            'approval_process' => 'All brand changes require approval from brand manager before distribution',
        ];
    }

    private function generateReviewProcess(string $institutionType): array
    {
        return [
            'review_stages' => [
                [
                    'stage' => 'Initial Draft',
                    'reviewer' => 'Content Creator',
                    'duration' => '2-3 days',
                    'approval_required' => false,
                ],
                [
                    'stage' => 'Brand Compliance Review',
                    'reviewer' => 'Brand Manager',
                    'duration' => '1-2 days',
                    'approval_required' => true,
                ],
                [
                    'stage' => 'Legal Review',
                    'reviewer' => 'Legal Counsel',
                    'duration' => '2-5 days',
                    'approval_required' => true,
                ],
                [
                    'stage' => 'Stakeholder Approval',
                    'reviewer' => 'Department Head',
                    'duration' => '1-3 days',
                    'approval_required' => true,
                ],
                [
                    'stage' => 'Final Sign-off',
                    'reviewer' => 'Executive Leadership',
                    'duration' => '1-2 days',
                    'approval_required' => true,
                ],
            ],
            'turnaround_times' => [
                'standard_review' => '7-10 business days',
                'expedited_review' => '3-5 business days',
                'crisis_communications' => '24-48 hours',
            ],
            'escalation_process' => [
                'If review takes longer than stated timeframe',
                'Contact brand manager directly',
                'Escalate through department leadership if necessary',
                'Executive sponsorship may expedite process',
            ],
        ];
    }

    // State methods for different variations
    public function forTenant($tenantId): static
    {
        return $this->state(['tenant_id' => $tenantId]);
    }

    public function forBrandConfig($brandConfigId): static
    {
        return $this->state(['brand_config_id' => $brandConfigId]);
    }

    public function approved(): static
    {
        return $this->state([
            'approved_by' => fake()->numberBetween(1, 10),
            'approved_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function requiringApproval(): static
    {
        return $this->state(['requires_approval' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forUniversity(): static
    {
        return $this->generateData('university');
    }

    public function forCorporate(): static
    {
        return $this->generateData('corporate');
    }

    public function forNonprofit(): static
    {
        return $this->generateData('nonprofit');
    }

    public function forHospital(): static
    {
        return $this->generateData('hospital');
    }

    private function generateData(string $institutionType): static
    {
        return $this->state([
            'name' => $this->generateGuidelinesName($institutionType),
            'description' => $this->generateDescription($institutionType),
            'usage_rules' => $this->generateUsageRules($institutionType),
            'color_guidelines' => $this->generateColorGuidelines($institutionType),
            'typography_guidelines' => $this->generateTypographyGuidelines($institutionType),
            'logo_guidelines' => $this->generateLogoGuidelines($institutionType),
            'dos_and_donts' => $this->generateDosAndDonts($institutionType),
            'brand_voice_tone' => $this->generateBrandVoice($institutionType),
            'brand_personality' => $this->generateBrandPersonality($institutionType),
            'target_audience' => $this->generateTargetAudience($institutionType),
            'brand_values' => $this->generateBrandValues($institutionType),
            'legal_restrictions' => $this->generateLegalRestrictions($institutionType),
            'contact_information' => $this->generateContactInformation($institutionType),
            'review_process' => $this->generateReviewProcess($institutionType),
        ]);
    }
}