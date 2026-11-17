# ADR-005: Eliminação do Microfone `INMP441` (I2S) e do Tacômetro Óptico no Nó Sensor

* **Status:** Aceito
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

As primeiras especificações da torre de sensores incluíam três transdutores conectados ao nó ESP32-S3: acelerômetro triaxial (`ADXL345`), sensor térmico de contato (`DS18B20`) e microfone acústico MEMS (`INMP441` I2S 24 bits), além de uma entrada de tacômetro óptico/Hall no nó principal.

Três evidências técnicas e construtivas motivaram a revisão desse conjunto:

1. **Auditoria de Importância de Features (XAI) no Modelo XGBoost:** conforme registrado em [Auditoria XGBoost](/Relatorio_Auditoria_XGBoost_Importancia_Features) *(e em `docs/iot/DECISOES_TECNICAS_E_REFERENCIAS_CIENTIFICAS.md`)*, as 12 variáveis acústicas do `INMP441` somaram apenas **0,30%** do ganho de informação nas 500 árvores do modelo especialista, enquanto as variáveis de vibração do `ADXL345` concentraram **99,70%**.
2. **Contaminação por Ruído Fabril e Vedação IP65:** microfones acústicos no ar captam ruído de máquinas vizinhas, compressores e vozes (alerta documentado em *HardwareX*, Jakobsen, 2024 — [`doi:10.1016/j.ohx.2024.e00525`](https://doi.org/10.1016/j.ohx.2024.e00525), acesso aberto), além de exigirem janela/membrana acústica na carcaça da sonda, comprometendo a vedação IP65 contra névoa de fluido de corte.
3. **Separação da Malha de Tacometria na Bancada:** o sensor Hall `BG1` da bancada elétrica opera ligado diretamente ao display local do painel frontal para leitura visual de segurança, enquanto a telemetria IoT extrai a rotação ($1\times\text{RPM}$) por DSP (*tacholess speed estimation* via aceleração mecânica, fundamentada em Lu et al., 2019 — [`doi:10.1109/tim.2019.2902806`](https://doi.org/10.1109/tim.2019.2902806)).

---

## Decisão

1. **Remover definitivamente o microfone `INMP441` (barramento I2S `I2S_SD`, `I2S_SCK`, `I2S_WS`)** das sondas ativas, do esquemático `amemiya_main_node` (`02_Sensors_AFE` / `03_MCU_ESP32S3`) e do firmware de coleta.
2. **Padronizar a sonda de mancal (`amemiya_probe_tower_dual`) e o conector `J_PROBE` do nó principal em 5 vias (`GX16-5` / `JST-XH 5P`)** com ligação 1-para-1 direta (`+3V3_SWITCHED`, `GND`, `1W_DQ` `GPIO5`, `I2C_SCL` `GPIO42`, `I2C_SDA` `GPIO41`).
3. **Manter a ausência de entrada de tacômetro no nó ESP32-S3:** a rotação para normalização espectral continua sendo estimada por FFT decimada de baixa frequência ($\Delta f \approx 0{,}39\text{ Hz}$) com pontuação harmônica ($1X + 2X + 3X$) sobre os dados do `ADXL345` (eventual leitura direta de `BG1` permanece prevista apenas para integração industrial via CLP na etapa futura).

---

## Justificativa Técnica

* **Ganho marginal nulo:** eliminar o `INMP441` não degrada a acurácia diagnóstica do modelo preditivo ($99{,}70\%$ depende apenas da aceleração triaxial e da curva térmica).
* **Economia de energia e CPU:** elimina a captura contínua DMA/I2S em 24 bits, reduzindo consumo ativo no ciclo de aquisição e simplificando o payload de telemetria.
* **Reaproveitamento de GPIOs:** libera `GPIO6`, `GPIO7` e `GPIO8` no `ESP32-S3`, permitindo realocar `SPI_MOSI` do TFT para `GPIO7` e isolar `GPIO11`.

---

## Consequências

* **Positivas:**
  * Sonda mecânica mais compacta (`amemiya_probe_tower_dual`, `24,0 × 48,0 mm`, conector `JST-XH` de 5 pinos), sem furo acústico exposto a cavaco e óleo.
  * Chicote de campo mais fino, barato e menos suscetível a diafonia entre clock I2S e linhas I2C/1-Wire.
  * Redução de complexidade elétrica em `02_Sensors_AFE.kicad_sch` e `03_MCU_ESP32S3.kicad_sch`.
* **Negativas / Cuidados:**
  * Legados de schema/banco que ainda mencionem métricas acústicas (como `mic_rms`) devem permanecer nulos/depreciados até limpeza de esquema em versão maior.
