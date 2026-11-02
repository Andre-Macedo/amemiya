# Primeira Conexão na Bancada ESP32

Este tutorial demonstra o procedimento prático para ligar o nó sensor industrial ESP32-S3 (Carrier Board) na bancada de ensaio, conectar a torre de sensores externa (ADXL345 + DS18B20) e verificar o envio do primeiro pacote de telemetria binária via LoRa 915 MHz.

---

## 1. Diagrama de Conexão Física

A placa Carrier Board conecta-se à torre externa através do conector industrial **J_PROBE** (ou chicote com conector blindado GX16):

```
+-----------------------------------------------------------+
|                   CARRIER BOARD (ESP32-S3)                |
|                                                           |
|  [J_PROBE]                                                |
|    Pin 1: +3V3_SW  ---------------------> VCC ADXL345     |
|                                     `---> VCC DS18B20     |
|    Pin 2: GND      ---------------------> GND (Comum)     |
|    Pin 3: SDA (GPIO 8)  ----------------> SDA ADXL345     |
|    Pin 4: SCL (GPIO 9)  ----------------> SCL ADXL345     |
|    Pin 5: 1W_DQ (GPIO 14) --------------> Data DS18B20    |
+-----------------------------------------------------------+
```

::: tip Resistores de Pull-up
Os resistores de pull-up de $4{,}7\text{ k}\Omega$ para as linhas I2C (`SDA`/`SCL`) e 1-Wire (`1W_DQ`) já estão montados na Carrier Board ou na placa da torre de sensores, dispensando componentes externos na fiação.
:::

---

## 2. Ligação e Energização da Bancada

1. Conecte a fonte de alimentação regulada de $12\text{V CC}$ no conector de força principal da bancada.
2. Certifique-se de que a chave cogumelo de emergência esteja destravada (girar no sentido horário).
3. Acione o disjuntor bipolar ou chave geral no painel frontal:
   - O LED verde de controle no painel se acenderá.
   - O conversor chaveado Step-Down fornecerá $5\text{V}$ estáveis para a Carrier Board e o módulo LoRa E32.
   - O regulador LDO interno derivará $3{,}3\text{V}$ com filtro LC para o ESP32-S3 e barramentos de sinal.

---

## 3. Verificação Inicial no Terminal Serial

Conecte a porta USB do ESP32-S3 ao seu computador e abra um monitor serial configurado a **115200 bps**:

```
[BOOT] ESP32-S3 Core 0 initialized. Freq: 240 MHz.
[POWER] Power-gating switch (+3V3_SW) ON.
[I2C] Scanning I2C bus at 400 kHz...
[I2C] Found ADXL345 at address 0x53.
[ADXL345] Self-test passed. Data Rate: 3200 Hz. Range: ±16g.
[1-WIRE] Scanning DS18B20...
[1-WIRE] Sensor detected. ROM: 28 FF 64 1E 8A 16 03 5E. Temperature: 24.5 °C.
[LORA] E32-915T20D initialized. Channel: 18 (915 MHz). Addr: 0x0001.
[MEASURE] Sampling 1024 points on X/Y/Z at 3200 Hz... DONE.
[DSP] Window: Hann. Computing FFT and RMS velocity (ISO 20816)...
      RMS Velocity: 1.25 mm/s (Zona A: Boa)
      Peak Accel: 0.82 g
      RPM (Hall): 1785 RPM
[TX] Packing binary telemetry struct (27 bytes)...
[TX] LoRa transmission completed in 42 ms.
[POWER] Power-gating OFF. Entering Deep-Sleep for 60s...
```

---

## 4. Visualização no Painel Web (Reverb WebSockets)

Ao transmitir o pacote:
1. O Gateway receptor capta o sinal LoRa e repassa para o broker Mosquitto (`amemiya/telemetry/nodes/NODE_01`).
2. O bridge Laravel consome o evento e dispara o Job assíncrono `ProcessTelemetryJob`.
3. Abra o navegador em [http://localhost:8000/admin](http://localhost:8000/admin) e acesse o menu **Telemetria IoT**:
   - O card do nó exibirá a rotação atual (~1785 RPM), vibração RMS ($1{,}25\text{ mm/s}$) e a faixa verde (Zona A - ISO 20816-3).
   - O indicador de temperatura exibirá a leitura em tempo real atualizada via WebSocket (Laravel Reverb).
