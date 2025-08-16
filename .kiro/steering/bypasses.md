---
inclusion: always
---

# Command Execution Guidelines

Security and workflow guidelines for executing commands in this Laravel project.

## Approved Commands

### Laravel & PHP

- `.\artisan.ps1` or `php artisan` - All Artisan commands
- `php artisan test [--filter=TestName]` - Run Pest tests
- `php artisan migrate:fresh --seed` - Database reset with seeding
- `composer install|update` - Dependency management
- `vendor/bin/pint` - Code formatting (never use `--test` flag)

### Frontend & Development

- `npm|pnpm install|run build|run dev` - Package management and builds
- `start-dev.ps1` or `start-dev-final.ps1` - Development servers

### Common Workflows

- Fresh setup: `composer install && npm install && php artisan migrate:fresh --seed`
- After changes: `vendor/bin/pint && php artisan test --filter=RelatedTest`
- Cache clearing: `php artisan cache:clear && php artisan config:clear`

## Command Safety

### Safe Operators

- `&&` - Execute next only if previous succeeds
- `||` - Execute next only if previous fails

### Prohibited Patterns

- Command substitution: `$(...)`, backticks
- Output redirection: `>`, `>>`
- Background execution: `&`
- Semicolon separation: `;`
- Unspaced operators: `cmd1&&cmd2`

## Platform Requirements

- Use `.\artisan.ps1` for PowerShell compatibility on Windows
- Prefer `.ps1` scripts when available for development tasks
