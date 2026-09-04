# Gemini Instructions: Sistema de Metrologia Lean Tech

Este documento fornece diretrizes, padrões e instruções para o desenvolvimento no projeto **Sistema de Metrologia Lean Tech**.

## 1. Visão Geral do Projeto
O Sistema de Metrologia Lean Tech é uma aplicação web para gestão de instrumentos de medição, calibrações e processos metrológicos. Ele visa garantir conformidade, rastreabilidade e eficiência nos processos de medição.

### Tecnologias Principais
- **Backend:** PHP 8.2+, Laravel 12
- **Painel Administrativo:** Filament 4
- **Banco de Dados:** MySQL
- **Cache/Queue:** Redis
- **Comunicação IoT:** MQTT via Broker Mosquitto.
- **Ponte MQTT:** Comando `iot:mqtt-bridge` para escutar dispositivos.
- **Processamento:** Background Workers (Laravel Queue) e Redis.
- **ML Service:** Microserviço Python (FastAPI/XGBoost) para análise de anomalias.
- **Frontend:** Vite, Tailwind CSS 4 e React (IoT Monitor).
- **WebSockets:** Laravel Reverb para atualizações em tempo real.

## 2. Arquitetura e Estrutura
O projeto utiliza uma arquitetura modular.
- **`Modules/Metrology/`**: Gestão metrológica e calibrações.
- **`Modules/IoT/`**: Gestão de Gateways, Nós e telemetria em tempo real.
    - Possui um serviço de ML interno e um Bridge para o MQTT.
- **`Modules/System/`**: Core do sistema, usuários e estações.

- **`app/`**: Contém lógica central, modelos compartilhados (`Station`, `Supplier`, `User`) e provedores globais.
- **`Modules/`**: Diretório para módulos de negócio independentes.
    - **`Metrology/`**: Módulo principal para gestão metrológica. Possui seus próprios controllers, modelos, recursos Filament e migrações.
- **`config/`**: Configurações da aplicação, incluindo `filament.php`, `modules.php` e `filament-shield.php`.
- **`database/`**: Migrações e seeders globais. Note que módulos possuem suas próprias pastas `database`.

### Convenções de Filament
- O projeto utiliza **Clusters** para organizar recursos relacionados (ex: `Modules/Metrology/app/Filament/Clusters/Metrology`).
- O acesso é controlado via **Filament Shield** (Permissões baseadas em Roles).
- Grupos de navegação globais são definidos no Enum `App\NavigationGroup`.

## 3. Comandos Essenciais

### Ambiente Docker
```bash
# Iniciar ambiente
docker-compose up -d --build

# Parar ambiente
docker-compose down

# Logs da aplicação
docker-compose logs -f app
```

### Desenvolvimento PHP (via Docker)
```bash
# Instalar dependências
docker-compose exec app composer install

# Migrações e Seeds (Módulo Metrology)
docker-compose exec app php artisan module:migrate Metrology
docker-compose exec app php artisan module:seed Metrology

# Gerar usuário admin Filament
docker-compose exec app php artisan make:filament-user

# Limpar caches
docker-compose exec app php artisan optimize:clear
```

### Frontend
```bash
# Iniciar servidor de desenvolvimento Vite
npm run dev

# Build de produção
npm run build
```

### Testes e Qualidade
```bash
# Executar testes (Pest)
docker-compose exec app php artisan test

# Análise estática (Larastan)
docker-compose exec app ./vendor/bin/phpstan analyse

# Corrigir estilo de código (Pint)
docker-compose exec app ./vendor/bin/pint
```

## 4. Convenções de Desenvolvimento

### Padrões de Código
- Siga as **PSR-12** para formatação de código PHP.
- Utilize **Typescript** (se aplicável) e **Tailwind CSS 4** para o frontend.
- Utilize **Typed Properties** e **Return Types** em todas as novas classes e métodos.

### Fluxo de Trabalho de Migração
- Sempre crie migrações dentro do módulo correspondente se a funcionalidade for específica dele:
  ```bash
  php artisan module:make-migration create_table_name ModuleName
  ```

### Testes
- Novos recursos devem ser acompanhados de testes funcionais/unidade utilizando **Pest**.
- Testes de módulos ficam em `Modules/<Module>/tests`.

## 5. Ambiente de Produção
Em produção, a arquitetura é híbrida, utilizando containers para a aplicação e Redis, enquanto o Nginx e o Banco de Dados são serviços externos/gerenciados.

### Estrutura de Produção
- **App, Redis, MQTT, Worker, Reverb & ML-Service:** Gerenciados via `docker-compose.prod.yml`.
- **Nginx:** Externo (`/var/www/nginx/conf.d/leantech.andremacedo.dev.br.conf`), configurado para:
    - PHP: Proxy para `amemiya-app:9000`.
    - Reverb (WebSockets): Proxy para `amemiya-reverb:8080` no path `/app`.
    - ML Service: Proxy para `amemiya-ml-service:5000` no path `/ml/`.
- **Banco de Dados:** MySQL externo.

### Configuração de Rede
A aplicação utiliza uma rede externa chamada `proxy-network` para se comunicar com o reverse proxy.
```bash
# Criar a rede caso não exista
docker network create proxy-network
```

### Comandos de Produção
```bash
# Subir ambiente de produção
docker-compose -f docker-compose.prod.yml up -d --build

# Executar migrações em produção
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### Variáveis de Ambiente (.env)
- `DB_HOST`: Host do banco de dados externo.
- `REDIS_HOST`: `redis` (nome do serviço no docker-compose.prod.yml).
- `APP_ENV`: `production`
- `APP_DEBUG`: `false`

## 6. Acessos Locais (Desenvolvimento)
- **Aplicação:** [http://localhost:8000](http://localhost:8000)
- **phpMyAdmin:** [http://localhost:8080](http://localhost:8080)
- **MailHog:** [http://localhost:8025](http://localhost:8025)

---
*Este arquivo deve ser atualizado sempre que houver mudanças significativas na arquitetura ou nos fluxos de trabalho do projeto.*
