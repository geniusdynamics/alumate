# 🎓 Graduate Tracking System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red?logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-3.x-green?logo=vue.js" alt="Vue.js">
  <img src="https://img.shields.io/badge/TypeScript-5.x-blue?logo=typescript" alt="TypeScript">
  <img src="https://img.shields.io/badge/Multi--Tenant-Enabled-purple" alt="Multi-Tenant">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-cyan?logo=tailwindcss" alt="TailwindCSS">
</p>

<p align="center">
  <strong>A comprehensive multi-tenant platform connecting TVET institutions, graduates, and employers</strong>
</p>

## 🌟 Overview

The Graduate Tracking System is a sophisticated multi-tenant web application designed to bridge the gap between Technical and Vocational Education and Training (TVET) institutions, their graduates, and potential employers. The platform facilitates graduate career tracking, job placement, institutional analytics, and comprehensive reporting while maintaining complete data isolation between institutions.

### 🎯 Mission

To create a seamless ecosystem where TVET institutions can effectively track their graduates' career progress, employers can find qualified talent, and graduates can access meaningful employment opportunities.

## ✨ Key Features

### 🏢 **Multi-Tenant Architecture**
- **Complete Data Isolation**: Each institution operates in its own secure environment
- **Domain-Based Tenant Resolution**: Automatic tenant identification via domains
- **Scalable Infrastructure**: Support for unlimited institutions
- **Centralized Super Admin Management**: System-wide oversight and control

### 👥 **Role-Based Access Control**
- **Super Admin**: System-wide management and analytics
- **Institution Admin**: Graduate and course management within their institution
- **Employer**: Job posting, candidate search, and application management
- **Graduate**: Profile management, job applications, and career tracking

### 🎓 **Graduate Management**
- **Comprehensive Profiles**: Academic records, skills, certifications, employment status
- **Bulk Import/Export**: Excel-based data management with validation
- **Employment Tracking**: Real-time career progress monitoring
- **Privacy Controls**: Granular profile visibility settings
- **Profile Completion Tracking**: Guided profile enhancement

### 💼 **Job Management & Placement**
- **Smart Job Matching**: AI-powered candidate-job matching algorithms
- **Application Tracking**: Complete hiring workflow management
- **Employer Verification**: Comprehensive company validation process
- **Job Analytics**: Performance metrics and placement insights
- **Automated Notifications**: Real-time updates for all stakeholders

### 📊 **Advanced Analytics & Reporting**
- **Employment Analytics**: Graduation-to-employment tracking
- **Course Performance**: Program effectiveness analysis
- **Predictive Analytics**: Job placement probability modeling
- **Custom Reports**: Flexible report builder with export capabilities
- **KPI Dashboards**: Real-time performance indicators
- **Trend Analysis**: Historical data insights and forecasting

### 🔍 **Search & Discovery**
- **Advanced Graduate Search**: Multi-criteria candidate filtering
- **Job Recommendation Engine**: Personalized job suggestions
- **Skill-Based Matching**: Competency-driven connections
- **Saved Searches**: Persistent search preferences
- **Smart Notifications**: Relevant opportunity alerts

### 💬 **Communication & Collaboration**
- **Messaging System**: Direct communication between stakeholders
- **Discussion Forums**: Graduate networking and peer support
- **Announcement System**: Institution-wide communications
- **Help Desk**: Integrated support ticket system
- **Feedback Collection**: Continuous improvement mechanisms

## 🏗️ Technical Architecture

### **Backend Stack**
- **Framework**: Laravel 11 with PHP 8.3+
- **Multi-Tenancy**: Stancl Tenancy package for complete isolation
- **Database**: PostgreSQL with tenant-specific schemas
- **Authentication**: Laravel Breeze with Spatie Permissions
- **API**: RESTful APIs with comprehensive validation
- **Queue System**: Redis-backed job processing
- **Caching**: Multi-layer caching strategy

### **Frontend Stack**
- **Framework**: Vue.js 3 with Composition API
- **Type Safety**: Full TypeScript implementation
- **UI Framework**: Tailwind CSS with Shadcn/Vue components
- **State Management**: Pinia for complex state handling
- **Build Tool**: Vite for optimized development and production builds
- **Testing**: Vitest for unit and integration testing

### **Infrastructure**
- **Containerization**: Docker support for consistent environments
- **CI/CD**: Automated testing and deployment pipelines
- **Monitoring**: Application performance and error tracking
- **Security**: Multi-layer security with audit logging
- **Backup**: Automated database and file backups

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Node.js 18+
- Composer 2.x
- PostgreSQL 13+
- Redis (optional, for caching and queues)

### Installation

1. **Clone and Setup**
   ```bash
   git clone <repository-url>
   cd graduate-tracking-system
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Configure your .env file with database credentials
   php artisan migrate
   php artisan tenants:migrate
   php artisan db:seed
   ```

4. **Create Sample Data**
   ```bash
   php scripts/data/create_sample_data.php
   php scripts/data/create_tenant_sample_data.php
   ```

5. **Start Development Servers**
   ```bash
   # Use the convenient development helper
   start-dev.bat
   
   # Or start manually
   php artisan serve --port=8080
   npm run dev
   ```

6. **Access the Application**
   - **Main Application**: http://127.0.0.1:8080
   - **Super Admin**: admin@system.com / password
   - **Institution Admin**: admin@tech-institute.edu / password

## 👤 User Roles & Capabilities

### 🔧 **Super Admin**
- System-wide institution management
- User management across all tenants
- Employer verification and approval
- System analytics and reporting
- Security monitoring and audit logs
- System health monitoring

### 🏫 **Institution Admin**
- Graduate profile management
- Course and program administration
- Bulk data import/export
- Institution-specific analytics
- Staff and tutor management
- Graduate outcome tracking

### 💼 **Employer**
- Company profile management
- Job posting and management
- Graduate search and filtering
- Application review and tracking
- Hiring analytics and metrics
- Communication with candidates

### 🎓 **Graduate**
- Profile completion and maintenance
- Job browsing and applications
- Career progress tracking
- Classmate networking
- Assistance request system
- Employment status updates

## 📊 Analytics & Insights

### **Employment Analytics**
- Graduate employment rates by course and year
- Salary progression tracking
- Industry placement analysis
- Geographic employment distribution
- Time-to-employment metrics

### **Institutional Performance**
- Course effectiveness measurements
- Graduate satisfaction scores
- Employer feedback analysis
- Placement success rates
- Alumni engagement metrics

### **Predictive Analytics**
- Job placement probability modeling
- Career path recommendations
- Market demand forecasting
- Skills gap analysis
- Employment trend predictions

## 🛠️ Development

### **Development Scripts**
```bash
# Interactive development helper
scripts/development/dev-helper.bat

# Data management
php scripts/data/check_users.php
php scripts/data/create_sample_data.php

# Testing utilities
php scripts/testing/test_analytics.php
scripts/testing/run-tests.bat

# Debugging tools
scripts/debugging/fix_blank_screen.bat
php scripts/debugging/diagnose_blank_screen.php
```

### **Testing**
```bash
# Run comprehensive test suite
scripts/testing/run-tests.bat

# Quick system test
php scripts/testing/quick_test.php

# User acceptance testing
http://127.0.0.1:8080/testing
```

### **Code Quality**
- **ESLint**: JavaScript/TypeScript linting
- **PHP CS Fixer**: PHP code style enforcement
- **PHPStan**: Static analysis for PHP
- **Automated Testing**: Unit, integration, and feature tests
- **Code Coverage**: Comprehensive test coverage reporting

## 🔒 Security Features

### **Multi-Tenant Security**
- Complete data isolation between tenants
- Cross-tenant access prevention
- Tenant-specific authentication
- Audit logging for all data access

### **Authentication & Authorization**
- Role-based access control (RBAC)
- Multi-factor authentication support
- Session management and security
- Password policies and complexity requirements

### **Data Protection**
- Encryption of sensitive data at rest
- HTTPS enforcement for all communications
- Input sanitization and XSS protection
- SQL injection prevention
- GDPR compliance features

## 📈 Performance & Scalability

### **Optimization Features**
- Database query optimization with eager loading
- Redis caching for frequently accessed data
- CDN support for static assets
- Image optimization and compression
- Lazy loading for improved page speeds

### **Scalability**
- Horizontal scaling support
- Load balancer compatibility
- Database connection pooling
- Queue-based background processing
- Microservice-ready architecture

## 🌐 Multi-Tenant Architecture

### **Tenant Management**
- Automatic tenant creation and setup
- Domain-based tenant resolution
- Tenant-specific database schemas
- Isolated file storage per tenant
- Tenant analytics and usage tracking

### **Data Isolation**
- Complete database separation
- Tenant-specific migrations
- Isolated user authentication
- Separate file storage spaces
- Independent caching layers

## 📚 Documentation

### **User Guides**
- [Development Guide](DEVELOPMENT.md) - Complete development setup
- [Script Organization](scripts/README.md) - Development script documentation
- [Port Configuration](PORTS.md) - Network configuration details
- [Cleanup Summary](CLEANUP_SUMMARY.md) - Project organization details

### **API Documentation**
- RESTful API endpoints
- Authentication mechanisms
- Request/response formats
- Error handling guidelines
- Rate limiting policies

## 🚀 Deployment

### **Production Deployment**
```bash
# Use the deployment script
./deploy.sh

# Or deploy manually
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### **Environment Configuration**
- Production environment variables
- Database optimization settings
- Caching configuration
- Security hardening
- Performance monitoring

## 🤝 Contributing

We welcome contributions to the Graduate Tracking System! Please follow these guidelines:

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **Follow coding standards** (ESLint, PHP CS Fixer)
4. **Write comprehensive tests**
5. **Update documentation** as needed
6. **Submit a pull request**

### **Development Standards**
- Follow PSR-12 coding standards for PHP
- Use TypeScript for all frontend code
- Write unit tests for new features
- Update documentation for API changes
- Follow semantic versioning

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Laravel Community** - For the robust PHP framework
- **Vue.js Team** - For the progressive JavaScript framework
- **Stancl Tenancy** - For multi-tenant architecture support
- **Spatie** - For excellent Laravel packages
- **Tailwind CSS** - For the utility-first CSS framework
- **TVET Institutions** - For inspiring this solution

## 📞 Support

For support and questions:
- **Documentation**: Check the comprehensive guides in the `/docs` directory
- **Issues**: Report bugs and feature requests via GitHub Issues
- **Development**: Use `scripts/development/dev-helper.bat` for development assistance
- **Testing**: Access the testing suite at `/testing` endpoint

---

<p align="center">
  <strong>Empowering TVET institutions to track graduate success and connect talent with opportunity</strong>
</p>

<p align="center">
  Built with ❤️ using Laravel, Vue.js, TypeScript, and modern web technologies
</p>