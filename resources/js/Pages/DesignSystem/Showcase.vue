<template>
    <div class="min-h-screen bg-background">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur-md dark:border-gray-700 dark:bg-gray-900/95">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <h1 class="text-2xl font-bold text-foreground">Design System Showcase</h1>
                    <div class="flex items-center space-x-4">
                        <ThemeToggle />
                        <Button variant="outline" @click="toggleMobileView">
                            {{ isMobileView ? 'Desktop View' : 'Mobile View' }}
                        </Button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="border-b border-border bg-muted/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8 overflow-x-auto py-4">
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        @click="activeSection = section.id"
                        :class="[
                            'whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            activeSection === section.id
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                        ]"
                    >
                        {{ section.name }}
                    </button>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div :class="isMobileView ? 'mx-auto max-w-sm' : ''">
                <!-- Colors Section -->
                <section v-if="activeSection === 'colors'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Color Palette</h2>

                        <!-- Primary Colors -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Primary Colors</h3>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <ColorSwatch color="hsl(var(--primary))" name="Primary" description="Main brand color" />
                                <ColorSwatch color="hsl(var(--primary-foreground))" name="Primary Foreground" description="Text on primary" />
                                <ColorSwatch color="hsl(var(--secondary))" name="Secondary" description="Secondary backgrounds" />
                                <ColorSwatch color="hsl(var(--secondary-foreground))" name="Secondary Foreground" description="Text on secondary" />
                            </div>
                        </div>

                        <!-- Semantic Colors -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Semantic Colors</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <ColorSwatch color="hsl(22 163 74)" name="Success" description="Success states" />
                                <ColorSwatch color="hsl(217 119 6)" name="Warning" description="Warning states" />
                                <ColorSwatch color="hsl(220 38 38)" name="Error" description="Error states" />
                            </div>
                        </div>

                        <!-- Background Colors -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Background Colors</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <ColorSwatch color="hsl(var(--background))" name="Background" description="Main background" />
                                <ColorSwatch color="hsl(var(--card))" name="Card" description="Card backgrounds" />
                                <ColorSwatch color="hsl(var(--muted))" name="Muted" description="Subtle backgrounds" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Typography Section -->
                <section v-if="activeSection === 'typography'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Typography</h2>

                        <!-- Headings -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Headings</h3>
                            <div class="space-y-4">
                                <div>
                                    <h1 class="text-4xl font-bold">Heading 1 - 36px Bold</h1>
                                    <code class="text-sm text-muted-foreground">text-4xl font-bold</code>
                                </div>
                                <div>
                                    <h2 class="text-3xl font-bold">Heading 2 - 30px Bold</h2>
                                    <code class="text-sm text-muted-foreground">text-3xl font-bold</code>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-semibold">Heading 3 - 24px Semibold</h3>
                                    <code class="text-sm text-muted-foreground">text-2xl font-semibold</code>
                                </div>
                                <div>
                                    <h4 class="text-xl font-semibold">Heading 4 - 20px Semibold</h4>
                                    <code class="text-sm text-muted-foreground">text-xl font-semibold</code>
                                </div>
                                <div>
                                    <h5 class="text-lg font-medium">Heading 5 - 18px Medium</h5>
                                    <code class="text-sm text-muted-foreground">text-lg font-medium</code>
                                </div>
                                <div>
                                    <h6 class="text-base font-medium">Heading 6 - 16px Medium</h6>
                                    <code class="text-sm text-muted-foreground">text-base font-medium</code>
                                </div>
                            </div>
                        </div>

                        <!-- Body Text -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Body Text</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-lg">Large body text - 18px</p>
                                    <code class="text-sm text-muted-foreground">text-lg</code>
                                </div>
                                <div>
                                    <p class="text-base">Regular body text - 16px</p>
                                    <code class="text-sm text-muted-foreground">text-base</code>
                                </div>
                                <div>
                                    <p class="text-sm">Small body text - 14px</p>
                                    <code class="text-sm text-muted-foreground">text-sm</code>
                                </div>
                                <div>
                                    <p class="text-xs">Extra small text - 12px</p>
                                    <code class="text-sm text-muted-foreground">text-xs</code>
                                </div>
                            </div>
                        </div>

                        <!-- Text Colors -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Text Colors</h3>
                            <div class="space-y-2">
                                <p class="text-foreground">Primary text color</p>
                                <p class="text-muted-foreground">Muted text color</p>
                                <p class="text-primary">Primary accent color</p>
                                <p class="text-destructive">Destructive/error color</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Spacing Section -->
                <section v-if="activeSection === 'spacing'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Spacing System</h2>

                        <div class="space-y-6">
                            <SpacingExample size="1" label="4px" />
                            <SpacingExample size="2" label="8px" />
                            <SpacingExample size="3" label="12px" />
                            <SpacingExample size="4" label="16px" />
                            <SpacingExample size="6" label="24px" />
                            <SpacingExample size="8" label="32px" />
                            <SpacingExample size="12" label="48px" />
                            <SpacingExample size="16" label="64px" />
                        </div>
                    </div>
                </section>

                <!-- Components Section -->
                <section v-if="activeSection === 'Components'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Components</h2>

                        <!-- Buttons -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Buttons</h3>
                            <div class="mb-4 flex flex-wrap gap-4">
                                <Button variant="default">Primary</Button>
                                <Button variant="secondary">Secondary</Button>
                                <Button variant="outline">Outline</Button>
                                <Button variant="ghost">Ghost</Button>
                                <Button variant="destructive">Destructive</Button>
                                <Button variant="link">Link</Button>
                            </div>

                            <div class="mb-4 flex flex-wrap gap-4">
                                <Button variant="default" size="sm">Small</Button>
                                <Button variant="default" size="default">Default</Button>
                                <Button variant="default" size="lg">Large</Button>
                                <Button variant="default" size="icon">
                                    <PlusIcon class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Cards -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Cards</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <Card class="p-6">
                                    <h4 class="mb-2 text-lg font-semibold">Basic Card</h4>
                                    <p class="text-muted-foreground">This is a basic card component with some content.</p>
                                </Card>

                                <Card class="p-6">
                                    <div class="mb-4 flex items-center space-x-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary">
                                            <UserIcon class="h-6 w-6 text-primary-foreground" />
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold">Alumni Card</h4>
                                            <p class="text-sm text-muted-foreground">Class of 2020</p>
                                        </div>
                                    </div>
                                    <p class="text-sm">Software Engineer at Tech Corp</p>
                                </Card>
                            </div>
                        </div>

                        <!-- Form Elements -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Form Elements</h3>
                            <div class="max-w-md space-y-4">
                                <div>
                                    <Label for="email">Email</Label>
                                    <Input id="email" type="email" placeholder="Enter your email" />
                                </div>

                                <div>
                                    <Label for="message">Message</Label>
                                    <Textarea id="message" placeholder="Enter your message" />
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="terms" />
                                    <Label for="terms">Accept terms and conditions</Label>
                                </div>

                                <Button type="submit" class="w-full">Submit</Button>
                            </div>
                        </div>

                        <!-- Badges -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Badges</h3>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="default">Default</Badge>
                                <Badge variant="secondary">Secondary</Badge>
                                <Badge variant="destructive">Destructive</Badge>
                                <Badge variant="outline">Outline</Badge>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Mobile Section -->
                <section v-if="activeSection === 'mobile'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Mobile Components</h2>

                        <!-- Mobile Navigation -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Mobile Navigation</h3>
                            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                <nav class="flex justify-around">
                                    <a href="#" class="mobile-nav-item-enhanced active">
                                        <HomeIcon class="h-5 w-5" />
                                        <span>Home</span>
                                    </a>
                                    <a href="#" class="mobile-nav-item-enhanced">
                                        <UsersIcon class="h-5 w-5" />
                                        <span>Alumni</span>
                                    </a>
                                    <a href="#" class="mobile-nav-item-enhanced">
                                        <CalendarIcon class="h-5 w-5" />
                                        <span>Events</span>
                                    </a>
                                    <a href="#" class="mobile-nav-item-enhanced">
                                        <BriefcaseIcon class="h-5 w-5" />
                                        <span>Jobs</span>
                                    </a>
                                </nav>
                            </div>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Mobile Cards</h3>
                            <div class="space-y-4">
                                <div class="card-mobile">
                                    <div class="card-mobile-header">
                                        <h4 class="card-mobile-title">John Doe</h4>
                                        <span class="card-mobile-subtitle">Class of 2020</span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Software Engineer at Tech Corp</p>
                                    <div class="mt-3 flex space-x-2">
                                        <Button size="sm" variant="outline">Connect</Button>
                                        <Button size="sm" variant="ghost">Message</Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Buttons -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Mobile Buttons</h3>
                            <div class="space-y-3">
                                <Button class="mobile-button-primary w-full">Primary Mobile Button</Button>
                                <Button class="mobile-button-secondary w-full">Secondary Mobile Button</Button>
                                <Button class="mobile-button-outline w-full">Outline Mobile Button</Button>
                            </div>
                        </div>

                        <!-- Touch Targets -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Touch Targets</h3>
                            <p class="mb-4 text-sm text-muted-foreground">All interactive elements meet the minimum 44px touch target size.</p>
                            <div class="grid grid-cols-3 gap-4">
                                <Button class="touch-target">44px</Button>
                                <Button class="touch-target">44px</Button>
                                <Button class="touch-target">44px</Button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Accessibility Section -->
                <section v-if="activeSection === 'accessibility'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Accessibility Features</h2>

                        <!-- Focus States -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Focus States</h3>
                            <p class="mb-4 text-sm text-muted-foreground">All interactive elements have visible focus indicators.</p>
                            <div class="space-y-4">
                                <Button>Tab to focus this button</Button>
                                <Input placeholder="Tab to focus this input" />
                                <a href="#" class="text-primary hover:underline">Tab to focus this link</a>
                            </div>
                        </div>

                        <!-- Color Contrast -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Color Contrast</h3>
                            <p class="mb-4 text-sm text-muted-foreground">All text meets WCAG AA standards (4.5:1 minimum contrast ratio).</p>
                            <div class="space-y-2">
                                <div class="rounded bg-primary p-4 text-primary-foreground">Primary text on primary background (4.8:1 contrast)</div>
                                <div class="rounded bg-secondary p-4 text-secondary-foreground">
                                    Secondary text on secondary background (9.6:1 contrast)
                                </div>
                                <div class="rounded bg-destructive p-4 text-destructive-foreground">
                                    Error text on error background (5.9:1 contrast)
                                </div>
                            </div>
                        </div>

                        <!-- Screen Reader Support -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Screen Reader Support</h3>
                            <div class="space-y-4">
                                <Button aria-label="Close modal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-4 w-4" />
                                </Button>

                                <div role="alert" class="rounded border border-destructive/20 bg-destructive/10 p-4 text-destructive">
                                    This is an error message that will be announced by screen readers.
                                </div>

                                <div>
                                    <Label for="accessible-input">Accessible Input</Label>
                                    <Input id="accessible-input" aria-describedby="input-help" placeholder="Enter text" />
                                    <p id="input-help" class="mt-1 text-sm text-muted-foreground">This input has proper labeling and description.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Loading States Section -->
                <section v-if="activeSection === 'loading'" class="space-y-8">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold">Loading States</h2>

                        <!-- Skeleton Loaders -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Skeleton Loaders</h3>
                            <div class="space-y-4">
                                <div class="mobile-skeleton-enhanced h-4 w-full"></div>
                                <div class="mobile-skeleton-enhanced h-4 w-3/4"></div>
                                <div class="mobile-skeleton-enhanced h-4 w-1/2"></div>
                            </div>
                        </div>

                        <!-- Loading Spinners -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Loading Spinners</h3>
                            <div class="flex space-x-4">
                                <div class="pull-to-refresh-spinner"></div>
                                <Button disabled>
                                    <div class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                    Loading...
                                </Button>
                            </div>
                        </div>

                        <!-- Progress Indicators -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-xl font-semibold">Progress Indicators</h3>
                            <div class="space-y-4">
                                <div class="progress-mobile">
                                    <div class="progress-mobile-bar" style="width: 25%"></div>
                                </div>
                                <div class="progress-mobile">
                                    <div class="progress-mobile-bar" style="width: 50%"></div>
                                </div>
                                <div class="progress-mobile">
                                    <div class="progress-mobile-bar" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { BriefcaseIcon, CalendarIcon, HomeIcon, PlusIcon, UserIcon, UsersIcon, XMarkIcon as XIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const activeSection = ref('colors');
const isMobileView = ref(false);

const sections = [
    { id: 'colors', name: 'Colors' },
    { id: 'typography', name: 'Typography' },
    { id: 'spacing', name: 'Spacing' },
    { id: 'Components', name: 'Components' },
    { id: 'mobile', name: 'Mobile' },
    { id: 'accessibility', name: 'Accessibility' },
    { id: 'loading', name: 'Loading States' },
];

const toggleMobileView = () => {
    isMobileView.value = !isMobileView.value;
};
</script>

<script lang="ts">
// Color Swatch Component
const ColorSwatch = {
    props: {
        color: String,
        name: String,
        description: String,
    },
    template: `
    <div class="bg-card border border-border rounded-lg p-4">
      <div 
        class="w-full h-16 rounded-md mb-3 border border-border"
        :style="{ backgroundColor: color }"
      ></div>
      <h4 class="font-medium text-sm">{{ name }}</h4>
      <p class="text-xs text-muted-foreground">{{ description }}</p>
      <code class="text-xs text-muted-foreground block mt-1">{{ color }}</code>
    </div>
  `,
};

// Spacing Example Component
const SpacingExample = {
    props: {
        size: String,
        label: String,
    },
    template: `
    <div class="flex items-center space-x-4">
      <div class="w-16 text-sm text-muted-foreground">{{ label }}</div>
      <div 
        class="bg-primary rounded"
        :class="'h-4 w-' + size"
      ></div>
      <code class="text-sm text-muted-foreground">w-{{ size }}</code>
    </div>
  `,
};

export default {
    Components: {
        ColorSwatch,
        SpacingExample,
    },
};
</script>
