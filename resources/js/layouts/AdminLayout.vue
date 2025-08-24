<template>
  <div class="admin-layout">
    <header class="admin-header">
      <div class="header-content">
        <div class="logo-section">
          <img src="/images/logo.png" alt="Logo" class="logo" />
          <h1 class="site-title">Admin Panel</h1>
        </div>
        <nav class="admin-nav">
          <ul class="nav-list">
            <li><a href="/admin/dashboard" class="nav-link">Dashboard</a></li>
            <li><a href="/admin/users" class="nav-link">Users</a></li>
            <li><a href="/admin/settings" class="nav-link">Settings</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main class="admin-main">
      <div class="content-wrapper">
        <aside class="admin-sidebar">
          <nav class="sidebar-nav">
            <ul class="sidebar-menu">
              <li class="menu-item">
                <a href="/admin/dashboard" class="menu-link">
                  <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                  </svg>
                  Dashboard
                </a>
              </li>
              <li class="menu-item">
                <a href="/admin/users" class="menu-link">
                  <svg class="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                  </svg>
                  Users
                </a>
              </li>
            </ul>
          </nav>
        </aside>

        <div class="admin-content">
          <div class="content-header">
            <h2 class="page-title">{{ title }}</h2>
          </div>
          <div class="page-content">
            <slot />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title?: string;
  breadcrumbs?: any[];
}

const props = withDefaults(defineProps<Props>(), {
  title: "Admin Dashboard",
  breadcrumbs: () => [],
});
</script>

<style scoped>
.admin-layout {
  @apply min-h-screen bg-gray-100;
}

.admin-header {
  @apply bg-white shadow-sm border-b border-gray-200;
}

.header-content {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
  @apply flex items-center justify-between h-16;
}

.logo {
  @apply h-8 w-8;
}

.site-title {
  @apply text-xl font-semibold text-gray-900;
}

.admin-nav {
  @apply hidden md:block;
}

.nav-list {
  @apply flex items-center space-x-6;
}

.nav-link {
  @apply text-gray-700 hover:text-gray-900 transition-colors;
  @apply font-medium px-3 py-2 rounded-md;
}

.admin-main {
  @apply flex-1;
}

.content-wrapper {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6;
  @apply flex space-x-6;
}

.admin-sidebar {
  @apply w-64 flex-shrink-0;
}

.sidebar-nav {
  @apply bg-white rounded-lg shadow-sm p-4;
}

.sidebar-menu {
  @apply space-y-2;
}

.menu-item {
  @apply list-none;
}

.menu-link {
  @apply flex items-center space-x-3 px-3 py-2 rounded-md;
  @apply text-gray-700 hover:text-gray-900 hover:bg-gray-100;
  @apply transition-colors font-medium;
}

.menu-icon {
  @apply h-5 w-5;
}

.admin-content {
  @apply flex-1 bg-white rounded-lg shadow-sm p-6;
}

.content-header {
  @apply mb-6 pb-4 border-b border-gray-200;
}

.page-title {
  @apply text-2xl font-bold text-gray-900 mb-2;
}

.page-content {
  @apply space-y-6;
}

@media (max-width: 768px) {
  .content-wrapper {
    @apply flex-col space-x-0 space-y-6;
  }

  .admin-sidebar {
    @apply w-full;
  }

  .admin-nav {
    @apply hidden;
  }
}
</style>