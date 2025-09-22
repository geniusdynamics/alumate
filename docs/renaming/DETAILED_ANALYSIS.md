# Detailed Case Sensitivity Analysis

## Components Import Issues (@/Components → @/components)

### Files with @/Components imports (need to change to @/components):

1. **Analytics Files:**
   - `Pages/Analytics/FundraisingDashboard.vue` (lines 139-145)
   - `Pages/Analytics/Dashboard.vue` (lines 154-156)
   - `Pages/Analytics/Reports.vue` (line 254)
   - `Pages/Analytics/Predictions.vue` (line 255)
   - `Pages/Analytics/Kpis.vue` (lines 179-180)

2. **Dashboard Files:**
   - `Pages/Dashboard.vue` (lines 218-222)
   - `Pages/Events.vue` (lines 166-169)
   - `Pages/HelpTickets/Index.vue` (lines 155-156)

3. **Student/Career Files:**
   - `Pages/Students/MentorshipHub.vue` (lines 260-264)
   - `Pages/Students/CareerGuidance.vue` (lines 289-291)
   - `Pages/Students/StoriesDiscovery.vue` (lines 216-218)
   - `Pages/Career/Goals.vue` (lines 173-174)

4. **Test/Example Files:**
   - `Pages/Test/RealTimeDemo.vue` (lines 173-174)
   - `Pages/Test/MobileComponents.vue` (lines 228-229)
   - `Pages/Test/PerformanceOptimization.vue` (lines 184-185)
   - `Pages/Examples/LoadingStates.vue` (lines 170-175)

5. **Search/Advanced Features:**
   - `Pages/Search/Advanced.vue` (lines 52-54)
   - `Pages/Search/Index.vue` (lines 154-155)
   - `Pages/Search/Components/SearchForm.vue` (line 296)

6. **Video/Communication:**
   - `Pages/VideoCall/Index.vue` (lines 165-169)
   - `Pages/Messages/Index.vue` (lines 54-55)
   - `Pages/Messages/Show.vue` (line 130)
   - `Pages/Messages/Create.vue` (line 132)

7. **Forums/Social:**
   - `Pages/Forums/Index.vue` (lines 136-139)
   - `Pages/Social/Timeline.vue` (lines 195-201)
   - `Pages/Discussions/Index.vue` (lines 181-182)

8. **Fundraising:**
   - `Pages/Fundraising/CampaignShow.vue` (lines 116-119)
   - `Pages/Fundraising/CampaignIndex.vue` (lines 16-17)

9. **Events/Reunions:**
   - `Pages/Reunions/Index.vue` (lines 192-193)
   - `Pages/SpeakerBureau/Index.vue` (lines 250-252)

10. **Success Stories:**
    - `Pages/SuccessStories.vue` (lines 153-155)
    - `Pages/Stories/Index.vue` (lines 161-162)

11. **Developer Tools:**
    - `Pages/Developer/ApiDocumentation.vue` (lines 447-452)

12. **Design System:**
    - `Pages/DesignSystem/Showcase.vue` (lines 432-439)

13. **Component Library:**
    - `Pages/ComponentLibrary/HeroDemo.vue` (lines 111-112)
    - `Pages/ComponentLibrary/StatisticsDemo.vue` (line 270)

## Layouts Import Issues (@/Layouts → @/layouts)

### Files with @/Layouts imports (need to change to @/layouts):

1. **Analytics:**
   - `Pages/Analytics/FundraisingDashboard.vue` (line 146)
   - `Pages/Analytics/Reports.vue` (line 255)
   - `Pages/Analytics/Predictions.vue` (line 256)
   - `Pages/Analytics/Kpis.vue` (line 181)

2. **Help/Training:**
   - `Pages/Help.vue` (line 141)
   - `Pages/WhatsNew.vue` (line 153)
   - `Pages/Training/Index.vue` (line 332)
   - `Pages/Training/VideoTutorial.vue` (line 343)
   - `Pages/Training/Guide.vue` (line 328)
   - `Pages/Training/FAQs.vue` (line 201)

3. **Search:**
   - `Pages/Search/Index.vue` (line 155)
   - `Pages/Discussions/Index.vue` (line 182)

4. **Messaging:**
   - `Pages/HelpTickets/Index.vue` (line 156)
   - `Pages/Messages/Show.vue` (line 130)
   - `Pages/Messages/Create.vue` (line 132)

5. **Examples:**
   - `Pages/Examples/LoadingStates.vue` (line 178)

6. **Fundraising:**
   - `Pages/Fundraising/CampaignShow.vue` (line 120)
   - `Pages/Fundraising/CampaignIndex.vue` (line 17)

7. **Scholarships:**
   - `Pages/Scholarships/Index.vue` (line 197)

## Mixed Case Issues

### Files with inconsistent casing patterns:
- Some files correctly use lowercase (`@/components`, `@/layouts`)
- Others incorrectly use uppercase (`@/Components`, `@/Layouts`)
- Need to standardize ALL to lowercase to match actual folder structure

## Summary Statistics
- **Total files analyzed**: ~200+
- **Files with @/Components issues**: ~80 files
- **Files with @/Layouts issues**: ~25 files
- **Total import statements to fix**: ~150+ imports

## Critical Priority
These fixes are **CRITICAL** for Linux deployment. Every single mismatch will cause:
- Build failures
- Runtime import errors
- Complete application breakdown on Linux servers