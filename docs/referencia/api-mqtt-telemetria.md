# Tópicos MQTT e Payloads de Telemetria

Esta página documenta a especificação das mensagens MQTT trocadas entre os gateways de campo e o broker Mosquitto, e apresenta os contratos dos dados que alimentam os modelos analíticos.

---

## 1. Tópico de Telemetria

* **Padrão oficial:** `v1/gateways/{device_id}/nodes/{node_id}/telemetry` *(com suporte legado a `sensors/+/telemetry` no `MqttBridge`)*
* **QoS:** `1` (`config('iot.mqtt_qos', 1)`) com `acl_file` por papel (`backend_service` vs `gateway_user`) e listener TLS `8883` em produção (`mosquitto.prod.conf`, sem exposição externa da porta `1883`)
* **Retain:** `false`

### Exemplo de Payload JSON (Gateway → Broker → `ProcessTelemetryJob`)

```json
{
  "device_id": "GW_AMEMIYA_LAB",
  "node_id": "NODE_01",
  "sequence_id": 10428,
  "rpm_estimated": 1750,
  "rpm_source": "accelerometer_firmware",
  "temp_celsius": 24.8,
  "rms_global": 0.38,
  "velocity_rms": 1.82,
  "battery_voltage": 3.72,
  "ml_status": "saudavel",
  "confianca": 0.94,
  "peaks": [
    { "frequency_hz": 29.17, "amplitude": 0.85 }
  ],
  "features": {
    "v_rms": 1.82,
    "x_rms": 1.25,
    "y_rms": 0.94,
    "z_rms": 1.82
  },
  "timestamp": 1761864000
}
```

---

## 2. Tópico de Feedback / Comandos de Sinalização

* **Padrão:** `v1/gateways/{device_id}/nodes/{node_id}/commands`
* **Uso estrito (`NR-12 12.4.12`):** apenas sinalização visual/sonora local (`LEDs` e `Buzzer` do nó/painel), **nunca** acionamento remoto de contator de força.

Emitido pelo `ProcessTelemetryJob::sendMqttCommand()` após avaliação da borda/nuvem e severidade ISO 20816-3:

```json
{
  "command": "set_alarm_state",
  "status": "fault_confirmed",
  "fault_type": "desbalanceamento",
  "confidence": 0.92,
  "ttl_seconds": 60,
  "ref_msg": "14:30:00.123"
}
```

---

## 3. Exploração da API REST (Dedoc Scramble)

Para integrar sistemas externos (ERP, CMMS, dashboards terceiros ou aplicativos móveis), consulte a documentação interativa oficial gerada via OpenAPI 3.1:

::: tip Console Interativo Stoplight Elements
Acesse o console interativo com documentação de todos os endpoints REST de Metrologia, IoT e Sistema:
* **Interface Web:** [http://localhost:8000/docs/api](http://localhost:8000/docs/api)
* **Contrato Bruto OpenAPI 3.1 JSON:** [http://localhost:8000/docs/api.json](http://localhost:8000/docs/api.json)
:::
