# ADR-007: Transmissão Espectral Híbrida (Gráficos no Front-end Next.js) vs. Enlace LoRa `27 bytes`

* **Status:** Aceito *(Backend pronto para JSON `peaks` + `fft_x/y/z`; fallback multi-pacote LoRa especificado para o firmware final)*
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

A interface de gráficos espectrais no front-end em Next.js (`IoTNodeApiController::spectrum()` e `BearingKinematicsService`) exige dados no domínio da frequência para:
1. Plotar o espectro de vibração e identificar harmônicos ($0{,}5\times, 1\times, 2\times, 3\times$) e frequências características de rolamento (`BPFO`, `BPFI`, `BSF`, `FTF`).
2. Estimar ou validar a rotação (*tacholess*) por interpolação parabólica sub-bin (`extractPeaksFromFft()`).

Entretanto, transmitir a forma de onda bruta (`3 × 1024 floats ≈ 12,3 KB`) ou a FFT completa em todo ciclo pelo rádio **LoRa 915 MHz (`E32` / `E220`)**, cujo tamanho útil típico por sub-pacote é de **~58 bytes**, exigiria mais de 240 transmissões por leitura — inviabilizando bateria, tempo de ar (*Time on Air*) e confiabilidade.

---

## Decisão

Adota-se uma **arquitetura de telemetria espectral em duas camadas (Híbrida + Fallback LoRa sob evento)**:

### 1. Fluxo Contínuo de Rotina: Binário LoRa (`27 bytes`)
Em operação periódica normal, o nó transmite exclusivamente a struct compacta documentada em [Payload LoRa Binário](/referencia/payload-lora-binario) (`node_id`, `sequence_id`, `rpm`, `temp_celsius_x100`, `vrms_x/y/z`, `peak_accel`, `crest_factor`, `iso_zone`, `edge_alarm`, `vbat_mv`, `crc16`).

### 2. Burst Espectral para os Gráficos do Front-end (Apenas sob Anomalia ou Demanda)
Quando ocorre anomalia (Zona ISO `C`/`D` ou `edge_alarm = 1`) ou solicitação explícita de inspeção espectral:
* **Caminho Primário (quando há Wi-Fi disponível, ex.: bancada/oficina):** o `ESP32-S3` envia um único payload JSON via **Wi-Fi MQTT** contendo `peaks` interpolados e arrays `fft_x/y/z` + `sample_rate`, armazenados pelo `ProcessTelemetryJob` no Redis (`iot:node:{id}:spectrum:latest`).
* **Fallback LoRa (quando não há cobertura Wi-Fi em campo):**
  1. **Pacote de Picos (`Top-12 peaks`, 1 pacote LoRa `~56 bytes`):** cabeçalho (`tipo`, `node_id`, `seq`, `crc16`) + até **12 picos dominantes** (`uint16 freq_hz_x10` + `uint16 amp_x1000`, `4 bytes` por pico). Isso já basta para o `BearingKinematicsService` casar `1X`, `2X`, `BPFO`, `BPFI`, `BSF` e `FTF` sem precisar da curva inteira.
  2. **Burst Espectral Reduzido (`0–1000 Hz`, ~7 pacotes LoRa sob demanda):** caso o operador solicite a curva contínua sem Wi-Fi, transmite apenas a banda normativa ISO 20816-3 (`~320 bins` de `10–1000 Hz` em um eixo dominante) quantizada em `uint8` (`320 bytes` divididos em **7 sub-pacotes de ~50 bytes** com índice e `CRC-16`), remontados pelo Gateway antes de publicar no MQTT.

---

## Justificativa Técnica

* **Eficiência Energética e de Espectro:** mantém $99\%$ das transmissões em apenas `27 bytes` (~`38 ms` no ar em SF9), preservando bateria e evitando colisão de RF.
* **Cobertura Completa dos Gráficos Espectrais:** mesmo em áreas fabris sem Wi-Fi, 1 único pacote de `Top-12 picos` entrega diagnósticos cinemáticos completos, e um burst eventual de 7 pacotes (~`1–2 s`) reconstrói os gráficos de `0–1000 Hz` no front-end sem tentar enviar `12 KB` de onda bruta.

---

## Consequências

* **Positivas:**
  * Backend Laravel (`ProcessTelemetryJob` e `IoTNodeApiController::spectrum()`) já suporta nativamente tanto `peaks` isolados quanto `fft_x/y/z` via MQTT/Redis.
  * Evita dependência rígida de Wi-Fi contínuo no chão de fábrica sem comprometer os gráficos de análise espectral no front-end em Next.js.
* **Negativas / Trabalho Pendente de Implementação:**
  * O protocolo de fragmentação/remontagem LoRa (`Top-12 picos` e `0–1000 Hz` multi-pacote) ainda precisa ser codificado no **firmware do Nó Sensor** e na **ponte do Gateway ESP32** durante a etapa de firmware.
