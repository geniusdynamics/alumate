<script setup lang="ts">
import AppLogoIcon from '@/Components/common/AppLogoIcon.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import ProfessionalMegaMenu from '@/Components/ProfessionalMegaMenu.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/Components/ui/navigation-menu';
import { Input } from '@/Components/ui/input';
import UserMenuContent from '@/Components/UserMenuContent.vue';
import { getInitials } from '@/Composables/useInitials';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Search, 
    Menu, 
    X, 
    User, 
    LogOut, 
    Settings, 
    ChevronDown, 
    ArrowRight,
    GraduationCap,
    Users,
    Briefcase,
    BarChart,
    Building,
    Heart,
    Target,
    Network,
    TrendingUp,
    Award,
    Megaphone,
    Filter,
    Calendar,
    Trophy,
    Calculator,
    BookOpen,
    CheckCircle,
    UserPlus,
    Mic,
    Gift,
    Book,
    Star,
    FileText
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth || { user: null });

// Navigation items state
const navigationItems = ref([]);

        // Navigation data matching 12twenty style mega menu
        const mockNavigationData = [
    { id: 1, title: 'Home', url: '/', order: 1, type: 'link', children: [] },
    {
        id: 2,
        title: 'Use Cases',
        url: '#',
        order: 2,
        type: 'mega-menu',
        children: [
            {
                id: 21,
                category: 'Use Cases for Universities',
                items: [
                    { title: 'University Career Centers', url: '/use-cases/university-career-centers', description: 'Streamline career services operations', icon: 'users' },
                    { title: 'Alumni Relations', url: '/use-cases/alumni-relations', description: 'Enhance alumni engagement strategies', icon: 'heart' },
                    { title: 'Institutional Research', url: '/use-cases/institutional-research', description: 'Track graduate outcomes effectively', icon: 'bar-chart' }
                ]
            },
            {
                id: 22,
                category: 'Use Cases for Employers',
                items: [
                    { title: 'Hiring MBAs', url: '/use-cases/hiring-mbas', description: 'Access top business school talent', icon: 'briefcase' },
                    { title: 'Undergraduate Recruiting', url: '/use-cases/undergraduate-recruiting', description: 'Recruit from undergraduate programs', icon: 'graduation-cap' },
                    { title: 'Hiring from Law Schools', url: '/use-cases/law-school-recruiting', description: 'Connect with legal profession talent', icon: 'scale' }
                ]
            }
        ]
    },
    {
        id: 3,
        title: 'Products',
        url: '#',
        order: 3,
        type: 'mega-menu',
        children: [
            {
                id: 31,
                category: 'Products for Student Career Success',
                items: [
                    { title: 'Career Services Hub', url: '/products/career-hub', description: 'Comprehensive career management', icon: 'briefcase' },
                    { title: 'Outcome Data Analytics Hub', url: '/products/analytics-hub', description: 'Track and analyze outcomes', icon: 'bar-chart' },
                    { title: 'Experiential Learning Hub', url: '/products/learning-hub', description: 'Hands-on learning experiences', icon: 'book-open' },
                    { title: 'Employer Relationship Management Hub', url: '/products/employer-hub', description: 'Build industry partnerships', icon: 'building' },
                    { title: 'Student-Alumni Community Hub', url: '/products/community-hub', description: 'Connect students and alumni', icon: 'users' }
                ]
            },
            {
                id: 32,
                category: 'Products for Continuous Alumni Success',
                items: [
                    { title: 'Alumni Career Services Hub', url: '/products/alumni-career-hub', description: 'Ongoing career support', icon: 'briefcase' },
                    { title: 'Alumni Outcome Data Analytics Hub', url: '/products/alumni-analytics-hub', description: 'Alumni outcome tracking', icon: 'bar-chart' },
                    { title: 'Alumni Community Engagement Hub', url: '/products/alumni-engagement-hub', description: 'Engagement and networking', icon: 'heart' }
                ]
            },
            {
                id: 33,
                category: 'Products for Recruiting Top Talent',
                items: [
                    { title: 'Talent Acquisition Hub', url: '/products/talent-hub', description: 'Advanced recruitment tools', icon: 'target' },
                    { title: 'Virtual Events Hub', url: '/products/events-hub', description: 'Virtual recruiting events', icon: 'video' },
                    { title: 'Recruiting Intelligence Hub', url: '/products/intelligence-hub', description: 'Market insights and data', icon: 'trending-up' },
                    { title: 'Multischool Interviews Hub', url: '/products/interviews-hub', description: 'Cross-institution interviews', icon: 'calendar' }
                ]
            }
        ]
    },
    {
        id: 4,
        title: 'Resources',
        url: '#',
        order: 4,
        type: 'mega-menu',
        children: [
            {
                id: 41,
                category: 'Learn & Grow',
                items: [
                    { title: 'Success Stories', url: '/resources/success-stories', description: 'Real impact stories', icon: 'star' },
                    { title: 'Blog', url: '/resources/blog', description: 'Industry insights and tips', icon: 'newspaper' },
                    { title: 'Webinars', url: '/resources/webinars', description: 'Educational sessions', icon: 'video' },
                    { title: '12twenty_live 2025', url: '/resources/conference', description: 'Annual conference', icon: 'calendar' },
                    { title: 'Marketing Toolkit', url: '/resources/toolkit', description: 'Marketing resources', icon: 'briefcase' },
                    { title: 'Events', url: '/resources/events', description: 'Upcoming events', icon: 'calendar' }
                ]
            },
            {
                id: 42,
                category: 'Company Info',
                items: [
                    { title: 'About Us', url: '/company/about', description: 'Learn about our mission', icon: 'building' },
                    { title: 'Careers', url: '/company/careers', description: 'Join our team', icon: 'user' }
                ]
            }
        ]
    },
];

// Fetch navigation items
onMounted(async () => {
    try {
        const response = await axios.get('/api/homepage-navigation');
        navigationItems.value = response.data;
    } catch (error) {
        console.error('Failed to fetch navigation items, using fallback data:', error);
        // Use mock data as fallback when API fails
        navigationItems.value = mockNavigationData;
    }
});

// Mobile menu state
const isMobileMenuOpen = ref(false);
const isSearchOpen = ref(false);
const searchQuery = ref('');

// Search functionality
const handleSearch = (query: string) => {
    if (query.trim()) {
        router.visit('/search', {
            method: 'get',
            data: { q: query },
            preserveState: true,
        });
    }
};

const handleSearchClear = () => {
    searchQuery.value = '';
};

// Navigation helpers
const getActiveClass = (path: string) => {
    return page.url.startsWith(path) ? 'text-primary bg-primary/10' : '';
};

// Mobile menu functions
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
    document.body.style.overflow = isMobileMenuOpen.value ? 'hidden' : '';
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
    document.body.style.overflow = '';
};

// Icon mapping function for mega menu
const getIcon = (iconName) => {
    const iconMap = {
        'graduation-cap': GraduationCap,
        'users': Users,
        'briefcase': Briefcase,
        'bar-chart': BarChart,
        'building': Building,
        'heart': Heart,
        'search': Search,
        'target': Target,
        'network': Network,
        'trending-up': TrendingUp,
        'award': Award,
        'megaphone': Megaphone,
        'filter': Filter,
        'calendar': Calendar,
        'trophy': Trophy,
        'calculator': Calculator,
        'book-open': BookOpen,
        'check-circle': CheckCircle,
        'user-plus': UserPlus,
        'mic': Mic,
        'gift': Gift,
        'book': Book,
        'star': Star,
        'file-text': FileText
    };
    return iconMap[iconName] || Users;
};

// Search functions
const toggleSearch = () => {
    isSearchOpen.value = !isSearchOpen.value;
    if (!isSearchOpen.value) searchQuery.value = '';
};

const closeSearch = () => {
    isSearchOpen.value = false;
    searchQuery.value = '';
};

const navigateToJobs = () => {
    router.visit('/jobs');
    closeSearch();
};

const navigateToAlumni = () => {
    router.visit('/alumni');
    closeSearch();
};

// Handle escape key
const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        if (isSearchOpen.value) closeSearch();
        else if (isMobileMenuOpen.value) closeMobileMenu();
    }
};

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
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
                <ProfessionalMegaMenu />
            </div>

            <div class="nav-actions">
                <div class="hidden items-center md:flex">
                    <div class="w-64">
                        <SearchInput v-model="searchQuery" placeholder="Search..." @search="handleSearch" @clear="handleSearchClear" />
                    </div>
                </div>
                <Button variant="ghost" size="icon" class="search-button md:hidden" @click="toggleSearch" aria-label="Search">
                    <Search class="search-icon" />
                </Button>
                <div v-if="!auth?.user" class="auth-buttons">
                    <Button variant="ghost" :as-child="true" class="login-button">
                        <Link :href="route('login')">Log In</Link>
                    </Button>
                    <Button variant="default" :as-child="true" class="signup-button">
                        <Link :href="route('register')">Sign Up</Link>
                    </Button>
                    <Button variant="outline" :as-child="true" class="employer-button">
                        <Link :href="route('employer.register')"> <Briefcase class="employer-icon" /> For Employers </Link>
                    </Button>
                </div>
                <div v-else-if="auth?.user" class="user-menu">
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="user-avatar-button">
                                <Avatar class="user-avatar">
                                    <AvatarImage v-if="auth?.user?.avatar" :src="auth?.user?.avatar" :alt="auth?.user?.name" />
                                    <AvatarFallback class="user-avatar-fallback">{{ getInitials(auth?.user?.name) }}</AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="user-dropdown">
                            <UserMenuContent :user="auth?.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <Button variant="ghost" size="icon" class="mobile-menu-toggle" @click="toggleMobileMenu" aria-label="Toggle mobile menu">
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
                            <Link
                                v-for="child in item.children"
                                :key="child.id"
                                :href="child.url"
                                class="mobile-nav-link pl-8"
                                @click="closeMobileMenu"
                                role="menuitem"
                            >
                                {{ child.title }}
                            </Link>
                        </template>
                    </template>
                </div>
                <div v-if="!auth?.user" class="mobile-auth">
                    <Button variant="default" :as-child="true" class="mobile-auth-button mobile-login" @click="closeMobileMenu">
                        <Link :href="route('login')">Log In</Link>
                    </Button>
                    <Button variant="outline" :as-child="true" class="mobile-auth-button mobile-signup" @click="closeMobileMenu">
                        <Link :href="route('register')">Sign Up</Link>
                    </Button>
                    <Button variant="ghost" :as-child="true" class="mobile-auth-button mobile-employer" @click="closeMobileMenu">
                        <Link :href="route('employer.register')"> <Briefcase class="mobile-employer-icon" /> For Employers </Link>
                    </Button>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.homepage-navigation {
    @apply sticky top-0 z-50 w-full border-b border-gray-200 bg-white shadow-sm transition-all duration-300;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
}
.nav-container {
    @apply container mx-auto flex h-24 items-center justify-between px-6;
    position: relative;
}
.nav-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(59, 130, 246, 0.05) 50%, transparent 100%);
    pointer-events: none;
}
.nav-brand {
    @apply flex items-center relative z-10;
}
.brand-link {
    @apply flex items-center gap-3 text-xl font-bold text-gray-900 transition-all duration-300 hover:text-blue-600;
    position: relative;
    font-weight: 700;
}
.brand-link::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #3b82f6;
    border-radius: 1px;
    transition: width 0.3s ease;
    transform-origin: left;
}
.brand-link:hover::after {
    width: 100%;
}
.brand-icon {
    @apply h-10 w-10;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.3));
}
.brand-text {
    @apply hidden sm:block;
}
.nav-menu-desktop {
    @apply hidden lg:flex relative z-10;
}
.nav-actions {
    @apply flex items-center gap-3 relative z-10;
}
.nav-menu-list {
    @apply flex items-center space-x-8;
}

.nav-menu-list .navigation-menu__trigger {
    @apply relative px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:text-blue-600;
}

.nav-menu-list .navigation-menu__trigger:hover {
    color: #3b82f6;
}

.nav-menu-list .navigation-menu__trigger[aria-expanded='true'] {
    @apply text-blue-600;
}

.nav-menu-list .navigation-menu__trigger::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #3b82f6;
    border-radius: 1px;
    transition: width 0.3s ease;
}

.nav-menu-list .navigation-menu__trigger[aria-expanded='true']::after,
.nav-menu-list .navigation-menu__trigger:hover::after {
    width: 100%;
}
.nav-dropdown {
    @apply grid w-[300px] gap-3 p-4;
}
.nav-dropdown-item {
    @apply flex items-start gap-4 rounded-lg p-3 transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 hover:shadow-md border border-transparent hover:border-blue-100;
}
.nav-dropdown-title {
    @apply text-sm font-semibold;
}
.auth-buttons {
    @apply flex items-center gap-3;
}
.login-button {
    @apply relative overflow-hidden px-4 py-2 rounded-lg font-medium transition-all duration-300 hover:scale-105;
    background: linear-gradient(135deg, transparent 0%, rgba(59, 130, 246, 0.05) 100%);
    border: 1px solid transparent;
    color: #374151;
}
.login-button:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
    border-color: rgba(59, 130, 246, 0.2);
    color: #1e40af;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}
.signup-button {
    @apply relative overflow-hidden rounded-full px-6 py-2 font-semibold text-white transition-all duration-300 hover:scale-105;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(99, 102, 241, 0.9) 50%, rgba(139, 92, 246, 0.9) 100%);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 
        0 10px 25px -5px rgba(59, 130, 246, 0.3),
        0 8px 10px -6px rgba(59, 130, 246, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    font-weight: 600;
    letter-spacing: 0.025em;
    text-transform: uppercase;
}
.signup-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.8s ease;
}
.signup-button:hover::before {
    left: 100%;
}
.signup-button:hover {
    box-shadow: 
        0 20px 40px -10px rgba(59, 130, 246, 0.4),
        0 16px 20px -8px rgba(59, 130, 246, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    transform: translateY(-3px) scale(1.05);
}
.employer-button {
    @apply relative overflow-hidden gap-2 rounded-full px-4 py-2 font-medium transition-all duration-300 hover:scale-105;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #3b82f6;
}
.employer-button:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    transform: translateY(-1px) scale(1.05);
}
.employer-icon {
    @apply h-4 w-4 transition-transform duration-300;
}
.employer-button:hover .employer-icon {
    transform: rotate(5deg) scale(1.1);
}
.user-menu {
    @apply hidden md:block;
}
.user-avatar-button {
    @apply h-10 w-10 rounded-full;
}
.user-avatar {
    @apply h-10 w-10;
}
.user-avatar-fallback {
    @apply bg-primary font-semibold text-primary-foreground;
}
.user-dropdown {
    @apply w-56;
}
.mobile-menu-toggle {
    @apply h-10 w-10 lg:hidden;
}
.menu-icon {
    @apply h-6 w-6;
}
.mobile-menu {
    @apply fixed inset-0 z-50 lg:hidden;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease-out;
}
.mobile-menu-content {
    @apply fixed left-0 top-0 h-full w-4/5 max-w-xs p-6 shadow-2xl;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
    backdrop-filter: blur(20px);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideIn 0.3s ease-out;
}
.mobile-menu-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
    pointer-events: none;
}
.mobile-nav-links {
    @apply mt-6 space-y-2 relative z-10;
}
.mobile-nav-link {
    @apply relative overflow-hidden flex items-center gap-4 rounded-xl p-4 text-lg font-medium transition-all duration-300;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(248, 250, 252, 0.8) 100%);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #374151;
}
.mobile-nav-link:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
    border-color: rgba(59, 130, 246, 0.3);
    color: #1e40af;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}
.mobile-nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}
.mobile-nav-link:hover::before {
    transform: scaleY(1);
}
.mobile-auth {
    @apply space-y-3 border-t border-white/20 pt-6 relative z-10;
}
.mobile-auth-button {
    @apply w-full justify-center rounded-full py-3 text-lg font-semibold transition-all duration-300 hover:scale-105;
}
.mobile-employer-icon {
    @apply h-5 w-5;
}
.search-button {
    @apply h-10 w-10;
}
.search-icon {
    @apply h-5 w-5;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(0);
    }
}
</style>
