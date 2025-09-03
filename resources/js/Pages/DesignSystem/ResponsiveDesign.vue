<template>
  <div class="max-w-4xl mx-auto p-6 space-y-8">
    <div>
      <h1 class="text-3xl font-bold text-gray-900 mb-4">Mobile-First Responsive Design System</h1>
      <p class="text-gray-600 mb-6">
        Our design system follows mobile-first principles, ensuring optimal user experience across all devices.
        We prioritize mobile users first, then progressively enhance for larger screens.
      </p>
    </div>

    <!-- Principles Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Core Principles</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="font-medium text-gray-900 mb-2">Mobile-First Approach</h3>
          <ul class="text-sm text-gray-600 space-y-1">
            <li>• Base styles apply to all screens (320px+)</li>
            <li>• Progressive enhancement for larger screens</li>
            <li>• Touch-friendly interactions (44px minimum)</li>
            <li>• Content prioritized over decorative elements</li>
          </ul>
        </div>
        <div>
          <h3 class="font-medium text-gray-900 mb-2">Responsive Breakpoints</h3>
          <ul class="text-sm text-gray-600 space-y-1">
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
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Breakpoint System</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-2 px-3 font-medium">Breakpoint</th>
              <th class="text-left py-2 px-3 font-medium">Min Width</th>
              <th class="text-left py-2 px-3 font-medium">Typical Device</th>
              <th class="text-left py-2 px-3 font-medium">Tailwind Class</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border-b border-gray-50">
              <td class="py-2 px-3">Base/Mobile</td>
              <td class="py-2 px-3">320px</td>
              <td class="py-2 px-3">Phones</td>
              <td class="py-2 px-3">No prefix</td>
            </tr>
            <tr class="border-b border-gray-50">
              <td class="py-2 px-3">Small</td>
              <td class="py-2 px-3">640px</td>
              <td class="py-2 px-3">Small tablets</td>
              <td class="py-2 px-3"><code>sm:</code></td>
            </tr>
            <tr class="border-b border-gray-50">
              <td class="py-2 px-3">Medium</td>
              <td class="py-2 px-3">768px</td>
              <td class="py-2 px-3">Tablets</td>
              <td class="py-2 px-3"><code>md:</code></td>
            </tr>
            <tr class="border-b border-gray-50">
              <td class="py-2 px-3">Large</td>
              <td class="py-2 px-3">1024px</td>
              <td class="py-2 px-3">Small laptops</td>
              <td class="py-2 px-3"><code>lg:</code></td>
            </tr>
            <tr class="border-b border-gray-50">
              <td class="py-2 px-3">Extra Large</td>
              <td class="py-2 px-3">1280px</td>
              <td class="py-2 px-3">Laptops</td>
              <td class="py-2 px-3"><code>xl:</code></td>
            </tr>
            <tr>
              <td class="py-2 px-3">2X Large</td>
              <td class="py-2 px-3">1536px</td>
              <td class="py-2 px-3">Large screens</td>
              <td class="py-2 px-3"><code>2xl:</code></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Typography Scale -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Responsive Typography</h2>
      <div class="space-y-4">
        <div class="border border-gray-100 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">Heading (Title)</span>
            <code class="text-xs bg-gray-100 px-2 py-1 rounded">text-base sm:text-lg</code>
          </div>
          <p class="text-base sm:text-lg text-gray-900">The quick brown fox jumps over the lazy dog</p>
          <p class="text-xs text-gray-500 mt-1">Mobile: 16px → Small+: 18px</p>
        </div>

        <div class="border border-gray-100 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">Body Text</span>
            <code class="text-xs bg-gray-100 px-2 py-1 rounded">text-sm sm:text-base</code>
          </div>
          <p class="text-xs sm:text-sm text-gray-700">This is standard body text that adjusts to screen size for optimal readability.</p>
          <p class="text-xs text-gray-500 mt-1">Mobile: 12px → Small+: 14px</p>
        </div>

        <div class="border border-gray-100 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">Small Text</span>
            <code class="text-xs bg-gray-100 px-2 py-1 rounded">text-xs</code>
          </div>
          <p class="text-xs text-gray-700">Small text for captions and metadata that remains consistent across all screen sizes.</p>
          <p class="text-xs text-gray-500 mt-1">12px (consistent)</p>
        </div>
      </div>
    </div>

    <!-- Spacing System -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Responsive Spacing</h2>
      <div class="space-y-4">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <div class="bg-blue-100 p-4 sm:p-6 border rounded-lg">
              <p class="text-sm font-medium">Card Content</p>
              <p class="text-xs text-gray-600">Mobile: p-4 (16px)<br/>Small+: p-6 (24px)</p>
            </div>
          </div>
        </div>

        <code class="text-sm bg-gray-100 px-3 py-2 rounded block">
          class="p-4 sm:p-6"
        </code>
      </div>
    </div>

    <!-- Implementation Examples -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Usage Examples</h2>

      <h3 class="font-medium text-gray-900 mb-2">Responsive Text</h3>
      <pre class="bg-gray-100 p-3 rounded text-xs mb-4 overflow-x-auto"><code><h2 class="text-lg sm:text-xl md:text-2xl font-bold">
  Responsive Heading
</h2></code></pre>

      <h3 class="font-medium text-gray-900 mb-2">Responsive Spacing</h3>
      <pre class="bg-gray-100 p-3 rounded text-xs mb-4 overflow-x-auto"><code><div class="p-4 sm:p-6 lg:p-8">
  Content with progressive spacing
</div></code></pre>

      <h3 class="font-medium text-gray-900 mb-2">Responsive Grid</h3>
      <pre class="bg-gray-100 p-3 rounded text-xs mb-4 overflow-x-auto"><code><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div></code></pre>

      <h3 class="font-medium text-gray-900 mb-2">Hide/Show Content</h3>
      <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto"><code><div class="hidden md:block">
  This content is hidden on mobile/tablet, shown on desktop
</div></code></pre>
    </div>

    <!-- Best Practices -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Best Practices</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="font-medium text-green-700 mb-2">✅ Do</h3>
          <ul class="text-sm text-gray-600 space-y-1">
            <li>• Start with mobile-first classes</li>
            <li>• Use progressive enhancement</li>
            <li>• Test on real devices</li>
            <li>• Prioritize content hierarchy</li>
            <li>• Use relative units (rem/em)</li>
          </ul>
        </div>
        <div>
          <h3 class="font-medium text-red-700 mb-2">❌ Don't</h3>
          <ul class="text-sm text-gray-600 space-y-1">
            <li>• Don't desktop-first design</li>
            <li>• Avoid fixed pixel values</li>
            <li>• Don't hide content on mobile</li>
            <li>• Avoid complex layouts on small screens</li>
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