<template>
  <nav class="homepage-navigation" role="navigation" aria-label="Main navigation">
    <div class="nav-container">
      <!-- Logo and Brand -->
      <div class="nav-brand">
        <Link :href="route('home')" class="brand-link" aria-label="Alumni Platform Home">
          <AppLogoIcon class="brand-icon" />
          <span class="brand-text">Alumni Platform</span>
        </Link>
      </div>

      <!-- Desktop Navigation Menu -->
      <div class="nav-menu-desktop">
        <NavigationMenu>
          <NavigationMenuList class="nav-menu-list">
            <NavigationMenuItem>
              <Link :href="route('home')">
                <NavigationMenuLink :class="[navigationMenuTriggerStyle(), getActiveClass('/')]">
                  Home
                </NavigationMenuLink>
              </Link>
            </NavigationMenuItem>
            
            <NavigationMenuItem>
              <Link :href="route('jobs.public.index')">
                <NavigationMenuLink :class="[navigationMenuTriggerStyle(), getActiveClass('/jobs')]">
                  Jobs
                </NavigationMenuLink>
              </Link>
            </NavigationMenuItem>
            
            <NavigationMenuItem>
              <NavigationMenuTrigger :class="getActiveClass('/alumni')">
                Alumni
              </NavigationMenuTrigger>
              <NavigationMenuContent>
                <div class="nav-dropdown">
                  <Link :href="route('alumni.public.directory')" class="nav-dropdown-item">
                    <Users class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Alumni Directory</div>
                      <div class="nav-dropdown-desc">Connect with fellow graduates</div>
                    </div>
                  </Link>
                  <Link :href="route('alumni.public.map')" class="nav-dropdown-item">
                    <MapPin class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Alumni Map</div>
                      <div class="nav-dropdown-desc">Find alumni near you</div>
                    </div>
                  </Link>
                  <Link :href="route('stories.public.index')" class="nav-dropdown-item">
                    <Star class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Success Stories</div>
                      <div class="nav-dropdown-desc">Read inspiring career journeys</div>
                    </div>
                  </Link>
                </div>
              </NavigationMenuContent>
            </NavigationMenuItem>
            
            <NavigationMenuItem>
              <NavigationMenuTrigger :class="getActiveClass('/about')">
                About
              </NavigationMenuTrigger>
              <NavigationMenuContent>
                <div class="nav-dropdown">
                  <a href="#features" class="nav-dropdown-item">
                    <Zap class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Features</div>
                      <div class="nav-dropdown-desc">Discover what we offer</div>
                    </div>
                  </a>
                  <a href="#pricing" class="nav-dropdown-item">
                    <DollarSign class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Pricing</div>
                      <div class="nav-dropdown-desc">Choose your plan</div>
                    </div>
                  </a>
                  <a href="/contact" class="nav-dropdown-item">
                    <Mail class="nav-dropdown-icon" />
                    <div>
                      <div class="nav-dropdown-title">Contact</div>
                      <div class="nav-dropdown-desc">Get in touch with us</div>
                    </div>
                  </a>
                </div>
              </NavigationMenuContent>
            </NavigationMenuItem>
          </NavigationMenuList>
        </NavigationMenu>
      </div>

      <!-- Search and Actions -->
      <div class="nav-actions">
        <!-- Search -->
        <div class="hidden md:flex items-center">
          <div class="w-64">
            <SearchInput
              v-model="searchQuery"
              placeholder="Search jobs, alumni, courses..."
              @search="handleSearch"
              @clear="handleSearchClear"
            />
          </div>
        </div>

        <!-- Mobile Search Button -->
        <Button 
          variant="ghost" 
          size="icon" 
          class="search-button md:hidden"
          @click="toggleSearch"
          aria-label="Search"
        >
          <Search class="search-icon" />
        </Button>

        <!-- Authentication Buttons -->
        <div v-if="!auth.user" class="auth-buttons">
          <Button 
            variant="ghost" 
            :as-child="true"
            class="login-button"
          >
            <Link :href="route('login')">
              Log In
            </Link>
          </Button>
          
          <Button 
            variant="default" 
            :as-child="true"
            class="signup-button"
          >
            <Link :href="route('register')">
              Sign Up
            </Link>
          </Button>
          
          <Button 
            variant="outline" 
            :as-child="true"
            class="employer-button"
          >
            <Link :href="route('employer.register')">
              <Briefcase class="employer-icon" />
              For Employers
            </Link>
          </Button>
        </div>

        <!-- User Menu (when authenticated) -->
        <div v-else class="user-menu">
          <DropdownMenu>
            <DropdownMenuTrigger :as-child="true">
              <Button variant="ghost" size="icon" class="user-avatar-button">
                <Avatar class="user-avatar">
                  <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                  <AvatarFallback class="user-avatar-fallback">
                    {{ getInitials(auth.user?.name) }}
                  </AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="user-dropdown">
              <UserMenuContent :user="auth.user" />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        <!-- Mobile Menu Toggle -->
        <Button 
          variant="ghost" 
          size="icon" 
          class="mobile-menu-toggle"
          @click="toggleMobileMenu"
          aria-label="Toggle mobile menu"
        >
          <Menu v-if="!isMobileMenuOpen" class="menu-icon" />
          <X v-else class="menu-icon" />
        </Button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div v-if="isMobileMenuOpen" class="mobile-menu" role="menu">
      <div class="mobile-menu-content">
        <!-- Mobile Navigation Links -->
        <div class="mobile-nav-links">
          <Link 
            :href="route('home')" 
            class="mobile-nav-link"
            @click="closeMobileMenu"
            role="menuitem"
          >
            <Home class="mobile-nav-icon" />
            Home
          </Link>
          
          <Link 
            :href="route('jobs.public.index')" 
            class="mobile-nav-link"
            @click="closeMobileMenu"
            role="menuitem"
          >
            <Briefcase class="mobile-nav-icon" />
            Jobs
          </Link>
          
          <Link 
            :href="route('alumni.public.directory')" 
            class="mobile-nav-link"
            @click="closeMobileMenu"
            role="menuitem"
          >
            <Users class="mobile-nav-icon" />
            Alumni Directory
          </Link>
          
          <Link 
            :href="route('alumni.public.map')" 
            class="mobile-nav-link"
            @click="closeMobileMenu"
            role="menuitem"
          >
            <MapPin class="mobile-nav-icon" />
            Alumni Map
          </Link>
          
          <Link 
            :href="route('stories.public.index')" 
            class="mobile-nav-link"
            @click="closeMobileMenu"
            role="menuitem"
          >
            <Star class="mobile-nav-icon" />
            Success Stories
          </Link>
        </div>

        <!-- Mobile Authentication -->
        <div v-if="!auth.user" class="mobile-auth">
          <Button 
            variant="default" 
            :as-child="true"
            class="mobile-auth-button mobile-login"
            @click="closeMobileMenu"
          >
            <Link :href="route('login')">
              Log In
            </Link>
          </Button>
          
          <Button 
            variant="outline" 
            :as-child="true"
            class="mobile-auth-button mobile-signup"
            @click="closeMobileMenu"
          >
            <Link :href="route('register')">
              Sign Up
            </Link>
          </Button>
          
          <Button 
            variant="ghost" 
            :as-child="true"
            class="mobile-auth-button mobile-employer"
            @click="closeMobileMenu"
          >
            <Link :href="route('employer.register')">
              <Briefcase class="mobile-employer-icon" />
              For Employers
            </Link>
          </Button>
        </div>
      </div>
    </div>

    <!-- Search Overlay -->
    <div v-if="isSearchOpen" class="search-overlay" @click="closeSearch">
      <div class="search-container" @click.stop>
        <div class="search-input-wrapper">
          <div class="flex-1">
            <SearchInput
              v-model="searchQuery"
              placeholder="Search jobs, alumni, courses..."
              @search="handleSearch"
              @clear="handleSearchClear"
              class="w-full"
            />
          </div>
          <Button 
            variant="ghost" 
            size="icon" 
            class="search-close ml-2"
            @click="closeSearch"
            aria-label="Close search"
          >
            <X class="search-close-icon" />
          </Button>
        </div>
        
        <div v-if="searchQuery" class="search-suggestions">
          <div class="search-suggestion-group">
            <h4 class="search-suggestion-title">Quick Actions</h4>
            <button class="search-suggestion" @click="navigateToJobs">
              <Briefcase class="search-suggestion-icon" />
              Search Jobs
            </button>
            <button class="search-suggestion" @click="navigateToAlumni">
              <Users class="search-suggestion-icon" />
              Find Alumni
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { 
  NavigationMenu, 
  NavigationMenuContent, 
  NavigationMenuItem, 
  NavigationMenuLink, 
  NavigationMenuList, 
  NavigationMenuTrigger,
  navigationMenuTriggerStyle
} from '@/components/ui/navigation-menu'
import { Button } from '@/components/ui/button'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import AppLogoIcon from '@/components/common/AppLogoIcon.vue'
import UserMenuContent from '@/components/UserMenuContent.vue'
import SearchInput from '@/components/common/SearchInput.vue'
import { getInitials } from '@/composables/useInitials'
import {
  Search,
  Menu,
  X,
  Home,
  Briefcase,
  Users,
  MapPin,
  Star,
  Zap,
  DollarSign,
  Mail
} from 'lucide-vue-next'

const page = usePage()
const auth = computed(() => page.props.auth)

// Mobile menu state
const isMobileMenuOpen = ref(false)
const isSearchOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement | null>(null)

// Search functionality
const handleSearch = (query: string) => {
  if (query.trim()) {
    router.visit('/search', {
      method: 'get',
      data: { q: query },
      preserveState: true
    })
  }
}

const handleSearchClear = () => {
  searchQuery.value = ''
}

// Navigation helpers
const getActiveClass = (path: string) => {
  return page.url.startsWith(path) ? 'text-primary bg-primary/10' : ''
}

// Mobile menu functions
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
  if (isMobileMenuOpen.value) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
  document.body.style.overflow = ''
}

// Search functions
const toggleSearch = () => {
  isSearchOpen.value = !isSearchOpen.value
  if (!isSearchOpen.value) {
    searchQuery.value = ''
  }
}

const closeSearch = () => {
  isSearchOpen.value = false
  searchQuery.value = ''
}

const navigateToJobs = () => {
  router.visit('/jobs')
  closeSearch()
}

const navigateToAlumni = () => {
  router.visit('/alumni')
  closeSearch()
}

// Handle escape key
const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    if (isSearchOpen.value) {
      closeSearch()
    } else if (isMobileMenuOpen.value) {
      closeMobileMenu()
    }
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.homepage-navigation {
  @apply sticky top-0 z-50 w-full border-b border-border bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90 shadow-sm;
}

.nav-container {
  @apply container mx-auto flex h-20 items-center justify-between px-6;
}

.nav-brand {
  @apply flex items-center;
}

.brand-link {
  @apply flex items-center gap-3 font-bold text-2xl text-gray-900 hover:text-blue-600 transition-colors duration-200;
}

.brand-icon {
  @apply h-10 w-10 fill-current text-blue-600;
}

.brand-text {
  @apply hidden sm:block;
}

.nav-menu-desktop {
  @apply hidden lg:flex;
}

.nav-actions {
  @apply flex items-center gap-2;
}

.nav-menu-list {
  @apply flex items-center space-x-1;
}

.nav-menu-item {
  @apply relative;
}

.nav-menu-trigger {
  @apply flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200;
}

.nav-menu-content {
  @apply absolute left-0 top-full mt-1 w-64 rounded-md border border-border bg-popover p-4 shadow-lg;
}

.nav-menu-section {
  @apply space-y-2;
}

.nav-menu-section-title {
  @apply text-sm font-semibold text-foreground;
}

.nav-menu-link {
  @apply block rounded-md p-2 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors duration-200;
}

.nav-menu-link-title {
  @apply font-medium;
}

.nav-menu-link-description {
  @apply text-xs text-muted-foreground;
}

.nav-dropdown {
  @apply grid gap-3 p-6 w-[400px];
}

.nav-dropdown-item {
  @apply flex items-start gap-3 rounded-md p-3 hover:bg-accent transition-colors duration-200;
}

.nav-dropdown-icon {
  @apply h-5 w-5 mt-1 text-muted-foreground;
}

.nav-dropdown-title {
  @apply font-medium text-sm;
}

.nav-dropdown-desc {
  @apply text-xs text-muted-foreground;
}

.nav-actions {
  @apply flex items-center gap-2;
}

.search-button {
  @apply h-9 w-9;
}

.search-icon {
  @apply h-4 w-4;
}

.auth-buttons {
  @apply flex items-center gap-3;
}

.login-button {
  @apply text-gray-700 hover:text-primary font-medium;
}

.signup-button {
  @apply bg-blue-600 text-white hover:bg-blue-700 font-medium px-6 py-2 rounded-lg shadow-sm;
}

.employer-button {
  @apply border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white font-medium px-4 py-2 rounded-lg transition-all duration-200 gap-1;
}

.employer-icon {
  @apply h-4 w-4;
}

.user-menu {
  @apply hidden md:block;
}

.user-avatar-button {
  @apply h-10 w-10 rounded-full;
}

.user-avatar {
  @apply h-8 w-8;
}

.user-avatar-fallback {
  @apply bg-primary text-primary-foreground text-sm font-medium;
}

.user-dropdown {
  @apply w-56;
}

.mobile-menu-toggle {
  @apply lg:hidden h-9 w-9;
}

.menu-icon {
  @apply h-5 w-5;
}

.mobile-menu {
  @apply fixed inset-0 z-50 bg-background/80 backdrop-blur-sm lg:hidden;
}

.mobile-menu-content {
  @apply fixed left-0 top-0 h-full w-3/4 max-w-sm border-r border-border bg-background p-6 shadow-lg;
}

.mobile-menu-header {
  @apply flex items-center justify-between mb-6;
}

.mobile-menu-nav {
  @apply space-y-4;
}

.mobile-menu-section {
  @apply space-y-2;
}

.mobile-menu-section-title {
  @apply text-sm font-semibold text-muted-foreground uppercase tracking-wide;
}

.mobile-nav-links {
  @apply space-y-2;
}

.mobile-nav-link {
  @apply flex items-center gap-3 p-3 rounded-md hover:bg-accent hover:text-accent-foreground transition-colors duration-200 text-foreground;
}

.mobile-nav-icon {
  @apply h-5 w-5 text-muted-foreground;
}

.mobile-auth {
  @apply space-y-2 pt-4 border-t border-border;
}

.mobile-menu-link {
  @apply block rounded-md px-3 py-2 text-base font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors duration-200;
}

.mobile-menu-auth {
  @apply mt-6 pt-6 border-t border-border space-y-2;
}

.mobile-auth-button {
  @apply w-full justify-start;
}

.mobile-employer-icon {
  @apply h-4 w-4;
}

.search-overlay {
  @apply fixed inset-0 z-50 bg-background/80 backdrop-blur-sm;
}

.search-content {
  @apply fixed top-0 left-0 right-0 bg-background border-b border-border p-4 shadow-lg;
}

.search-container {
  @apply absolute top-20 left-1/2 transform -translate-x-1/2 w-full max-w-2xl mx-auto p-4;
}

.search-input-wrapper {
  @apply relative flex items-center bg-background border rounded-lg shadow-lg;
}

.search-input-icon {
  @apply absolute left-3 h-4 w-4 text-muted-foreground;
}

.search-input {
  @apply flex-1 pl-10 pr-12 py-3 bg-transparent border-0 focus:outline-none focus:ring-0 text-foreground placeholder:text-muted-foreground;
}

.search-close {
  @apply absolute right-2 h-8 w-8 p-2 hover:bg-accent hover:text-accent-foreground rounded-md transition-colors duration-200;
}

.search-close-icon {
  @apply h-4 w-4;
}

/* User menu dropdown */
.user-menu-content {
  @apply absolute right-0 top-full mt-1 w-56 rounded-md border border-border bg-popover p-1 shadow-lg;
}

.user-menu-item {
  @apply flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors duration-200 cursor-pointer;
}

.user-menu-separator {
  @apply my-1 h-px bg-border;
}

.search-suggestions {
  @apply mt-4 bg-background border rounded-lg shadow-lg p-4;
}

.search-suggestion-group {
  @apply space-y-2;
}

.search-suggestion-title {
  @apply font-medium text-sm text-muted-foreground mb-2;
}

.search-suggestion {
  @apply flex items-center gap-3 w-full p-2 rounded-md hover:bg-accent transition-colors text-left;
}

.search-suggestion-icon {
  @apply h-4 w-4 text-muted-foreground;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .nav-container {
    @apply px-4;
  }
  
  .search-container {
    @apply top-16 px-4;
  }
}

/* Focus styles for accessibility */
.brand-link:focus,
.mobile-nav-link:focus,
.search-suggestion:focus {
  @apply outline-none ring-2 ring-primary ring-offset-2;
}

/* Animation for mobile menu */
.mobile-menu {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Search overlay animation */
.search-overlay {
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>