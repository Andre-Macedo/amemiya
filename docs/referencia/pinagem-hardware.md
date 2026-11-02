# Pinagem e Interfaces de Hardware

Esta página consolida as tabelas completas de pinagem, mapeamento de GPIOs do ESP32-S3 e pinout dos conectores industriais na Carrier Board e na Torre de Sensores Dual.

---

## 1. Mapeamento de GPIOs do Microcontrolador (ESP32-S3 WROOM-1)

| GPIO | Função / Sinal | Barramento / Periférico | Características Elétricas |
| :---: | :--- | :--- | :--- |
| **GPIO 4** | `LORA_M0` | Módulo LoRa E32 | Saída digital $3{,}3\text{V}$, controle de modo operacional |
| **GPIO 5** | `LORA_M1` | Módulo LoRa E32 | Saída digital $3{,}3\text{V}$, controle de modo operacional |
| **GPIO 6** | `LORA_AUX` | Módulo LoRa E32 | Entrada digital, status de buffer / transmissão ativa |
| **GPIO 7** | `PWR_SW_EN` | Power-Gating (+3V3_SW) | Saída digital ativa alta (gate do MOSFET canal P / chave de carga) |
| **GPIO 8** | `I2C_SDA` | ADXL345 / OLED | Linha de dados bidirecional I2C (Pull-up $4{,}7\text{ k}\Omega$) |
| **GPIO 9** | `I2C_SCL` | ADXL345 / OLED | Linha de clock I2C a 400 kHz (Pull-up $4{,}7\text{ k}\Omega$) |
| **GPIO 10** | `TACHO_PULSE` | Sensor Hall RPM | Entrada com interrupção externa por borda de descida |
| **GPIO 14** | `1W_DQ` | DS18B20 Temperatura | Barramento 1-Wire Dallas bidirecional (Pull-up $4{,}7\text{ k}\Omega$) |
| **GPIO 15** | `BUZZER_PWM` | Buzzer Piezolétrico | Saída PWM para sinalização acústica de falha ($2{,}4\text{ kHz}$) |
| **GPIO 16** | `LED_NORMAL` | LED Verde | Sinalização de operação normal (Zona A/B - ISO 20816) |
| **GPIO 17** | `LED_WARN` | LED Amarelo | Sinalização de alerta preventivo (Zona C - ISO 20816) |
| **GPIO 18** | `LED_ALARM` | LED Vermelho | Sinalização de parada / falha crítica (Zona D - ISO 20816) |
| **GPIO 19** | `LORA_RXD` | UART1 LoRa TX | Recepção serial UART a 9600 bps |
| **GPIO 20** | `LORA_TXD` | UART1 LoRa RX | Transmissão serial UART a 9600 bps |
| **GPIO 1** | `VBAT_ADC` | Divisor Resistivo Bateria | Entrada analógica ADC1 Ch0 (Atenuação 11 dB, 0 a $3{,}1\text{V}$) |

---

## 2. Conector da Sonda Externa (`J_PROBE` / GX16-5)

A conexão entre a Carrier Board e a Torre de Sensores Dual é realizada através de um conector blindado industrial:

| Pino | Nome do Sinal | Tensão Nominal | Descrição |
| :---: | :--- | :---: | :--- |
| **1** | `+3V3_SW` | $+3{,}3\text{V CC}$ | Alimentação chaveada dos sensores (desligada em Deep-Sleep) |
| **2** | `GND` | $0\text{V}$ | Referência de terra e blindagem do cabo |
| **3** | `I2C_SDA` | $0 - 3{,}3\text{V}$ | Linha de dados I2C para o acelerômetro ADXL345 |
| **4** | `I2C_SCL` | $0 - 3{,}3\text{V}$ | Linha de clock I2C (400 kHz) |
| **5** | `1W_DQ` | $0 - 3{,}3\text{V}$ | Linha de dados 1-Wire para o termômetro digital DS18B20 |

---

## 3. Conector de Alimentação e Barramento de Potência (`J_POWER`)

| Pino | Identificação | Especificação |
| :---: | :--- | :--- |
| **Pin 1** | `VIN` | $+9\text{V}$ a $+24\text{V CC}$ (Alimentação externa com proteção contra inversão de polaridade via diodo Schottky) |
| **Pin 2** | `GND_PWR` | Terra de potência isolado de transitórios |
| **Barramento +5V** | `+5V_DCDC` | Tensão regulada pelo conversor chaveado (capacidade: até $2\text{A}$) |
| **Barramento +3V3** | `+3V3_LDO` | Tensão ultra-estável filtrada por indutor de ferrite para o ESP32 |
