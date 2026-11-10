# Modules (Arquitetura Modular de Negócio Amemiya)

## Overview
Agrupador arquitetural dos três módulos de domínio desacoplados que compõem o núcleo do Sistema Metrológico Lean Tech: IoT (telemetria e manutenção preditiva), Metrology (gestão de instrumentos e conformidade ISO/IEC 17025) e System (segurança, multi-tenancy e trilha de auditoria). Cada módulo encapsula suas próprias migrações, rotas, regras de negócio, recursos Filament e testes.

## Subdirectories & Boundaries
- [IoT](./IoT/EGM.md): Telemetria contínua de vibração, temperatura e ruído, ponte MQTT, análise de severidade ISO 20816 e microserviço de ML.
- [Metrology](./Metrology/EGM.md): Calibrações metrológicas, checklists, cálculo de incerteza e assinatura digital de certificados (21 CFR Part 11).
- [System](./System/EGM.md): Autenticação, gestão de estações/máquinas, RBAC com Filament Shield e Audit Chain criptográfica.

## Invariants
- **Desacoplamento Modular**: Módulos não devem referenciar modelos de outros módulos via chaves estrangeiras rígidas sem validação de limites ou contratos de eventos.
- **Migrações e Rotas Autocontidas**: Cada módulo mantém suas próprias migrações em `database/migrations` e rotas em `routes/api.php` e `routes/web.php`.

## Side Effects
- Carregamento dinâmico de Service Providers e recursos Filament pelo orquestrador `nwidart/laravel-modules`.

## Verification
- Run: `docker-compose exec app php artisan module:list`
