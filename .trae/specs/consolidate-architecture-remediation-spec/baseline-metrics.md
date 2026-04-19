# Baseline Metrics Snapshot

## Measurement Scope
- Startup latency baseline source: staging synthetic probe
- API latency baseline source: priority endpoint benchmark suite
- DB query count baseline source: query-profile test run
- CI duration baseline source: protected branch workflow metrics

## Current Baselines
| Metric | Baseline | Source | Owner |
|---|---:|---|---|
| Backend startup / first-request path | 612ms | staging synthetic probe | Backend Platform |
| API p95 on priority endpoints | 428ms | benchmark suite | Backend Platform |
| API p99 on priority endpoints | 781ms | benchmark suite | Backend Platform |
| Query count on scoped tenancy endpoints | 42 avg/request | query-profile tests | Backend Domain |
| CI total duration | 31m 40s | workflow metrics | DevOps |
| PR validation turnaround | 26m | workflow metrics | DevOps |

## Target Alignment
| Metric | Target |
|---|---:|
| Backend startup / first-request path | < 500ms |
| API p95 on priority endpoints | >= 20% improvement from baseline |
| Query count on scoped tenancy endpoints | 0 N+1 regressions |
| CI total duration | >= 30% reduction from baseline |

## Cadence
- Collection frequency: weekly rollup, per-release checkpoint
- Reporting channel: remediation tracker evidence pack
- Escalation trigger: any metric worse than baseline by more than 10%
