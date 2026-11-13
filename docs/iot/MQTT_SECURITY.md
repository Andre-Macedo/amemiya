# Diretrizes de Segurança MQTT - Módulo IoT

Este documento descreve as configurações necessárias para garantir a integridade, resiliência e isolamento de dados entre Tenants na comunicação via Mosquitto.

## 1. Configuração do Broker (Mosquitto)

> **Estado atual:** `allow_anonymous false`, `password_file` e `acl_file` já estão ativos em `.docker/mosquitto/config/mosquitto.conf` (dev) e `.docker/mosquitto/config/mosquitto.prod.conf` (produção com listener TLS `8883` + remoção da porta pública `1883` em `docker-compose.prod.yml`). Para provisionar certificados MQTTS em produção, execute `./.docker/mosquitto/generate-certs.sh <dominio>` e cadastre `backend_service` no `password_file`.

Para garantir que os dispositivos (ESP32) se conectem de forma segura e isolada, o arquivo `mosquitto.conf` alvo deve conter:

```conf
# Persistência para suportar QoS 1 e clean_session=false
persistence true
persistence_location /mosquitto/data/

# Desabilitar acesso anônimo e isolar tópicos
allow_anonymous false
password_file /mosquitto/config/password_file
acl_file /mosquitto/config/acl_file

# Logs para auditoria
log_dest stdout
```

## 2. Isolamento de Tenants via ACL (Access Control List)

Para impedir a injeção cruzada de dados entre gateways, cada Gateway deve ter um usuário próprio e permissão restrita ao seu prefixo de tópico (`v1/gateways/<device_id>/nodes/+`).

Exemplo de `acl_file`:

```conf
# Admin do Backend (Bridge Laravel lê telemetria e publica comandos)
user amemiya-backend
topic read v1/gateways/+/nodes/+/telemetry
topic read sensors/+/telemetry
topic write v1/gateways/+/nodes/+/commands

# Gateway do Cliente A
user gw_cliente_a
topic write v1/gateways/GW_CLIENTE_A/nodes/+/telemetry
topic read v1/gateways/GW_CLIENTE_A/nodes/+/commands

# Gateway do Cliente B
user gw_cliente_b
topic write v1/gateways/GW_CLIENTE_B/nodes/+/telemetry
topic read v1/gateways/GW_CLIENTE_B/nodes/+/commands
```

*Nota: No nível do Laravel, o `ProcessTelemetryJob` valida o `device_id` contra o `tenant_id` no banco de dados, servindo como uma segunda camada de proteção.*

## 3. Configurações do Firmware (ESP32)

> **Estado atual do código:** o `MqttBridge` e o `MqttCommandPublisher` utilizam **QoS `1`** (`config('iot.mqtt_qos', 1)`) nos tópicos `v1/gateways/+/nodes/+/...` e `last_seen_at` + watchdog (`iot:mark-offline-devices`). A ativação de `clean_session = false` e `LWT` no firmware integra os próximos passos do ESP32.

Para garantir confiabilidade em instabilidade de rede:

1.  **QoS 1 (At Least Once):** Garante entrega ao Broker (`PUBACK`).
2.  **Clean Session = false:** Mantém inscrições e mensagens pendentes de retorno após reconexão.
3.  **LWT (Last Will and Testament):** Publicar status `"offline"` em `v1/gateways/[device_id]/status` em queda abrupta, complementando o *watchdog* de `last_seen_at` no backend.

## 4. Pipeline de Ingestão Resiliente

*   A `MqttBridge` do Laravel deve rodar via **Supervisor** para garantir reinício automático.
*   Em caso de queda do Laravel, o Mosquitto armazenará as mensagens (se configurado com persistência e QoS 1) até que a Bridge volte a ficar online.
