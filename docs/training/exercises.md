# Hands-On Exercises

Practice your Alumate skills with these hands-on exercises designed for users, administrators, and developers.

## Table of Contents

1. [User Exercises](#user-exercises)
2. [Admin Exercises](#admin-exercises)
3. [Developer Exercises](#developer-exercises)

---

## User Exercises

### Exercise 1: Complete Your Profile

**Objective**: Achieve 100% profile completeness

**Steps**:

1. Log in to Alumate
2. Navigate to your profile page
3. Complete all profile sections:
   - Profile photo upload
   - Educational history (add all degrees)
   - Professional experience
   - Skills and endorsements
   - Contact information
   - Social links

**Success Criteria**:
- Profile completeness: 100%
- Profile photo uploaded
- At least 3 educational entries
- At least 2 work experiences
- At least 5 skills added

**Estimated Time**: 15-20 minutes

---

### Exercise 2: Build Your Network

**Objective**: Connect with at least 25 alumni

**Steps**:

1. Use the alumni search feature
2. Filter by graduation year (your year and nearby years)
3. Filter by major/degree
4. Filter by location
5. Send personalized connection requests (minimum 25)

**Success Criteria**:
- 25+ connection requests sent
- All requests include personalized messages
- At least 10 accepted connections

**Estimated Time**: 30-45 minutes

---

### Exercise 3: Join and Engage in Groups

**Objective**: Join groups and participate in discussions

**Steps**:

1. Browse available groups
2. Join at least 3 relevant groups:
   - Your graduating class group
   - Interest-based group
   - Regional/city group
3. Read recent discussions
4. Comment on at least 2 posts
5. Share 1 relevant resource or article

**Success Criteria**:
- Membership in 3+ groups
- 2+ meaningful comments
- 1 shared resource

**Estimated Time**: 20-30 minutes

---

### Exercise 4: Use Career Resources

**Objective**: Explore career tools and resources

**Steps**:

1. Access the career calculator
2. Enter your current information
3. Review salary projections
4. Explore job listings
5. Save 3 interesting jobs
6. Set up job alerts for your interests

**Success Criteria**:
- Career profile completed
- 3 jobs saved
- Job alerts configured

**Estimated Time**: 20-30 minutes

---

### Exercise 5: Explore Analytics Dashboard

**Objective**: Understand your personal analytics

**Steps**:

1. Navigate to your analytics dashboard
2. Review your profile views over time
3. Check your engagement metrics
4. Review your connection growth
5. Export your activity report

**Success Criteria**:
- Reviewed all dashboard sections
- Understood metrics shown
- Exported activity report

**Estimated Time**: 10-15 minutes

---

## Admin Exercises

### Exercise 1: User Management

**Objective**: Perform common user management tasks

**Steps**:

1. Log in to admin dashboard
2. Navigate to User Management
3. Create a new user account
4. Search for users using filters
5. Export user list to CSV
6. Deactivate and reactivate a test user

**Success Criteria**:
- User created successfully
- Search working with filters
- CSV export completed
- User deactivated and reactivated

**Commands/Reference**:
```bash
# Create user via CLI
php artisan users:create

# Import users
php artisan users:import path/to/users.csv
```

---

### Exercise 2: Tenant Configuration

**Objective**: Configure tenant settings and features

**Steps**:

1. Navigate to Tenant Management
2. Create a new test tenant
3. Configure tenant settings:
   - Feature flags
   - Branding options
   - User limits
   - Storage limits
4. Test tenant isolation
5. Run tenant migrations

**Success Criteria**:
- Tenant created
- All settings configured
- Tenant isolation verified
- Migrations successful

**Commands/Reference**:
```bash
# Create tenant
php artisan tenants:create

# Run migrations for tenant
php artisan tenants:migrate --tenant=tenant_id
```

---

### Exercise 3: Security Configuration

**Objective**: Configure security settings

**Steps**:

1. Navigate to Security settings
2. Configure password policy
3. Enable/disable 2FA options
4. Set up IP whitelist (if applicable)
5. Configure session settings
6. Review audit logs

**Success Criteria**:
- Password policy configured
- 2FA options set
- IP whitelist configured
- Session management set
- Audit logs reviewed

---

### Exercise 4: Analytics Configuration

**Objective**: Configure analytics features

**Steps**:

1. Navigate to Analytics settings
2. Enable/disable analytics features
3. Configure data retention
4. Set up custom metrics
5. Create an analytics report
6. Export analytics data

**Success Criteria**:
- Features configured
- Retention policy set
- Custom metrics created
- Report generated

---

### Exercise 5: Backup and Recovery

**Objective**: Perform backup and recovery operations

**Steps**:

1. Navigate to Backup management
2. Create a manual backup
3. Verify backup integrity
4. Review backup schedule
5. Test recovery procedure (in test environment)

**Success Criteria**:
- Backup created and verified
- Schedule reviewed
- Recovery tested (test env)

**Commands/Reference**:
```bash
# Create backup
php artisan backup:run

# List backups
php artisan backup:list

# Restore backup
php artisan backup:restore backup_file.zip
```

---

## Developer Exercises

### Exercise 1: Environment Setup

**Objective**: Set up complete development environment

**Steps**:

1. Clone the repository
2. Install dependencies (Composer, npm)
3. Configure environment file
4. Set up database
5. Run migrations and seeders
6. Start development servers

**Success Criteria**:
- Repository cloned
- Dependencies installed
- Database created and seeded
- Both servers running

**Estimated Time**: 45-60 minutes

---

### Exercise 2: Create API Endpoint

**Objective**: Create a new API endpoint for resources

**Steps**:

1. Create a new model (or use existing)
2. Create migration for database table
3. Create controller with CRUD methods
4. Define API routes
5. Create resource transformer
6. Write unit and feature tests
7. Test endpoint with Postman/curl

**Success Criteria**:
- Migration created and run
- Controller with CRUD
- Routes defined
- Tests passing
- Endpoint tested

**Example Deliverable**:
```
GET    /api/v1/resources
POST   /api/v1/resources
GET    /api/v1/resources/{id}
PUT    /api/v1/resources/{id}
DELETE /api/v1/resources/{id}
```

---

### Exercise 3: Create Vue Component

**Objective**: Create a reusable Vue component

**Steps**:

1. Design component based on existing patterns
2. Create component file (.vue)
3. Implement props, emits, and computed
4. Add styling (following design system)
5. Write unit tests
6. Use component in a page
7. Test in browser

**Success Criteria**:
- Component created
- All props/emit working
- Tests passing
- Component integrated

**Example Component**: Resource list with filtering

---

### Exercise 4: Implement Analytics Event

**Objective**: Add analytics tracking to a feature

**Steps**:

1. Identify feature requiring tracking
2. Create analytics event definition
3. Implement event tracking service
4. Add tracking calls to feature
5. Query analytics for event data
6. Create simple visualization

**Success Criteria**:
- Event type defined
- Tracking implemented
- Data querying works
- Visualization created

---

### Exercise 5: Write Tests

**Objective**: Write comprehensive tests for a feature

**Steps**:

1. Identify feature to test
2. Write unit tests for service layer
3. Write feature tests for API endpoints
4. Write component tests for Vue
5. Run full test suite
6. Verify code coverage

**Success Criteria**:
- Unit tests created
- Feature tests created
- Component tests created
- All tests passing
- Coverage >80%

---

## Exercise Solutions

### User Exercise Solutions

#### Exercise 1: Profile Tips
- Use professional headshot
- Fill in all fields with detailed information
- Add keywords relevant to your industry
- Request endorsements from colleagues

#### Exercise 2: Connection Tips
- Personalize each request
- Mention shared experiences or interests
- Keep messages concise
- Follow up after connections accepted

---

### Admin Exercise Solutions

#### Exercise 1: User Creation CLI

```bash
# Interactive user creation
php artisan users:create \
  --name="John Doe" \
  --email="john@example.com" \
  --role="user" \
  --tenant="tenant_id"
```

#### Exercise 2: Tenant Configuration

```php
// In tenant settings
[
    'features' => [
        'analytics' => true,
        'mentorship' => true,
        'jobs' => true,
    ],
    'limits' => [
        'users' => 1000,
        'storage' => '10GB',
    ]
]
```

---

### Developer Exercise Solutions

#### Exercise 2: API Endpoint Template

**Model**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = ['name', 'description', 'user_id'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

**Controller**:
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $resources = Resource::paginate(15);
        return response()->json([
            'data' => ResourceResource::collection($resources)
        ]);
    }
    
    // ... store, show, update, destroy methods
}
```

---

## Submission Guidelines

### For Training Assessment

1. Complete all exercises in your role track
2. Document your work with screenshots
3. Submit completed exercises for review
4. Receive feedback and improve

### Review Process

1. Self-review using success criteria
2. Peer review (optional)
3. Instructor review
4. Final assessment

---

## Support

For exercise-related questions:

- **User Exercises**: user-training@alumate.io
- **Admin Exercises**: admin-training@alumate.io
- **Developer Exercises**: dev-training@alumate.io

**Return to**: [Training Overview](README.md)
