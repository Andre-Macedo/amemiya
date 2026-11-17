# ADR-006: Arquitetura da Bancada Experimental M1 (Painel NR-10/NR-12 e 3 Modos de Falha)

* **Status:** Aceito
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

Na versão V1 da bancada de testes, o ensaio limitava-se a **desbalanceamento simples** por inserção de peso excêntrico no disco, sem infraestrutura industrial dedicada de intertravamento elétrico nem recursos cinemáticos para reproduzir outras falhas clássicas de máquinas rotativas. Validar um sistema de manutenção preditiva e metrologia para a Metalúrgica Amemiya exige:

1. **Segurança elétrica e mecânica compatível com normas brasileiras (`NR-10`, `NR-12` e `ABNT NBR 5410`):** operação assistida em laboratório com motor `127V AC`, proteção de partes girantes e prevenção contra religamento automático após queda de energia.
2. **Diversidade real de modos de falha sem depender apenas de datasets públicos:** gerar dados próprios em hardware real para validar a `ISO 20816-3`, a cinemática de rolamentos e os modelos `XGBoost` / `Isolation Forest`.

---

## Decisão

Adotou-se na Bancada Experimental M1 uma arquitetura eletromecânica em dois pilares:

### 1. Painel Elétrico e Cadeia de Segurança (`amemiya_bancada_painel`)
* **Separação Força vs. Comando:** circuito de força em **`127V AC`** (`QM1` disjuntor bipolar C10A $\rightarrow$ contatos principais `KM1` $\rightarrow$ dimmer TRIAC `TC1` $\rightarrow$ motor `M1`, cabeado em `2,5 mm²` com aterramento `PE` contínuo), alimentando diretamente a fonte chaveada isolada `PS1` (`24V DC`, `2,5 A`, `60 W` SELV, sem porta-fusíveis `FU1`/`FU2` no ramal interno).
* **Intertravamento e Retenção (`NR-12`):** circuito de comando em **`24V DC` SELV (`1,0 mm²`)** ligando em série o botão de emergência com retenção **`SB0`** (1NF + 1NA para alarme `HL4`), a chave fim de curso da tampa móvel **`SQ1`** (SPDT, alarme `HL3`), o botão desliga **`SB1`** e o selo eletromecânico **`KM1:13-14`** com botão **`SB2`** — garantindo parada imediata na abertura de proteção e impedindo religamento automático após retorno de energia.
* **Tacometria Visual Independente (`PA1` / `BG1`):** sensor Hall `BG1` (`24V`) ligado exclusivamente ao display local `PA1` do painel para leitura de referência na bancada, isolado eletricamente dos pinos `3,3V` do `ESP32-S3`.

### 2. Expansão Mecânica para 3 Modos de Falha Controlados (V1 $\rightarrow$ V2)
Além do **desbalanceamento** (V1), a bancada V2 incorpora:
1. **Desbalanceamento de Massa (`1X`):** disco calibrado em aço inox ($\varnothing 120 \times 12\text{ mm}$) com furação M6/M8 para inserção controlada de massas excêntricas.
2. **Desalinhamento Controlado (`2X` / axial-radial):** placa base do motor usinada com 4 rasgos oblongos, 4 parafusos verticais (*jacking bolts*, passo $1{,}0\text{ mm}$) para ajuste $\Delta Z / \theta_v$ e 2 blocos laterais *push-pull* para ajuste $\Delta Y / \theta_h$ sem desmontar o motor, transmitindo ao eixo via acoplamento flexível de mandíbulas com inserto PU 92 ShA.
3. **Falha em Rolamento (`BPFO/BPFI/BSF/FTF`):** conjunto de mancal intercambiável permitindo substituição do mancal saudável por **mancal com rolamento defeituoso** para coleta real de impactos e degradação térmica/espectral.

---

## Justificativa Técnica

* **Isolamento 24V SELV + Selo Eletromecânico:** evita risco de choque nos botões frontais da porta e atende ao critério determinístico da `NR-12` (comando por hardware não depende de software/ESP32 para interromper o motor).
* **Indução Mecânica Reprodutível:** os fusos *jacking bolts* e *push-pull* eliminam o uso improvisado de calços soltos e permitem repetir ensaios limpos de desalinhamento paralelo/angular e falha de rolamento sob velocidade variável pelo dimmer `TC1`.

---

## Consequências

* **Positivas:**
  * Bancada defensável frente às normas `NR-10`, `NR-12` e `ABNT NBR 5410` (validada por `python dev.py nr:check`).
  * Capacidade de treinar e auditar o sistema com **4 estados reais** (*saudável*, *desbalanceamento*, *desalinhamento* e *falha de rolamento*).
* **Negativas / Pendências Físicas Obrigatórias (Campo):**
  * Conforme a `MATRIZ_CONFORMIDADE_NR10_NR12.md`, continuam obrigatórios na entrega física: placa/adesivo **`PERIGO 127V`** na porta (`NR-10 10.7.7.1 b`), conexão exclusiva em tomada predial com **DR 30 mA** (`NR-10 10.6.4`) e confirmação funcional do intertravamento `SQ1` (ou fechamento por parafusos que exijam ferramenta — `NR-12 12.5.4`).
