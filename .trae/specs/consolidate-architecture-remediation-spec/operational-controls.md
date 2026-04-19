# Operational Controls

## Incident Triggers
- Tenant isolation alert from context mismatch telemetry
- Contract test failure on protected endpoints
- Linux CI build failure on case-sensitive import checks
- p95 or startup latency regression beyond threshold

## Escalation Mapping
1. Workstream responder acknowledges incident
2. Accountable architect coordinates containment
3. QA/SDET validates rollback criteria
4. DevOps executes rollback if trigger is met

## Change-Freeze Policy
- Freeze window applies to P0 remediation releases until rollback drill passes
- Only approved hotfixes are allowed during freeze windows
- Freeze exits after phase gate approval evidence is complete

## Communication Plan
- Daily status for active phase milestones
- Immediate blocker updates in incident channel
- Weekly risk review summary with open/closed actions
- Post-rollback incident report within one business day
