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
- [ ] **Backend: Data Correlation**
  - [ ] Enhance the analytics service to correlate course data with graduate employment and salary data.
  - [ ] Create a new API endpoint to serve course ROI metrics.
- [ ] **Frontend: Dashboard UI**
  - [ ] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/CourseROI.vue`.
  - [ ] Add a link to the new page.
  - [ ] Build UI to display a list of courses ranked by graduate employment rate and average salary.
  - [ ] Add drill-down views to see detailed outcome stats for a specific course.

### Task 1.3: Employer Engagement Dashboard
- [ ] **Backend: Data Tracking**
  - [ ] Track employer engagement metrics (job posts, hires, profile views).
  - [ ] Create a new API endpoint for employer engagement data.
- [ ] **Frontend: Dashboard UI**
  - [ ] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/EmployerEngagement.vue`.
  - [ ] Add a link to the new page.
  - [ ] Build UI to show top engaging employers, most in-demand skills from job posts, and hiring trends by industry.

### Task 1.4: Community Health Dashboard
- [ ] **Backend: Engagement Metrics**
  - [ ] Aggregate data on user logins, post creation, comments, event registrations, and mentorship connections.
  - [ ] Create an API endpoint for these community health metrics.
- [ ] **Frontend: Dashboard UI**
  - [ ] Create a new Vue page at `resources/js/Pages/InstitutionAdmin/Analytics/CommunityHealth.vue`.
  - [ ] Add a link to the new page.
  - [ ] Build UI with charts and stats for user activity, popular topics, and event attendance.

## Phase 2: Enhanced Analytics for Super Admins

### Task 2.1: Platform-Wide Benchmarking Dashboard
- [ ] **Backend: Cross-Tenant Aggregation**
  - [ ] Create a system to securely and anonymously aggregate key metrics across all tenants.
  - [ ] Create a new Super Admin API endpoint for benchmark data.
- [ ] **Frontend: Dashboard UI**
  - [ ] Enhance the existing Super Admin analytics page.
  - [ ] Add charts to compare metrics (employment rate, salary) across institutions (anonymized).

### Task 2.2: Market Trends Dashboard
- [ ] **Backend: Ecosystem-Wide Analysis**
  - [ ] Aggregate data on in-demand skills and top industries across all employers on the platform.
  - [ ] Create a new Super Admin API endpoint for market trends.
- [ ] **Frontend: Dashboard UI**
  - [ ] Add a new section to the Super Admin analytics page for market trends.
  - [ ] Build UI to visualize top skills and industries.

### Task 2.3: System Growth & Health Dashboard
- [ ] **Backend: Business Metrics**
  - [ ] Track platform-wide metrics (new users, new institutions, revenue if applicable).
  - [ ] Create a Super Admin API endpoint for these metrics.
- [ ] **Frontend: Dashboard UI**
  - [ ] Add a new section to the Super Admin dashboard for key business metrics.
  - [ ] Build UI with charts for user growth and other KPIs.
