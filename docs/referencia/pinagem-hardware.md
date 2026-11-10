# Pinagem e Interfaces de Hardware — Nó de Borda ESP32-S3

Esta página consolida as tabelas oficiais e definitivas de pinagem, mapeamento de GPIOs do microcontrolador ESP32-S3 DevKitC-1 e especificações elétricas dos conectores industriais na **Carrier Board do Nó Principal (`amemiya_main_node`)** e na **Torre de Sensores Dual (`amemiya_probe_tower_dual`)**.

---

## 1. Mapeamento de GPIOs do Microcontrolador (ESP32-S3 DevKitC-1 — 44 Pinos)

O barramento foi alocado considerando isolamento de ruído RF, controle PWM de iluminação, barramentos digitais de alta velocidade e integridade dos pinos de *boot/strapping* da Espressif.

| Pino / Header | GPIO / Nome | Barramento / Periférico | Padrão Elétrico & Função |
| :--- | :--- | :--- | :--- |
| **J1: 04** | `GPIO4` | Touch Screen XPT2046 | Saída Digital: Chip Select do digitalizador resistivo (`TOUCH_CS`) |
| **J1: 05** | `GPIO5` | DS18B20 (Temperatura) | 1-Wire Open-Drain: Barramento de dados da sonda térmica de mancal (`1W_DQ`, Pull-up $4{,}7\text{ k}\Omega$) |
| **J1: 06** | `GPIO6` | INMP441 (Microfone MEMS) | I2S Serial Data: Entrada serial de áudio digital de 24 bits (`I2S_SD`) |
| **J1: 07** | `GPIO7` | INMP441 (Microfone MEMS) | I2S Continuous Clock: Bit Clock contínuo de amostragem acústica (`I2S_SCK`) |
| **J1: 08** | `GPIO15` | LED Status Verde | Saída Digital ($3{,}3\text{V}$, limitador $330\Omega$): Sinalização de nó saudável / Zona A-B ISO 20816 (`GPIO_LED_GRN`) |
| **J1: 09** | `GPIO16` | LED Status Laranja | Saída Digital ($3{,}3\text{V}$, limitador $330\Omega$): Alerta de manutenção preventiva / Zona C ISO 20816 (`GPIO_LED_ORG`) |
| **J1: 10** | `GPIO17` | LED Status Azul | Saída Digital ($3{,}3\text{V}$, limitador $330\Omega$): Transmissão ativa de telemetria LoRa / Conexão Wi-Fi (`GPIO_LED_BLU`) |
| **J1: 11** | `GPIO18` | Buzzer Piezolétrico Ativo | Saída Digital ($3{,}3\text{V}$, limitador $100\Omega$): Alarme acústico industrial para Zona D ISO 20816 (`GPIO_BUZZER`) |
| **J1: 12** | `GPIO8` | INMP441 (Microfone MEMS) | I2S Word Select: Sincronismo de canal Left/Right (`I2S_WS`) |
| **J1: 15** | `GPIO3` | Touch Screen XPT2046 | Entrada Digital com Interrupção: Notificação de toque ativo no painel (`TOUCH_IRQ`) |
| **J1: 17** | `GPIO9` | Display TFT ILI9341 | Saída Digital: Seleção de Dados / Comando do controlador gráfico (`LCD_DC`) |
| **J1: 18** | `GPIO10` | Display TFT ILI9341 | Saída Digital: Chip Select dedicado do display LCD (`LCD_CS`) |
| **J1: 19** | `GPIO11` | Barramento SPI Gráfico | SPI MOSI: Master Out Slave In compartilhado entre ILI9341 e XPT2046 (`SPI_MOSI`) |
| **J1: 20** | `GPIO12` | Barramento SPI Gráfico | SPI SCK: Clock de alta velocidade até 40 MHz compartilhado (`SPI_SCK`) |
| **J1: 21** | `GPIO13` | Barramento SPI Gráfico | SPI MISO: Master In Slave Out vindo do digitalizador Touch XPT2046 (`SPI_MISO`) |
| **J1: 22** | `GPIO14` | Display TFT ILI9341 | Saída Digital: Reset por hardware do controlador gráfico (`LCD_RST`) |
| **J3: 24** | `GPIO38` | Transceptor LoRa E32 | Saída Digital ($3{,}3\text{V}$ com pull-up $10\text{ k}\Omega$): Modo de operação M0 (`LORA_M0`) |
| **J3: 25** | `GPIO47` | Transceptor LoRa E32 | Entrada Digital: Status de buffer e transmissão em andamento (`LORA_AUX`) |
| **J3: 32** | `GPIO39` | Transceptor LoRa E32 | Saída Digital ($3{,}3\text{V}$ com pull-up $10\text{ k}\Omega$): Modo de operação M1 (`LORA_M1`) |
| **J3: 34** | `GPIO41` | Acelerômetro ADXL345 | I2C Fast-Mode (400 kHz): Linha de dados bidirecional (`I2C_SDA`, Pull-up $4{,}7\text{ k}\Omega$) |
| **J3: 35** | `GPIO42` | Acelerômetro ADXL345 | I2C Fast-Mode (400 kHz): Linha de clock serial (`I2C_SCL`, Pull-up $4{,}7\text{ k}\Omega$) |
| **J3: 36** | `GPIO44` | Transceptor LoRa E32 | UART1 RX: Recepção assíncrona serial a 9600 bps vinda do rádio (`LORA_RX`) |
| **J3: 37** | `GPIO43` | Transceptor LoRa E32 | UART1 TX: Transmissão assíncrona serial a 9600 bps para o rádio (`LORA_TX`) |
| **J3: 38** | `GPIO21` | Power-Gating (+3V3_SWITCHED) | Saída Digital ativa baixa: Aciona o P-MOSFET AO3401A para cortar energia dos sensores no Deep-Sleep (`PWR_GATE_EN`) |
| **J3: 39** | `GPIO2` | Backlight Display TFT | Saída Digital / PWM: Controle de brilho da tela via resistor limitador `R_BL` de $10\Omega$ 1206 (`LCD_BL`) |
| **J3: 40** | `GPIO1` | Fuel Gauge da Bateria | Entrada Analógica ADC1_CH0 (Atenuação 11 dB, faixa $0 - 3{,}1\text{V}$): Divisor simétrico 1:2 ($100\text{ k}\Omega / 100\text{ k}\Omega$) |

---

## 2. Conector da Interface Homem-Máquina Local (`J_TFT` — Header 1x14)

Conector para encaixe direto do **Display TFT LCD 3.2" SPI (240x320 RGB) com Touch Screen Resistivo (ILI9341 + XPT2046)**:

| Pino | Identificação | Conexão na Placa | Função Operacional |
| :---: | :--- | :--- | :--- |
| **1** | `VCC` | Barramento `+3V3` | Alimentação lógica do controlador ILI9341 (desacoplada por 100nF e 10µF) |
| **2** | `GND` | Plano de Terra (`GND`) | Referência comum |
| **3** | `CS` | `LCD_CS` (`GPIO10`) | Chip Select ativo baixo do LCD |
| **4** | `RESET` | `LCD_RST` (`GPIO14`) | Reset por hardware do display |
| **5** | `DC` | `LCD_DC` (`GPIO9`) | Seleção Data/Command (0: Comando, 1: Dados de Pixel) |
| **6** | `SDI (MOSI)` | `SPI_MOSI` (`GPIO11`) | Entrada serial de dados gráficos |
| **7** | `SCK` | `SPI_SCK` (`GPIO12`) | Clock serial do barramento SPI (até 40 MHz) |
| **8** | `LED` | `TFT_BL_A` (via `R_BL` $10\Omega$) | Ânodo da retroiluminação LED (Backlight ~100mA, acionado pelo `GPIO2`) |
| **9** | `SDO (MISO)` | `SPI_MISO` (`GPIO13`) | Saída de dados do LCD (não utilizada na escrita gráfica) |
| **10** | `T_CLK` | `SPI_SCK` (`GPIO12`) | Clock SPI compartilhado para o digitalizador XPT2046 |
| **11** | `T_CS` | `TOUCH_CS` (`GPIO4`) | Chip Select ativo baixo do controlador de toque |
| **12** | `T_DIN` | `SPI_MOSI` (`GPIO11`) | Entrada de comandos de conversão analógico-digital (eixos X/Y) |
| **13** | `T_DO` | `SPI_MISO` (`GPIO13`) | Retorno das coordenadas digitalizadas de toque (12 bits) |
| **14** | `T_IRQ` | `TOUCH_IRQ` (`GPIO3`) | Interrupção ativa baixa quando a caneta ou dedo pressiona a tela |

---

## 3. Conector Industrial da Sonda Externa (`J_PROBE` / GX16-8)

A interligação entre a Carrier Board e a **Torre de Sensores Dual** no mancal é realizada através de chicote blindado com conector metálico roscado de 8 pinos:

| Pino | Sinal | Tensão Nominal | Função / Transdutor Conectado |
| :---: | :--- | :---: | :--- |
| **1** | `+3V3_SWITCHED` | $+3{,}3\text{V CC}$ | Alimentação comutada dos sensores (cortada pelo P-MOSFET em Deep-Sleep) |
| **2** | `GND` | $0\text{V}$ | Referência de terra e malha de blindagem contra EMI de motores |
| **3** | `1W_DQ` | $0 - 3{,}3\text{V}$ | Linha bidirecional 1-Wire do termômetro digital DS18B20 |
| **4** | `I2S_SD` | $0 - 3{,}3\text{V}$ | Linha de dados seriais de áudio do microfone MEMS INMP441 |
| **5** | `I2S_SCK` | $0 - 3{,}3\text{V}$ | Bit Clock contínuo de amostragem acústica do INMP441 |
| **6** | `I2S_WS` | $0 - 3{,}3\text{V}$ | Word Select (sincronismo de canal) do INMP441 |
| **7** | `I2C_SCL` | $0 - 3{,}3\text{V}$ | Clock serial I2C (400 kHz) para o acelerômetro triaxial ADXL345 |
| **8** | `I2C_SDA` | $0 - 3{,}3\text{V}$ | Dados seriais bidirecionais I2C para o acelerômetro triaxial ADXL345 |

---

## 4. Estágio de Alimentação Industrial e Monitoramento de Carga

| Estágio | Dispositivo / Módulo | Especificação Elétrica | Diretrizes no Sistema |
| :--- | :--- | :--- | :--- |
| **Bateria Primária** | 2x Células 18650 Li-Ion (Paralelo) | $3{,}7\text{V}$ nom ($3{,}0 - 4{,}2\text{V}$), $\sim 5200\text{ mAh}$ | Autonomia prolongada e capacidade para picos de emissão RF LoRa (120mA) |
| **BMS / Carregador** | Módulo TP4056 USB-C | Entrada $5\text{V CC}$ / Carga $1{,}0\text{A}$ CC/CV | Proteção contra sobrecarga, subtensão e sobrecorrente na recarga |
| **Chave Geral** | Chave Alavanca Painel (250V 3A) | Polo Positivo da Bateria | Interrupção mecânica física entre bateria e regulador |
| **Regulador Boost** | Módulo MT3608 Step-Up | $V_{\text{in}}: 3{,}0-4{,}2\text{V} \rightarrow V_{\text{out}}: 5{,}00\text{V CC}$ | Elevador de tensão chaveado de alta eficiência ($\sim 93\%$) para barramento $+5\text{V}$ |
| **Conector da Placa** | Conector JST-XH 2P (`J_BAT`) | $+5{,}00\text{V CC}$ / `GND` | Entrada direta no LDO interno do ESP32-S3 e capacitores bulk de $10\mu\text{F}$ |
| **Fuel Gauge** | Conector JST-XH 2P (`J_BAT_SENSE`) | $0 - 4{,}2\text{V CC}$ (Direto da bateria) | Alimentação do divisor $100\text{k}\Omega / 100\text{k}\Omega$ com filtro passa-baixa RC ($100\text{nF}$) conectado ao `GPIO1` |
