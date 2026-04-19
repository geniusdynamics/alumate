<template>
    <div class="mx-auto max-w-4xl space-y-8 p-6">
        <div>
            <h1 class="mb-4 text-3xl font-bold text-gray-900">Mobile-First Responsive Design System</h1>
            <p class="mb-6 text-gray-600">
                Our design system follows mobile-first principles, ensuring optimal user experience across all devices. We prioritize mobile users
                first, then progressively enhance for larger screens.
            </p>
        </div>

        <!-- Principles Section -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Core Principles</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 font-medium text-gray-900">Mobile-First Approach</h3>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li>• Base styles apply to all screens (320px+)</li>
                        <li>• Progressive enhancement for larger screens</li>
                        <li>• Touch-friendly interactions (44px minimum)</li>
                        <li>• Content prioritized over decorative elements</li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-2 font-medium text-gray-900">Responsive Breakpoints</h3>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li>• <strong>sm:</strong> 640px+ (Small tablets)</li>
                        <li>• <strong>md:</strong> 768px+ (Tablets)</li>
                        <li>• <strong>lg:</strong> 1024px+ (Small laptops)</li>
                        <li>• <strong>xl:</strong> 1280px+ (Laptops)</li>
                        <li>• <strong>2xl:</strong> 1536px+ (Large screens)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Breakpoints Section -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Breakpoint System</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left font-medium">Breakpoint</th>
                            <th class="px-3 py-2 text-left font-medium">Min Width</th>
                            <th class="px-3 py-2 text-left font-medium">Typical Device</th>
                            <th class="px-3 py-2 text-left font-medium">Tailwind Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2">Base/Mobile</td>
                            <td class="px-3 py-2">320px</td>
                            <td class="px-3 py-2">Phones</td>
                            <td class="px-3 py-2">No prefix</td>
                        </tr>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2">Small</td>
                            <td class="px-3 py-2">640px</td>
                            <td class="px-3 py-2">Small tablets</td>
                            <td class="px-3 py-2"><code>sm:</code></td>
                        </tr>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2">Medium</td>
                            <td class="px-3 py-2">768px</td>
                            <td class="px-3 py-2">Tablets</td>
                            <td class="px-3 py-2"><code>md:</code></td>
                        </tr>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2">Large</td>
                            <td class="px-3 py-2">1024px</td>
                            <td class="px-3 py-2">Small laptops</td>
                            <td class="px-3 py-2"><code>lg:</code></td>
                        </tr>
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2">Extra Large</td>
                            <td class="px-3 py-2">1280px</td>
                            <td class="px-3 py-2">Laptops</td>
                            <td class="px-3 py-2"><code>xl:</code></td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2">2X Large</td>
                            <td class="px-3 py-2">1536px</td>
                            <td class="px-3 py-2">Large screens</td>
                            <td class="px-3 py-2"><code>2xl:</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Typography Scale -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Responsive Typography</h2>
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-100 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium">Heading (Title)</span>
                        <code class="rounded bg-gray-100 px-2 py-1 text-xs">text-base sm:text-lg</code>
                    </div>
                    <p class="text-base text-gray-900 sm:text-lg">The quick brown fox jumps over the lazy dog</p>
                    <p class="mt-1 text-xs text-gray-500">Mobile: 16px → Small+: 18px</p>
                </div>

                <div class="rounded-lg border border-gray-100 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium">Body Text</span>
                        <code class="rounded bg-gray-100 px-2 py-1 text-xs">text-sm sm:text-base</code>
                    </div>
                    <p class="text-xs text-gray-700 sm:text-sm">This is standard body text that adjusts to screen size for optimal readability.</p>
                    <p class="mt-1 text-xs text-gray-500">Mobile: 12px → Small+: 14px</p>
                </div>

                <div class="rounded-lg border border-gray-100 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium">Small Text</span>
                        <code class="rounded bg-gray-100 px-2 py-1 text-xs">text-xs</code>
                    </div>
                    <p class="text-xs text-gray-700">Small text for captions and metadata that remains consistent across all screen sizes.</p>
                    <p class="mt-1 text-xs text-gray-500">12px (consistent)</p>
                </div>
            </div>
        </div>

        <!-- Spacing System -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Responsive Spacing</h2>
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="rounded-lg border bg-blue-100 p-4 sm:p-6">
                            <p class="text-sm font-medium">Card Content</p>
                            <p class="text-xs text-gray-600">Mobile: p-4 (16px)<br />Small+: p-6 (24px)</p>
                        </div>
                    </div>
                </div>

                <code class="block rounded bg-gray-100 px-3 py-2 text-sm"> class="p-4 sm:p-6" </code>
            </div>
        </div>

        <!-- Implementation Examples -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Usage Examples</h2>

            <h3 class="mb-2 font-medium text-gray-900">Responsive Text</h3>
            <pre class="mb-4 overflow-x-auto rounded bg-gray-100 p-3 text-xs"><code><h2 class="text-lg sm:text-xl md:text-2xl font-bold">
  Responsive Heading
</h2></code></pre>

            <h3 class="mb-2 font-medium text-gray-900">Responsive Spacing</h3>
            <pre class="mb-4 overflow-x-auto rounded bg-gray-100 p-3 text-xs"><code><div class="p-4 sm:p-6 lg:p-8">
  Content with progressive spacing
</div></code></pre>

            <h3 class="mb-2 font-medium text-gray-900">Responsive Grid</h3>
            <pre class="mb-4 overflow-x-auto rounded bg-gray-100 p-3 text-xs"><code><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div></code></pre>

            <h3 class="mb-2 font-medium text-gray-900">Hide/Show Content</h3>
            <pre class="overflow-x-auto rounded bg-gray-100 p-3 text-xs"><code><div class="hidden md:block">
  This content is hidden on mobile/tablet, shown on desktop
</div></code></pre>
        </div>

        <!-- Best Practices -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Best Practices</h2>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 font-medium text-green-700">✅ Do</h3>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li>• Start with mobile-first classes</li>
                        <li>• Use progressive enhancement</li>
                        <li>• Test on real devices</li>
                        <li>• Prioritize content hierarchy</li>
                        <li>• Use relative units (rem/em)</li>
                    </ul>
                </div>
                <div>
                    <h3 class="mb-2 font-medium text-red-700">❌ Don't</h3>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li>• Don't desktop-first design</li>
                        <li>• Avoid fixed pixel values</li>
                        <li>• Don't hide content on mobile</li>
                        <li>• Avoid complex Layouts on small screens</li>
                        <li>• Don't use hover-only interactions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// This page demonstrates the mobile-first responsive design system
// and provides documentation for developers
</script>
