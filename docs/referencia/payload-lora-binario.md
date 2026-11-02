# Payload LoRa Binário (27 bytes)

Para maximizar a eficiência energética e minimizar o tempo no ar (*Time on Air - ToA*) na modulação LoRa a 915 MHz, o nó sensor industrial transmite uma estrutura binária compacta em C em vez de JSON legível.

---

## 1. Definição da Estrutura em Linguagem C

A struct é compactada com alinhamento de 1 byte via diretiva `__attribute__((packed))`:

```c
typedef struct __attribute__((packed)) {
    uint8_t  node_id;           // Byte 0: Identificador único do nó (1 a 255)
    uint32_t sequence_id;       // Bytes 1..4: Contador monotônico de pacotes (anti-replay)
    uint16_t rpm;               // Bytes 5..6: Rotação medida pelo sensor Hall (0 a 65535 RPM)
    int16_t  temp_celsius_x100; // Bytes 7..8: Temperatura mancal DS18B20 em °C * 100 (-4000 a +12500)
    uint16_t vrms_x_x100;       // Bytes 9..10: Velocidade RMS Eixo X em mm/s * 100
    uint16_t vrms_y_x100;       // Bytes 11..12: Velocidade RMS Eixo Y em mm/s * 100
    uint16_t vrms_z_x100;       // Bytes 13..14: Velocidade RMS Eixo Z em mm/s * 100
    uint16_t peak_accel_x100;   // Bytes 15..16: Pico de aceleração em g * 100
    uint16_t crest_factor_x100; // Bytes 17..18: Fator de crista de vibração * 100
    uint8_t  iso_zone;          // Byte 19: Classificação ISO 20816 (1: A, 2: B, 3: C, 4: D)
    uint8_t  edge_alarm;        // Byte 20: Flag de anomalia inferida pela IA de borda (0 ou 1)
    uint16_t vbat_mv;           // Bytes 21..22: Tensão da bateria em milivolts (ex: 3700 mV)
    uint16_t reserved;          // Bytes 23..24: Reservado para expansão futura
    uint16_t crc16;             // Bytes 25..26: Checksum CRC-16-CCITT (Polinômio 0x1021)
} AmemiyaTelemetryPayload_t;
```

---

## 2. Mapa de Memória e Escalas de Conversão

| Byte Offset | Campo | Tipo | Escala / Multiplicador | Faixa Representável | Unidade Real |
| :---: | :--- | :---: | :---: | :---: | :---: |
| `0` | `node_id` | `uint8_t` | $1$ | $1$ a $255$ | ID do Dispositivo |
| `1 - 4` | `sequence_id` | `uint32_t` | $1$ | $0$ a $4.294.967.295$ | Contador |
| `5 - 6` | `rpm` | `uint16_t` | $1$ | $0$ a $65.535$ | RPM |
| `7 - 8` | `temp_celsius_x100` | `int16_t` | $/ 100{,}0$ | $-40{,}00$ a $+125{,}00$ | $^\circ\text{C}$ |
| `9 - 10` | `vrms_x_x100` | `uint16_t` | $/ 100{,}0$ | $0{,}00$ a $655{,}35$ | $\text{mm/s RMS}$ |
| `11 - 12` | `vrms_y_x100` | `uint16_t` | $/ 100{,}0$ | $0{,}00$ a $655{,}35$ | $\text{mm/s RMS}$ |
| `13 - 14` | `vrms_z_x100` | `uint16_t` | $/ 100{,}0$ | $0{,}00$ a $655{,}35$ | $\text{mm/s RMS}$ |
| `15 - 16` | `peak_accel_x100` | `uint16_t` | $/ 100{,}0$ | $0{,}00$ a $655{,}35$ | $g$ ($9{,}8\text{ m/s}^2$) |
| `17 - 18` | `crest_factor_x100` | `uint16_t` | $/ 100{,}0$ | $0{,}00$ a $655{,}35$ | Adimensional |
| `19` | `iso_zone` | `uint8_t` | $1$ | $1$ (A), $2$ (B), $3$ (C), $4$ (D) | Categoria ISO |
| `20` | `edge_alarm` | `uint8_t` | $1$ | $0$ (Normal), $1$ (Alarme Crítico) | Booleano |
| `21 - 22` | `vbat_mv` | `uint16_t` | $/ 1000{,}0$ | $0{,}000$ a $65{,}535$ | Volts ($\text{V}$) |
| `23 - 24` | `reserved` | `uint16_t` | $1$ | $0$ | — |
| `25 - 26` | `crc16` | `uint16_t` | $1$ | $0\text{x}0000$ a $0\text{xFFFF}$ | Código Verificador |

---

## 3. Benefício Energético (Payload Binário vs JSON)

* **Formato JSON textual:** 230 bytes médios $\rightarrow$ *Time on Air*: ~180 ms no SF9.
* **Formato Binário Amemiya:** 27 bytes fixos $\rightarrow$ *Time on Air*: **~38 ms** no SF9.
* **Economia de energia por transmissão:** $\approx 78{,}8\%$, permitindo vida útil superior a **24 meses** em operação com bateria LiFePO4 de 2000 mAh transmitindo a cada 10 minutos.
