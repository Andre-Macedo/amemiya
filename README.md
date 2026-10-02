# Sistema de Metrologia Lean Tech (Amemiya)

[![Backend CI](https://github.com/Andre-Macedo/amemiya/actions/workflows/ci.yml/badge.svg)](https://github.com/Andre-Macedo/amemiya/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/)
[![Filament](https://img.shields.io/badge/Filament-4.x-orange.svg)](https://filamentphp.com/)

Plataforma integrada de metrologia industrial, gestão de conformidade (ISO/IEC 17025, ILAC-G8, OIML D10, FDA 21 CFR Part 11) e monitoramento contínuo com IoT e inteligência artificial (XGBoost).

---

## 1. Visão Geral da Arquitetura

```mermaid
graph TD
    subgraph Frontend & Clients
        UI["Next.js 16 Frontend (metrology-sass-front)"]
        Admin["Filament 4 Admin Panel (:8000)"]
        Sensors["Sensores Industriais / ESP32 Nodes"]
    end

    subgraph Backend Core (Amemiya)
        Nginx["Nginx (:8000)"]
        App["amemiya-app (PHP 8.3 FPM)"]
        Reverb["Laravel Reverb (:8080 WebSockets)"]
        Worker["Queue Worker (Laravel Queues)"]
        Bridge["MQTT Bridge (iot:mqtt-bridge)"]
    end

    subgraph Data & Messaging
        DB[("MySQL 8.0 (:3307)")]
        Redis[("Redis 7.x (:6379)")]
        MQTT["Mosquitto Broker (:1883)"]
    end

    subgraph Machine Learning
        ML["ML Service (FastAPI / XGBoost :8000)"]
    end

    Sensors -->|MQTT QoS 1| MQTT
    MQTT -->|iot:mqtt-bridge| Bridge
    Bridge --> Redis
    Bridge --> App

    UI -->|REST API| Nginx
    UI -->|WebSockets| Reverb
    Admin --> Nginx
    Nginx --> App

    App --> DB
    App --> Redis
    App --> ML
    Worker --> Redis
    Worker --> DB
    App -->|Broadcast| Reverb
```

---

## 2. Módulos da Aplicação

O sistema é construído sobre uma **arquitetura modular** (`nwidart/laravel-modules`):

- **[`Modules/Metrology/`](Modules/Metrology)**: Motor metrológico completo. Gestão de calibrações, instrumentos, padrões de referência RBC, cálculo de incerteza de medição conforme GUM, bandas de guarda (ILAC-G8), otimização de intervalos (OIML D10), cartas de controle de Shewhart (ILAC-G24), laudos forenses com assinatura digital SHA-256 e trilha encadeada de auditoria imutável (FDA 21 CFR Part 11).
- **[`Modules/IoT/`](Modules/IoT)**: Ingestão de telemetria industrial de vibração e temperatura em tempo real via Mosquitto MQTT, classificação de severidade conforme ISO 20816-3 e integração com modelo preditivo de anomalias.
- **[`Modules/System/`](Modules/System)**: Multi-tenancy central, perfis e matriz de permissões via Filament Shield, auditoria Spatie Activitylog e usuários.
- **[`hardware/`](hardware)**: Projetos completos de eletrônica (KiCad) e mecânica (Autodesk Fusion 360 / STEP) para os nós sensores, gateway LoRa e bancada industrial com proteção NR-12.

---

## 3. Guia Rápido de Instalação (Docker)

### Pré-requisitos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (com suporte a Docker Compose v2)
- [Git](https://git-scm.com/)

### Passo a Passo

1. **Clonar o repositório:**
   ```bash
   git clone git@github.com:Andre-Macedo/amemiya.git
   cd amemiya
   ```

2. **Configurar variáveis de ambiente:**
   ```bash
   cp .env.example .env
   ```

3. **Subir os containers:**
   ```bash
   docker compose up -d --build
   ```

4. **Instalar dependências e preparar a aplicação:**
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   docker compose exec app php artisan module:migrate Metrology
   docker compose exec app php artisan module:migrate IoT
   docker compose exec app php artisan module:seed Metrology
   docker compose exec app php artisan storage:link
   ```

5. **Criar o usuário administrador do Filament:**
   ```bash
   docker compose exec app php artisan make:filament-user
   ```

---

## 4. Portas e Serviços Locais

| Serviço | Porta Local | Descrição |
| :--- | :--- | :--- |
| **Painel Filament / API** | [http://localhost:8000](http://localhost:8000) | Aplicação web principal |
| **Frontend Next.js** | [http://localhost:3000](http://localhost:3000) | Interface do cliente e monitor IoT |
| **phpMyAdmin** | [http://localhost:8080](http://localhost:8080) | Gerenciador web do MySQL (root/rootpass) |
| **MailHog (SMTP)** | [http://localhost:8025](http://localhost:8025) | Caixa de entrada para e-mails de teste |
| **Mosquitto MQTT** | `localhost:1883` | Broker de mensagens IoT |
| **Redis** | `localhost:6379` | Cache e filas de processamento |
| **MySQL 8.0** | `localhost:3307` | Banco de dados relacional |
| **Laravel Reverb** | `localhost:8080` | Servidor de WebSockets |

---

## 5. Scripts de Qualidade e Testes

Antes de submeter código, execute os scripts de verificação:

```bash
# Executar todos os testes automatizados (Pest)
docker compose exec app composer test

# Verificação de formatação de código (Laravel Pint)
docker compose exec app composer format:test

# Correção automática de formatação
docker compose exec app composer format

# Análise estática de tipos (PHPStan / Larastan)
docker compose exec app composer analyse

# Validação completa de qualidade (Pint + Larastan + Pest)
docker compose exec app composer quality
```

---

## 6. Fluxo de Contribuição e Branches

- **Branch principal de desenvolvimento:** **`develop`**
- **Branch de produção:** **`main`**
- **Todas as Pull Requests devem ter como destino a branch `develop`.**
- Consulte o guia completo em [`CONTRIBUTING.md`](CONTRIBUTING.md) para detalhes sobre Conventional Commits e padrões de arquitetura.

---

## 7. Índice de Documentação Técnica

- **Metrologia e Qualidade:**
  - [`Modules/Metrology/docs/BUSINESS_RULES.md`](Modules/Metrology/docs/BUSINESS_RULES.md) — Manual canônico de regras de negócio, normas ISO e cálculos.
  - [`docs/metrology/METROLOGY_MODULE_GUIDE.md`](docs/metrology/METROLOGY_MODULE_GUIDE.md) — Guia arquitetural completo, diagramas ER e conformidade normativa.
  - [`docs/metrology/USER_ACCEPTANCE_TESTS.md`](docs/metrology/USER_ACCEPTANCE_TESTS.md) — Roteiros de testes de aceitação de usuário (UAT).
- **IoT, Telemetria e MLOps:**
  - [`docs/iot/DECISOES_TECNICAS_E_REFERENCIAS_CIENTIFICAS.md`](docs/iot/DECISOES_TECNICAS_E_REFERENCIAS_CIENTIFICAS.md) — Fundamentação científica e amostragem de vibração.
  - [`docs/iot/REGRAS_DE_NEGOCIO_MLOPS_E_AMOSTRAGEM.md`](docs/iot/REGRAS_DE_NEGOCIO_MLOPS_E_AMOSTRAGEM.md) — Pipeline de machine learning e triagem de anomalias.
  - [`docs/iot/MQTT_SECURITY.md`](docs/iot/MQTT_SECURITY.md) — Diretrizes de segurança, isolamento e ACL do Mosquitto.
  - [`docs/iot/RELATORIO_PCBS.md`](docs/iot/RELATORIO_PCBS.md) — Especificações de eletrônica e esquemáticos.
- **Arquitetura Geral:**
  - [`docs/architecture/TENANCY_ARCHITECTURE.md`](docs/architecture/TENANCY_ARCHITECTURE.md) — Isolamento de dados multi-tenant.
  - [`docs/architecture/WEBSOCKETS_REVERB_GUIDE.md`](docs/architecture/WEBSOCKETS_REVERB_GUIDE.md) — Guia de integração Reverb WebSockets.
  - [`docs/architecture/IMPROVEMENT_PLAN.md`](docs/architecture/IMPROVEMENT_PLAN.md) — Roadmap de melhorias e evolução contínua.
