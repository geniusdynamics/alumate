# Frontend Technical Documentation

## Component System Architecture

### Component Hierarchy & Organization

#### 1. Base UI Components (`components/ui/`)

**Form Components**:

- `Input.vue`: Text input with validation support
- `Button.vue`: Configurable button with variants
- `Select.vue`: Dropdown selection component
- `Checkbox.vue`: Checkbox with label support
- `Radio.vue`: Radio button groups
- `Textarea.vue`: Multi-line text input
- `Label.vue`: Form field labels
- `FormField.vue`: Complete form field wrapper

**Navigation Components**:

- `Breadcrumb.vue`: Navigation breadcrumb trail
- `NavigationMenu.vue`: Main navigation menu
- `Sidebar.vue`: Collapsible sidebar navigation
- `Tabs.vue`: Tabbed interface component
- `Pagination.vue`: Data pagination controls

**Feedback Components**:

- `Toast.vue`: Notification messages
- `Modal.vue`: Modal dialog system
- `Alert.vue`: Alert messages
- `Progress.vue`: Progress indicators
- `Skeleton.vue`: Loading placeholders

**Data Display**:

- `Table.vue`: Data table with sorting/filtering
- `Card.vue`: Content card container
- `Avatar.vue`: User avatar display
- `Badge.vue`: Status badges
- `Tooltip.vue`: Hover tooltips

#### 2. Layout Components (`components/layout/`)

**AppHeader.vue**:

```typescript
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

// Features:
- Global search integration
- User menu dropdown
- Notification center
- Breadcrumb navigation
- Responsive mobile menu
```

**AppSidebar.vue**:

```typescript
// Features:
- Collapsible navigation
- Role-based menu items
- User profile section
- Institution branding
- Quick actions
```

#### 3. Feature-Specific Components

**Homepage Components** (`components/homepage/`):

- `HeroSection.vue`: Landing page hero
- `AudienceSelector.vue`: User type selection
- `FeaturesShowcase.vue`: Feature highlights
- `PlatformPreview.vue`: Product demonstrations
- `ValueCalculator.vue`: ROI calculator
- `SocialProofSection.vue`: Testimonials and stats
- `ConversionCTAs.vue`: Call-to-action sections

**Alumni Components**:

- `AlumniCard.vue`: Alumni profile card
- `AlumniProfile.vue`: Detailed profile view
- `CareerTimeline.vue`: Career progression display
- `SkillsProfile.vue`: Skills and endorsements
- `ConnectionCard.vue`: Network connections

**Event Components**:

- `EventCard.vue`: Event listing card
- `EventDetailModal.vue`: Event details popup
- `EventRegistrationModal.vue`: Registration form
- `VirtualEventViewer.vue`: Virtual event interface
- `EventHighlights.vue`: Event photo/video gallery

### TypeScript Integration

#### Type Definitions (`types/`)

**Core Types**:

```typescript
// User and Authentication
interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    institution?: Institution;
    profile?: UserProfile;
}

interface UserProfile {
    avatar?: string;
    bio?: string;
    skills: Skill[];
    career_entries: CareerEntry[];
    achievements: Achievement[];
}

// Navigation and UI
interface BreadcrumbItemType {
    label: string;
    href?: string;
    current?: boolean;
}

interface NavItem {
    title: string;
    href: string;
    icon?: Component;
    badge?: string;
    children?: NavItem[];
}

// Events and Activities
interface Event {
    id: number;
    title: string;
    description: string;
    start_date: string;
    end_date: string;
    location?: string;
    virtual_link?: string;
    type: EventType;
    attendees: User[];
}

// Jobs and Career
interface Job {
    id: number;
    title: string;
    company: Company;
    description: string;
    requirements: string[];
    salary_range?: SalaryRange;
    location: string;
    remote_allowed: boolean;
    posted_at: string;
}
```

#### Component Props Typing

**Example: AlumniCard Component**:

```typescript
<script setup lang="ts">
interface Props {
    alumni: User;
    showActions?: boolean;
    compact?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
    compact: false,
    class: '',
});

// Computed properties with proper typing
const displayName = computed(() => 
    props.alumni.profile?.display_name || props.alumni.name
);

const careerSummary = computed(() => 
    props.alumni.profile?.career_entries?.[0]?.title || 'Alumni'
);
</script>
```

### State Management with Pinia

#### Store Structure

**Alumni Map Store** (`stores/alumniMapStore.ts`):

```typescript
export const useAlumniMapStore = defineStore('alumniMap', () => {
    const alumni = ref<AlumniLocation[]>([]);
    const filters = ref<MapFilters>({
        graduationYear: null,
        industry: null,
        location: null,
    });
    const isLoading = ref(false);

    const filteredAlumni = computed(() => {
        return alumni.value.filter(alumnus => {
            if (filters.value.graduationYear && 
                alumnus.graduation_year !== filters.value.graduationYear) {
                return false;
            }
            // Additional filtering logic
            return true;
        });
    });

    const fetchAlumni = async (bounds: MapBounds) => {
        isLoading.value = true;
        try {
            const response = await httpService.get('/api/alumni/map', {
                params: { bounds, ...filters.value }
            });
            alumni.value = response.data;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        alumni: readonly(alumni),
        filters,
        filteredAlumni,
        isLoading: readonly(isLoading),
        fetchAlumni,
    };
});
```

**Events Store** (`stores/eventsStore.ts`):

```typescript
export const useEventsStore = defineStore('events', () => {
    const events = ref<Event[]>([]);
    const currentEvent = ref<Event | null>(null);
    const registrations = ref<EventRegistration[]>([]);

    const upcomingEvents = computed(() => 
        events.value.filter(event => 
            new Date(event.start_date) > new Date()
        ).sort((a, b) => 
            new Date(a.start_date).getTime() - new Date(b.start_date).getTime()
        )
    );

    const registerForEvent = async (eventId: number) => {
        const response = await httpService.post(`/api/events/${eventId}/register`);
        registrations.value.push(response.data);
        return response.data;
    };

    return {
        events: readonly(events),
        currentEvent: readonly(currentEvent),
        upcomingEvents,
        registerForEvent,
    };
});
```

### Composables for Reusable Logic

#### Data Table Composable (`composables/useDataTable.ts`)

```typescript
export function useDataTable<T>(
    fetchFn: (params: TableParams) => Promise<PaginatedResponse<T>>,
    options: TableOptions = {}
) {
    const data = ref<T[]>([]);
    const loading = ref(false);
    const pagination = ref<PaginationState>({
        page: 1,
        perPage: 10,
        total: 0,
    });
    const sorting = ref<SortState>({
        column: null,
        direction: 'asc',
    });
    const filters = ref<Record<string, any>>({});

    const fetch = async () => {
        loading.value = true;
        try {
            const response = await fetchFn({
                page: pagination.value.page,
                perPage: pagination.value.perPage,
                sort: sorting.value,
                filters: filters.value,
            });
            
            data.value = response.data;
            pagination.value.total = response.total;
        } finally {
            loading.value = false;
        }
    };

    const sort = (column: string) => {
        if (sorting.value.column === column) {
            sorting.value.direction = 
                sorting.value.direction === 'asc' ? 'desc' : 'asc';
        } else {
            sorting.value.column = column;
            sorting.value.direction = 'asc';
        }
        fetch();
    };

    const filter = (key: string, value: any) => {
        filters.value[key] = value;
        pagination.value.page = 1; // Reset to first page
        fetch();
    };

    return {
        data: readonly(data),
        loading: readonly(loading),
        pagination,
        sorting: readonly(sorting),
        filters,
        fetch,
        sort,
        filter,
    };
}
```

#### Real-time Updates Composable (`composables/useRealTimeUpdates.js`)

```javascript
export function useRealTimeUpdates(channels = []) {
    const connected = ref(false);
    const connection = ref(null);
    const listeners = new Map();

    const connect = () => {
        if (window.Echo) {
            connection.value = window.Echo;
            connected.value = true;
            
            channels.forEach(channel => {
                subscribeToChannel(channel);
            });
        }
    };

    const subscribeToChannel = (channelName) => {
        if (!connection.value) return;

        const channel = connection.value.channel(channelName);
        listeners.set(channelName, channel);
        
        return {
            listen: (event, callback) => {
                channel.listen(event, callback);
            },
            stopListening: (event) => {
                channel.stopListening(event);
            }
        };
    };

    const disconnect = () => {
        listeners.forEach((channel, channelName) => {
            connection.value.leaveChannel(channelName);
        });
        listeners.clear();
        connected.value = false;
    };

    onMounted(connect);
    onUnmounted(disconnect);

    return {
        connected: readonly(connected),
        subscribeToChannel,
        disconnect,
    };
}
```

### Service Layer Architecture

#### HTTP Service (`services/httpService.ts`)

```typescript
class HttpService {
    private axios: AxiosInstance;

    constructor() {
        this.axios = axios.create({
            baseURL: '/api',
            timeout: 10000,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        this.setupInterceptors();
    }

    private setupInterceptors() {
        // Request interceptor for auth tokens
        this.axios.interceptors.request.use(
            (config) => {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (token) {
                    config.headers['X-CSRF-TOKEN'] = token;
                }
                return config;
            },
            (error) => Promise.reject(error)
        );

        // Response interceptor for error handling
        this.axios.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error.response?.status === 401) {
                    // Handle unauthorized access
                    window.location.href = '/login';
                }
                return Promise.reject(error);
            }
        );
    }

    async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
        const response = await this.axios.get(url, config);
        return response.data;
    }

    async post<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
        const response = await this.axios.post(url, data, config);
        return response.data;
    }

    // Additional HTTP methods...
}

export const httpService = new HttpService();
```

#### Performance Service (`services/PerformanceService.ts`)

```typescript
class PerformanceService {
    private marks: Map<string, number> = new Map();
    private measures: Map<string, number> = new Map();

    markStart(name: string): void {
        this.marks.set(`${name}-start`, performance.now());
        if (performance.mark) {
            performance.mark(`${name}-start`);
        }
    }

    markEnd(name: string): number {
        const endTime = performance.now();
        const startTime = this.marks.get(`${name}-start`);
        
        if (startTime) {
            const duration = endTime - startTime;
            this.measures.set(name, duration);
            
            if (performance.mark && performance.measure) {
                performance.mark(`${name}-end`);
                performance.measure(name, `${name}-start`, `${name}-end`);
            }
            
            return duration;
        }
        
        return 0;
    }

    reportMetrics(): void {
        const metrics = {
            marks: Object.fromEntries(this.marks),
            measures: Object.fromEntries(this.measures),
            navigation: this.getNavigationTiming(),
            resources: this.getResourceTiming(),
        };

        // Send to analytics service
        if (window.gtag) {
            window.gtag('event', 'performance_metrics', {
                custom_parameter: JSON.stringify(metrics)
            });
        }
    }

    private getNavigationTiming() {
        if (!performance.getEntriesByType) return null;
        
        const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
        return {
            domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
            loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
            firstPaint: this.getFirstPaint(),
            firstContentfulPaint: this.getFirstContentfulPaint(),
        };
    }

    private getResourceTiming() {
        if (!performance.getEntriesByType) return [];
        
        return performance.getEntriesByType('resource')
            .map(entry => ({
                name: entry.name,
                duration: entry.duration,
                size: (entry as any).transferSize || 0,
            }))
            .filter(entry => entry.duration > 0);
    }
}

export const performanceService = new PerformanceService();
```

### Build Configuration & Optimization

#### Vite Configuration Highlights

```typescript
export default defineConfig({
    // Code splitting for optimal loading
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-ui': ['@headlessui/vue', '@heroicons/vue'],
                    'homepage-core': [
                        './resources/js/components/homepage/HeroSection.vue',
                        './resources/js/components/homepage/AudienceSelector.vue',
                    ],
                    // Additional chunks for features
                },
                chunkFileNames: (chunkInfo) => {
                    if (chunkInfo.facadeModuleId?.includes('homepage')) {
                        return 'assets/homepage/[name]-[hash].js';
                    }
                    return 'assets/[name]-[hash].js';
                }
            }
        },
        assetsInlineLimit: 4096,
        cssCodeSplit: true,
        sourcemap: process.env.NODE_ENV === 'development',
    },

    // Development optimizations
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'lodash-es'],
        exclude: ['chart.js', 'leaflet'], // Load on demand
    },
});
```

### Testing Strategy

#### Component Testing with Vitest

```typescript
// Example: AlumniCard.test.ts
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import AlumniCard from '@/components/AlumniCard.vue';

describe('AlumniCard', () => {
    const mockAlumni = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        profile: {
            avatar: '/avatar.jpg',
            bio: 'Software Engineer',
            career_entries: [
                { title: 'Senior Developer', company: 'Tech Corp' }
            ]
        }
    };

    it('renders alumni information correctly', () => {
        const wrapper = mount(AlumniCard, {
            props: { alumni: mockAlumni }
        });

        expect(wrapper.text()).toContain('John Doe');
        expect(wrapper.text()).toContain('Software Engineer');
        expect(wrapper.find('img').attributes('src')).toBe('/avatar.jpg');
    });

    it('shows actions when showActions is true', () => {
        const wrapper = mount(AlumniCard, {
            props: { alumni: mockAlumni, showActions: true }
        });

        expect(wrapper.find('[data-testid="alumni-actions"]').exists()).toBe(true);
    });

    it('hides actions when showActions is false', () => {
        const wrapper = mount(AlumniCard, {
            props: { alumni: mockAlumni, showActions: false }
        });

        expect(wrapper.find('[data-testid="alumni-actions"]').exists()).toBe(false);
    });
});
```

This technical documentation provides a comprehensive overview of the frontend architecture, including component organization, TypeScript integration, state management, and testing strategies. The system is designed for scalability, maintainability, and optimal user experience.
