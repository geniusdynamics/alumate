# Cohort Analysis User Guide

## Overview

Cohort analysis helps you understand how groups of users behave over time by tracking their shared characteristics and experiences. This guide explains how to create, analyze, and interpret cohort data to make informed decisions about user retention, engagement, and growth.

## Table of Contents

1. [Understanding Cohorts](#understanding-cohorts)
2. [Creating Cohorts](#creating-cohorts)
3. [Cohort Metrics](#cohort-metrics)
4. [Retention Analysis](#retention-analysis)
5. [Engagement Metrics](#engagement-metrics)
6. [Conversion Funnels](#conversion-funnels)
7. [Cohort Comparison](#cohort-comparison)
8. [Trend Analysis](#trend-analysis)
9. [Automated Insights](#automated-insights)
10. [Best Practices](#best-practices)

---

## Understanding Cohorts

### What is a Cohort?

A cohort is a group of users who share a common characteristic or experience within a defined time period. For example:
- Users who signed up in January 2024
- Graduates from the class of 2023
- Users who completed their first purchase

### Cohort Types

| Cohort Type | Description | Use Case |
|-------------|-------------|----------|
| **Acquisition Cohort** | Grouped by signup/join date | Track retention over time |
| **Graduation Cohort** | Grouped by graduation year | Analyze career outcomes |
| **Degree Cohort** | Grouped by degree program | Compare program effectiveness |
| **Source Cohort** | Grouped by acquisition channel | Evaluate marketing channels |
| **Behavior Cohort** | Grouped by behavior patterns | Identify engagement patterns |

### Why Cohort Analysis Matters

- **Identify Patterns**: Spot trends that aggregate data masks
- **Measure True Retention**: Track behavior from a common starting point
- **Compare Fairly**: Benchmark cohorts against similar groups
- **Predict Outcomes**: Use historical data to forecast future behavior

---

## Creating Cohorts

### Cohort Creation Wizard

1. Navigate to **Analytics** > **Cohorts**
2. Click **+ Create Cohort**
3. Follow the step-by-step wizard

### Step 1: Define Cohort Criteria

**Required Fields:**
- **Cohort Name**: Enter a descriptive name
- **Cohort Type**: Select acquisition, graduation, degree, or custom

**Criteria Options:**

```json
{
  "criteria": {
    "grad_year": 2024,
    "degree": "Computer Science",
    "acquisition_date": {
      "start": "2024-01-01",
      "end": "2024-03-31"
    },
    "acquisition_source": "organic_search"
  }
}
```

### Step 2: Configure Date Parameters

| Parameter | Description |
|-----------|-------------|
| **Acquisition Start Date** | Beginning of the acquisition period |
| **Acquisition End Date** | End of the acquisition period |
| **Analysis Start Date** | When to begin analyzing (usually acquisition date) |
| **Analysis Period** | Duration for tracking (e.g., 90 days, 1 year) |

### Step 3: Set Analysis Options

- **Metrics to Track**: Select retention, engagement, conversion
- **Comparison Cohorts**: Choose cohorts to compare against
- **Time Granularity**: Day, week, or month

### Step 4: Review and Create

1. Review cohort summary
2. Verify member count
3. Click **Create Cohort**

### Quick Cohort Creation

Use presets for common cohorts:

| Preset | Criteria | Description |
|--------|----------|-------------|
| **Last 30 Days** | `acquisition_date >= now()-30` | Recently acquired users |
| **New Graduates** | `grad_year = current_year` | Latest graduation class |
| **Active Users** | `last_active >= now()-7` | Recently engaged users |

---

## Cohort Metrics

### Retention Metrics

Retention measures how many cohort members return over time.

| Metric | Description | Calculation |
|--------|-------------|-------------|
| **Day 7 Retention** | % active 7 days after acquisition | `(Day 7 active users / Total cohort) × 100` |
| **Day 30 Retention** | % active 30 days after acquisition | `(Day 30 active users / Total cohort) × 100` |
| **Day 90 Retention** | % active 90 days after acquisition | `(Day 90 active users / Total cohort) × 100` |
| **Churn Rate** | % who stopped engaging | `100% - Retention Rate` |

### Engagement Metrics

Engagement metrics quantify how actively users interact with the platform.

```json
{
  "engagement_metrics": {
    "avg_sessions_per_week": 3.5,
    "avg_pages_per_session": 8.2,
    "avg_active_days_per_week": 4.1,
    "engagement_score": 72.5
  }
}
```

**Engagement Score Formula:**
```
Engagement Score = (Sessions × 20%) + (Page Views × 30%) + (Active Days × 50%)
```

**Score Ranges:**
- **80-100**: Highly Engaged
- **60-79**: Moderately Engaged
- **40-59**: Low Engagement
- **Below 40**: At Risk

### Conversion Metrics

Track progression through key user journeys.

| Stage | Description | Funnel Position |
|-------|-------------|-----------------|
| **Signup** | Account creation | 1st |
| **First Login** | Initial platform visit | 2nd |
| **Profile Complete** | Profile filled out | 3rd |
| **First Purchase** | First transaction | 4th |
| **Repeat Purchase** | Subsequent transactions | 5th |

---

## Retention Analysis

### Retention Heatmap

The retention heatmap provides a visual representation of cohort retention over time.

**Reading the Heatmap:**

```
Cohort    Jan    Feb    Mar    Apr    May    Jun
--------  -----  -----  -----  -----  -----  -----
Jan 2024  100%   75%    62%    55%    51%    48%
Feb 2024  100%   78%    65%    58%    54%    -
Mar 2024  100%   72%    60%    53%    -      -
Apr 2024  100%   80%    67%    -      -      -
```

**Interpretation:**
- Rows = Cohort groups
- Columns = Time periods (weeks/months)
- Values = Percentage of retained users
- Color intensity = Retention strength

### Classic Retention vs. Bracket Retention

| Type | Description | Use Case |
|------|-------------|----------|
| **Classic Retention** | % of cohort active in each period | General user tracking |
| **Bracket Retention** | % of active users who remain active | Engagement-focused analysis |

### Retention Benchmarks

| Industry | Day 7 | Day 30 | Day 90 |
|----------|-------|--------|--------|
| **Education** | 45-55% | 30-40% | 20-30% |
| **SaaS** | 35-45% | 25-35% | 15-25% |
| **E-commerce** | 25-35% | 15-25% | 10-20% |

### Identifying Retention Issues

**Warning Signs:**
- Day 7 retention below 20%
- Day 30 retention below 10%
- Steep drop in first 2 weeks
- Sudden decline after period of stability

**Investigation Steps:**
1. Analyze onboarding completion rates
2. Check for technical issues in the first week
3. Survey churned users for feedback
4. Compare with high-performing cohorts

---

## Engagement Metrics

### Session Analysis

Track user session patterns:

```json
{
  "session_metrics": {
    "total_sessions": 15234,
    "avg_session_duration": "12:34",
    "avg_pages_per_session": 8.2,
    "sessions_per_user": 4.5
  }
}
```

### Activity Tracking

| Metric | Description | Insight |
|--------|-------------|---------|
| **Active Days** | Days with at least one session | Commitment level |
| **Feature Usage** | Which features are used most | Product-market fit |
| **Time on Site** | Total time spent | Content engagement |
| **Actions Per Session** | Interactions within sessions | User investment |

### Learning Engagement

For educational cohorts:

| Metric | Description |
|--------|-------------|
| **Modules Completed** | Course modules finished |
| **Quiz Scores** | Assessment performance |
| **Time per Module** | Average learning duration |
| **Completion Rate** | % who finish courses |

---

## Conversion Funnels

### Building Conversion Funnels

1. Navigate to **Cohorts** > **Funnel Analysis**
2. Select **Create Funnel**
3. Define funnel stages:

```json
{
  "funnel_stages": [
    {"name": "signup", "event": "user.registered"},
    {"name": "first_login", "event": "user.login"},
    {"name": "profile_complete", "event": "profile.completed"},
    {"name": "first_course", "event": "course.enrolled"},
    {"name": "certification", "event": "certificate.earned"}
  ]
}
```

### Funnel Visualization

| Stage | Users | Conversion Rate | Dropoff |
|-------|-------|-----------------|---------|
| Signup | 1,000 | 100% | - |
| First Login | 850 | 85% | 15% |
| Profile Complete | 700 | 82% | 18% |
| First Course | 500 | 71% | 29% |
| Certification | 350 | 70% | 30% |

### Identifying Funnel Leaks

**High Dropoff Points:**
1. Identify stages with >20% dropoff
2. Analyze user behavior before exit
3. A/B test improvements
4. Monitor post-fix conversion rates

### Time-to-Conversion

Track how long users take to convert:

| Metric | Description |
|--------|-------------|
| **Median Time to Conversion** | Middle value of conversion times |
| **Average Time to Conversion** | Mean time to convert |
| **Conversion Velocity** | How fast users move through funnel |

---

## Cohort Comparison

### Side-by-Side Comparison

Compare multiple cohorts to identify differences:

1. Select cohorts to compare (2-5 recommended)
2. Choose metrics to compare
3. View results in comparison chart

**Comparison Metrics:**

| Metric | Comparison Insight |
|--------|-------------------|
| **Retention Rate** | Which cohort retains better |
| **Engagement Score** | Which cohort is more active |
| **LTV** | Which cohort has higher value |
| **Conversion Rate** | Which cohort converts faster |

### Statistical Significance

When comparing cohorts, consider statistical significance:

```json
{
  "statistical_comparison": {
    "cohort_a": "January 2024",
    "cohort_b": "February 2024",
    "day7_retention_diff": 5.2,
    "p_value": 0.032,
    "significant": true,
    "confidence": 95
  }
}
```

**Interpretation:**
- **p-value < 0.05**: Statistically significant difference
- **p-value > 0.05**: No significant difference (may be random)
- Higher confidence = more reliable conclusion

### Identifying Best Performers

The system automatically identifies top-performing cohorts based on:
- Highest retention rates
- Best engagement scores
- Strongest conversion rates
- Most consistent performance

---

## Trend Analysis

### Viewing Trends

Access trend analysis from the cohort detail page:

```json
{
  "trend_analysis": {
    "period": "week",
    "periods_analyzed": 12,
    "trends": [
      {
        "period": "2024-W01",
        "active_users": 1250,
        "event_count": 5420,
        "indicator": "up",
        "change_percent": 8.5
      }
    ],
    "summary": {
      "overall_trend": "improving",
      "avg_active_users": 1180,
      "periods_with_growth": 7
    }
  }
}
```

### Trend Indicators

| Indicator | Meaning |
|-----------|---------|
| **↑ (Up)** | >10% improvement |
| **↗ (Slight Up)** | 0-10% improvement |
| **→ (Neutral)** | No significant change |
| **↘ (Slight Down)** | 0-10% decline |
| **↓ (Down)** | >10% decline |

### Period-over-Period Analysis

Compare metrics across time periods:

| Period | Active Users | Change | Events |
|--------|--------------|--------|--------|
| Week 1 | 1,200 | - | 5,400 |
| Week 2 | 1,150 | -4.2% | 5,100 |
| Week 3 | 1,280 | +11.3% | 5,800 |
| Week 4 | 1,310 | +2.3% | 6,100 |

---

## Automated Insights

### Insight Types

The system generates automatic insights:

| Type | Trigger | Example |
|------|---------|---------|
| **Retention Alert** | Day 7 retention < 20% | "Low retention detected" |
| **Engagement Warning** | Engagement score < 30 | "Engagement declining" |
| **Trend Detection** | Significant trend change | "Improving retention" |
| **Anomaly Detection** | Unusual pattern | "Unexpected spike" |

### Insight Severity Levels

| Severity | Meaning | Action |
|----------|---------|--------|
| **Critical** | Immediate attention needed | High priority |
| **High** | Significant issue | Review soon |
| **Medium** | Moderate concern | Monitor |
| **Low** | Minor observation | Informational |
| **Positive** | Success indicator | Acknowledge |

### Acting on Insights

1. **Review Insight**: Read the full description
2. **Check Recommendation**: Review suggested action
3. **Implement Fix**: Take recommended steps
4. **Track Progress**: Monitor impact of changes
5. **Provide Feedback**: Rate insight effectiveness

### Insight Effectiveness Tracking

Rate insights to improve recommendations:

- **1-3**: Not useful / Didn't work
- **4-6**: Somewhat useful
- **7-8**: Useful
- **9-10**: Very useful

---

## Best Practices

### Cohort Definition

1. **Be Specific**: Define clear, measurable criteria
2. **Stay Consistent**: Use same criteria for comparisons
3. **Consider Seasonality**: Account for time-based variations
4. **Document Methodology**: Record how cohorts are defined

### Analysis Approach

1. **Start with Questions**: What do you need to know?
2. **Compare Contextually**: Compare similar cohorts
3. **Look for Patterns**: Identify trends across groups
4. **Validate with Data**: Cross-reference multiple sources
5. **Act on Insights**: Implement changes based on findings

### Common Mistakes

| Mistake | Correction |
|---------|------------|
| Comparing dissimilar cohorts | Match cohorts by size, source, time |
| Ignoring seasonality | Account for seasonal variations |
| Short-term analysis | Use at least 90-day analysis periods |
| Small sample sizes | Ensure statistical significance |
| Confirmation bias | Test alternative hypotheses |

### Reporting

1. **Include Context**: Explain cohort definitions
2. **Show Comparisons**: Benchmark against controls
3. **Visualize Trends**: Use charts and heatmaps
4. **Highlight Insights**: Focus on actionable findings
5. **Make Recommendations**: Suggest specific actions

---

## Troubleshooting

### Cohort Member Count is 0

**Cause**: Criteria too restrictive

**Solution**: Broaden cohort criteria or check date ranges

### Retention Shows 100% for All Periods

**Cause**: Misconfigured retention calculation

**Solution**: Verify event tracking is working correctly

### Statistical Comparison Shows "Insufficient Data"

**Cause**: Cohort sizes too small

**Solution**: Combine similar cohorts or extend date range

### Trend Analysis is Empty

**Cause**: Not enough historical data

**Solution**: Wait for more data points (minimum 4 periods)

---

## Additional Resources

- [Analytics Dashboard Guide](analytics-dashboard.md)
- [Attribution Analysis Guide](attribution-analysis.md)
- [Custom Events Guide](custom-events.md)
- [Insights Dashboard Guide](insights-dashboard.md)
- [Privacy Management Guide](privacy-management.md)
- [Learning Analytics Guide](learning-analytics.md)
- [Troubleshooting Guide](troubleshooting.md)

---

For additional support, contact the analytics team at analytics-support@alumni-platform.com
