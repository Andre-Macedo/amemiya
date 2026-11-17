# Relatório Técnico de PCBs - Amemiya Industrial IoT

Este relatório reúne a documentação técnica, parâmetros de fabricação e inspeção visual fotorrealista das placas desenvolvidas para o sistema de manutenção preditiva Amemiya IoT:

1. **Sonda Torre Dual Ativa (`amemiya_probe_tower_dual`)** — **24.0 mm × 48.0 mm** (`ADXL345` + `DS18B20` + conector `JST-XH 5P`, sem `INMP441`)
2. **Nó Principal de Aquisição (`amemiya_main_node`)** — **ESP32-S3 + LoRa E32 + Display TFT 3.2" SPI (`ILI9341` + `XPT2046`, `SPI_MOSI` em `GPIO7`) + Buzzer + LEDs RGB** *(sem OLED e sem tacômetro)*
3. **Placas Legadas (`amemiya_probe_tower`, `amemiya_probe_sensor`)** — revisões anteriores (`24.0 × 48.0 mm` e `46.0 × 40.0 mm`, com `INMP441` / `OLED` / `Ra-02`) mantidas apenas para histórico.

Todas as 3 placas foram projetadas em **100% Through-Hole (PTH)** (montagem manual fácil com ferros comuns de solda), em **2 camadas (Top / Bottom)** e aprovadas com **Zero Violações de DRC (KiCad 9.0 CLI)** e **Zero Itens Desconectados**.

---

## 1. Inspeção Visual 3D (Renders Oficiais KiCad 9)

### A. Sonda Cartucho Torre (24 × 48 mm — Render Histórico v1)
![Sonda Torre Topo](/images/hardware/probe_tower_top.png)
*Figura 1: Render histórico da revisão v1 da Sonda Torre (que incluía jumpers para `INMP441` e conector `GX12-8`; na revisão ativa `amemiya_probe_tower_dual`, o microfone foi removido e padronizado para 5 vias `JST-XH 5P` / `GX16-5`).*

![Sonda Torre Fundo](/images/hardware/probe_tower_bot.png)
*Figura 2: Vista traseira da Sonda Torre com trilhas de alimentação isoladas (3.3V na esquerda e GND na direita).*

---

### B. Sonda Horizontal Clássica (46 × 40 mm)
![Sonda Horizontal Topo](/images/hardware/probe_top.png)
*Figura 3: Vista superior da Sonda Horizontal com todos os sensores embarcados lado a lado.*

![Sonda Horizontal Fundo](/images/hardware/probe_bot.png)
*Figura 4: Vista inferior da Sonda Horizontal com corredores de alimentação independentes.*

---

### C. Nó Principal ESP32-S3 (90 × 65 mm — Render Histórico v1)
![Nó Principal Topo](/images/hardware/main_top.png)
*Figura 5: Render histórico da revisão v1 do Nó Principal (que contemplava `OLED`, `SCT-013` e entrada de tacômetro; na revisão ativa V2 `amemiya_main_node`, esses três itens foram removidos em favor de `TFT 3.2" SPI`, conector `GX16-5` e estimação *tacholess* via acelerômetro).*

![Nó Principal Fundo](/images/hardware/main_bot.png)
*Figura 6: Vista inferior do Nó Principal com plano de potência e barramentos de alimentação.*

---

## 2. Status de Validação Elétrica (DRC KiCad 9.0)

Relatório oficial de regras de design compilado via linha de comando:

| Placa | Dimensões | Violações de Regra | Itens Desconectados | Erros de Footprint | Status |
|---|:---:|:---:|:---:|:---:|:---:|
| **Sonda Torre (`amemiya_probe_tower`)** | **24.0 × 48.0 mm** | **0** | **0** | **0** | **APROVADO** |
| **Sonda Horizontal (`amemiya_probe_sensor`)** | **46.0 × 40.0 mm** | **0** | **0** | **0** | **APROVADO** |
| **Nó Principal (`amemiya_main_node`)** | **90.0 × 65.0 mm** | **0** | **0** | **0** | **APROVADO** |

---

## 3. Arquivos de Fabricação Gerados (Prontos para Envio)

Os pacotes Gerber RS-274X e arquivos de furação Excellon estão compactados na pasta `hardware/`:

* `hardware/amemiya_probe_tower_gerbers.zip` — **Sonda Cartucho Torre (24 × 48 mm)**
* `hardware/amemiya_probe_sensor_gerbers.zip` — **Sonda Horizontal (46 × 40 mm)**
* `hardware/amemiya_main_node_gerbers.zip` — **Nó Principal ESP32-S3 (90 × 65 mm)**

### Parâmetros para Pedido na PCBWay / JLCPCB:
* **Camadas:** 2 Layers
* **Material:** FR-4 Standard ($T_g \ge 130\text{--}140^\circ\text{C}$)
* **Espessura da Placa:** 1.6 mm
* **Espessura do Cobre:** 1 oz ($35\ \mu\text{m}$)
* **Acabamento da Superfície:** HASL com chumbo ou HASL Lead-Free (sem chumbo)
* **Cor da Máscara de Solda:** Verde (ou Preto/Azul conforme preferência)
* **Cor da Serigrafia (Silkscreen):** Branco
