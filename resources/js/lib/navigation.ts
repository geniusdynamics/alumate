import { Home, Users, Settings, Shield, Bell, MessageCircle, UserPlus, Briefcase, Calendar, Star, Target, BarChart3, FileText, PieChart, Database, Heart, GraduationCap, Trophy, Building, BookOpen, GitMerge, UserCheck, Palette, Plug } from 'lucide-vue-next';

// --- Icon Aliases ---
const ChartBarIcon = BarChart3;
const DocumentTextIcon = FileText;
const ChartPieIcon = PieChart;
const CircleStackIcon = Database;

// --- Role-Based Navigation Menus ---

export const graduateMenuItems = [
    { title: 'Dashboard', icon: Home, href: route('dashboard'), active: route().current('dashboard') },
    { title: 'My Applications', icon: Briefcase, href: route('my.applications'), active: route().current('my.applications'), permission: 'view applications' },
    { title: 'Job Dashboard', icon: Briefcase, href: route('jobs.dashboard'), active: route().current('jobs.dashboard*'), permission: 'view jobs' },
    { title: 'Career Timeline', icon: Target, href: route('career.timeline'), active: route().current('career.timeline'), permission: 'view career' },
    { title: 'Mentorship Hub', icon: Users, href: route('career.mentorship-hub'), active: route().current('career.mentorship-hub'), permission: 'view career' },
    { title: 'Social Timeline', icon: MessageCircle, href: route('social.timeline'), active: route().current('social.*'), permission: 'view social' },
    { title: 'Alumni Directory', icon: UserPlus, href: route('alumni.directory'), active: route().current('alumni.directory'), permission: 'view alumni' },
    { title: 'Events', icon: Calendar, href: route('events.discovery'), active: route().current('events.*'), permission: 'view events' },
    { title: 'Scholarships', icon: GraduationCap, href: route('scholarships.index'), active: route().current('scholarships.*'), permission: 'view scholarships' },
    { title: 'Success Stories', icon: Star, href: route('stories.index'), active: route().current('stories.*'), permission: 'view stories' },
    { title: 'Achievements', icon: Trophy, href: route('achievements.index'), active: route().current('achievements.*'), permission: 'view achievements' },
    { title: 'Education History', icon: BookOpen, href: route('education.index'), active: route().current('education.*'), permission: 'manage education' },
    { title: 'Request Assistance', icon: Heart, href: route('assistance.index'), active: route().current('assistance.*'), permission: 'request assistance' },
];

export const employerMenuItems = [
    { title: 'Dashboard', icon: Home, href: route('dashboard'), active: route().current('dashboard') },
    { title: 'Manage Jobs', icon: Briefcase, href: route('jobs.dashboard'), active: route().current('jobs.dashboard*'), permission: 'view jobs' },
    { title: 'Search Graduates', icon: UserPlus, href: route('graduates.search'), active: route().current('graduates.search'), permission: 'view graduates' },
    // { title: 'Company Profile', icon: Building, href: route('employer.profile'), active: route().current('employer.profile'), permission: 'manage company' },
    { title: 'Events', icon: Calendar, href: route('events.discovery'), active: route().current('events.*'), permission: 'view events' },
];

export const institutionAdminMenuItems = [
    { title: 'Dashboard', icon: Home, href: route('dashboard'), active: route().current('dashboard') },
    { title: 'Manage Graduates', icon: GraduationCap, href: route('graduates.index'), active: route().current('graduates.*'), permission: 'manage graduates' },
    { title: 'Manage Courses', icon: BookOpen, href: route('courses.index'), active: route().current('courses.*'), permission: 'manage courses' },
    { title: 'Manage Tutors', icon: Users, href: route('tutors.index'), active: route().current('tutors.*'), permission: 'manage tutors' },
    { title: 'Manage Jobs', icon: Briefcase, href: route('jobs.public.index'), active: route().current('jobs.public.index'), permission: 'view jobs' },
    { title: 'Approve Companies', icon: UserCheck, href: route('companies.index'), active: route().current('companies.*'), permission: 'approve companies' },
    { title: 'Merge Records', icon: GitMerge, href: route('merge.index'), active: route().current('merge.*'), permission: 'merge records' },
    { title: 'User Management', icon: Users, href: route('users.index'), active: route().current('users.*'), permission: 'view users' },
    { title: 'Role Management', icon: Shield, href: route('roles.index'), active: route().current('roles.*'), permission: 'view roles' },
    { title: 'Fundraising', icon: Heart, href: route('campaigns.index'), active: route().current('campaigns.*'), permission: 'view fundraising' },
    { title: 'Analytics', icon: ChartBarIcon, href: route('institution-admin.analytics'), active: route().current('institution-admin.analytics') },
    { title: 'Branding', icon: Palette, href: route('institution-admin.settings.branding'), active: route().current('institution-admin.settings.branding') },
    { title: 'Integrations', icon: Plug, href: route('institution-admin.settings.integrations'), active: route().current('institution-admin.settings.integrations') },
    { title: 'Institution Settings', icon: Settings, href: route('institution.edit'), active: route().current('institution.edit'), permission: 'manage institution' },
];

export const superAdminMenuItems = [
    { title: 'Dashboard', icon: Home, href: route('super-admin.dashboard'), active: route().current('super-admin.dashboard') },
    { title: 'Institutions', icon: Building, href: route('institutions.index'), active: route().current('institutions.*'), permission: 'view institutions' },
    { title: 'System Analytics', icon: ChartBarIcon, href: route('super-admin.analytics'), active: route().current('super-admin.analytics') },
    { title: 'Content Management', icon: DocumentTextIcon, href: route('super-admin.content'), active: route().current('super-admin.content') },
    { title: 'Activity Monitoring', icon: ChartPieIcon, href: route('super-admin.activity'), active: route().current('super-admin.activity') },
    { title: 'Database Management', icon: CircleStackIcon, href: route('super-admin.database'), active: route().current('super-admin.database') },
    { title: 'Performance', icon: ChartBarIcon, href: route('super-admin.performance'), active: route().current('super-admin.performance') },
    { title: 'Notifications', icon: Bell, href: route('super-admin.notifications'), active: route().current('super-admin.notifications') },
    { title: 'System Settings', icon: Settings, href: route('super-admin.settings'), active: route().current('super-admin.settings') },
    { title: 'Security Dashboard', icon: Shield, href: route('security.dashboard'), active: route().current('security.*') },
    { title: 'Manage Admins', icon: Users, href: route('super-admins.index'), active: route().current('super-admins.*'), permission: 'manage super admins' },
];

export const personalMenuItems = [
    { title: 'My Profile', icon: Users, href: route('profile.show'), active: route().current('profile.*') },
    { title: 'Settings', icon: Settings, href: route('settings.profile'), active: route().current('settings.*') },
];
