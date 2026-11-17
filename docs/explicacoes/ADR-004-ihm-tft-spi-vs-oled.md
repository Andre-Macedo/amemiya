# ADR-004: Adoção de Display TFT 3.2" SPI (`ILI9341` + `XPT2046`) em Substituição ao OLED 0.96" I2C

* **Status:** Aceito
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

As primeiras revisões do nó principal (`amemiya_main_node`) previam um display **OLED 0.96" I2C (`SSD1306`, 128×64 px)** para inspeção local. Na evolução da bancada didática e dos requisitos operacionais de chão de fábrica, o OLED mostrou limitações práticas:

1. **Baixa densidade informacional:** $128 \times 64$ pixels monocromáticos dificultam exibir simultaneamente velocidade RMS (`mm/s`), Zona ISO 20816-3 (A–D), temperatura do mancal, RPM estimado, status do enlace LoRa e tensão da bateria.
2. **Ausência de interação local:** ajustes de tara/calibração, troca de tela (sumário vs espectro FFT) e reconhecimento de alarmes exigiam botões mecânicos extras ou acesso obrigatório ao painel web.
3. **Compartilhamento de barramento:** dividir o barramento I2C entre o `ADXL345` em *Fast-Mode* ($400\text{ kHz}$, leitura contínua a $3{,}2\text{ kHz}$) e atualizações gráficas do display introduzia contenção de barramento.

Em bancada com hardware real (`3.2 TFT SPI 240X320 V1.0`, `ESP32-S3-N16R8`), também foi validado o remapeamento de `SPI_MOSI` para **`GPIO7`** (aproveitando pino liberado pela remoção do `INMP441`), mantendo **`GPIO11` desconectado (`NC`)** após constatação de curto interno para `GND` na unidade de bancada.

---

## Decisão

Substituiu-se o display OLED 0.96" I2C por uma **IHM local em Display TFT LCD 3.2" SPI (240×320 RGB, controlador `ILI9341`) com Touch Screen Resistivo (`XPT2046`)**, utilizando a biblioteca **`LovyanGFX` (`SPI2_HOST`)** no conector `J_TFT` (1×14 pinos):

* **Barramento SPI Gráfico (`SPI2_HOST`):** `GPIO7` (`SPI_MOSI`), `GPIO12` (`SPI_SCK`), `GPIO13` (`SPI_MISO` — conectado apenas ao `T_DO` do `XPT2046`, mantendo `SDO(MISO)` do LCD desconectado se houver interferência).
* **Controle LCD & Touch:** `GPIO10` (`LCD_CS`), `GPIO9` (`LCD_DC`), `GPIO14` (`LCD_RST`), `GPIO4` (`TOUCH_CS`), `GPIO3` (`TOUCH_IRQ`).
* **Backlight (`LED`):** `GPIO2` (`LCD_BL` PWM) com resistor limitador `R_BL` de $10\ \Omega$.
* **Calibração Touch validada (`setRotation(1)`):** `uint16_t calData[8] = { 3757, 490, 3790, 3893, 328, 504, 390, 3865 };`.

---

## Justificativa Técnica

1. **Desacoplamento entre Aquisição I2C e Vídeo SPI:** o `ADXL345` passa a operar em barramento I2C exclusivo (`GPIO41`/`GPIO42`), enquanto o TFT opera em SPI de alta velocidade sem disputar ciclos de I2C com a leitura da FIFO do acelerômetro.
2. **Controle Elétrico do Jumper `J1` (3,3 V vs 5 V):** módulos `3.2 TFT SPI 240X320 V1.0` saem de fábrica com o jumper `J1` aberto (passando pelo regulador `U1` de 3,3 V, exigindo 5 V em `VCC`). Como o conector `J_TFT` da PCB entrega `+3V3` regulado no pino 1, é mandatório fechar `J1` com solda antes do encaixe na placa definitiva (e nunca mais alimentar em 5 V após fechar `J1`).
3. **Alocação Segura de `SPI_MOSI` em `GPIO7`:** no `ESP32-S3`, `SPI2_HOST` utiliza a matriz de GPIO sem perda prática até 40 MHz. Usar `GPIO7` evita o `GPIO11` defeituoso na unidade de bancada sem tocar em *strapping pins* (`GPIO0`, `GPIO45`, `GPIO46`) ou USB nativo (`GPIO19/20`).

---

## Consequências

* **Positivas:**
  * Interface local rica (cartões de severidade ISO 20816-3, espectro e toque resistivo direto na bancada).
  * Barramento I2C 100% dedicado ao acelerômetro triaxial.
  * Pinagem validada em hardware real e alinhada com `amemiya_main_node`.
* **Negativas / Cuidados:**
  * Backlight consome $\sim 80\text{--}120\text{ mA}$ quando ativo; deve ser desligado via PWM/`GPIO2` durante *Deep-Sleep* ou operação prolongada em bateria.
  * Exige atenção rigorosa ao estado do jumper `J1` do módulo TFT antes da energização.
