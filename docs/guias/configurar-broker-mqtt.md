# Configuração do Broker MQTT e Pareamento de Nós

Este guia aborda o provisionamento seguro do broker Mosquitto, autenticação via credenciais ACL e pareamento de novos gateways e nós sensores no cluster Lean Tech.

---

## 1. Topologia de Comunicação

O ecossistema utiliza uma topologia em estrela hierárquica:

```
[Nó Amemiya 01 (LoRa)] ---\
[Nó Amemiya 02 (LoRa)] ----> [Gateway ESP32 (LoRa + WiFi)] ---> [Broker Mosquitto (MQTT)] ---> [Laravel Bridge / Redis]
[Nó Amemiya 03 (LoRa)] ---/
```

* **Camada de Campo (Field Layer):** LoRa 915 MHz com payload binário compacto (27 bytes).
* **Camada de Transporte (IP Layer):** MQTT com TLS (porta 8883) ou TCP protegido por ACL (porta 1883 na rede interna Docker).

---

## 2. Estrutura de Tópicos MQTT

Os tópicos seguem o padrão padronizado:

| Tópico | Direção | Finalidade |
| :--- | :--- | :--- |
| `amemiya/telemetry/nodes/{nodeId}` | Gateway → Broker | Pacotes periódicos de telemetria (vibração RMS, RPM, temp, bateria). |
| `amemiya/events/nodes/{nodeId}` | Gateway → Broker | Eventos de borda (disparo de alarme ISO 20816 Zona C/D, reboot). |
| `amemiya/commands/nodes/{nodeId}` | Backend → Nó | Comandos remotos (ajuste de taxa de amostragem, calibração remota). |
| `amemiya/status/gateways/{gwId}` | Gateway → Broker | Heartbeat de saúde do gateway (LWT - Last Will & Testament). |

---

## 3. Provisionamento de Credenciais e ACLs

Para evitar que dispositivos não autorizados publiquem dados corrompidos:

1. Acesse o arquivo de configuração de senhas do Mosquitto em `.docker/mosquitto/config/mosquitto.conf`:
   ```conf
   listener 1883
   allow_anonymous false
   password_file /mosquitto/config/passwords
   acl_file /mosquitto/config/acl
   ```
2. Gere a chave criptografada para o novo gateway:
   ```bash
   docker exec -it amemiya-mqtt mosquitto_passwd -b /mosquitto/config/passwords gateway_01 "ChaveForteAleatoria123"
   ```
3. Defina as permissões no arquivo de ACL (`/mosquitto/config/acl`):
   ```conf
   user gateway_01
   topic write amemiya/telemetry/nodes/+
   topic write amemiya/events/nodes/+
   topic write amemiya/status/gateways/gateway_01
   topic read amemiya/commands/nodes/+
   ```

---

## 4. Inicialização do Bridge de Escuta no Laravel

O backend escuta continuamente o broker através do comando dedicado:

```bash
docker exec -d amemiya-app php artisan iot:mqtt-bridge
```

Este comando:
1. Conecta-se ao Mosquitto como assinante autorizado.
2. Desserializa o JSON de telemetria recebido.
3. Despacha o processamento para a fila assíncrona do Redis (`ProcessTelemetryJob`), garantindo processamento em menos de $15\text{ ms}$ sem bloquear o gateway de campo.
