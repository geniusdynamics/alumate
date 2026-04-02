# Active Risk Register

| Risk ID | Risk | Owner | Likelihood | Impact | Monitoring Signal | Mitigation | Rollback Trigger |
|---|---|---|---|---|---|---|---|
| R-01 | Tenancy regression during context unification | Backend Platform | Medium | Critical | cross-tenant anomaly alert | shadow validation + smoke matrix | any cross-tenant read/write leak |
| R-02 | Service decomposition contract drift | Backend Domain | High | High | contract snapshot diff | endpoint contract tests + golden payloads | protected route contract mismatch |
| R-03 | Frontend import instability on Linux | Frontend Platform | Medium | Medium | CI build failures on linux | case-safe rename batches + import checks | protected branch build failure |
| R-04 | CI optimization omits required checks | DevOps | Low | High | gate inventory mismatch | explicit suite assertions | missing required suite execution |
| R-05 | Performance regression from tuning changes | DevOps + Backend | Medium | High | p95 delta > 10% worse baseline | before/after load benchmarks | p95 degradation above threshold |
