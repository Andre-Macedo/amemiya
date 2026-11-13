# Module IoT (Telemetria Industrial & Monitoramento Preditivo)

## Overview
Módulo de gestão de hardware IoT, telemetria industrial, análise de severidade vibracional (ISO 20816-3), cinemática de rolamentos e diagnóstico preditivo. Responsável por escutar os nós sensores de bancada via ponte MQTT Mosquitto, processar fluxos de alta frequência de forma assíncrona com filas Redis, invocar o microserviço de ML e transmitir eventos em tempo real via WebSockets (Laravel Reverb) para as interfaces Web e Mobile.

## Interface
- **Comandos de Console**:
  - `MqttBridge.php` (`iot:mqtt-bridge`): Worker CLI contínuo que conecta ao broker Mosquitto e despacha `ProcessTelemetryJob` para o Redis.
  - `MarkOfflineDevices.php` (`iot:mark-offline-devices`): Watchdog agendado que marca gateways e nós sem telemetria recente (`last_seen_at`) como `offline`.
  - `PruneSensorData.php` (`iot:prune-sensor-data`): Retenção e expurgo de séries temporais antigas.
- **Jobs Assíncronos & Listeners**:
  - `ProcessTelemetryJob.php`: Processa métricas físicas, normaliza tensão de bateria, calcula RMS, avalia severidade ISO 20816, invoca o ML Service e emite eventos broadcast.
  - `PersistSensorData.php`: Listener assíncrono para persistência de registros brutos em `iot_sensor_data` e `iot_device_logs`.
- **Eventos de Tempo Real (WebSockets)**:
  - `SensorDataReceived.php`: Transmite telemetria atualizada no canal `tenant.{tenantSlug}.iot` (alias `.sensor.data`).
  - `AnomalyDetected.php`: Emite alerta de anomalia no canal `tenant.{tenantSlug}.iot` (alias `.anomaly.detected`).
- **Serviços de Domínio**:
  - `ISO20816SeverityService.php`: Classificação em Zonas A, B, C e D para motores e máquinas rotativas.
  - `BearingKinematicsService.php`: Cálculo das frequências de defeito de pista externa (BPFO), pista interna (BPFI), corpos rolantes (BSF) e gaiola (FTF).
  - `MachineLearningService.php`: Cliente HTTP com timeout e retry resiliente para o microserviço ML.
  - `PrescriptiveDiagnosticsService.php`: Motor prescritivo com recomendações de manutenção metrológica.
- **APIs RESTful (`/api/v1/iot`)**:
  - `IoTNodeApiController.php`: CRUD de nós e telemetria atual.
  - `IoTGatewayApiController.php`: Gestão de gateways industriais.
  - `IoTHistoryApiController.php`: Consulta agregada de séries temporais de vibração, temperatura, bateria e ruído acústico.
- **Subdiretórios**:
  - [ML Service](./ml_service/EGM.md): Microserviço especialista XGBoost para detecção de anomalias.

## Invariants
- **Multi-Tenancy Scoping**: Todos os eventos WebSocket e consultas de banco de dados devem ser rigidamente vinculados ao slug da organização (`tenant.{tenantSlug}.iot`).
- **Fallback de Anomalia Seguro**: Em caso de falha de comunicação ou timeout no `MachineLearningService`, o status da análise deve ser gravado obrigatoriamente como `pending_analysis` (alerta de atenção), sendo expressamente proibido o fallback para `healthy`.
- **Depreciação de Tacômetro no ESP32 (`BG1` apenas local):** Leituras sem tacômetro físico não devem ser rejeitadas; o RPM é resolvido dinamicamente via `BearingKinematicsService::resolveSpectralRpm()` (priorizando `rpm_estimated` do firmware, estimação harmônica `1X/2X/3X` em `peaks`/`fft_x` e fallback para `nominal_rpm`).
- **Tratamento de Tensão de Bateria**: Leituras de `battery_voltage` devem ser normalizadas e armazenadas na faixa operacional de Li-Ion (3.0V a 4.2V) com cálculo de porcentagem correspondente para o nó industrial.
- **PSR-12 e Tipagem**: Todos os métodos em Controllers, Services e Jobs devem declarar explicitamente tipos de retorno (`void`, `bool`, etc.) e propriedades tipadas.

## Side Effects
- Conexão TCP contínua e consumo de mensagens no Broker MQTT Mosquitto.
- Inserção de batches de telemetria nas tabelas `iot_device_logs` e `iot_sensor_data`.
- Despacho de jobs na fila Redis `telemetry-processing`.
- Chamadas HTTP síncronas para `http://ml-service:8000/predict-anomalia`.
- Emissão de pacotes WebSockets autenticados via Laravel Reverb para clientes conectados.

## Verification
- Run: `docker-compose exec app php artisan test --filter=IoT`
