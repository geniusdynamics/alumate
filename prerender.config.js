// Static pages that should be prerendered for SEO and performance
export const prerenderRoutes = [
    '/',
    '/login',
    '/register',
    '/privacy',
    '/terms',
    '/about',
    '/contact',
];

// Configuration for static page generation
export const prerenderConfig = {
    routes: prerenderRoutes,
    // Add any pages that should be excluded from prerendering
    excludeRoutes: [
        '/dashboard',
        '/profile',
        '/settings',
        '/admin',
    ],
    // SEO configuration
    seo: {
        defaultTitle: 'Laravel Inertia Starter Kit',
        titleTemplate: '%s - Laravel Inertia Starter Kit',
        description: 'A modern Laravel Inertia.js Vue 3 starter kit with TypeScript and Tailwind CSS',
        keywords: ['Laravel', 'Inertia.js', 'Vue 3', 'TypeScript', 'Tailwind CSS'],
        ogImage: '/images/og-image.jpg',
        twitterCard: 'summary_large_image',
    },
};
