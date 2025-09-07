# Comprehensive Analytics Implementation Plan

This document outlines the tasks and sub-tasks required to build the enhanced analytics features for the platform.

## Phase 1: Enhanced Analytics for Institution Admins

### Task 1.1: Graduate Outcome Analysis Dashboard
- [x] **Backend: Data Aggregation**
  - [x] Create a new service or job to periodically calculate and cache graduate outcome statistics (time-to-employment, salary progression, etc.).
  - [x] Create a new API endpoint to serve this aggregated data.
- [x] **Frontend: Dashboard UI**
  - [x] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/GraduateOutcomes.vue`.
  - [x] Add a link to the new page in the Institution Admin navigation menu.
  - [x] Build UI components for visualizing data (charts for salary progression, tables for top employers, maps for geographic distribution).
  - [x] Add filters to the dashboard (by graduation year, course, demographics).

### Task 1.2: Course & Program ROI Dashboard
- [x] **Backend: Data Correlation**
  - [x] Enhance the analytics service to correlate course data with graduate employment and salary data.
  - [x] Create a new API endpoint to serve course ROI metrics.
- [x] **Frontend: Dashboard UI**
  - [x] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/CourseROI.vue`.
  - [x] Add a link to the new page.
  - [x] Build UI to display a list of courses ranked by graduate employment rate and average salary.
  - [x] Add drill-down views to see detailed outcome stats for a specific course.

### Task 1.3: Employer Engagement Dashboard
- [x] **Backend: Data Tracking**
  - [x] Track employer engagement metrics (job posts, hires, profile views).
  - [x] Create a new API endpoint for employer engagement data.
- [x] **Frontend: Dashboard UI**
  - [x] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue`.
  - [x] Add a link to the new page.
  - [x] Build UI to show top engaging employers, most in-demand skills from job posts, and hiring trends by industry.

### Task 1.4: Community Health Dashboard
- [x] **Backend: Engagement Metrics**
  - [x] Aggregate data on user logins, post creation, comments, event registrations, and mentorship connections.
  - [x] Create an API endpoint for these community health metrics.
- [x] **Frontend: Dashboard UI**
  - [x] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/CommunityHealth.vue`.
  - [x] Add a link to the new page.
  - [x] Build UI with charts and stats for user activity, popular topics, and event attendance.

## Phase 2: Enhanced Analytics for Super Admins

### Task 2.1: Platform-Wide Benchmarking Dashboard
- [x] **Backend: Cross-Tenant Aggregation**
  - [x] Create a system to securely and anonymously aggregate key metrics across all tenants.
  - [x] Create a new Super Admin API endpoint for benchmark data.
- [x] **Frontend: Dashboard UI**
  - [x] Enhance the existing Super Admin analytics page.
  - [x] Add charts to compare metrics (employment rate, salary) across institutions (anonymized).

### Task 2.2: Market Trends Dashboard
- [x] **Backend: Ecosystem-Wide Analysis**
  - [x] Aggregate data on in-demand skills and top industries across all employers on the platform.
  - [x] Create a new Super Admin API endpoint for market trends.
- [x] **Frontend: Dashboard UI**
  - [x] Add a new section to the Super Admin analytics page for market trends.
  - [x] Build UI to visualize top skills and industries.

### Task 2.3: System Growth & Health Dashboard
- [x] **Backend: Business Metrics**
  - [x] Track platform-wide metrics (new users, new institutions, revenue if applicable).
  - [x] Create a Super Admin API endpoint for these metrics.
- [x] **Frontend: Dashboard UI**
  - [x] Add a new section to the Super Admin dashboard for key business metrics.
  - [x] Build UI with charts for user growth and other KPIs.
