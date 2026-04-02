# Frontend Architecture Policy

## Canonical Structure
- `Components`: reusable UI and feature components
- `Pages`: route-level orchestration only
- `Services`: API and integration adapters
- `Stores`: global state only
- `Composables`: local reusable behavior
- `Types`: shared type contracts

## Layering Rules
- `Pages` may import `Components`, `Services`, `Stores`, `Composables`, and `Types`
- `Components` may import `Composables`, `Services`, and `Types`
- `Services` may import `Types` only
- `Stores` may import `Services` and `Types`

## Case-Sensitivity Controls
- Import paths must match exact file casing
- CI executes import-case validation script
- Any mismatch blocks quality gate in strict mode

## Performance Controls
- Heavy visual libraries must be loaded via dynamic import boundaries
- Shared primitives remain in reusable component library paths
- Critical pages require rendering parity checks across breakpoints
