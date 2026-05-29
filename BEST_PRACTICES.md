# Best Practices & Guidelines

## General
- **Version Control**: Commit often using Semantic Commit Messages (e.g., `feat:`, `fix:`, `chore:`).
- **Code Style**: **Laravel Pint** is mandatory. Run `vendor/bin/pint` before committing.
- **Strict Types**: Always use `declare(strict_types=1);` in new PHP files.

## Laravel 12 Application Architecture
### Design Patterns
- **Action Pattern**: Use Single-Action Invokable classes for complex business logic (e.g., `ProcessCalibrationAction`).
- **Strategy Pattern**: Use for swappable algorithms (e.g., `DecisionRuleStrategy`). Avoid massive `if/else` or `switch` blocks in domain logic.
- **Event-Driven**: Decouple side-effects (Notifications, Status Updates) using Events and Listeners.

### Testing (Pest PHP)
- **Framework**: Use **Pest** for all new tests. Refactor PHPUnit tests to Pest when touching legacy files.
- **Structure**:
    - `Unit`: Pure logic (Math, String parsing). No Database access.
    - `Feature`: Request/Response lifecycle, Database persistence, Filament/Livewire interactions.
- **Filament Testing**: Use `livewire(Resource::class)` to test Forms and Tables interactions.

## Filament 4
- **Clusters**: Use Clusters to group related Resources.
- **State Management**: Prefer `->live()` and `->afterStateUpdated()` for dependent fields.
- **Widgets**: Keep widgets "Lazy Loaded" if they perform heavy queries.

## Modular Development (`nwidart/laravel-modules`)
- **Isolation**: Modules should not directly import classes from other Modules unless via a defined Contract/Interface.
- **Migrations**: Always specify `['module' => 'ModuleName']` when running module-specific Artisan commands in tests.

## Security & Compliance (ISO 17025)
- **Audit Trails**: Critical actions (Calibration, Deletion) must be logged (`AccessLog` or Spatie `activity-log`).
- **Traceability**: Never allow deleting a `ReferenceStandard` or `Instrument` that has linked Calibrations (Use SoftDeletes).
