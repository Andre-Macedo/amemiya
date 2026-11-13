# Medição de RPM: Display Local (`BG1`) e Validação *Tacholess*

Este guia explica o papel do sensor de efeito Hall **`BG1` (`24V`)** instalado na bancada para leitura no display local e como validar metrologicamente a **estimação de RPM via acelerômetro (*tacholess*)** utilizada na telemetria IoT.

::: info Arquitetura da Bancada Atual
Na revisão atual da bancada, o sensor Hall `BG1` alimenta **exclusivamente o display tacométrico local do painel (`24V DC`)** — ele **não** é conectado às GPIOs do `ESP32-S3` (onde o `GPIO10` é reservado ao `LCD_CS` do display TFT). A integração direta de tacômetro industrial fica prevista apenas via CLP em uma futura versão `v3`. Na telemetria IoT, a rotação é estimada pelo acelerômetro (`BearingKinematicsService::resolveSpectralRpm()`).
:::

---

## 1. Alinhamento Mecânico do Sensor Hall Local (`BG1`)

Para evitar falso disparo ou perda de pulsos no display local da bancada:

1. **Distância de *Air Gap* (Folga):**
   - Mantenha a distância entre a face do ímã no disco/polia e a face sensora de `BG1` entre **$1{,}5\text{ mm}$ e $3{,}0\text{ mm}$**.
   - Distâncias superiores a $4\text{ mm}$ causam atenuação do campo magnético e falha de contagem.
   - Distâncias inferiores a $1\text{ mm}$ apresentam risco mecânico de colisão por vibração axial.
2. **Separação de Cabos (`NR-10 10.4.3.1`):**
   - Conduza o chicote `24V` de `BG1` em rota separada dos cabos `127V AC` do motor e do Dimmer TRIAC `TC1` para evitar disparo espúrio por EMI.

---

## 2. Estimação de RPM por Acelerômetro (*Tacholess*) na Telemetria IoT

No sistema IoT (`ESP32-S3` + `BearingKinematicsService`), o eixo `1X` é obtido sem ligação física com `BG1`:

1. **Firmware (`ESP32-S3`):** calcula FFT de baixa frequência (com decimação para `~400 Hz` ou janela longa) + interpolação parabólica nos *bins* $[k-1, k, k+1]$ e publica `rpm_estimated` (com `"rpm_source": "accelerometer_firmware"`).
2. **Fallback no Backend (`ProcessTelemetryJob`):** caso o firmware envie apenas `peaks` ou `fft_x/y/z`, o [`BearingKinematicsService::estimateRpmFromPeaks()`](/referencia/api-mqtt-telemetria) aplica pontuação harmônica (`1X + 2X + 3X`) na faixa operacional da máquina (`400–3800 RPM`) para evitar confundir o pico `2X` de desalinhamento com a rotação fundamental.

---

## 3. Validação Metrológica Comparativa

Durante a qualificação da bancada:
1. Varie a rotação pelo Dimmer `TC1` em 5 patamares dentro da faixa operacional (ex.: `600`, `900`, `1200`, `1500` e `1750 RPM`).
2. Compare a leitura do **display local `BG1`** (ou tacômetro óptico padrão calibrado) com o valor `rpm` estimado na telemetria IoT.
3. Confirme que o desvio relativo permanece abaixo da tolerância de **`2,5%`** exigida pelo casamento de frequências de rolamento (`BPFO`, `BPFI`, `BSF`, `FTF`).
