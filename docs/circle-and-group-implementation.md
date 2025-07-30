# Circle and Group Models Implementation

## Overview

This document describes the implementation of the Circle and Group models for the Modern Alumni Platform, including their associated services, jobs, and commands.

## Implemented Components

### Models

#### Circle Model (`app/Models/Circle.php`)
- **Purpose**: Represents automatic community formation based on educational background
- **Key Features**:
  - Automatic circle generation based on school and graduation year
  - Multi-school circles for users with multiple educational backgrounds
  - Member management with automatic count updates
  - Post visibility controls
  - Criteria-based membership validation

#### Group Model (`app/Models/Group.php`)
- **Purpose**: Represents structured communities with specific interests and activities
- **Key Features**:
  - Multiple group types (school, custom, interest, professional)
  - Privacy levels (public, private, secret)
  - Role-based membership (member, moderator, admin)
  - Posting permission controls
  - Member approval workflow for private groups

### Services

#### CircleManager (`app/Services/CircleManager.php`)
- **Purpose**: Manages circle creation, assignment, and maintenance
- **Key Methods**:
  - `generateCirclesForUser()`: Creates circles based on user's education history
  - `findOrCreateCircle()`: Finds existing or creates new circles
  - `getSchoolCombinations()`: Generates multi-school circle combinations
  - `updateCirclesForUser()`: Refreshes user's circle assignments
  - `cleanupEmptyCircles()`: Removes unused circles

#### GroupManager (`app/Services/GroupManager.php`)
- **Purpose**: Manages group creation, invitations, and membership
- **Key Methods**:
  - `createGroup()`: Creates new groups with creator as admin
  - `handleInvitation()`: Processes group invitations
  - `autoJoinSchoolGroups()`: Automatically joins users to school groups
  - `processJoinRequest()`: Handles join requests for private groups
  - `getRecommendedGroups()`: Suggests relevant groups to users

### Background Jobs

#### UpdateUserCirclesJob (`app/Jobs/UpdateUserCirclesJob.php`)
- **Purpose**: Updates user's circle assignments when education data changes
- **Trigger**: Dispatched when user education history is modified
- **Features**: Error handling, logging, and job tagging

#### ProcessGroupInvitationsJob (`app/Jobs/ProcessGroupInvitationsJob.php`)
- **Purpose**: Handles bulk group invitations
- **Features**: Batch processing, rate limiting, progress tracking

### Console Commands

#### GenerateCirclesCommand (`app/Console/Commands/GenerateCirclesCommand.php`)
- **Purpose**: Generates circles for all existing users
- **Usage**: `php artisan circles:generate-all`
- **Features**:
  - Batch processing with configurable batch size
  - Dry-run mode for testing
  - Force regeneration option
  - Progress tracking and statistics
  - Empty circle cleanup

### Observers

#### UserObserver (`app/Observers/UserObserver.php`)
- **Purpose**: Handles user lifecycle events
- **Events**:
  - `created`: Generates circles and auto-joins school groups
  - `updated`: Updates circles when education data changes
  - `deleted`: Cleans up circle and group memberships

#### EducationHistoryObserver (`app/Observers/EducationHistoryObserver.php`)
- **Purpose**: Handles education history changes
- **Events**: Triggers circle updates when education records are modified

### Notifications

#### GroupInvitationNotification (`app/Notifications/GroupInvitationNotification.php`)
- **Purpose**: Notifies users about group invitations
- **Channels**: Email and database notifications

#### GroupJoinRequestNotification (`app/Notifications/GroupJoinRequestNotification.php`)
- **Purpose**: Notifies group admins about join requests
- **Channels**: Email and database notifications

## Database Structure

### Tables Created
- `circles`: Stores circle information and criteria
- `circle_memberships`: Manages user-circle relationships
- `groups`: Stores group information and settings
- `group_memberships`: Manages user-group relationships with roles

### Key Relationships
- Users belong to many circles and groups
- Circles and groups have many users through pivot tables
- Groups belong to institutions (tenants)
- Groups have creators (users)

## Integration Points

### User Registration Flow
1. User creates account
2. UserObserver triggers circle generation
3. CircleManager analyzes education history
4. Automatic circle assignment based on school/graduation year
5. Auto-join to relevant school groups

### Education Data Updates
1. User updates education history
2. EducationHistoryObserver detects changes
3. UpdateUserCirclesJob dispatched
4. CircleManager refreshes user's circle assignments
5. New circles created if needed

### Group Management
1. Users can create custom groups
2. Institution admins can create school groups
3. Invitation system for private groups
4. Role-based permissions for posting and moderation

## Testing

### Test Files Created
- `tests/Unit/Models/CircleTest.php`: Circle model functionality
- `tests/Unit/Models/GroupTest.php`: Group model functionality
- `tests/Unit/Services/CircleManagerTest.php`: Circle management logic
- `tests/Unit/Services/GroupManagerTest.php`: Group management logic
- `tests/Feature/CircleAndGroupIntegrationTest.php`: Integration testing

### Factory Files Created
- `database/factories/CircleFactory.php`: Circle model factory
- `database/factories/GroupFactory.php`: Group model factory
- `database/factories/EducationHistoryFactory.php`: Education history factory
- `database/factories/TenantFactory.php`: Tenant model factory

## Usage Examples

### Creating Circles for a User
```php
$circleManager = new CircleManager();
$circles = $circleManager->generateCirclesForUser($user);
```

### Creating a Group
```php
$groupManager = new GroupManager();
$group = $groupManager->createGroup([
    'name' => 'Alumni Developers',
    'type' => 'professional',
    'privacy' => 'public'
], $creator);
```

### Auto-joining School Groups
```php
$joinedCount = $groupManager->autoJoinSchoolGroups($user);
```

### Running Circle Generation Command
```bash
php artisan circles:generate-all --batch-size=50 --dry-run
```

## Future Enhancements

1. **Matrix/ActivityPub Integration**: The models are designed with future federation in mind
2. **Advanced Matching**: Enhanced algorithms for circle and group recommendations
3. **Analytics**: Detailed insights into community engagement
4. **Moderation Tools**: Advanced content and member management features
5. **API Endpoints**: RESTful APIs for mobile and third-party integrations

## Configuration

### Observer Registration
Observers are registered in `app/Providers/AppServiceProvider.php`:
```php
\App\Models\User::observe(\App\Observers\UserObserver::class);
\App\Models\EducationHistory::observe(\App\Observers\EducationHistoryObserver::class);
```

### Queue Configuration
Background jobs require queue processing:
```bash
php artisan queue:work
```

## Monitoring and Logging

All services include comprehensive logging for:
- Circle generation and updates
- Group creation and membership changes
- Job processing status
- Error handling and debugging

Logs can be monitored through Laravel's logging system and are tagged for easy filtering.