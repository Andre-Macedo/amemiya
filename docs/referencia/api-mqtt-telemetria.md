# Tópicos MQTT e Payloads de Telemetria

Esta página documenta a especificação das mensagens MQTT trocadas entre os gateways de campo e o broker Mosquitto, e apresenta os contratos dos dados que alimentam os modelos analíticos.

---

## 1. Tópico de Telemetria

* **Padrão:** `amemiya/telemetry/nodes/{nodeId}`
* **QoS:** `1` (At least once)
* **Retain:** `false`

### Exemplo de Payload JSON (Gateway → Broker)

```json
{
  "node_id": "NODE_01",
  "gateway_id": "GW_AMEMIYA_LAB",
  "sequence_id": 10428,
  "rpm": 1785,
  "temperature_celsius": 24.8,
  "vibration": {
    "vrms_x": 1.25,
    "vrms_y": 0.94,
    "vrms_z": 1.82,
    "peak_accel_g": 0.82,
    "crest_factor": 3.14,
    "iso_zone": "A"
  },
  "diagnostics": {
    "edge_alarm": false,
    "battery_mv": 3720,
    "rssi_dbm": -68,
    "snr_db": 9.5
  },
  "timestamp": 1761864000
}
```

---

## 2. Tópico de Eventos de Falha e Anomalia

* **Padrão:** `amemiya/events/nodes/{nodeId}`
* **QoS:** `1`

Disparado imediatamente quando a inferência TinyML na borda detecta transição para **Zona C (Alerta)** ou **Zona D (Perigo)** segundo a norma ISO 20816-3:

```json
{
  "event_type": "ISO_ZONE_TRANSITION",
  "node_id": "NODE_01",
  "severity": "CRITICAL",
  "previous_zone": "A",
  "current_zone": "D",
  "trigger_value": 4.85,
  "threshold": 4.50,
  "action_required": "INSPECAO_IMEDIATA_DESBALANCEAMENTO",
  "timestamp": 1761864015
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
