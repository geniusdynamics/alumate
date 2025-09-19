# Calendar Integration Implementation Plan

## Missing Methods (13 total)

### Apple Calendar Integration
- `fetchAppleEvents(CalendarConnection $connection): Collection`
- `testAppleConnection(CalendarConnection $connection): bool`

### CalDAV Integration
- `fetchCalDAVEvents(CalendarConnection $connection): Collection`
- `testCalDAVConnection(CalendarConnection $connection): bool`

### Connection Testing
- `testGoogleConnection(CalendarConnection $connection): bool`
- `testOutlookConnection(CalendarConnection $connection): bool`

### Token Management
- `refreshGoogleToken(CalendarConnection $connection): void`

### Event Management
- `createOrUpdateEvent(CalendarConnection $connection, array $externalEvent): Event`
- `syncEventToExternalCalendars(Event $event): void`
- `createEventInUserCalendar(User $user, Event $event): void`

### Communication
- `sendEmailInvite(Event $event, string $email): void`

### Availability Calculation
- `fetchBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection`
- `findSlotsInDay(Carbon $dayStart, Carbon $dayEnd, Collection $busyTimes, int $durationMinutes): Collection`

## Implementation Priority

### Phase 1: Foundation
1. Connection testing methods
2. Token refresh functionality
3. Apple Calendar and CalDAV integration

### Phase 2: Core Functionality
1. Event creation and synchronization
2. Email invitation system
3. Availability calculation

### Phase 3: Testing
1. Unit tests for all methods
2. Integration tests
3. Update existing tests

Handoff to Orchestrator for detailed task breakdown.