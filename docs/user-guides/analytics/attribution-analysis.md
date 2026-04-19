# Attribution Analysis User Guide

## Overview

Attribution analysis helps you understand how different marketing touchpoints contribute to user conversions and outcomes. This guide covers how to track, analyze, and optimize your marketing efforts using multi-touch attribution models.

## Table of Contents

1. [Introduction to Attribution](#introduction-to-attribution)
2. [Attribution Models](#attribution-models)
3. [Tracking Touchpoints](#tracking-touchpoints)
4. [Channel Analysis](#channel-analysis)
5. [ROI Calculation](#roi-calculation)
6. [Budget Optimization](#budget-optimization)
7. [Touchpoint History](#touchpoint-history)
8. [Best Practices](#best-practices)

---

## Introduction to Attribution

### What is Attribution?

Attribution is the process of assigning credit to marketing touchpoints that contribute to user actions. It helps answer the question: **"Which marketing efforts are actually driving results?"**

### Why Attribution Matters

- **Optimize Marketing Spend**: Focus on channels that work
- **Understand User Journeys**: Map the path to conversion
- **Improve Campaign Performance**: Identify successful tactics
- **Justify Budget Requests**: Data-driven decisions

### Key Terms

| Term | Definition |
|------|------------|
| **Touchpoint** | Any interaction a user has with marketing |
| **Conversion** | A desired user action (signup, purchase, etc.) |
| **Attribution Window** | Time period for tracking touches |
| **Conversion Value** | The value assigned to a conversion |
| **Attribution Model** | Rules for assigning credit to touchpoints |

---

## Attribution Models

### Supported Models

The system supports five attribution models:

| Model | Description | Best For |
|-------|-------------|----------|
| **Last Touch** | 100% credit to the last touchpoint | Simple tracking, short cycles |
| **First Touch** | 100% credit to the first touchpoint | Brand awareness focus |
| **Linear** | Equal credit to all touchpoints | Even distribution desired |
| **Time Decay** | More credit to recent touches | Long sales cycles |
| **Position-Based** | 40% first, 40% last, 20% middle | Balanced approach |

### Last Touch Attribution

Gives all credit to the final touchpoint before conversion.

**Example:**
```
User Journey:
Email → Social Ad → Search Ad → Direct Visit → Purchase

Last Touch Attribution:
Email: 0%
Social Ad: 0%
Search Ad: 0%
Direct Visit: 100% ← Last touch gets all credit
```

**Use Cases:**
- Short sales cycles
- Simple conversion funnels
- Performance marketing tracking

### First Touch Attribution

Gives all credit to the initial touchpoint.

**Example:**
```
User Journey:
Social Ad → Email → Search → Direct → Purchase

First Touch Attribution:
Social Ad: 100% ← First touch gets all credit
Email: 0%
Search: 0%
Direct: 0%
```

**Use Cases:**
- Brand awareness campaigns
- Top-of-funnel optimization
- Customer acquisition focus

### Linear Attribution

Distributes credit equally across all touchpoints.

**Example:**
```
User Journey: Email → Social → Search → Direct → Purchase (4 touches)

Linear Attribution:
Email: 25%
Social: 25%
Search: 25%
Direct: 25%
```

**Use Cases:**
- Complex buyer journeys
- Multi-touch marketing
- Equal importance across channels

### Time Decay Attribution

Gives more credit to touchpoints closer to conversion.

**Example:**
```
User Journey: Email → Social → Search → Direct → Purchase

Time Decay Attribution:
Email: 6.5%
Social: 10.8%
Search: 18.0%
Direct: 64.7%
```

**Use Cases:**
- Long sales cycles
- Multiple interactions required
- Recent engagement focus

### Position-Based Attribution

Assigns 40% to first touch, 40% to last touch, and 20% distributed among middle touches.

**Example:**
```
User Journey: Email → Social → Search → Direct → Purchase

Position-Based Attribution:
Email: 40% ← First touch
Social: 6.7%
Search: 6.7%
Direct: 40% ← Last touch
Other: 6.6%
```

**Use Cases:**
- Balanced brand and performance focus
- Nurture campaigns
- Full-funnel visibility

### Choosing the Right Model

| Model Selection Guide | Recommended Model |
|----------------------|-------------------|
| Short sales cycle (< 1 week) | Last Touch |
| Brand awareness focus | First Touch |
| Complex multi-touch journey | Linear or Time Decay |
| Full-funnel optimization | Position-Based |
| Uncertain which to use | Try Position-Based |

---

## Tracking Touchpoints

### Touchpoint Types

| Event Type | Description | Example |
|------------|-------------|---------|
| **page_view** | User viewed a page | Landing page visit |
| **click** | User clicked a link | Ad click |
| **form_submit** | User submitted a form | Signup form |
| **purchase** | User completed purchase | Transaction |
| **signup** | User created account | Registration |
| **login** | User logged in | Session start |

### Tracking Integration

Touchpoints are automatically tracked for users who have consented to analytics:

```javascript
// Example: Track a touchpoint programmatically
trackTouch({
  user_id: 12345,
  source: 'google_ads',
  event_type: 'click',
  campaign: 'spring_sale_2024',
  value: 25.00,
  metadata: {
    keyword: 'alumni networking',
    ad_group: 'brand_terms'
  }
});
```

### Tracking Sources

| Source | Description |
|--------|-------------|
| **organic_search** | Search engine traffic |
| **paid_search** | Paid search ads |
| **social** | Social media platforms |
| **email** | Email campaigns |
| **direct** | Direct navigation |
| **referral** | External websites |
| **display** | Display advertising |
| **affiliate** | Partner/affiliate links |

### Data Collection

Touchpoints are collected and stored with:
- Timestamp
- Source/Medium
- Campaign name
- User identifier
- Event type
- Associated value (if applicable)

---

## Channel Analysis

### Channel Performance Overview

Access channel analysis from **Analytics** > **Attribution** > **Channels**:

```json
{
  "channel_performance": {
    "google_ads": {
      "total_touches": 15420,
      "total_value": 125000,
      "unique_users": 8420,
      "conversion_rate": 3.2,
      "avg_value_per_touch": 8.11,
      "roi": 245.5
    },
    "email_marketing": {
      "total_touches": 28900,
      "total_value": 98000,
      "unique_users": 12500,
      "conversion_rate": 2.8,
      "avg_value_per_touch": 3.39,
      "roi": 312.0
    }
  }
}
```

### Key Metrics

| Metric | Description | Interpretation |
|--------|-------------|----------------|
| **Total Touches** | Number of interactions | Channel activity level |
| **Unique Users** | Distinct users reached | True reach |
| **Conversion Rate** | Touches leading to conversion | Channel effectiveness |
| **Avg Value Per Touch** | Average attributed value | Channel efficiency |
| **Total Value** | Overall attributed revenue | Channel contribution |

### Channel Comparison

Compare channels using side-by-side metrics:

| Channel | Touches | Conversions | Conv. Rate | Value | ROI |
|---------|---------|-------------|------------|-------|-----|
| Email | 28,900 | 807 | 2.8% | $98,000 | 312% |
| Google Ads | 15,420 | 494 | 3.2% | $125,000 | 246% |
| Social | 22,100 | 398 | 1.8% | $52,000 | 185% |
| Direct | 45,200 | 1,128 | 2.5% | $156,000 | — |

### Attribution by Channel

View how each channel contributes to conversions:

```
Channel Attribution (Linear Model):

User Journey: Social → Email → Search → Direct → Purchase

Attribution Breakdown:
┌─────────┬────────────┬───────────────┐
│ Channel │ Touch Count │ Attributed % │
├─────────┼────────────┼───────────────┤
│ Social  │     1      │     25%       │
│ Email   │     1      │     25%       │
│ Search  │     1      │     25%       │
│ Direct  │     1      │     25%       │
└─────────┴────────────┴───────────────┘
```

---

## ROI Calculation

### Understanding ROI Metrics

| Metric | Formula | Description |
|--------|---------|-------------|
| **ROI** | `((Revenue - Cost) / Cost) × 100` | Return on investment |
| **ROAS** | `Revenue / Cost` | Return on ad spend |
| **Cost Per Acquisition** | `Total Cost / Conversions` | Cost to acquire customer |
| **Revenue Per Touch** | `Revenue / Touches` | Value generated per interaction |

### Calculating Channel ROI

1. Navigate to **Analytics** > **Attribution** > **ROI**
2. Select **Channel** and **Time Period**
3. Enter **Ad Spend** for each channel
4. View calculated metrics:

```json
{
  "channel_roi": {
    "google_ads": {
      "revenue": 125000,
      "spend": 36000,
      "roi": 247.2,
      "roas": 3.47,
      "cpa": 72.84
    },
    "email_marketing": {
      "revenue": 98000,
      "spend": 23000,
      "roi": 326.1,
      "roas": 4.26,
      "cpa": 28.50
    }
  }
}
```

### ROI Interpretation Guide

| ROI Range | Interpretation | Action |
|-----------|----------------|--------|
| **> 300%** | Excellent | Scale budget |
| **200-300%** | Good | Optimize and maintain |
| **100-200%** | Average | Test improvements |
| **< 100%** | Poor | Reconsider strategy |
| **Negative** | Loss | Pause or significantly optimize |

### Attribution Window

Set the attribution window to define which touches count:

| Window | Description | Best For |
|--------|-------------|----------|
| **Click 7 Days** | 7 days after click | Standard tracking |
| **Click 30 Days** | 30 days after click | Longer sales cycles |
| **View 1 Day** | 1 day after view | Impression-based |
| **Multi-Touch** | All touches in window | Full journey analysis |

---

## Budget Optimization

### Budget Allocation Recommendations

The system generates automated budget recommendations based on performance:

```json
{
  "budget_recommendations": {
    "period": {
      "start": "2024-01-01",
      "end": "2024-03-31"
    },
    "total_budget": 100000,
    "recommendations": [
      {
        "channel": "email_marketing",
        "current_performance": {
          "revenue": 98000,
          "roi": 326,
          "conversion_rate": 2.8
        },
        "recommended_budget": 40000,
        "recommended_percentage": 40,
        "recommendation": "High performing channel - consider increasing budget",
        "efficiency_score": 92.5
      },
      {
        "channel": "google_ads",
        "current_performance": {
          "revenue": 125000,
          "roi": 247,
          "conversion_rate": 3.2
        },
        "recommended_budget": 35000,
        "recommended_percentage": 35,
        "recommendation": "Positive ROI - maintain current allocation",
        "efficiency_score": 85.3
      }
    ]
  }
}
```

### Efficiency Score

The efficiency score (0-100) combines multiple factors:

```json
{
  "efficiency_score_calculation": {
    "roi_weight": 0.4,
    "conversion_rate_weight": 0.3,
    "unique_users_weight": 0.2,
    "touches_weight": 0.1,
    "example": {
      "normalized_roi": 75,
      "normalized_conversion": 65,
      "normalized_users": 80,
      "normalized_touches": 70,
      "final_score": 71.5
    }
  }
}
```

### Budget Allocation Strategies

| Strategy | Description | When to Use |
|----------|-------------|-------------|
| **Maximize ROI** | Allocate to highest ROI channels | Limited budget |
| **Balanced** | Mix of high ROI and reach | Brand + performance |
| **Reach Focus** | Maximize unique users | Awareness goal |
| **Conversion Focus** | Maximize conversions | Direct response |
| **Test Budget** | Reserve for testing new channels | Growth phase |

### Budget Adjustment Process

1. **Review Current Performance**: Check ROI and conversion metrics
2. **Consider Business Goals**: Align with overall objectives
3. **Analyze Competitive Landscape**: Factor in market conditions
4. **Calculate Recommended Budget**: Use system recommendations
5. **Test Changes Gradually**: Ramp up over time
6. **Monitor Results**: Track impact of changes

---

## Touchpoint History

### Viewing Individual Touch History

Access touch history for any user:

1. Navigate to **Analytics** > **Attribution** > **Touch History**
2. Enter **User ID** or search by email
3. View all tracked touchpoints:

```json
{
  "user_id": 12345,
  "touch_history": [
    {
      "timestamp": "2024-03-15T10:30:00Z",
      "source": "google_ads",
      "event_type": "click",
      "campaign": "spring_sale",
      "value": 15.00
    },
    {
      "timestamp": "2024-03-18T14:22:00Z",
      "source": "email",
      "event_type": "click",
      "campaign": "follow_up",
      "value": 25.00
    }
  ]
}
```

### Touchpoint Timeline

Visual timeline showing user journey:

```
User Journey Timeline:
─────────────────────────────────────────────────────────────►
 Mar 15      Mar 18      Mar 22      Mar 25      Mar 28
   ●──────────●──────────●───────────●───────────●
   Google     Email      Direct      Search      Purchase
   Ads        Follow-up  Visit       Click       ($150)
```

### Conversion Path Analysis

Identify common paths to conversion:

| Path Pattern | Frequency | Avg. Value |
|--------------|-----------|------------|
| Direct Only | 25% | $120 |
| Social → Direct | 18% | $95 |
| Search → Direct | 22% | $145 |
| Email → Direct | 15% | $175 |
| Multi-Touch Mix | 20% | $210 |

---

## Best Practices

### Data Quality

1. **Track Consistently**: Ensure all channels are tracked
2. **Use UTM Parameters**: Standardize campaign tracking
3. **Validate Touch Data**: Regular quality checks
4. **Handle Ad Blockers**: Implement server-side tracking
5. **Manage Privacy**: Respect user consent preferences

### Model Selection

1. **Match Business Model**: Choose based on your sales cycle
2. **Test Multiple Models**: Compare results
3. **Document Decisions**: Record why you chose a model
4. **Review Periodically**: Re-evaluate as business evolves
5. **Consider Hybrid Models**: Mix models for different goals

### Actionable Insights

1. **Focus on Controllable Factors**: Channel mix, spend allocation
2. **Look Beyond ROI**: Consider brand value, LTV
3. **Segment Your Analysis**: Different channels for different audiences
4. **A/B Test Attribution**: Validate model accuracy
5. **Corroborate with Other Data**: Cross-reference with analytics

### Common Pitfalls

| Pitfall | Solution |
|---------|----------|
| Ignoring assisted conversions | Use multi-touch models |
| Over-relying on last-click | Consider full journey |
| Not tracking offline | Integrate offline touchpoints |
| Ignoring mobile journey | Track cross-device |
| Attribution bias | Use multiple models for validation |

### Reporting Best Practices

1. **Regular Reporting Cadence**: Weekly/monthly reviews
2. **Visual Dashboards**: Use charts and visualizations
3. **Contextual Data**: Explain the "why" behind numbers
4. **Action-Oriented**: Focus on recommendations
5. **Iterative Improvement**: Track progress over time

---

## Troubleshooting

### Missing Touchpoints

**Issue**: Not all marketing touches are being tracked

**Solutions:**
1. Verify tracking code implementation
2. Check UTM parameter consistency
3. Review attribution window settings
4. Ensure server-side tracking for ad blockers
5. Validate consent settings

### Inconsistent Data

**Issue**: Data varies between attribution systems

**Possible Causes:**
- Different attribution models
- Varying attribution windows
- Different conversion definitions
- Tracking implementation differences

**Solution:** Standardize definitions and models

### Low Conversion Rates

**Issue**: High traffic but low conversions

**Solutions:**
1. Analyze touchpoints before conversion
2. Check for friction in conversion funnel
3. Review channel targeting
4. A/B test landing pages
5. Consider attribution window adjustment

---

## Additional Resources

- [Analytics Dashboard Guide](analytics-dashboard.md)
- [Cohort Analysis Guide](cohort-analysis.md)
- [Custom Events Guide](custom-events.md)
- [Insights Dashboard Guide](insights-dashboard.md)
- [Privacy Management Guide](privacy-management.md)
- [Learning Analytics Guide](learning-analytics.md)
- [Troubleshooting Guide](troubleshooting.md)

---

For additional support, contact the analytics team at analytics-support@alumni-platform.com
