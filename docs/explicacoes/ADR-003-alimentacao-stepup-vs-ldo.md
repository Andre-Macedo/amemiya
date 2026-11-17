# ADR-003: Arquitetura de Alimentação do Nó Sensor: Bateria Li-Ion + Step-Up `MT3608` (`5V`) + LDO (`3,3V`)

* **Status:** Aceito
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

O painel de comando e segurança da bancada (`amemiya_bancada_painel`) possui uma fonte chaveada `PS1` de `24V DC` destinada **exclusivamente** aos dispositivos de comando industrial (contator `KM1`, botões/sinaleiros NR-12 e tacômetro `PA1`). O nó sensor IoT (`amemiya_main_node`) precisa operar de forma **independente e eletricamente desacoplada** desse quadro de força, permitindo posicionamento livre próximo aos mancais monitorados.

Para o nó principal (`ESP32-S3 DevKitC-1`, rádio LoRa `E32`, display TFT `3.2"` e sonda `ADXL345` + `DS18B20`), a tensão de bateria `1S2P` Li-Ion (`3,0V` a `4,2V`) precisa gerar `+5,00V` estáveis na entrada `J_BAT` do DevKit e `+3,3V` limpos para lógica e sensores, mesmo durante picos de corrente de rádio e backlight.

---

## Decisão

Adotou-se na folha `01_Power_Supply.kicad_sch` (repositório irmão `amemiya-hardware`) a seguinte cadeia de alimentação em **2 estágios a partir de bateria 1S2P**, completamente isolada do `24V` do painel:

1. **Banco Primário e Proteção:** 2× células `18650` Li-Ion em paralelo (`3,7V` nominais, faixa `3,0V–4,2V`, $\sim 5200\text{ mAh}$), com carregador/BMS **`TP4056` USB-C** (`1,0 A` CC/CV) e chave alavanca geral (`250V 3A`).
2. **Estágio 1 — Conversor Boost / Step-Up (`MT3608`):** eleva a tensão variável da bateria (`3,0V–4,2V`) para um barramento regulado de **`+5,00V CC`** conectado ao borne **`J_BAT` (`JST-XH 2P`)**, com capacitores de entrada/desacoplamento (`10 µF + 100 nF`).
3. **Estágio 2 — Regulador Linear LDO (`ESP32-S3 DevKitC-1`):** deriva o barramento lógico **`+3,3V CC`** (filtrado por `10 µF + 100 nF`) para o microcontrolador, rádio LoRa e sensores.
4. **Supervisão Real de Carga (`J_BAT_SENSE`):** tomada direta da tensão bruta **antes do Step-Up** (`0V–4,2V`) conectada ao divisor simétrico `1:2` (`100 kΩ / 100 kΩ 1%`) com filtro passa-baixa RC (`100 nF`) no pino `GPIO1` (`VBAT_ADC`).

---

## Justificativa Técnica

1. **Prevenção de *Brownout* com Célula Li-Ion 1S:**
   - O regulador LDO linear embarcado no `ESP32-S3 DevKitC-1` (típico `AMS1117-3.3`) possui *dropout* acima de `~1,0 V`, exigindo $\ge 4{,}5\text{V}$ na entrada `5V` para manter `3,3V` estáveis.
   - Ligar uma bateria `18650` (`3,0V–4,2V`) diretamente no pino `5V` causaria queda imediata de tensão e reinícios (*brownout*) assim que o rádio LoRa ou o TFT puxassem pico de corrente. O **`MT3608`** mantém `5,00V` constantes até o corte de subtensão do BMS.
2. **Atenuação de *Ripple* de Chaveamento via LDO:**
   - Conversores *Step-Up* chaveados introduzem *ripple* de alta frequência na saída de `5V`. Manter o LDO como segundo estágio filtra esse ruído antes de alimentar o ADC (`GPIO1`), o acelerômetro `ADXL345` e o módulo `LoRa`.
3. **Independência entre Bancada (`24V`) e Nó Sensor:**
   - Desacoplar a alimentação do ESP32 da fonte `24V` do painel evita passar chicote extra de energia e ruído do circuito de potência até o nó portátil, além de manter compatibilidade direta com operação em campo a bateria ou recarga via USB-C (`TP4056`).

---

## Consequências

* **Positivas:**
  * Operação portátil e estável durante toda a curva de descarga da bateria `18650` (`4,2V` até `~3,0V`).
  * Arquitetura simples de montar em placa caseira usando módulos comerciais (`TP4056` + `MT3608` + `DevKitC-1`).
  * Monitoramento fiel do estado de carga via `J_BAT_SENSE` antes do Step-Up (já que após o `MT3608` a tensão permanece fixa em `5,00V`).
* **Negativas / Cuidados:**
  * O módulo `MT3608` conectado continuamente junto ao LDO do `DevKitC-1` possui corrente quiescente na faixa de miliampères, reduzindo a autonomia prática em bateria em comparação ao cálculo ideal apenas do chip ESP32 em *Deep-Sleep* ([ADR-002](/explicacoes/ADR-002-deep-sleep-power-gating)); para instalações em bancada contínua, recomenda-se manter alimentação/recarga USB-C no `TP4056`.
  * Caso se deseje autonomia multi-anual exclusivamente em bateria em uma revisão futura, será necessário substituir o conjunto `MT3608 + DevKitC-1` por módulo ESP32-S3 puro com bateria `LiFePO4` (`3,2V`) ou regulador *buck-boost* de ultra-baixo $I_q$ (`< 10 µA`).
