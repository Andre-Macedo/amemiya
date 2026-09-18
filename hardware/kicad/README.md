# Amemiya IoT — Projetos de PCB KiCad (v2.0)

Este repositório contém os projetos de placas de circuito impresso (PCB) desenvolvidos para o sistema de manutenção preditiva e IoT industrial **Amemiya**.

---

## Estrutura dos Projetos KiCad

Os projetos foram modelados de forma **100% modular**, onde os módulos de sensores, microcontrolador e rádio são encaixados na PCB principal através de **soquetes de barra de pinos fêmea (headers 2.54 mm)**, permitindo substituição rápida de qualquer componente sem necessidade de solda direta nos chips.

```
hardware/kicad/
├── amemiya_probe_sensor/            # PCB 1: Sonda de Mancal Ultra-Compacta (25x25 mm)
│   ├── amemiya_probe_sensor.kicad_sch  # Esquemático elétrico KiCad
│   └── amemiya_probe_sensor.kicad_pcb  # Layout da placa PCB
├── amemiya_main_node/               # PCB 2: Nó de Borda Principal ESP32-S3 (70x50 mm)
│   ├── amemiya_main_node.kicad_sch     # Esquemático elétrico KiCad
│   └── amemiya_main_node.kicad_pcb     # Layout da placa PCB
├── amemiya_probe_tower/             # PCB 3: Sonda Cartucho Torre Cilíndrica (24x48 mm)
│   └── amemiya_probe_tower.kicad_pcb   # Layout da placa PCB
├── scripts/                         # Scripts Python de geração procedural e roteamento A*
│   ├── generate_zero_drc_pcbs_v35.py   # Gerador master v35 (PTH, 0 DRC)
│   ├── astar_router_v13.py             # Roteador A* Grid Maze v13
│   └── generate_probe_tower_v5.py      # Gerador da torre v5
└── README.md                         # Este manual
```

---

## 1. PCB 1 — Sonda de Mancal (Probe Sensor Board)

- **Dimensões:** $25.0 \text{ mm} \times 25.0 \text{ mm}$ (formato compacto para mancal de $2.5 \text{ cm}$).
- **Conector de Saída:** Bornes / Pads para cabo blindado 6 vias conectado ao plugue **GX12 6 Pinos**.

### Soquetes de Encaixe dos Sensores na Sonda:

| Ref | Componente / Módulo | Tipo de Soquete | Pinos Utilizados |
|---|---|---|---|
| **J_ACCEL** | Acelerômetro 3D (MPU-6050 / MPU-9250 / ADXL345) | Header Fêmea 1x8 (2.54 mm) | VCC, GND, SCL, SDA |
| **J_MIC** | Microfone Digital I2S (INMP441) | Header Fêmea 1x6 (2.54 mm) | VCC, GND, SD, WS, SCK, L/R (GND) |
| **J_TEMP** | Termômetro (DS18B20 TO-92) | Header Fêmea 1x3 (2.54 mm) | VCC (Pino 3), DATA (Pino 2), GND (Pino 1) |
| **R1** | Resistor Pull-up DS18B20 | Resistor TH / SMD 0805 | $4.7 \text{ k}\Omega$ entre VCC e DATA |
| **C1** | Capacitor Desacoplamento | Capacitor SMD 0805 | $100 \text{ nF}$ entre VCC e GND |

### Mapeamento de Vias do Cabo Único (Conector GX12 6 Vias):

| Pino GX12 | Sinal | Descrição |
|---|---|---|
| **Pino 1** | **VCC (+3.3V)** | Alimentação principal da sonda |
| **Pino 2** | **GND (0V)** | Terra comum |
| **Pino 3** | **I2C_SDA** | Dados I2C do Acelerômetro |
| **Pino 4** | **I2C_SCL** | Clock I2C do Acelerômetro |
| **Pino 5** | **TEMP_DATA** | Sinal 1-Wire do DS18B20 |
| **Pino 6** | **MIC_SD** | Dados I2S do Microfone INMP441 |

---

## 2. PCB 2 — Nó Principal de Borda (Main Edge Node ESP32-S3)

- **Dimensões:** $70.0 \text{ mm} \times 50.0 \text{ mm}$ (Encaixa em caixas herméticas IP65 padrão Patola).
- **Alimentação:** Borne / Conector JST para bateria Lítio 18650 / Módulo BMS TP4056.

### Soquetes de Encaixe dos Módulos na Placa Principal:

| Ref | Componente / Módulo | Tipo de Soquete | Pinos / GPIOs ESP32-S3 |
|---|---|---|---|
| **U_ESP32** | Microcontrolador **ESP32-S3 DevKit** | 2x Headers Fêmea 1x22 (2.54 mm) | Módulo soquetado central |
| **U_LORA** | Módulo Rádio LoRa (E220-900T22D / Ra-02 / SX1276) | Header Fêmea 1x7 ou 1x16 | SPI / UART (TX: GPIO 43, RX: GPIO 44) |
| **U_OLED** | Display OLED 0.96" I2C (128x64) | Header Fêmea 1x4 (2.54 mm) | VCC, GND, SDA (GPIO 8), SCL (GPIO 9) |
| **J_PROBE** | Entrada da Sonda de Mancal | Conector GX12-6 de Painel / Header 1x6 | VCC, GND, SDA (GPIO 8), SCL (GPIO 9), Temp (GPIO 4), MicSD (GPIO 5) |
| **J_TACHO** | Entrada do Tacômetro Hall (KY-003) | Header Fêmea 1x3 (2.54 mm) | VCC, GND, Signal (GPIO 6 com Interrupção) |
| **J_AMP** | Entrada do Alicate Amperométrico (SCT-013) | Jack P2 3.5 mm de Painel | ADC_IN (GPIO 1), GND + Burden Resistor $33\Omega$ |
| **J_SIGNAL**| Saída Sinaleira RGB + Buzzer | Header Macho 1x5 (2.54 mm) | VCC, Red (GPIO 10), Green (GPIO 11), Blue (GPIO 12), Buzzer (GPIO 13) |
| **J_BAT** | Entrada da Bateria 3.7V / TP4056 | Conector JST-PH 2 Pinos | VCC_BAT, GND |

---

## 3. Instruções para Abertura no KiCad

1. Instale o **KiCad 7.0 ou 8.0** ([kicad.org](https://www.kicad.org/)).
2. Abra o KiCad e navegue até a pasta `hardware/kicad/amemiya_probe_sensor/` ou `hardware/kicad/amemiya_main_node/`.
3. Dê um duplo clique no arquivo `.kicad_sch` para abrir o editor de esquemáticos (**Eeschema**) ou no arquivo `.kicad_pcb` para abrir o layout de placa (**Pcbnew**).
4. Para gerar arquivos de fabricação (Gerber e NC Drill):
   - No Pcbnew, vá em **Arquivo $\rightarrow$ Plotter (Geração de Gerber)**.
   - Selecione a camada superior (`F.Cu`), inferior (`B.Cu`), máscara de solda (`F.Mask`, `B.Mask`), serigrafia (`F.Silkscreen`) e borda da placa (`Edge.Cuts`).
   - Clique em **Plotar** e depois em **Gerar Arquivos de Perfuração (Drill Files)**.
