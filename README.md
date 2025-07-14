# 🚀 Laravel Vue TypeScript Starter Kit

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
  <br>
  <strong>Modern Laravel + Vue.js + TypeScript Starter Kit</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red?logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-3.x-green?logo=vue.js" alt="Vue.js">
  <img src="https://img.shields.io/badge/TypeScript-5.x-blue?logo=typescript" alt="TypeScript">
  <img src="https://img.shields.io/badge/Inertia.js-2.x-purple" alt="Inertia.js">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-cyan?logo=tailwindcss" alt="TailwindCSS">
</p>

## ✨ Features

- **🎯 Modern Stack**: Laravel 11 + Vue 3 + TypeScript + Inertia.js
- **🎨 Beautiful UI**: Shadcn/Vue components with TailwindCSS
- **🔐 Role-Based Access Control**: Spatie Laravel Permission integration
- **📊 Advanced DataTable**: Reusable component with search, sort, and pagination
- **🏗️ Clean Architecture**: Organized component structure and reusable traits
- **📱 Responsive Design**: Mobile-first approach with modern UI patterns
- **⚡ TypeScript**: Full type safety throughout the application
- **🛠️ Developer Experience**: Hot module replacement, ESLint, and modern tooling

## 🏗️ Architecture

### Frontend Structure

```
resources/js/
├── components/
│   ├── common/           # Reusable common components
│   ├── data/            # Data display components (DataTable)
│   ├── forms/           # Form components
│   ├── layout/          # Layout-specific components
│   └── ui/              # Shadcn/Vue UI components
├── layouts/
│   ├── AuthLayout.vue   # Authentication layout
│   └── DefaultLayout.vue # Main application layout
├── pages/               # Page components
├── types/               # TypeScript type definitions
└── lib/                 # Utility functions
```

### Backend Structure

```
app/
├── Http/
│   ├── Controllers/     # API and web controllers
│   ├── Middleware/      # Custom middleware
│   ├── Requests/        # Form request validation
│   └── Traits/          # Reusable controller traits
├── Models/              # Eloquent models
├── Policies/            # Authorization policies
└── Providers/           # Service providers
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- Node.js 18+
- Composer
- MySQL/PostgreSQL

### Installation

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd laravel-starter-kit
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Install Node.js dependencies**

    ```bash
    npm install
    ```

4. **Environment setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Configure database**

    ```bash
    # Edit .env file with your database credentials
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

6. **Run migrations and seeders**

    ```bash
    php artisan migrate
    php artisan db:seed --class=RolePermissionSeeder
    ```

7. **Build assets**

    ```bash
    npm run build
    # or for development
    npm run dev
    ```

8. **Start the application**
    ```bash
    php artisan serve
    ```

## 🔐 Role-Based Access Control

### Default Roles

- **Admin**: Full system access
- **Moderator**: Limited user and content management
- **User**: Basic access

### Permissions

- `view users`, `create users`, `edit users`, `delete users`
- `view roles`, `create roles`, `edit roles`, `delete roles`
- `view permissions`, `create permissions`, `edit permissions`, `delete permissions`

### Usage Examples

**In Controllers:**

```php
$this->authorize('view', User::class);
```

**In Vue Components:**

```typescript
const canManageUsers = computed(() => {
    return user.value?.permissions?.includes('view users');
});
```

## 📊 DataTable Component

### Features

- 🔍 Real-time search with debouncing
- 📈 Column sorting (ascending/descending)
- 📄 Pagination with customizable page sizes
- 🎨 Customizable cell rendering via slots
- ⚡ Loading states and error handling
- 📱 Responsive design

### Usage

```vue
<template>
    <DataTable
        :data="users"
        :columns="columns"
        :pagination="pagination"
        :loading="loading"
        @search="handleSearch"
        @sort="handleSort"
        @paginate="handlePaginate"
    >
        <template #cell.actions="{ row }">
            <!-- Custom action buttons -->
        </template>
    </DataTable>
</template>

<script setup lang="ts">
const columns: Column[] = [
    {
        key: 'name',
        label: 'Name',
        sortable: true,
        searchable: true,
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true,
        render: (value) => value.toLowerCase(),
    },
];
</script>
```

### Backend Trait

```php
use App\Http\Traits\HasDataTable;

class UserController extends Controller
{
    use HasDataTable;

    public function index(Request $request)
    {
        $query = User::with('roles');

        $users = $this->processDataTableRequest(
            $query,
            $request,
            ['name', 'email'], // searchable columns
            ['name', 'email', 'created_at'] // sortable columns
        );

        return Inertia::render('Users/Index', [
            'users' => $users->items(),
            'pagination' => $this->formatPaginationData($users),
        ]);
    }
}
```

## 🎨 UI Components

### Shadcn/Vue Integration

This starter kit includes a full set of shadcn/vue components:

- **Layout**: Card, Sheet, Sidebar
- **Navigation**: Breadcrumb, Navigation Menu
- **Forms**: Input, Select, Button, Checkbox
- **Data Display**: Table, Badge, Avatar
- **Feedback**: Dialog, Tooltip, Skeleton

### Custom Components

- **DataTable**: Advanced table with search, sort, and pagination
- **AppSidebar**: Role-aware navigation sidebar
- **AppHeader**: Breadcrumb navigation header
- **AppLogo**: Branded logo component

## 🛠️ Development

### Commands

```bash
# Development server with hot reload
npm run dev

# Build for production
npm run build

# Type checking
npm run type-check

# Linting
npm run lint

# Run tests
php artisan test
```

### Adding New Features

1. **Create a new page**

    ```bash
    # Create controller
    php artisan make:controller FeatureController --resource

    # Create Vue page
    touch resources/js/pages/Feature/Index.vue
    ```

2. **Add routes**

    ```php
    Route::resource('features', FeatureController::class);
    ```

3. **Add permissions**
    ```php
    Permission::create(['name' => 'view features']);
    ```

## 📁 Project Structure

### Key Files

- `app/Http/Traits/HasDataTable.php` - Reusable DataTable backend logic
- `resources/js/components/data/DataTable.vue` - Frontend DataTable component
- `resources/js/layouts/DefaultLayout.vue` - Main application layout
- `resources/js/types/index.ts` - TypeScript type definitions
- `database/seeders/RolePermissionSeeder.php` - Default roles and permissions

## 🔧 Configuration

### Environment Variables

```env
# App
APP_NAME="Laravel Starter Kit"
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=starter_kit

# Permissions
PERMISSION_CACHE_EXPIRATION_TIME=3600
```

### Customization

1. **Brand Colors**: Edit `tailwind.config.js`
2. **Logo**: Replace components in `resources/js/components/common/`
3. **Permissions**: Modify `database/seeders/RolePermissionSeeder.php`
4. **Layout**: Customize `resources/js/layouts/DefaultLayout.vue`

## 🐛 Troubleshooting

### Common Issues

1. **Ziggy route errors**

    ```bash
    php artisan route:cache
    npm run build
    ```

2. **Permission issues**

    ```bash
    php artisan permission:cache-reset
    ```

3. **Asset compilation**
    ```bash
    npm run build
    php artisan view:cache
    ```

## 📚 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [TypeScript Documentation](https://www.typescriptlang.org/)
- [Shadcn/Vue Documentation](https://www.shadcn-vue.com/)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

## 🚀 Deployment

To deploy the application, you can use the `deploy.sh` script.

```bash
./deploy.sh
```

This script will:

1.  Enter maintenance mode.
2.  Pull the latest changes from the `main` branch.
3.  Install Composer dependencies.
4.  Run database migrations.
5.  Clear and create caches.
6.  Build frontend assets.
7.  Exit maintenance mode.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Vue.js](https://vuejs.org) - The progressive JavaScript framework
- [Inertia.js](https://inertiajs.com) - The modern monolith
- [Shadcn/Vue](https://www.shadcn-vue.com) - Beautiful UI components
- [Spatie](https://spatie.be) - Amazing Laravel packages

---

<p align="center">
  Built with ❤️ using Laravel, Vue.js, and TypeScript
</p>
