# Diretrizes de Segurança MQTT - Módulo IoT

Este documento descreve as configurações necessárias para garantir a integridade, resiliência e isolamento de dados entre Tenants na comunicação via Mosquitto.

## 1. Configuração do Broker (Mosquitto)

Para garantir que os dispositivos (ESP32) se conectem de forma segura e resiliente, o arquivo `mosquitto.conf` deve conter:

```conf
# Persistência para suportar QoS 1 e clean_session=false
persistence true
persistence_location /mosquitto/data/

# Desabilitar acesso anônimo
allow_anonymous false
password_file /mosquitto/config/password_file
acl_file /mosquitto/config/acl_file

# Logs para auditoria
log_dest file /mosquitto/log/mosquitto.log
log_type error
log_type warning
log_type notice
log_type information
```

## 2. Isolamento de Tenants via ACL (Access Control List)

Para impedir a injeção cruzada de dados, cada Gateway deve ter um usuário próprio e permissão de publicação restrita aos seus tópicos.

Exemplo de `acl_file`:

```conf
# Admin do Backend (Pode ler tudo para a Bridge)
user amemiya-backend
topic read sensors/#

# Gateway do Cliente A
user gw_cliente_a
topic write sensors/vibration/telemetry
topic write sensors/acoustic/telemetry

# Gateway do Cliente B
user gw_cliente_b
topic write sensors/vibration/telemetry
```

*Nota: No nível do Laravel, o `ProcessTelemetryJob` valida o `device_id` contra o `tenant_id` no banco de dados, servindo como uma segunda camada de proteção.*

## 3. Configurações do Firmware (ESP32)

Para garantir que nenhum dado seja perdido em caso de instabilidade de rede:

1.  **QoS 1 (At Least Once):** Garante que o Broker receba a mensagem. O dispositivo deve aguardar o `PUBACK`.
2.  **Clean Session = false:** Ao reconectar com o mesmo `client_id`, o Broker manterá as inscrições e mensagens pendentes para aquele dispositivo.
3.  **LWT (Last Will and Testament):** Configurar uma mensagem de "offline" no tópico `sensors/[device_id]/status` para que o sistema saiba quando um gateway caiu.

## 4. Pipeline de Ingestão Resiliente

*   A `MqttBridge` do Laravel deve rodar via **Supervisor** para garantir reinício automático.
*   Em caso de queda do Laravel, o Mosquitto armazenará as mensagens (se configurado com persistência e QoS 1) até que a Bridge volte a ficar online.
