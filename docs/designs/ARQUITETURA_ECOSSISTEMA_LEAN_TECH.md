# Especificação Arquitetural do Ecossistema Integrado — Sistema de Metrologia Lean Tech

> **Documento de Arquitetura EGM (System Blueprint)**  
> **Status:** Aprovado após Auditoria EGM (`egm-codebase-details` & `egm-design`)  
> **Revisão:** v37.0  
> **Escopo:** Integração Multi-Repositório (`amemiya`, `amemiya-hardware`, `metrology-sass-front`, `amemiya-app`)  
> **Data:** Novembro / 2026  

---

## 1. Visão Geral e Topologia do Ecossistema

O **Sistema de Metrologia Lean Tech** é uma plataforma ciberfísica distribuída para monitoramento de condições mecânicas, metrologia legal e manutenção preditiva na indústria 4.0. O ecossistema opera de ponta a ponta através de 4 subsistemas especializados:

```mermaid
flowchart TD
    subgraph HW["1. Camada de Borda & Instrumentação (amemiya-hardware)"]
        PROBE["Torre de Sensores Dual\n(Mancal de Teste)\n• ADXL345 (Vibração I2C Fast-Mode)\n• INMP441 (Acústica I2S 24 bits)\n• DS18B20 (Temperatura 1-Wire)"]
        CHICOTE["Chicote Industrial Blindado\n(Conector GX16-8)"]
        CARRIER["Nó Principal (ESP32-S3 DevKitC-1)\n• Power-Gating (AO3401A com Pull-up & RTC Hold)\n• Driver Backlight N-MOSFET (GPIO2 PWM)\n• Fuel Gauge (Divisor 100k/100k - GPIO1)\n• Transceptor LoRa (E32-TTL-100)\n• 2x 18650 Li-Ion + Boost MT3608"]
        IHM["IHM Local de Bancada\n• Display TFT 3.2\" SPI ILI9341 (240x320)\n• Touch Screen Resistivo XPT2046\n• LEDs RGB (Zonas ISO 20816) + Buzzer"]
        
        PROBE --> CHICOTE --> CARRIER
        CARRIER <--> IHM
    end

    subgraph GW["2. Camada de Concentração & Roteamento"]
        LORA_AIR["Enlace RF LoRa 915 MHz\n(Pacote Binário 27 bytes)"]
        GATEWAY["Gateway Industrial ESP32\n(Fixação em Parede)"]
        MQTT_NET["Rede IP Local / Ethernet\n(Protocolo MQTT QoS 0/1)"]
        
        CARRIER --> LORA_AIR --> GATEWAY --> MQTT_NET
    end

    subgraph CORE["3. Backend Core & Inteligência (amemiya)"]
        BROKER["Broker MQTT Mosquitto"]
        BRIDGE["Daemon Ponte MQTT\n(php artisan iot:mqtt-bridge)"]
        REDIS["Buffer & Fila Redis"]
        WORKER["Queue Workers Assíncronos\n(ProcessTelemetryJob)"]
        FASTAPI["Microsserviço de ML (Python FastAPI)\n• Classificação XGBoost (36 Features)\n• Detecção de Falhas em Rolamento"]
        MYSQL[(Banco Relacional MySQL\nMódulo IoT + Metrologia)]
        REVERB["Laravel Reverb (WebSockets)\nCanal: tenant.{slug}.iot\nEventos: .sensor.data / .anomaly.detected"]
        API_SANCTUM["API RESTful v1 (Laravel Sanctum)\nAutenticação Bearer + X-Tenant-ID"]

        MQTT_NET --> BROKER --> BRIDGE --> REDIS --> WORKER
        WORKER <--> FASTAPI
        WORKER --> MYSQL
        WORKER --> REVERB
        MYSQL --> API_SANCTUM
    end

    subgraph CLIENTS["4. Camada de Apresentação e Aplicação"]
        FILAMENT["Painel Administrativo Filament 4\n(amemiya - MLOps & Calibração)"]
        SASS_FRONT["Frontend Web SaaS (Next.js 16 / React 19)\n(metrology-sass-front)\n• Calibrações & Laudos PDF\n• Kanban de Ordens de Serviço\n• AnomalyMonitor via Reverb WebSockets"]
        APP_MOBILE["Aplicativo Mobile Android (Expo / React Native)\n(amemiya-app)\n• IHM Digital de Bolso\n• Polling Reativo TanStack Query (10s a 30s)\n• Push Notifications & Alertas"]

        MYSQL & REVERB --> FILAMENT
        API_SANCTUM & REVERB --> SASS_FRONT
        API_SANCTUM --> APP_MOBILE
    end
```

---

## 2. Contrato de Hardware e Borda (`amemiya-hardware`)

### 2.1 Alocação Definitiva de GPIOs (ESP32-S3 DevKitC-1)
O hardware do Nó Principal (`amemiya_main_node`) foi consolidado na versão **v37.0**, eliminando o tacômetro óptico e o display OLED, e integrando o display gráfico TFT de 3.2":

* **Barramento SPI Gráfico (Display & Touch):**
  * `GPIO11`: `SPI_MOSI` (Master Out Slave In compartilhado entre ILI9341 e XPT2046)
  * `GPIO12`: `SPI_SCK` (Serial Clock até 40 MHz compartilhado)
  * `GPIO13`: `SPI_MISO` (Master In Slave Out vindo do XPT2046)
  * `GPIO10`: `LCD_CS` (Chip Select do controlador gráfico ILI9341)
  * `GPIO9`: `LCD_DC` (Data/Command do ILI9341)
  * `GPIO14`: `LCD_RST` (Hardware Reset do ILI9341)
  * `GPIO2`: `LCD_BL` (Controle PWM de iluminação acionando gate de transistor de comutação N-MOSFET com resistor limitador de dreno `R_BL` de $10\Omega$)
  * `GPIO4`: `TOUCH_CS` (Chip Select do controlador de toque XPT2046)
  * `GPIO3`: `TOUCH_IRQ` (Interrupção ativa baixa de toque)

* **Barramento de Sensores Multimodais (Chicote GX16-8):**
  * `GPIO41`: `I2C_SDA` (Acelerômetro ADXL345, Fast-Mode 400 kHz, Pull-up $4{,}7\text{k}\Omega$)
  * `GPIO42`: `I2C_SCL` (Clock I2C ADXL345, Pull-up $4{,}7\text{k}\Omega$)
  * `GPIO6`: `I2S_SD` (Microfone MEMS INMP441 - Dados Seriais 24 bits)
  * `GPIO7`: `I2S_SCK` (Microfone MEMS INMP441 - Continuous Bit Clock)
  * `GPIO8`: `I2S_WS` (Microfone MEMS INMP441 - Word Select / L-R Sync)
  * `GPIO5`: `1W_DQ` (Termômetro Digital DS18B20, Pull-up $4{,}7\text{k}\Omega$)
  * `GPIO21`: `PWR_GATE_EN` (Chaveamento de energia $+3\text{V}3\text{\_SWITCHED}$ via P-MOSFET AO3401A com pull-up de $100\text{k}\Omega$ ao $+3\text{V}3$ e retenção de estado via `rtc_gpio_hold_en`)

* **Transceptor LoRa E32-TTL-100 (915 MHz):**
  * `GPIO44`: `LORA_RX` (Recepção serial UART @ 9600 bps)
  * `GPIO43`: `LORA_TX` (Transmissão serial UART @ 9600 bps)
  * `GPIO38`: `LORA_M0` (Modo de operação com pull-up $10\text{k}\Omega$)
  * `GPIO39`: `LORA_M1` (Modo de operação com pull-up $10\text{k}\Omega$)
  * `GPIO47`: `LORA_AUX` (Status de buffer livre / transmissão em andamento)

* **Sinalização de Painel & Energia:**
  * `GPIO15`: `GPIO_LED_GRN` (LED Verde - Nó Saudável / Zona A-B ISO 20816)
  * `GPIO16`: `GPIO_LED_ORG` (LED Laranja - Alerta Preventivo / Zona C ISO 20816)
  * `GPIO17`: `GPIO_LED_BLU` (LED Azul - Telemetria LoRa ativa / Conexão)
  * `GPIO18`: `GPIO_BUZZER` (Buzzer Piezolétrico Ativo - Alarme Zona D ISO 20816)
  * `GPIO1`: `VBAT_ADC` (Fuel Gauge analógico via divisor resistivo $100\text{k}\Omega / 100\text{k}\Omega$)

* **Preservação de Strapping Pins (Boot Seguro):**
  * `GPIO0`: Seletor de modo Download/SPI Boot (sem periféricos conectados na placa)
  * `GPIO45`: Seleção de tensão Flash/PSRAM `VDD_SPI` (mantido desimpedido para não forçar 1.8V acidentalmente)
  * `GPIO46`: Strapping de boot ROM (não utilizado para saída)

### 2.2 Invariante de Segurança Elétrica do Backlight
O backlight do display TFT ILI9341 consome entre 80 mA e 120 mA em regime contínuo. Como o limite máximo de corrente por pino GPIO no ESP32-S3 é de 40 mA (com recomendação operacional de até 20 mA), o pino `GPIO2` comanda a base/gate de um estágio comutador (MOSFET canal N tipo 2N7002 / AO3400A ou BJT MMBT3904), com o resistor `R_BL` de $10\Omega$ inserido no laço de carga do barramento $+3\text{V}3$.

---

## 3. Pipeline de Telemetria e Protocolos de Comunicação (`amemiya`)

### 3.1 Tópicos MQTT Padronizados
1. **Publicação de Telemetria do Gateway:**
   - Tópico: `v1/gateways/{gw_id}/nodes/{node_id}/telemetry` (e legado `sensors/+/telemetry`)
   - Formato do Payload:
     ```json
     {
       "device_id": "GW_01",
       "node_id": "NODE_01",
       "timestamp": "2026-11-10T14:30:00Z",
       "vibration_rms": 2.15,
       "temp_celsius": 42.3,
       "battery_voltage": 3.92,
       "mic_rms": 0.045,
       "features": { ... }
     }
     ```

2. **Comando de Feedback de Alarme para a Borda (LEDs e Buzzer):**
   - Tópico: `v1/gateways/{gw_id}/nodes/{node_id}/commands`
   - Formato do Payload Emitido pelo `ProcessTelemetryJob`:
     ```json
     {
       "command": "set_alarm_state",
       "status": "pending_analysis" | "fault_confirmed" | "healthy" | "off",
       "fault_type": "desbalanceamento" | "normal",
       "confidence": 0.92,
       "ttl_seconds": 60,
       "ref_msg": "14:30:00.123"
     }
     ```

### 3.2 Ingestão e Concorrência no Laravel
- O daemon `php artisan iot:mqtt-bridge` despacha o job `ProcessTelemetryJob` para o Redis.
- Em caso de falha de conexão ou resposta indeterminada do microsserviço de ML, o fallback do sistema rebaixa o status para `pending_analysis` (Alerta Amarelo), impedindo o mascaramento inadvertido de falhas mecânicas como `healthy`.

---

## 4. Microsserviço de Machine Learning & MLOps

O microsserviço Python FastAPI (`Modules/IoT/ml_service`) expõe o endpoint síncrono `POST /predict-anomalia`.

### 4.1 Vetor Estrito de 36 Features
A ordem das features no array numpy é idêntica ao treinamento do modelo especialista:
```text
[
  z_rms, z_kurtosis, z_skewness, z_energy_band_1, z_peak_band_1, z_energy_band_2, z_energy_band_3, z_energy_band_4,
  y_rms, y_kurtosis, y_skewness, y_energy_band_1, y_peak_band_1, y_energy_band_2, y_energy_band_3, y_energy_band_4,
  x_rms, x_kurtosis, x_skewness, x_energy_band_1, x_peak_band_1, x_energy_band_2, x_energy_band_3, x_energy_band_4,
  mic_rms, mic_crest_factor, mic_energy_0_500, mic_peak_0_500, mic_energy_500_2000, mic_peak_500_2000,
  mic_energy_2000_5000, mic_peak_2000_5000, mic_energy_5000_10000, mic_peak_5000_10000, mic_energy_above_10000, mic_peak_above_10000
]
```

### 4.2 Avaliação Normativa de Severidade (ISO 20816-3)
- **Zona A:** Nova / Excelente ($< 1{,}4\text{ mm/s}$).
- **Zona B:** Operação irrestrita ($1{,}4 - 2{,}8\text{ mm/s}$).
- **Zona C:** Alerta operacional / Manutenção preventiva ($2{,}8 - 4{,}5\text{ mm/s}$).
- **Zona D:** Parada mandatória do equipamento ($> 4{,}5\text{ mm/s}$).

---

## 5. Contratos de API RESTful v1 e WebSockets

### 5.1 Endpoints de Telemetria (`/api/v1`)
Todos os endpoints exigem os cabeçalhos:
- `Authorization: Bearer <token_sanctum>`
- `X-Tenant-ID: amemiya`
- `Accept: application/json`

| Método | Endpoint | Responsabilidade | Consumidores |
| :---: | :--- | :--- | :--- |
| `GET` | `/api/v1/iot-nodes` | Listagem de nós com status e última telemetria | Expo App / Filament |
| `GET` | `/api/v1/iot-nodes/{id}` | Detalhes do nó, metadados de máquina e bateria | Expo App / Filament |
| `GET` | `/api/v1/iot-history` | Série temporal com filtros de período (`1h`, `6h`, `24h`, `7d`) | Expo App |
| `GET` | `/api/v1/iot-gateways` | Monitoramento de conectividade dos gateways | Expo App / Filament |
| `GET` | `/api/v1/iot-nodes/{id}/spectrum`| Espectro FFT de vibração e bandas de frequência | Filament / Web |

### 5.2 Eventos WebSockets (Laravel Reverb)
- **Host / Porta:** `reverb:8080` (proxy reverso `/app`)
- **Canal Multi-Tenant:** `tenant.{tenantSlug}.iot` (ex.: `tenant.amemiya.iot`)
- **Eventos:**
  - `.sensor.data` (emitido via `SensorDataReceived`): telemetria de vibração, temperatura e bateria em tempo real.
  - `.anomaly.detected` (emitido via `AnomalyDetected`): alerta imediato de transição para Zonas C/D ou falha confirmada pelo XGBoost.
- **Consumidores:** Painel Filament 4 e componente `AnomalyMonitor.tsx` do portal SaaS.

---

## 6. Papel e Estratégia de Cada Interface

| Interface | Tecnologia | Repositório | Padrão de Comunicação | Propósito no Ecossistema |
| :--- | :--- | :--- | :--- | :--- |
| **IHM de Bancada** | TFT 3.2" SPI ILI9341 + Touch XPT2046 | `amemiya-hardware` | Barramento SPI / UART direto no ESP32-S3 | Operador de bancada: Leitura local instantânea, alarmes visuais/sonoros e diagnóstico de partida sem depender de internet. |
| **Painel Web Admin** | Filament 4 (PHP) + React | `amemiya` | Eloquent ORM + Reverb WebSockets | Engenheiro de confiabilidade: Administração de dispositivos, MLOps, calibração de modelos e auditoria de logs. |
| **Portal SaaS Web** | Next.js 16 (React 19) + Tailwind | `metrology-sass-front` | REST API v1 + Reverb WebSockets (`AnomalyMonitor`) | Gestores de manutenção e metrologia: Gestão de calibrações, cálculo de incerteza GUM, laudos em PDF e Kanban de O.S. |
| **App Mobile** | React Native / Expo (Android) | `amemiya-app` | Polling reativo TanStack Query (10s a 30s) + Push Notifications | Técnicos e mecânicos de campo: Terminal móvel de bolso com status das máquinas, leitura de tensão de bateria e alertas. |
