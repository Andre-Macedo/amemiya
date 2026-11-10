# Sistema de Metrologia Lean Tech (Amemiya Backend & Core) Blueprint

## System Overview
Repositório central do Sistema Metrológico Lean Tech. Implementa o backend em Laravel 12 (PHP 8.2+), painel administrativo e operacional em Filament 4, barramento de telemetria IoT com filas Redis e ponte Mosquitto MQTT, microserviço de inteligência artificial em Python (FastAPI/XGBoost) e camada de tempo real via WebSockets (Laravel Reverb). O sistema atende aos rigores regulatórios da ISO/IEC 17025 e FDA 21 CFR Part 11 com arquitetura multi-tenant isolada.

## Local Interface & Invariants
- `dev.py`: CLI de automação e quality gate unificado para desenvolvedores e agentes de IA (`env:check`, `audit`, `pcb:list`, `frontend:build`, `docs:build`).
- `docker-compose.yml` & `docker-compose.prod.yml`: Definições formais de infraestrutura (containers para app, redis, mqtt, worker, reverb e ml-service).

## Subdirectories & System Boundaries
- [app/](./app/EGM.md): Camada de infraestrutura central, middleware de multi-tenancy (`X-Tenant`), autenticação Sanctum e Filament Admin Panel Provider.
- [Modules/](./Modules/EGM.md): Módulos desacoplados de domínio:
  - [Modules/IoT/](./Modules/IoT/EGM.md): Telemetria, ponte MQTT, análise de severidade ISO 20816, prescrição de falhas e integração WebSockets.
  - [Modules/IoT/ml_service/](./Modules/IoT/ml_service/EGM.md): Microserviço Python FastAPI com modelo XGBoost de 36 features.
  - [Modules/Metrology/](./Modules/Metrology/EGM.md): Gestão de instrumentos, calibrações, cálculo de incerteza, checklists e certificados digitais 21 CFR Part 11.
  - [Modules/System/](./Modules/System/EGM.md): Segurança, RBAC (Filament Shield), gestão de estações/máquinas e Audit Chain criptográfica.
- [config/](./config/EGM.md): Configurações declarativas de persistência, mensageria, filas e segurança.
- [docs/designs/ARQUITETURA_ECOSSISTEMA_LEAN_TECH.md](./docs/designs/ARQUITETURA_ECOSSISTEMA_LEAN_TECH.md): Especificação oficial de arquitetura de hardware, software, pinagem e dados do ecossistema.

## Global Invariants
- **Multi-Tenancy por Design**: Todas as requisições API devem resolver o tenant via cabeçalho `X-Tenant` ou subdomínio; tabelas com escopo de tenant devem utilizar `BelongsToTenant`.
- **Integridade Regulatória (21 CFR Part 11)**: Assinaturas eletrônicas exigem re-autenticação por senha, hash SHA-256 e gravação na trilha de auditoria encadeada imutável.
- **Resiliência de Telemetria**: Se o microserviço de ML falhar ou atingir timeout, a telemetria é obrigatoriamente classificada como `pending_analysis`, preservando o registro e alertando a equipe técnica.
- **Padrão de Código**: Conformidade obrigatória com PSR-12, Spatie PHP Guidelines (propriedades tipadas, tipos de retorno estritos, cláusulas de guarda / happy path last) e Larastan nível 5+.

## Global Side Effects
- Mutação contínua de banco de dados relacional MySQL (transações ACID para calibrações e trilhas de auditoria).
- Ingestão contínua de eventos no broker Mosquitto MQTT e filas Redis.
- Emissão de pacotes WebSocket autenticados via Laravel Reverb (`tenant.{tenantSlug}.iot`).

## System Verification
- Run: `python dev.py audit` (executa DRC de hardware, Vite build, sintaxe PHP e testes de integridade).
- Run: `docker-compose exec app php artisan test`
