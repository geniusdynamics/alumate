import {
    BarChart3,
    Bell,
    BookOpen,
    Briefcase,
    Building,
    Calendar,
    Database,
    DollarSign,
    FileText,
    GitMerge,
    GraduationCap,
    Heart,
    HeartPulse,
    Home,
    MessageCircle,
    Palette,
    PieChart,
    Plug,
    Settings,
    Shield,
    Star,
    Target,
    Trophy,
    UserCheck,
    UserPlus,
    Users,
} from 'lucide-vue-next';

// --- Icon Aliases ---
const ChartBarIcon = BarChart3;
const DocumentTextIcon = FileText;
const ChartPieIcon = PieChart;
const CircleStackIcon = Database;

// --- Role-Based Navigation Menus ---

const safeRoute = (name: string, params?: unknown) => {
    try {
        return route(name as never, params as never);
    } catch {
        return '#';
    }
};

const safeCurrent = (name: string) => {
    try {
        return route().current(name);
    } catch {
        return false;
    }
};

export const graduateMenuItems = [
    { title: 'Dashboard', icon: Home, href: safeRoute('dashboard'), active: safeCurrent('dashboard') },
    {
        title: 'My Applications',
        icon: Briefcase,
        href: safeRoute('graduate.applications'),
        active: safeCurrent('graduate.applications'),
        permission: 'view applications',
    },
    { title: 'Job Dashboard', icon: Briefcase, href: safeRoute('jobs.dashboard'), active: safeCurrent('jobs.dashboard*'), permission: 'view jobs' },
    { title: 'Career Timeline', icon: Target, href: safeRoute('career.timeline'), active: safeCurrent('career.timeline'), permission: 'view career' },
    {
        title: 'Mentorship Hub',
        icon: Users,
        href: safeRoute('career.mentorship-hub'),
        active: safeCurrent('career.mentorship-hub'),
        permission: 'view career',
    },
    { title: 'Social Timeline', icon: MessageCircle, href: safeRoute('social.timeline'), active: safeCurrent('social.*'), permission: 'view social' },
    {
        title: 'Alumni Directory',
        icon: UserPlus,
        href: safeRoute('alumni.directory'),
        active: safeCurrent('alumni.directory'),
        permission: 'view alumni',
    },
    { title: 'Events', icon: Calendar, href: safeRoute('events.discovery'), active: safeCurrent('events.*'), permission: 'view events' },
    {
        title: 'Scholarships',
        icon: GraduationCap,
        href: safeRoute('scholarships.index'),
        active: safeCurrent('scholarships.*'),
        permission: 'view scholarships',
    },
    { title: 'Success Stories', icon: Star, href: safeRoute('stories.index'), active: safeCurrent('stories.*'), permission: 'view stories' },
    {
        title: 'Achievements',
        icon: Trophy,
        href: safeRoute('achievements.index'),
        active: safeCurrent('achievements.*'),
        permission: 'view achievements',
    },
    {
        title: 'Education History',
        icon: BookOpen,
        href: safeRoute('education.index'),
        active: safeCurrent('education.*'),
        permission: 'manage education',
    },
    {
        title: 'Request Assistance',
        icon: Heart,
        href: safeRoute('assistance.index'),
        active: safeCurrent('assistance.*'),
        permission: 'request assistance',
    },
];

export const employerMenuItems = [
    { title: 'Dashboard', icon: Home, href: safeRoute('dashboard'), active: safeCurrent('dashboard') },
    { title: 'Manage Jobs', icon: Briefcase, href: safeRoute('jobs.dashboard'), active: safeCurrent('jobs.dashboard*'), permission: 'view jobs' },
    {
        title: 'Search Graduates',
        icon: UserPlus,
        href: safeRoute('employer.search-graduates'),
        active: safeCurrent('employer.search-graduates'),
        permission: 'view graduates',
    },
    // { title: 'Company Profile', icon: Building, href: route('employer.profile'), active: route().current('employer.profile'), permission: 'manage company' },
    { title: 'Events', icon: Calendar, href: safeRoute('events.discovery'), active: safeCurrent('events.*'), permission: 'view events' },
];

export const institutionAdminMenuItems = [
    { title: 'Dashboard', icon: Home, href: safeRoute('dashboard'), active: safeCurrent('dashboard') },
    {
        title: 'Manage Graduates',
        icon: GraduationCap,
        href: safeRoute('institution-admin.graduates.index'),
        active: safeCurrent('institution-admin.graduates.*'),
        permission: 'manage graduates',
    },
    {
        title: 'Manage Courses',
        icon: BookOpen,
        href: safeRoute('institution-admin.courses.index'),
        active: safeCurrent('institution-admin.courses.*'),
        permission: 'manage courses',
    },
    {
        title: 'Manage Tutors',
        icon: Users,
        href: safeRoute('institution-admin.tutors.index'),
        active: safeCurrent('institution-admin.tutors.*'),
        permission: 'manage tutors',
    },
    {
        title: 'Manage Jobs',
        icon: Briefcase,
        href: safeRoute('institution-admin.jobs.public.index'),
        active: safeCurrent('institution-admin.jobs.public.index'),
        permission: 'view jobs',
    },
    {
        title: 'Approve Companies',
        icon: UserCheck,
        href: safeRoute('institution-admin.companies.index'),
        active: safeCurrent('institution-admin.companies.*'),
        permission: 'approve companies',
    },
    {
        title: 'Merge Records',
        icon: GitMerge,
        href: safeRoute('merge.index'),
        active: safeCurrent('merge.*'),
        permission: 'merge records',
    },
    {
        title: 'User Management',
        icon: Users,
        href: safeRoute('institution-admin.users.index'),
        active: safeCurrent('institution-admin.users.*'),
        permission: 'view users',
    },
    {
        title: 'Role Management',
        icon: Shield,
        href: safeRoute('institution-admin.roles.index'),
        active: safeCurrent('institution-admin.roles.*'),
        permission: 'view roles',
    },
    {
        title: 'Fundraising',
        icon: Heart,
        href: safeRoute('campaigns.index'),
        active: safeCurrent('campaigns.*'),
        permission: 'view fundraising',
    },
    { title: 'Analytics', icon: ChartBarIcon, href: safeRoute('institution-admin.analytics'), active: safeCurrent('institution-admin.analytics') },
    {
        title: 'Course ROI',
        icon: DollarSign,
        href: safeRoute('institution-admin.analytics.course-roi'),
        active: safeCurrent('institution-admin.analytics.course-roi'),
    },
    {
        title: 'Employer Engagement',
        icon: Briefcase,
        href: safeRoute('institution-admin.analytics.employer-engagement'),
        active: safeCurrent('institution-admin.analytics.employer-engagement'),
    },
    {
        title: 'Community Health',
        icon: HeartPulse,
        href: safeRoute('institution-admin.analytics.community-health'),
        active: safeCurrent('institution-admin.analytics.community-health'),
    },
    {
        title: 'Branding',
        icon: Palette,
        href: safeRoute('institution-admin.settings.branding'),
        active: safeCurrent('institution-admin.settings.branding'),
    },
    {
        title: 'Integrations',
        icon: Plug,
        href: safeRoute('institution-admin.settings.integrations'),
        active: safeCurrent('institution-admin.settings.integrations'),
    },
    {
        title: 'Institution Settings',
        icon: Settings,
        href: safeRoute('institution-admin.institution.edit'),
        active: safeCurrent('institution-admin.institution.edit'),
        permission: 'manage institution',
    },
];

export const superAdminMenuItems = [
    { title: 'Dashboard', icon: Home, href: safeRoute('super-admin.dashboard'), active: safeCurrent('super-admin.dashboard') },
    {
        title: 'Institutions',
        icon: Building,
        href: safeRoute('institutions.index'),
        active: safeCurrent('institutions.*'),
        permission: 'view institutions',
    },
    { title: 'System Analytics', icon: ChartBarIcon, href: safeRoute('super-admin.analytics'), active: safeCurrent('super-admin.analytics') },
    { title: 'Content Management', icon: DocumentTextIcon, href: safeRoute('super-admin.content'), active: safeCurrent('super-admin.content') },
    { title: 'Activity Monitoring', icon: ChartPieIcon, href: safeRoute('super-admin.activity'), active: safeCurrent('super-admin.activity') },
    { title: 'Database Management', icon: CircleStackIcon, href: safeRoute('super-admin.database'), active: safeCurrent('super-admin.database') },
    { title: 'Performance', icon: ChartBarIcon, href: safeRoute('super-admin.performance'), active: safeCurrent('super-admin.performance') },
    { title: 'Notifications', icon: Bell, href: safeRoute('super-admin.notifications'), active: safeCurrent('super-admin.notifications') },
    { title: 'System Settings', icon: Settings, href: safeRoute('super-admin.settings'), active: safeCurrent('super-admin.settings') },
    { title: 'Security Dashboard', icon: Shield, href: safeRoute('security.dashboard'), active: safeCurrent('security.*') },
    {
        title: 'Manage Admins',
        icon: Users,
        href: safeRoute('super-admins.index'),
        active: safeCurrent('super-admins.*'),
        permission: 'manage super admins',
    },
];

export const personalMenuItems = [
    { title: 'My Profile', icon: Users, href: safeRoute('profile.show'), active: safeCurrent('profile.*') },
    { title: 'Settings', icon: Settings, href: safeRoute('settings.profile'), active: safeCurrent('settings.*') },
];
