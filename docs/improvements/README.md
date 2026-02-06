# Improvements Folder - Living Documentation for Project Enhancements

## Overview
This folder serves as a centralized, living repository for all project improvement plans, tasks, and completion summaries. It ensures continuity, allowing teams to track progress without losing context. The structure supports iterative development: Start with a main plan (improvement-plan.md), execute in phases, and archive completions in dated files (e.g., "phase1-completion-2025-09-20.md"). This allows overwriting the active plan with updates while preserving historical records for review.

The Graduate Tracking System project uses this for structured enhancements, starting with the core production readiness fixes. Future developers: Use this as a blueprint for new initiatives – fork the plan, execute, and add your own dated entries.

## Folder Structure Guidelines
- **improvement-plan.md**: Current active plan. Update this as progress is made (e.g., mark tasks [x] as completed, append notes).
- **phase-X-completion-YYYY-MM-DD.md**: Dedicated files for completed phases, with detailed outcomes, timestamps, reviewers, and metrics (e.g., test coverage before/after).
- **archived-plans/**: Subfolder for old plans once a major version is complete (e.g., v1.0-completion-2025-09-20.md).
- **tools-and-scripts/**: Supporting files like validation scripts or CI snippets referenced in plans.

## Instructions for Future Developers
### Adding a New Improvement Plan
1. **Identify Issue**: Review existing docs (e.g., audit-report.md) or create a new issue in GitHub.
2. **Draft Plan**: Copy the format from improvement-plan.md. Include phases, tasks, sub-tasks, code snippets, commands, effort estimates, dependencies, and verification steps. Make it junior-friendly with explanations.
3. **File Naming**: Use "improvement-plan-vX.md" for major versions (e.g., v2.0-plan.md).
4. **Add to README**: Update this README.md: Add a bullet under "Active Plans" with link and status.
5. **Version Control**: Commit to a branch (e.g., `improvements/new-feature`), PR for review.
6. **Execution**: Assign to Orchestrator mode or junior dev. Track in the plan with [ ]/[x] checkboxes.

### Building Upon Existing Plans
- **Update Active Plan**: Edit improvement-plan.md directly for ongoing work. Use Markdown checklists for tasks.
- **Completion Summary**: When a phase completes:
  - Create dated file: "phase-X-completion-YYYY-MM-DD.md" with:
    - Date/Time: YYYY-MM-DD HH:MM (UTC).
    - Summary: What was done, outcomes (e.g., "Fixed N+1: Queries reduced from 50 to 5").
    - Metrics: Before/after (e.g., "Coverage: 65% → 85%").
    - Reviewer: @username.
    - Lessons Learned: 2-3 bullets.
    - Open Issues: Any follow-ups.
  - Append to plan: Link to completion file.
  - Archive if major: Move old plan to archived-plans/.
- **Review and Reference**: For audits, reference dated files (e.g., "Review phase1-completion-2025-09-20.md for tenancy fixes").
- **Tools Integration**: Plans reference tools (phpstan, Debugbar). Run verifications before marking complete.
- **Handoff**: End plans with handoff notes for Orchestrator/Code mode.

### Best Practices
- **Date/Time Stamps**: Use ISO 8601 (e.g., 2025-09-20T08:00:00Z) for all entries.
- **Mermaid Diagrams**: Add for workflows (e.g., tenancy flow) – avoid " " and () in [labels].
- **Cross-References**: Link to AGENTS.md for standards, docs/api for APIs.
- **Cleanup**: Once a full cycle completes (e.g., v2.0), summarize in "improvements-summary-YYYY-MM-DD.md" and close issues.
- **Future Contributions**: If adding files, ensure Markdown format. Update this README for new conventions. Tag @architect for review.

## Active Plans
- **[Current: Production Readiness v1.0](improvement-plan.md)**: Addresses core blockers (tenancy, security, perf). Status: Drafted 2025-09-20. Execute via Orchestrator.
- **Archived Plans**: See archived-plans/ for history.

## Quick Commands
- View all: `ls docs/improvements/`
- Generate TOC: Use VS Code extension or script to auto-update README links.
- Backup: `git add docs/improvements/; git commit -m "Update improvements"`.

This keeps the project evolving systematically. Questions? Open issue #1 in GitHub.