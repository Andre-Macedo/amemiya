# Início Rápido com Docker

Este tutorial guia você pelos primeiros passos para subir todo o ecossistema Lean Tech (Backend Laravel, MySQL, Redis, Broker MQTT Mosquitto, Microserviço ML Python e Filament Admin) em menos de 5 minutos utilizando Docker.

---

## Pré-requisitos

Certifique-se de ter instalado em sua máquina de desenvolvimento:
* **Docker Engine** (24.0+) e **Docker Compose** (v2+)
* **Git**
* Portas livres no host: `8000` (Nginx/App), `3307` (MySQL), `6379` (Redis), `1883` (MQTT Mosquitto) e `8080` (phpMyAdmin).

---

## Passo 1: Clonar o Repositório e Configurar Ambiente

Clone o repositório e crie o arquivo de ambiente a partir do exemplo:

```bash
git clone https://github.com/andremacedo/amemiya.git
cd amemiya
cp .env.example .env
```

---

## Passo 2: Subir os Containers

Suba os serviços em segundo plano:

```bash
docker compose up -d --build
```

Verifique o status da infraestrutura com o comando:

```bash
docker compose ps
```

Todos os serviços principais devem estar com o status `Up` ou `healthy`:
* `amemiya-app`: PHP 8.3 / Laravel 12
* `amemiya-nginx`: Reverse Proxy HTTP
* `amemiya-db`: MySQL 8.0 na porta `3307`
* `amemiya-redis`: Fila de telemetria e cache na porta `6379`
* `amemiya-mqtt`: Broker Mosquitto nas portas `1883` e `9001` (WebSockets)
* `amemiya-ml-service`: Microserviço de predição XGBoost na porta `5001`

---

## Passo 3: Executar Migrações e Seeds

Com os containers em execução, instale as dependências e popule o banco de dados inicial:

```bash
# Dependências PHP
docker exec amemiya-app composer install

# Migrações e seeders dos módulos Metrology e IoT
docker exec amemiya-app php artisan migrate --seed
docker exec amemiya-app php artisan module:migrate Metrology
docker exec amemiya-app php artisan module:seed Metrology
docker exec amemiya-app php artisan module:migrate IoT
```

---

## Passo 4: Simulação de Telemetria IoT

Para validar o fluxo completo de ponta a ponta sem necessidade de hardware físico conectado, use o harness de desenvolvimento `dev.py`:

```bash
# Simular 5 pacotes de telemetria de um nó em estado saudável
python dev.py iot:mock --profile=healthy --count=5

# Simular falha de mancal/desgaste de rolamento
python dev.py iot:mock --profile=defect_bearing --count=5

# Simular aquecimento anômalo
python dev.py iot:mock --profile=overheating --count=5
```

---

## Acessos do Sistema

| Aplicação / Serviço | URL de Acesso | Credenciais Padrão |
| :--- | :--- | :--- |
| **Painel Administrativo Filament** | [http://localhost:8000/admin](http://localhost:8000/admin) | `admin@leantech.com` / `password` |
| **Documentação Interativa da API (Scramble)** | [http://localhost:8000/docs/api](http://localhost:8000/docs/api) | Livre em ambiente local |
| **Especificação OpenAPI JSON** | [http://localhost:8000/docs/api.json](http://localhost:8000/docs/api.json) | Livre em ambiente local |
| **phpMyAdmin** | [http://localhost:8080](http://localhost:8080) | Usuário: `root` / Senha: `rootpass` |

---

## Próximos Passos

* [Primeira Conexão na Bancada ESP32](/tutoriais/primeira-bancada-esp32): aprenda a conectar os sensores físicos e transmitir o primeiro pacote real.
* [Calibração Estática ADXL345](/guias/calibrar-adxl345): procedimento prático para determinação dos coeficientes de offset e sensibilidade.
