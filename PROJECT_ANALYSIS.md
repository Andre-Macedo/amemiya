# Deep Architectural Analysis: Amemiya Metrology System

## Executive Summary
This document provides a comprehensive analysis of the **Amemiya** application, a Laravel 12-based Laboratory Information Management System (LIMS) specialized in **Metrology (ISO 17025)**. The system leverages a **Modular Monolith** architecture to ensure scalability and separation of concerns, utilizing **Filament 4** for a rapid, robust administrative interface.

## 1. Architectural Patterns & Core Design
The application follows the **Modular Monolith** design pattern facilitated by `nwidart/laravel-modules`. This allows the application to grow without the complexity of microservices while maintaining strict boundaries between domains.

### 1.1. Module Structure (`Modules/Metrology`)
The `Metrology` module encapsulates the core business domain.
- **Domain-Driven Design (DDD) Influences**:
    - **Entities**: `Instrument`, `Calibration`, `ReferenceStandard` are rich models with encapsulated logic (e.g., `getMpeValue()`, `getNextCalibrationDue()`).
    - **Services**: Complex calculations (Uncertainty, Drift) are offloaded to stateless services like `UncertaintyCalculator` and `MetrologyMath`.
    - **Strategy Pattern**: Decision Rules (compliance with ISO 17025) are implemented via the `DecisionRuleStrategy` interface, allowing hot-swapping of algorithms (`SimpleAcceptance`, `GuardBand`) at runtime based on `InstrumentType`.
    - **Actions**: Write operations are encapsulated in Action classes (e.g., `ProcessCalibrationAction`, `PrepareCertificateDataAction`), keeping Controllers/Livewire components "thin".

### 1.2. Event-Driven Architecture
The system decouples side effects from the primary transaction lifecycle using Laravel Events:
- **Event**: `CalibrationSaved`
- **Listener**: `ProcessCalibrationListener`
- **Benefit**: The UI (Filament Form) does not need to know *how* to calculate pass/fail or update the instrument status; it simply saves the record. The listener handles the heavy lifting (MPE comparison, status transition), ensuring consistent behavior whether the calibration is created via UI, API, or CLI.

## 2. Technology Stack & Implementation
### 2.1. Backend (Laravel 12)
- **Strict Typing**: New code enforces PHP 8.2+ features (Typed properties, Return types).
- **Database**: SQLite (Development) / MySQL (Production).
- **Testing**: **Pest PHP** is the standard.
    - *Feature Tests*: Cover end-to-end flows (e.g., "User fills form -> Certificate Generated").
    - *Unit Tests*: Cover mathematical logic (e.g., "MPE Regex Parsing", "Uncertainty RSS Calculation").

### 2.2. Frontend (Filament 4 & Tailwind 4)
- **Cluster Organization**: Resources are grouped into **Clusters** (`Clusters/Metrology`) to keep the navigation sidebar clean as the number of entities grows (Instruments, Calibrations, Standards, Suppliers, etc.).
- **Dynamic Forms**: Heavy use of `Livewire` reactivity (`->live()`, `->afterStateUpdated()`) to create "Wizard-like" experiences within a single form (e.g., Auto-filling Reference Standards based on Kit selection).

## 3. ISO 17025 Compliance Features
The architecture was specifically verified against ISO/IEC 17025:2017 requirements:

| ISO Requirement | Implementation Approach | Key Classes/Files |
| :--- | :--- | :--- |
| **Traceability** | Mandatory linkage of `ReferenceStandard` to every measurement point. | `CalibrationForm`, `ChecklistItem` |
| **Decision Rules** | Configurable pass/fail logic considering measurement uncertainty. | `DecisionRuleStrategy`, `GuardBand` |
| **Method Validation** | Automated "Method Check" via Intermediate Checks. | `IntermediateCheckResource` |
| **Reporting** | PDF Certificate generation including Uncertainty Budget. | `PrepareCertificateDataAction`, `certificate.blade.php` |
| **Monitoring** | Trend analysis of Reference Standard drift. | `DriftChart` (Widget) |

## 4. Code Quality & Standards
- **Fat Controller Avoidance**: Complex orchestration is moved to `Services` or `Actions`.
- **Testing**: A "Green" test suite is maintained for the core module.
    - *Coverage*: Critical "Money Paths" (Calibration Approval) are 100% covered.

## 5. Future Roadmap & Recommendations
### 5.1. Immediate Improvements
- **API Versioning**: Formalize `Modules/Metrology/routes/api.php` with versioned Controllers for external integration.
- **Queue Optimization**: Move `ProcessCalibrationListener` to a queued job (`ShouldQueue`) to improve UI responsiveness during heavy calculations.

### 5.2. Long-term Vision
- **Multi-Tenant Support**: The current schema allows for Multi-tenancy (via `Team` or `Tenant` scope) if needed, as most queries are already centralized in Filament Resources.
- **Frontend Customization**: Move from standard Filament Views to custom Blade/Livewire components for the "Laboratory View" (Tablet focused interface for technicians).
