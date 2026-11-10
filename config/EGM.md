# Configuration Layer (Configurações do Ecossistema Amemiya)

## Overview
Camada declarativa de configuração do ecossistema Laravel. Define parâmetros de conexão para banco de dados MySQL, cache/filas Redis, broker Mosquitto MQTT, servidor de WebSockets Laravel Reverb, painel Filament e arquitetura de módulos.

## Interface
- `config/filament.php`: Configuração do painel administrativo Filament 4 e preferências de interface.
- `config/modules.php`: Definição dos caminhos, namespaces e carregamento dos módulos de negócio (`Modules/`).
- `config/reverb.php`: Configurações de porta, timeouts e certificados do servidor WebSocket Laravel Reverb.
- `config/database.php`: Conexões MySQL e clusters Redis (cache, sessão e filas).
- `config/cors.php`: Políticas de Cross-Origin para integração com Next.js (`metrology-sass-front`) e Expo (`amemiya-app`).

## Invariants
- **Nenhum Dado Sensível Hardcoded**: Senhas de banco, credenciais MQTT e secrets de JWT/Reverb devem ser lidos obrigatoriamente de variáveis de ambiente (`.env`).
- **Isolamento de Cache de Tenancy**: Prefixos de chaves Redis devem ser devidamente isolados para evitar colisão entre ambientes e tenants.
- **Portas Padronizadas**: Reverb escuta internamente em `8080`, PHP-FPM em `9000`, ML Service em `5000` e Mosquitto MQTT em `1883`.

## Side Effects
- Leitura de variáveis de ambiente no boot da aplicação.
- Inicialização de pools de conexão de banco de dados e sockets de rede.

## Verification
- Run: `docker-compose exec app php artisan config:show database`
