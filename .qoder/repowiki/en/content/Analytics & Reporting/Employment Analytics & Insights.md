# Employment Analytics & Insights

<cite>
**Referenced Files in This Document**
- [README.md](file://README.md)
- [CareerOutcomeAnalyticsService.php](file://app/Services/CareerOutcomeAnalyticsService.php)
- [AlumniMapService.php](file://app/Services/AlumniMapService.php)
- [CareerOutcomeAnalyticsController.php](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php)
- [api.php](file://routes/api.php)
- [api-reference.md](file://docs/api/api-reference.md)
- [CareerOutcomeAnalyticsTest.php](file://tests/Feature/CareerOutcomeAnalyticsTest.php)
- [AlumniMapServiceTest.php](file://tests/Feature/AlumniMapServiceTest.php)
- [CareerAnalyticsApiTest.php](file://tests/Feature/CareerAnalyticsApiTest.php)
- [SalaryProgression.php](file://app/Models/SalaryProgression.php)
- [IndustryPlacement.php](file://app/Models/IndustryPlacement.php)
- [ProgramEffectiveness.php](file://app/Models/ProgramEffectiveness.php)
- [DemographicOutcome.php](file://app/Models/DemographicOutcome.php)
- [CareerPath.php](file://app/Models/CareerPath.php)
- [CareerTrend.php](file://app/Models/CareerTrend.php)
- [OverviewMetrics.vue](file://resources/js/components/Analytics/OverviewMetrics.vue)
- [SalaryAnalysis.vue](file://resources/js/components/Analytics/SalaryAnalysis.vue)
- [GeographicMap.vue](file://resources/js/components/Analytics/Charts/GeographicMap.vue)
- [PublicMap.vue](file://resources/js/Pages/Alumni/PublicMap.vue)
- [EmployerEngagement.vue](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue)
- [CareerOutcomes.vue](file://resources/js/Pages/Analytics/CareerOutcomes.vue)
- [MIGRATION_RESOLUTION_SUMMARY.md](file://docs/MIGRATION_RESOLUTION_SUMMARY.md)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document describes the employment analytics and insights system designed to track graduate outcomes, analyze salary progression, compute employment rates by course, measure employer engagement, visualize geographic distribution, and support ROI and benchmarking analyses. It consolidates backend services, models, controllers, routes, and frontend dashboards to provide a complete picture of institutional performance and market alignment.

## Project Structure
The analytics system spans backend services and models, API endpoints, and frontend dashboards:
- Backend: Services orchestrate analytics computations; models represent domain entities; controllers expose endpoints; routes define API surface.
- Frontend: Vue components render overview metrics, salary analysis, geographic distribution, and employer engagement dashboards.

```mermaid
graph TB
subgraph "Backend"
SVC["CareerOutcomeAnalyticsService"]
MAPSVC["AlumniMapService"]
CTRL["CareerOutcomeAnalyticsController"]
ROUTE["routes/api.php"]
MODEL1["SalaryProgression"]
MODEL2["IndustryPlacement"]
MODEL3["ProgramEffectiveness"]
MODEL4["DemographicOutcome"]
MODEL5["CareerPath"]
MODEL6["CareerTrend"]
end
subgraph "Frontend"
OVERVIEW["OverviewMetrics.vue"]
SALARY["SalaryAnalysis.vue"]
GEO["GeographicMap.vue"]
PUBMAP["PublicMap.vue"]
EMPENG["EmployerEngagement.vue"]
CAREEROUT["CareerOutcomes.vue"]
end
ROUTE --> CTRL
CTRL --> SVC
CTRL --> MAPSVC
SVC --> MODEL1
SVC --> MODEL2
SVC --> MODEL3
SVC --> MODEL4
SVC --> MODEL5
SVC --> MODEL6
OVERVIEW --> CTRL
SALARY --> CTRL
GEO --> CTRL
PUBMAP --> MAPSVC
EMPENG --> CTRL
CAREEROUT --> CTRL
```

**Diagram sources**
- [CareerOutcomeAnalyticsService.php:15-511](file://app/Services/CareerOutcomeAnalyticsService.php#L15-L511)
- [AlumniMapService.php:9-236](file://app/Services/AlumniMapService.php#L9-L236)
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [api.php:670-686](file://routes/api.php#L670-L686)
- [SalaryProgression.php:9-70](file://app/Models/SalaryProgression.php#L9-L70)
- [IndustryPlacement.php:8-64](file://app/Models/IndustryPlacement.php#L8-L64)
- [ProgramEffectiveness.php:8-145](file://app/Models/ProgramEffectiveness.php#L8-L145)
- [DemographicOutcome.php:8-123](file://app/Models/DemographicOutcome.php#L8-L123)
- [CareerPath.php:9-115](file://app/Models/CareerPath.php#L9-L115)
- [CareerTrend.php:8-175](file://app/Models/CareerTrend.php#L8-L175)
- [OverviewMetrics.vue:1-221](file://resources/js/components/Analytics/OverviewMetrics.vue#L1-L221)
- [SalaryAnalysis.vue:1-145](file://resources/js/components/Analytics/SalaryAnalysis.vue#L1-L145)
- [GeographicMap.vue:1-219](file://resources/js/components/Analytics/Charts/GeographicMap.vue#L1-L219)
- [PublicMap.vue:36-51](file://resources/js/Pages/Alumni/PublicMap.vue#L36-L51)
- [EmployerEngagement.vue:28-116](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue#L28-L116)
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)

**Section sources**
- [README.md:492-504](file://README.md#L492-L504)
- [MIGRATION_RESOLUTION_SUMMARY.md:44-70](file://docs/MIGRATION_RESOLUTION_SUMMARY.md#L44-L70)

## Core Components
- CareerOutcomeAnalyticsService: Central orchestrator computing overview metrics, program effectiveness, salary analysis, industry placement, demographic outcomes, career paths, and trend analysis. Implements time-to-employment metrics, salary progression analysis, and employment rate calculations by course.
- AlumniMapService: Provides geographic distribution analytics, clustering, and nearby alumni discovery for visualization and regional insights.
- CareerOutcomeAnalyticsController: Exposes REST endpoints for analytics queries, snapshots, and exports.
- Models: SalaryProgression, IndustryPlacement, ProgramEffectiveness, DemographicOutcome, CareerPath, CareerTrend encapsulate data structures and derived metrics.
- Frontend Dashboards: OverviewMetrics, SalaryAnalysis, GeographicMap, PublicMap, EmployerEngagement, and CareerOutcomes pages.

**Section sources**
- [CareerOutcomeAnalyticsService.php:15-511](file://app/Services/CareerOutcomeAnalyticsService.php#L15-L511)
- [AlumniMapService.php:9-236](file://app/Services/AlumniMapService.php#L9-L236)
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [SalaryProgression.php:9-70](file://app/Models/SalaryProgression.php#L9-L70)
- [IndustryPlacement.php:8-64](file://app/Models/IndustryPlacement.php#L8-L64)
- [ProgramEffectiveness.php:8-145](file://app/Models/ProgramEffectiveness.php#L8-L145)
- [DemographicOutcome.php:8-123](file://app/Models/DemographicOutcome.php#L8-L123)
- [CareerPath.php:9-115](file://app/Models/CareerPath.php#L9-L115)
- [CareerTrend.php:8-175](file://app/Models/CareerTrend.php#L8-L175)
- [OverviewMetrics.vue:1-221](file://resources/js/components/Analytics/OverviewMetrics.vue#L1-L221)
- [SalaryAnalysis.vue:1-145](file://resources/js/components/Analytics/SalaryAnalysis.vue#L1-L145)
- [GeographicMap.vue:1-219](file://resources/js/components/Analytics/Charts/GeographicMap.vue#L1-L219)
- [PublicMap.vue:36-51](file://resources/js/Pages/Alumni/PublicMap.vue#L36-L51)
- [EmployerEngagement.vue:28-116](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue#L28-L116)
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)

## Architecture Overview
The system follows a layered architecture:
- Presentation: Vue pages and components consume analytics via API endpoints.
- API: Laravel controller validates filters and delegates to analytics service.
- Service: Computes aggregates and derived metrics from models.
- Persistence: Eloquent models and database tables store raw and aggregated data.

```mermaid
sequenceDiagram
participant UI as "CareerOutcomes.vue"
participant API as "CareerOutcomeAnalyticsController"
participant SVC as "CareerOutcomeAnalyticsService"
participant DB as "Models"
UI->>API : GET /api/career-analytics?filters
API->>SVC : generateOutcomeAnalytics(filters)
SVC->>DB : Query overview, salary, industry, demographics, paths, trends
DB-->>SVC : Aggregated collections
SVC-->>API : Analytics payload
API-->>UI : JSON response
```

**Diagram sources**
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)
- [CareerOutcomeAnalyticsController.php:25-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L25-L42)
- [CareerOutcomeAnalyticsService.php:20-31](file://app/Services/CareerOutcomeAnalyticsService.php#L20-L31)

**Section sources**
- [api.php:670-686](file://routes/api.php#L670-L686)
- [api-reference.md:316-354](file://docs/api/api-reference.md#L316-L354)

## Detailed Component Analysis

### Career Outcome Analytics Service
Responsibilities:
- Compute overview metrics: total alumni, employment rate, average salary, tracking rate, top industries, top employers, geographic distribution.
- Program effectiveness: employment rates at 6 months/1 year/2 years, average salaries, top employers, skills gaps.
- Salary analysis: overall statistics, progression by years, industry comparison, percentiles, growth trends.
- Industry placement: placement counts, starting/current salaries, retention, top companies, skills in demand.
- Demographic outcomes: employment, salary, leadership, entrepreneurship rates, industry distribution.
- Career paths: distribution, success metrics, progression patterns, leadership development.
- Trend analysis: direction, strength, formatted growth rate, significance, chart-ready data points.
- Snapshot generation: period-based metrics with filters.

Implementation highlights:
- Filters applied across education histories, career timelines, and salary progressions.
- Time-to-employment computed by checking employment presence at specific dates after graduation.
- Salary progression computed by latest effective salary per user up to a given date.
- Derived metrics include equity scores, performance grades, and growth rates.

```mermaid
classDiagram
class CareerOutcomeAnalyticsService {
+generateOutcomeAnalytics(filters) array
+getOverviewMetrics(filters) array
+getProgramEffectiveness(filters) Collection
+generateProgramEffectiveness(program, year) array
+getSalaryAnalysis(filters) array
+getIndustryPlacement(filters) Collection
+generateIndustryPlacement(industry, year, program) array
+getDemographicOutcomes(filters) Collection
+getCareerPathAnalysis(filters) array
+getTrendAnalysis(filters) Collection
+generateSnapshot(type, start, end, filters) array
-getEmploymentRateAtDate(graduates, date) float
-calculateSalaryProgression(graduates, year) array
-getAverageSalaryAtDate(graduates, date) float
}
class SalaryProgression {
+user() BelongsTo
+byIndustry(industry) Scope
+byYearsSinceGraduation(years) Scope
+ordered() Scope
+formatted_salary string
+annualized_salary float
}
class IndustryPlacement {
+byIndustry(industry) Scope
+byGraduationYear(year) Scope
+byProgram(program) Scope
+salary_growth float
+formatted_retention_rate string
}
class ProgramEffectiveness {
+byProgram(program) Scope
+byDepartment(department) Scope
+byGraduationYear(year) Scope
+recent(years) Scope
+employment_trend string
+salary_growth_rate float
+overall_effectiveness_score float
+performance_grade string
}
class DemographicOutcome {
+byType(type) Scope
+byValue(value) Scope
+byGraduationYear(year) Scope
+byProgram(program) Scope
+equity_score int
+opportunity_gap array
+top_industries array
}
class CareerPath {
+user() BelongsTo
+byPathType(type) Scope
+withLeadership() Scope
+path_type_display string
+job_stability_score float
+career_velocity float
}
class CareerTrend {
+byTrendType(type) Scope
+byCategory(category) Scope
+recent(months) Scope
+byDirection(direction) Scope
+trend_strength string
+trend_icon string
+formatted_growth_rate string
+period_length int
+calculateTrendSignificance() int
+getDataPointsForChart() array
}
CareerOutcomeAnalyticsService --> SalaryProgression : "queries"
CareerOutcomeAnalyticsService --> IndustryPlacement : "queries"
CareerOutcomeAnalyticsService --> ProgramEffectiveness : "queries"
CareerOutcomeAnalyticsService --> DemographicOutcome : "queries"
CareerOutcomeAnalyticsService --> CareerPath : "queries"
CareerOutcomeAnalyticsService --> CareerTrend : "queries"
```

**Diagram sources**
- [CareerOutcomeAnalyticsService.php:15-511](file://app/Services/CareerOutcomeAnalyticsService.php#L15-L511)
- [SalaryProgression.php:9-70](file://app/Models/SalaryProgression.php#L9-L70)
- [IndustryPlacement.php:8-64](file://app/Models/IndustryPlacement.php#L8-L64)
- [ProgramEffectiveness.php:8-145](file://app/Models/ProgramEffectiveness.php#L8-L145)
- [DemographicOutcome.php:8-123](file://app/Models/DemographicOutcome.php#L8-L123)
- [CareerPath.php:9-115](file://app/Models/CareerPath.php#L9-L115)
- [CareerTrend.php:8-175](file://app/Models/CareerTrend.php#L8-L175)

**Section sources**
- [CareerOutcomeAnalyticsService.php:20-339](file://app/Services/CareerOutcomeAnalyticsService.php#L20-L339)

### Alumni Map Service
Responsibilities:
- Retrieve alumni with location data, applying filters by graduation year, school, industry, country, and region.
- Provide clustered alumni data for map rendering using SQL grouping and zoom-aware cluster sizes.
- Compute regional statistics: by country, by region, by industry, and total alumni with location data.
- Support nearby alumni discovery using a distance formula and privacy-aware filtering.
- Update location privacy settings and placeholder geocoding integration.

```mermaid
flowchart TD
Start(["Get Alumni With Locations"]) --> ApplyFilters["Apply Filters<br/>graduation_year, school_id, industry, country, region"]
ApplyFilters --> Query["Query Users with Location Data"]
Query --> ReturnUsers["Return Collection"]
Start2(["Get Clustered Alumni"]) --> Bounds["Define Spatial Bounds"]
Bounds --> Zoom["Compute Cluster Size by Zoom"]
Zoom --> SQL["SQL GROUP BY Rounded Coordinates"]
SQL --> ReturnClusters["Return Clustered Data"]
Start3(["Get Regional Stats"]) --> ByCountry["Count by Country"]
Start3 --> ByRegion["Count by Region"]
Start3 --> ByIndustry["Count by Industry (Current Employment)"]
ByCountry --> Stats["Aggregate Stats"]
ByRegion --> Stats
ByIndustry --> Stats
Stats --> ReturnStats["Return Stats"]
```

**Diagram sources**
- [AlumniMapService.php:14-236](file://app/Services/AlumniMapService.php#L14-L236)

**Section sources**
- [AlumniMapService.php:14-236](file://app/Services/AlumniMapService.php#L14-L236)

### API Endpoints and Controllers
Endpoints:
- GET /api/career-analytics
- GET /api/career-analytics/overview
- GET /api/career-analytics/program-effectiveness
- POST /api/career-analytics/program-effectiveness/generate
- GET /api/career-analytics/salary-analysis
- GET /api/career-analytics/industry-placement
- POST /api/career-analytics/industry-placement/generate
- GET /api/career-analytics/demographic-outcomes
- GET /api/career-analytics/career-path-analysis
- GET /api/career-analytics/trend-analysis
- POST /api/career-analytics/generate-snapshot
- GET /api/career-analytics/snapshots
- GET /api/career-analytics/filter-options
- POST /api/career-analytics/export

Controller responsibilities:
- Validate filters from requests.
- Delegate to analytics service to build comprehensive analytics payload.
- Return standardized JSON responses.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Route as "routes/api.php"
participant Ctrl as "CareerOutcomeAnalyticsController"
participant Svc as "CareerOutcomeAnalyticsService"
Client->>Route : GET /api/career-analytics?graduation_year=2020&program=CS
Route->>Ctrl : index(request)
Ctrl->>Ctrl : validate filters
Ctrl->>Svc : generateOutcomeAnalytics(filters)
Svc-->>Ctrl : analytics payload
Ctrl-->>Client : JSON { success : true, data }
```

**Diagram sources**
- [api.php:670-686](file://routes/api.php#L670-L686)
- [CareerOutcomeAnalyticsController.php:25-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L25-L42)
- [CareerOutcomeAnalyticsService.php:20-31](file://app/Services/CareerOutcomeAnalyticsService.php#L20-L31)

**Section sources**
- [api.php:670-686](file://routes/api.php#L670-L686)
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [api-reference.md:316-354](file://docs/api/api-reference.md#L316-L354)

### Frontend Dashboards and Visualizations
- OverviewMetrics.vue: Displays total alumni, employment rate, average salary, tracking rate, plus top industries, top employers, and geographic distribution bars.
- SalaryAnalysis.vue: Shows overall metrics, salary distribution ranges, experience-level breakdowns, progression timeline, and regional comparisons.
- GeographicMap.vue: Offers list and map view modes for location distribution with summary stats.
- PublicMap.vue: Presents global alumni distribution visualization placeholders.
- EmployerEngagement.vue: Shows top engaging employers, jobs posted, hires, and hiring trends by industry for institutional admins.
- CareerOutcomes.vue: Loads analytics via API with filter parameters and supports export.

```mermaid
graph TB
OVERVIEW["OverviewMetrics.vue"] --> API["CareerOutcomeAnalyticsController"]
SALARY["SalaryAnalysis.vue"] --> API
GEO["GeographicMap.vue"] --> API
PUBMAP["PublicMap.vue"] --> MAPSVC["AlumniMapService"]
EMPENG["EmployerEngagement.vue"] --> API
CAREEROUT["CareerOutcomes.vue"] --> API
API --> SVC["CareerOutcomeAnalyticsService"]
MAPSVC --> DB["Models"]
SVC --> DB
```

**Diagram sources**
- [OverviewMetrics.vue:1-221](file://resources/js/components/Analytics/OverviewMetrics.vue#L1-L221)
- [SalaryAnalysis.vue:1-145](file://resources/js/components/Analytics/SalaryAnalysis.vue#L1-L145)
- [GeographicMap.vue:1-219](file://resources/js/components/Analytics/Charts/GeographicMap.vue#L1-L219)
- [PublicMap.vue:36-51](file://resources/js/Pages/Alumni/PublicMap.vue#L36-L51)
- [EmployerEngagement.vue:28-116](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue#L28-L116)
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [AlumniMapService.php:9-236](file://app/Services/AlumniMapService.php#L9-L236)

**Section sources**
- [OverviewMetrics.vue:1-221](file://resources/js/components/Analytics/OverviewMetrics.vue#L1-L221)
- [SalaryAnalysis.vue:1-145](file://resources/js/components/Analytics/SalaryAnalysis.vue#L1-L145)
- [GeographicMap.vue:1-219](file://resources/js/components/Analytics/Charts/GeographicMap.vue#L1-L219)
- [PublicMap.vue:36-51](file://resources/js/Pages/Alumni/PublicMap.vue#L36-L51)
- [EmployerEngagement.vue:28-116](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue#L28-L116)
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)

## Dependency Analysis
- Controllers depend on CareerOutcomeAnalyticsService and AlumniMapService.
- Services depend on Eloquent models for data retrieval and aggregation.
- Frontend components depend on API endpoints for data fetching.
- Routes define the contract for analytics exposure.

```mermaid
graph LR
CTRL["CareerOutcomeAnalyticsController"] --> SVC["CareerOutcomeAnalyticsService"]
CTRL --> MAPSVC["AlumniMapService"]
SVC --> MODELS["Models"]
MAPSVC --> MODELS
UI["Vue Components"] --> CTRL
ROUTE["routes/api.php"] --> CTRL
```

**Diagram sources**
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [CareerOutcomeAnalyticsService.php:15-511](file://app/Services/CareerOutcomeAnalyticsService.php#L15-L511)
- [AlumniMapService.php:9-236](file://app/Services/AlumniMapService.php#L9-L236)
- [api.php:670-686](file://routes/api.php#L670-L686)

**Section sources**
- [CareerOutcomeAnalyticsController.php:16-42](file://app/Http/Controllers/Api/CareerOutcomeAnalyticsController.php#L16-L42)
- [CareerOutcomeAnalyticsService.php:15-511](file://app/Services/CareerOutcomeAnalyticsService.php#L15-L511)
- [AlumniMapService.php:9-236](file://app/Services/AlumniMapService.php#L9-L236)
- [api.php:670-686](file://routes/api.php#L670-L686)

## Performance Considerations
- Use filtered queries and scopes to limit dataset size early.
- Aggregate data at the database layer (GROUP BY, COUNT, AVG) to reduce PHP processing overhead.
- Implement clustering for map rendering to avoid heavy client-side computations.
- Cache frequently accessed snapshots and program effectiveness results.
- Paginate long lists in dashboards to improve responsiveness.
- Index columns used in filters (graduation_year, program, industry, effective_date).

## Troubleshooting Guide
Common issues and resolutions:
- Empty analytics responses: Verify filters and ensure data exists for selected periods/programs.
- Missing salary data: Confirm SalaryProgression records exist and are recent; check effective_date boundaries.
- Geographic map shows no data: Ensure users have non-private location data and coordinates.
- Export failures: Validate export endpoint payload and CSRF token handling in frontend.
- Performance bottlenecks: Review query plans for grouped aggregations and consider adding appropriate indexes.

**Section sources**
- [CareerOutcomeAnalyticsTest.php:1-133](file://tests/Feature/CareerOutcomeAnalyticsTest.php#L1-L133)
- [AlumniMapServiceTest.php:129-212](file://tests/Feature/AlumniMapServiceTest.php#L129-L212)
- [CareerAnalyticsApiTest.php:84-132](file://tests/Feature/CareerAnalyticsApiTest.php#L84-L132)

## Conclusion
The employment analytics and insights system integrates robust backend services, models, and API endpoints with intuitive frontend dashboards. It enables comprehensive tracking of time-to-employment, salary progression, employment rates by course, employer engagement, geographic distribution, and trend analysis. With proper indexing, caching, and modular design, the system scales to support institutional reporting and strategic decision-making.

## Appendices

### Data Sources and Calculation Methodologies
- Time-to-employment metrics: Employment presence at specific dates after graduation using career timelines.
- Salary progression analysis: Latest annualized salary per user up to a given date, grouped by user.
- Employment rate calculations by course: Filters applied to education histories and career timelines to compute percentages.
- Employer engagement metrics: Aggregations of jobs posted and hires by employer; hiring trends by industry.
- Geographic distribution: Counts by location with privacy-aware filtering; clustering for map performance.
- Program effectiveness: Composite scoring combining employment rates, salary growth, job satisfaction, and engagement.
- Trend analysis: Direction, strength, and significance derived from time-series trend data.

**Section sources**
- [CareerOutcomeAnalyticsService.php:459-510](file://app/Services/CareerOutcomeAnalyticsService.php#L459-L510)
- [SalaryProgression.php:55-68](file://app/Models/SalaryProgression.php#L55-L68)
- [ProgramEffectiveness.php:66-125](file://app/Models/ProgramEffectiveness.php#L66-L125)
- [CareerTrend.php:69-152](file://app/Models/CareerTrend.php#L69-L152)
- [AlumniMapService.php:101-170](file://app/Services/AlumniMapService.php#L101-L170)

### Visualization Approaches
- Overview: Cards for key metrics, bar charts for top industries/employers/geographic distribution.
- Salary: Distribution ranges, experience-level breakdowns, progression timelines, regional comparisons.
- Geography: List view for sortable locations; map view placeholder for interactive mapping libraries.
- Employer engagement: Tables for top engaging employers and hiring trends by industry.
- Dashboards: Filtered loading via API with export capability.

**Section sources**
- [OverviewMetrics.vue:1-221](file://resources/js/components/Analytics/OverviewMetrics.vue#L1-L221)
- [SalaryAnalysis.vue:1-145](file://resources/js/components/Analytics/SalaryAnalysis.vue#L1-L145)
- [GeographicMap.vue:1-219](file://resources/js/components/Analytics/Charts/GeographicMap.vue#L1-L219)
- [EmployerEngagement.vue:28-116](file://resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue#L28-L116)
- [CareerOutcomes.vue:247-303](file://resources/js/Pages/Analytics/CareerOutcomes.vue#L247-L303)