<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
import axios from 'axios'

const page = usePage()
const auth = computed(() => page.props.auth)

// Navigation items state
const navigationItems = ref([])

// Mock navigation data as fallback
const mockNavigationData = [
  { id: 1, title: 'Home', url: '/', order: 1, type: 'link', children: [] },
  { id: 2, title: 'Jobs', url: '/jobs', order: 2, type: 'link', children: [] },
  {
    id: 3,
    title: 'Alumni',
    url: '#',
    order: 3,
    type: 'dropdown',
    children: [
      { id: 5, title: 'Alumni Directory', url: '/alumni/directory', order: 1, type: 'link' },
      { id: 6, title: 'Alumni Events', url: '/alumni/events', order: 2, type: 'link' }
    ]
  },
  {
    id: 4,
    title: 'About',
    url: '#',
    order: 4,
    type: 'dropdown',
    children: [
      { id: 7, title: 'Our Story', url: '/about/story', order: 1, type: 'link' },
      { id: 8, title: 'Team', url: '/about/team', order: 2, type: 'link' },
      { id: 9, title: 'Contact', url: '/about/contact', order: 3, type: 'link' }
    ]
  }
]

// Fetch navigation items
onMounted(async () => {
  try {
    const response = await axios.get('/api/homepage-navigation')
    navigationItems.value = response.data
  } catch (error) {
    console.error('Failed to fetch navigation items, using fallback data:', error)
    // Use mock data as fallback when API fails
    navigationItems.value = mockNavigationData
  }
})

// Mobile menu state
const isMobileMenuOpen = ref(false)
const isSearchOpen = ref(false)
const searchQuery = ref('')

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
  document.body.style.overflow = isMobileMenuOpen.value ? 'hidden' : ''
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
  document.body.style.overflow = ''
}

// Search functions
const toggleSearch = () => {
  isSearchOpen.value = !isSearchOpen.value
  if (!isSearchOpen.value) searchQuery.value = ''
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
    if (isSearchOpen.value) closeSearch()
    else if (isMobileMenuOpen.value) closeMobileMenu()
  }
}

// Lifecycle hooks
onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<template>
  <nav class="homepage-navigation" role="navigation" aria-label="Main navigation">
    <div class="nav-container">
      <div class="nav-brand">
        <Link :href="route('home')" class="brand-link" aria-label="Alumni Platform Home">
          <AppLogoIcon class="brand-icon" />
          <span class="brand-text">Alumni Platform</span>
        </Link>
      </div>

      <div class="nav-menu-desktop">
        <NavigationMenu>
          <NavigationMenuList class="nav-menu-list">
            <template v-for="item in navigationItems" :key="item.id">
              <NavigationMenuItem v-if="item.type === 'link'">
                <Link :href="item.url">
                  <NavigationMenuLink :class="[navigationMenuTriggerStyle(), getActiveClass(item.url)]">
                    {{ item.title }}
                  </NavigationMenuLink>
                </Link>
              </NavigationMenuItem>
              <NavigationMenuItem v-if="item.type === 'dropdown'">
                <NavigationMenuTrigger :class="getActiveClass(item.url)">
                  {{ item.title }}
                </NavigationMenuTrigger>
                <NavigationMenuContent>
                  <div class="nav-dropdown">
                    <Link v-for="child in item.children" :key="child.id" :href="child.url" class="nav-dropdown-item">
                       <div>
                        <div class="nav-dropdown-title">{{ child.title }}</div>
                      </div>
                    </Link>
                  </div>
                </NavigationMenuContent>
              </NavigationMenuItem>
            </template>
          </NavigationMenuList>
        </NavigationMenu>
      </div>

      <div class="nav-actions">
        <div class="hidden md:flex items-center">
          <div class="w-64">
            <SearchInput
              v-model="searchQuery"
              placeholder="Search..."
              @search="handleSearch"
              @clear="handleSearchClear"
            />
          </div>
        </div>
        <Button
          variant="ghost"
          size="icon"
          class="search-button md:hidden"
          @click="toggleSearch"
          aria-label="Search"
        >
          <Search class="search-icon" />
        </Button>
        <div v-if="!auth.user" class="auth-buttons">
          <Button variant="ghost" :as-child="true" class="login-button">
            <Link :href="route('login')">Log In</Link>
          </Button>
          <Button variant="default" :as-child="true" class="signup-button">
            <Link :href="route('register')">Sign Up</Link>
          </Button>
          <Button variant="outline" :as-child="true" class="employer-button">
            <Link :href="route('employer.register')">
              <Briefcase class="employer-icon" /> For Employers
            </Link>
          </Button>
        </div>
        <div v-else class="user-menu">
          <DropdownMenu>
            <DropdownMenuTrigger :as-child="true">
              <Button variant="ghost" size="icon" class="user-avatar-button">
                <Avatar class="user-avatar">
                  <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                  <AvatarFallback class="user-avatar-fallback">{{ getInitials(auth.user?.name) }}</AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="user-dropdown">
              <UserMenuContent :user="auth.user" />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
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

    <div v-if="isMobileMenuOpen" class="mobile-menu" role="menu">
      <div class="mobile-menu-content">
        <div class="mobile-nav-links">
          <template v-for="item in navigationItems" :key="item.id">
            <Link :href="item.url" class="mobile-nav-link" @click="closeMobileMenu" role="menuitem">
              {{ item.title }}
            </Link>
            <template v-if="item.children && item.children.length > 0">
               <Link v-for="child in item.children" :key="child.id" :href="child.url" class="mobile-nav-link pl-8" @click="closeMobileMenu" role="menuitem">
                {{ child.title }}
              </Link>
            </template>
          </template>
        </div>
        <div v-if="!auth.user" class="mobile-auth">
          <Button variant="default" :as-child="true" class="mobile-auth-button mobile-login" @click="closeMobileMenu">
            <Link :href="route('login')">Log In</Link>
          </Button>
          <Button variant="outline" :as-child="true" class="mobile-auth-button mobile-signup" @click="closeMobileMenu">
            <Link :href="route('register')">Sign Up</Link>
          </Button>
           <Button variant="ghost" :as-child="true" class="mobile-auth-button mobile-employer" @click="closeMobileMenu">
            <Link :href="route('employer.register')">
              <Briefcase class="mobile-employer-icon" /> For Employers
            </Link>
          </Button>
        </div>
      </div>
    </div>

  </nav>
</template>

<style scoped>
.homepage-navigation {
  @apply sticky top-0 z-50 w-full border-b border-border bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90 shadow-sm transition-all duration-300;
}
.nav-container {
  @apply container mx-auto flex h-24 items-center justify-between px-6; /* Increased height */
}
.nav-brand { @apply flex items-center; }
.brand-link { @apply flex items-center gap-3 font-bold text-2xl text-gray-900 hover:text-primary transition-colors duration-300; }
.brand-icon { @apply h-10 w-10 text-primary; }
.brand-text { @apply hidden sm:block; }
.nav-menu-desktop { @apply hidden lg:flex; }
.nav-actions { @apply flex items-center gap-3; }
.nav-menu-list { @apply flex items-center space-x-2; }
.nav-dropdown { @apply grid gap-3 p-4 w-[300px]; }
.nav-dropdown-item { @apply flex items-start gap-4 rounded-lg p-3 hover:bg-accent transition-colors duration-200; }
.nav-dropdown-title { @apply font-semibold text-sm; }
.auth-buttons { @apply flex items-center gap-2; }
.login-button { @apply text-foreground/80 hover:text-foreground; }
.signup-button { @apply bg-primary text-primary-foreground hover:bg-primary/90 rounded-full px-6; }
.employer-button { @apply border-primary/50 text-primary hover:bg-primary/10 hover:border-primary rounded-full px-4 gap-2; }
.employer-icon { @apply h-4 w-4; }
.user-menu { @apply hidden md:block; }
.user-avatar-button { @apply h-10 w-10 rounded-full; }
.user-avatar { @apply h-10 w-10; }
.user-avatar-fallback { @apply bg-primary text-primary-foreground font-semibold; }
.user-dropdown { @apply w-56; }
.mobile-menu-toggle { @apply lg:hidden h-10 w-10; }
.menu-icon { @apply h-6 w-6; }
.mobile-menu {
  @apply fixed inset-0 z-50 bg-black/50 backdrop-blur-sm lg:hidden;
  animation: fadeIn 0.3s ease-out;
}
.mobile-menu-content {
  @apply fixed left-0 top-0 h-full w-4/5 max-w-xs border-r border-border bg-background p-6 shadow-xl;
  animation: slideIn 0.3s ease-out;
}
.mobile-nav-links { @apply space-y-1 mt-6; }
.mobile-nav-link { @apply flex items-center gap-4 p-4 rounded-lg text-lg font-medium text-foreground/80 hover:bg-accent hover:text-foreground transition-colors duration-200; }
.mobile-auth { @apply space-y-3 pt-6 border-t border-border; }
.mobile-auth-button { @apply w-full justify-center text-lg py-3 rounded-full; }
.mobile-employer-icon { @apply h-5 w-5; }
.search-button { @apply h-10 w-10; }
.search-icon { @apply h-5 w-5; }

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { transform: translateX(-100%); }
  to { transform: translateX(0); }
}
</style>