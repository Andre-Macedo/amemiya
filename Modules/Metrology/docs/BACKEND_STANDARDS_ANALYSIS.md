# Backend Rigorous Analysis & Standardization Report (Modular ERP)

Este documento detalha o estado atual do backend Laravel do MetroLab e define as diretrizes para padronização técnica de nível Enterprise.

## 1. Diagnóstico de Arquitetura Atual

### Pontos Fortes:
- **Modularidade:** Uso correto de `nwidart/laravel-modules`.
- **Versionamento:** APIs versionadas (V1).
- **Tipagem:** Uso extensivo de DTOs, Enums e FormRequests.
- **Ecossistema:** Laravel 12 + Filament v4 (Estado da arte).
- **Documentação:** Adoção total do padrão de Docstrings do Google e Relatórios Técnicos.

### Pontos de Atenção (Garantidos):
- **Clean Architecture:** Lógica de negócio extraída para Actions reutilizáveis.
- **Desacoplamento:** Módulo `System` isolado de `Metrology`.
- **Output Consistente:** Uso de `JsonResources` em 100% das rotas de API.

## 2. Padrões Obrigatórios (Boas Práticas)

### 2.1. Controllers (Skinny Controllers)
Os controladores devem apenas:
1. Receber o Request (validado via FormRequest).
2. Chamar uma Action (passando um DTO se o payload for complexo).
3. Retornar um Resource ou JsonResponse documentado.

### 2.2. Actions (Service Layer)
Toda lógica que envolve mais de uma tabela ou cálculos técnicos deve residir em uma **Action**.
- Local: `Modules/{Module}/app/Actions`
- Padrão: Único método público `execute()`.

### 2.3. DTOs (Data Transfer Objects)
Para payloads complexos (Checklists, Cálculos), o uso de DTOs é obrigatório.
- Local: `Modules/{Module}/app/DTOs`

### 2.4. Form Requests
Nunca usar `$request->validate()` dentro do controller.
- Local: `Modules/{Module}/app/Http/Requests`

### 2.5. Documentação (Google Style)
Todas as classes e métodos públicos devem ter docstrings seguindo o padrão Google.

## 3. Histórico de Padronização (Roadmap)

### Fase 1: Limpeza do Core (CONCLUÍDO)
- [x] Criação do módulo `System`.
- [x] Migração de `User`, `Station`, `Supplier`, `Auth`, `Profile`, `Logs`.

### Fase 2: Refatoração de Calibrações (CONCLUÍDO)
- [x] `CreateCalibrationAction`, `ApproveCalibrationAction`, `RejectCalibrationAction`.
- [x] `CalibrationSubmissionDTO`.

### Fase 3: Padronização de Instrumentos (CONCLUÍDO)
- [x] `GetInstrumentDriftAction`, `PrintInstrumentLabelAction`.
- [x] `StoreInstrumentRequest`, `UpdateInstrumentRequest`.

### Fase 4: Padrões e Procedimentos (CONCLUÍDO)
- [x] `SaveChecklistTemplateAction`.
- [x] Padronização de `ReferenceStandardApiController`.

### Fase 5: Auxiliares e Qualidade (CONCLUÍDO)
- [x] `CloseNonConformityAction`.
- [x] Padronização de `DashboardApiController` e Recursos Auxiliares (Types/Materials).

---
*Relatório finalizado em 06/03/2026. Backend pronto para escala Enterprise.*
