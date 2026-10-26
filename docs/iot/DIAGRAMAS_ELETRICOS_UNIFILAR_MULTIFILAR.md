# Projeto Elétrico Industrial: Diagramas Unifilar e Multifilar
## Bancada Metrológica de Testes M1 — Sistema Lean Tech

![Render 3D do Painel de Comando Industrial Montado](../../hardware/kicad/amemiya_bancada_painel/painel_comando_3d_render.jpg)

---

## 1. O que são Diagramas Unifilar e Multifilar?

Na engenharia elétrica industrial (normas **ABNT NBR 5410**, **NR-10**, **NR-12** e **IEC 60617**), existem formas distintas de representar uma instalação elétrica dependendo do objetivo: visão sistêmica macro ou execução física fio a fio.

```mermaid
flowchart TD
    A["Projeto Elétrico Industrial"] --> B["Diagrama Unifilar<br/>(Visão Sistêmica / Macro)"]
    A --> C["Diagrama Multifilar<br/>(Visão Física / Fio a Fio)"]
    
    B --> B1["Um único traço representa múltiplos condutores"]
    B --> B2["Foco na topologia, proteções e fluxo de energia"]
    B --> B3["Usado por projetistas, inspetores e bombeiros"]
    
    C --> C1["Circuito de Potência (Força)"]
    C --> C2["Circuito de Comando (24V DC / NR-12)"]
    C --> C3["Mostra cada borne, cada condutor e polaridade"]
    C --> C4["Usado pelo eletrotécnico de montagem e manutenção"]
```

### 1.1 Diagrama Unifilar
- **Conceito:** Representa todos os condutores de um circuito (fase, neutro, terra, retorno) simplificados em **uma única linha**. Sobre essa linha, traços inclinados normalizados indicam quantos e quais fios passam naquele trecho:
  - Traço longo vertical: Condutor de **Fase** ($L$).
  - Traço em "L" invertido: Condutor de **Neutro** ($N$).
  - Traço em "T": Condutor de **Proteção / Terra** ($PE$).
- **Finalidade:** Permite enxergar num relance a rota da energia: entrada da rede $\rightarrow$ disjuntor geral $\rightarrow$ barramentos $\rightarrow$ ramais de potência e comando.

### 1.2 Diagrama Multifilar
- **Conceito:** Representa **cada condutor físico individualmente com sua rota real**, identificando número de borne, terminais de componentes (ex: `A1`, `A2`, `13`, `14`), bitola em $mm^2$ e cor da fiação.
- **Divisão Padrão:**
  1. **Circuito de Potência (Força):** Linhas de alimentação que transportam a corrente pesada do motor (220V AC).
  2. **Circuito de Comando (Controle e Segurança):** Circuito em extra-baixa tensão de segurança (24V DC) que opera a lógica de liga/desliga, emergência e intertravamento de segurança.

---

## 2. Diagrama Unifilar da Bancada M1

Abaixo está o diagrama unifilar que sintetiza a distribuição geral de energia no painel elétrico:

```mermaid
graph TD
    classDef main fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff;
    classDef safety fill:#7f1d1d,stroke:#ef4444,stroke-width:2px,color:#fff;
    classDef power fill:#064e3b,stroke:#10b981,stroke-width:2px,color:#fff;
    classDef ctrl fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff;

    ALIM["Alimentação Externa<br/>220V AC — 1F + N + PE (60 Hz)"]:::main
    PRENSA_IN["Prensa-Cabo Entrada M16<br/>Cabo PP 3x1.5mm²"]:::main
    Q1["Disjuntor Bipolar Steck C10A (Q1)<br/>Proteção Geral Icn = 4.5kA"]:::power
    
    BAR_AC["Barramento de Distribuição 220V AC<br/>(Bornes Cinza SAK 2.5mm²)"]:::power
    
    F1["Porta-Fusível DIN 10x38 (F1)<br/>Fusível 2A gG (Comando/Fonte)"]:::ctrl
    PS1["Fonte Chaveada DIN 24V DC 2.5A (PS1)<br/>Isolação Galvânica SELV (60W)"]:::ctrl
    
    K1_POT["Contator Steck 24V (K1)<br/>Contatos Principais 1-2 e 3-4"]:::power
    DIM1["Dimmer AC Rotativo 2000W (DM1)<br/>Ajuste de Rotação por Triac"]:::power
    MTR["Motor Elétrico da Bancada M1<br/>Universal / Assíncrono Monofásico 220V"]:::power
    
    SAFE_LOOP["Cadeia de Segurança 24V DC<br/>Emergência S0 + Tampa S1 + Botão S2"]:::safety
    TAC["Tacômetro Digital 4 Dígitos LED (TAC1)<br/>Sensor Hall Magnético 24V DC"]:::ctrl

    ALIM --> PRENSA_IN --> Q1 --> BAR_AC
    BAR_AC --> F1 --> PS1 --> SAFE_LOOP
    BAR_AC --> K1_POT --> DIM1 --> MTR
    PS1 --> TAC
    SAFE_LOOP -.->|"Habilita Bobina A1/A2"| K1_POT
```

---

## 3. Diagrama Multifilar de Potência (Circuito de Força — 220V AC)

Este diagrama detalha a rota física dos condutores de potência que alimentam o contator, dimmer e motor elétrico:

```mermaid
flowchart TD
    classDef ac fill:#1e1b4b,stroke:#6366f1,stroke-width:2px,color:#fff;
    classDef sw fill:#1e293b,stroke:#94a3b8,stroke-width:1.5px,color:#fff;
    classDef motor fill:#064e3b,stroke:#10b981,stroke-width:2px,color:#fff;

    subgraph REDE_220V["Rede de Alimentação Externa"]
        L_IN["Fase L (Preto 1.5mm²)"]:::ac
        N_IN["Neutro N (Azul-Claro 1.5mm²)"]:::ac
        PE_IN["Terra PE (Verde/Amarelo 1.5mm²)"]:::ac
    end

    subgraph DISJUNTOR_Q1["Disjuntor Bipolar Steck Q1 (Trilho DIN)"]
        Q1_1["Borne 1 (Entrada L)"]:::sw
        Q1_3["Borne 3 (Entrada N)"]:::sw
        Q1_2["Borne 2 (Saída L)"]:::sw
        Q1_4["Borne 4 (Saída N)"]:::sw
    end

    subgraph BARRAMENTO_PE["Aterramento e Equipotencialização"]
        BORNE_PE["Borne Terra Verde/Amarelo SAK PE<br/>Preso diretamente no trilho DIN metálico"]:::ac
    end

    subgraph CONTATOR_K1["Contator Industrial Steck K1 (Contatos de Potência)"]
        K1_L1["Terminal 1 (L1)"]:::sw
        K1_L2["Terminal 3 (L2)"]:::sw
        K1_T1["Terminal 2 (T1)"]:::sw
        K1_T2["Terminal 4 (T2)"]:::sw
    end

    subgraph MODULO_DIMMER["Módulo Dimmer AC 2000W (Painel Frontal)"]
        DM_IN_L["IN-L (Fase Comutada)"]:::sw
        DM_IN_N["IN-N (Neutro)"]:::sw
        DM_OUT_L["OUT-L (Fase Regulada 0-220V)"]:::sw
        DM_OUT_N["OUT-N (Neutro Direto)"]:::sw
    end

    subgraph CARGA_MOTOR["Motor da Bancada M1"]
        M1_TERM1["Terminal Motor 1"]:::motor
        M1_TERM2["Terminal Motor 2"]:::motor
        M1_CARC["Carcaça Metálica do Motor"]:::motor
    end

    %% Ligações de Força
    L_IN --> Q1_1
    N_IN --> Q1_3
    PE_IN --> BORNE_PE

    Q1_2 --> K1_L1
    Q1_4 --> K1_L2

    K1_T1 --> DM_IN_L
    K1_T2 --> DM_IN_N

    DM_OUT_L --> M1_TERM1
    DM_OUT_N --> M1_TERM2

    BORNE_PE ==>|"Fio Verde/Amarelo 1.5mm²"| M1_CARC
```

---

## 4. Diagrama Multifilar de Comando e Segurança (24V DC / NR-12)

O circuito de comando opera em **tensão contínua de segurança (24V DC)**, isolado galvanicamente da rede 220V através da fonte chaveada DIN `T1`. A distribuição de alimentação é realizada por **réguas de bornes SAK dedicadas no trilho DIN**:
- **Régua `XT1` (+24V DC):** 4 bornes interligados por ponte conectora FBS (+24V da fonte, alimentação de segurança S0, alarme e sinaleiro branco/tacômetro).
- **Régua `XT2` (0V / COM):** 6 bornes interligados por ponte conectora FBS (retorno 0V da fonte, contator K1, sinaleiros H1/H2/H3/H4 e tacômetro).

Todos os condutores recebem **anilhas industriais identificadas de `[W01]` a `[W42]`**.

```mermaid
flowchart TD
    classDef dc_pos fill:#7f1d1d,stroke:#ef4444,stroke-width:2px,color:#fff;
    classDef dc_neg fill:#1e1b4b,stroke:#3b82f6,stroke-width:2px,color:#fff;
    classDef safe fill:#991b1b,stroke:#f87171,stroke-width:2px,color:#fff;
    classDef button fill:#064e3b,stroke:#34d399,stroke-width:2px,color:#fff;
    classDef coil fill:#78350f,stroke:#fbbf24,stroke-width:2px,color:#fff;
    classDef white fill:#334155,stroke:#e2e8f0,stroke-width:2px,color:#fff;
    classDef yellow fill:#854d0e,stroke:#facc15,stroke-width:2px,color:#fff;
    classDef terminal fill:#1f2937,stroke:#9ca3af,stroke-width:2px,color:#fff;

    FONTE_T1["Fonte Chaveada DIN T1<br/>Saída 24V DC 2.5A (60W)"]:::dc_pos

    subgraph REGUA_XT1["Régua de Bornes XT1 (+24V DC — Ponte FBS)"]
        XT1_1["Borne XT1.1<br/>Entrada +24V da Fonte"]:::terminal
        XT1_2["Borne XT1.2<br/>Saída p/ Emergência NF"]:::terminal
        XT1_3["Borne XT1.3<br/>Saída p/ Alarme Emergência NA"]:::terminal
        XT1_4["Borne XT1.4<br/>Saída p/ Sinaleiro Branco & Tacômetro"]:::terminal
    end

    subgraph REGUA_XT2["Régua de Bornes XT2 (0V / COM — Ponte FBS)"]
        XT2_1["Borne XT2.1<br/>Entrada 0V da Fonte"]:::terminal
        XT2_2["Borne XT2.2<br/>Retorno Bobina K1:A2"]:::terminal
        XT2_3["Borne XT2.3<br/>Retorno Sinaleiro Verde H2:X2"]:::terminal
        XT2_4["Borne XT2.4<br/>Retorno Sinaleiro Amarelo H3:X2"]:::terminal
        XT2_5["Borne XT2.5<br/>Retorno Sinaleiro Vermelho H4:X2"]:::terminal
        XT2_6["Borne XT2.6<br/>Retorno Sinaleiro Branco H1:X2"]:::terminal
    end

    subgraph SEGURANCA_EMERGENCIA["1. Botão de Emergência S0 (Cogumelo 40mm c/ Trava)"]
        subgraph BLOCO_EMERG_NF["Bloco 1: 1NF (Série de Segurança)"]
            S0_11["S0: Term. 11 (NF)"]:::safe
            S0_12["S0: Term. 12 (NF)"]:::safe
        end
        subgraph BLOCO_EMERG_NA["Bloco 2: 1NA (Sinalização de Bloqueio)"]
            S0_23["S0: Term. 23 (NA)"]:::button
            S0_24["S0: Term. 24 (NA)"]:::button
        end
    end

    subgraph SEGURANCA_TAMPA["2. Microswitch da Tampa S_TAMPA (Bancada)"]
        ST_COM["S_TAMPA: Comum (COM)"]:::safe
        ST_NC["S_TAMPA: Contato NF / Segurança<br/>(Fechado com tampa abaixada)"]:::safe
        ST_NO["S_TAMPA: Contato NA / Alarme<br/>(Fecha com tampa levantada)"]:::yellow
    end

    subgraph BOTOES_COMANDO["3. Abotoadeira Liga / Desliga (Porta do Painel)"]
        subgraph SECAO_DESLIGA["Botão Desliga Vermelho S_OFF"]
            SOFF_1["S_OFF: Term. 1 (NF)"]:::safe
            SOFF_2["S_OFF: Term. 2 (NF)"]:::safe
        end
        subgraph SECAO_LIGA["Botão Liga Verde S_ON"]
            SON_3["S_ON: Term. 3 (NA)"]:::button
            SON_4["S_ON: Term. 4 (NA)"]:::button
        end
    end

    subgraph CONTATOR_K1["4. Contator Steck K1 (Trilho DIN)"]
        K1_13["Contato Auxiliar NA 13 (Selo)"]:::button
        K1_14["Contato Auxiliar NA 14 (Selo)"]:::button
        K1_A1["Bobina K1: A1 (+24V)"]:::coil
        K1_A2["Bobina K1: A2 (0V)"]:::coil
    end

    subgraph SINALIZACAO_PAINEL["5. Sinalização Visual LED 22mm (Porta Frontal)"]
        H1_LED["Sinaleiro Branco H1<br/>'PAINEL 24V ON'"]:::white
        H2_LED["Sinaleiro Verde H2<br/>'MOTOR OPERANDO'"]:::button
        H3_LED["Sinaleiro Amarelo H3<br/>'TAMPA ABERTA (ALERTA)'"]:::yellow
        H4_LED["Sinaleiro Vermelho H4<br/>'EMERGÊNCIA ACIONADA'"]:::safe
    end

    %% Alimentação Fonte -> Bornes de Distribuição
    FONTE_T1 -->|"[W17] +24V"| XT1_1
    FONTE_T1 -->|"[W18] 0V"| XT2_1

    %% Distribuição XT1 (+24V)
    XT1_2 -->|"[W19] +24V"| S0_11
    XT1_3 -->|"[W20] +24V"| S0_23
    XT1_4 -->|"[W21] +24V"| H1_LED

    %% Cadeia de Segurança em Série (NR-12)
    S0_12 -->|"[W22]"| ST_COM
    ST_NC -->|"[W23]"| SOFF_1
    SOFF_2 -->|"[W24]"| NO_CMD(("Nó Comando<br/>(212, 112)"))

    %% Ramo Liga / Selo em Paralelo
    NO_CMD -->|"[W25]"| SON_3
    NO_CMD -->|"[W26]"| K1_13
    SON_4 -->|"[W27]"| NO_COIL(("Nó Bobina<br/>(212, 136)"))
    K1_14 -->|"[W28]"| NO_COIL

    %% Acionamento da Bobina e Sinaleiro Verde (Paralelo)
    NO_COIL -->|"[W29]"| K1_A1
    NO_COIL -->|"[W30]"| H2_LED

    %% Alarmes de Segurança
    ST_NO -->|"[W36] Alarme Tampa"| H3_LED
    S0_24 -->|"[W37] Alarme Emergência"| H4_LED

    %% Retornos de 0V para a Régua XT2
    K1_A2 -->|"[W31] 0V"| XT2_2
    H2_LED -->|"[W32] 0V"| XT2_3
    H3_LED -->|"[W33] 0V"| XT2_4
    H4_LED -->|"[W34] 0V"| XT2_5
    H1_LED -->|"[W35] 0V"| XT2_6
```

---

## 5. Circuito do Tacômetro Digital & Sensor Hall

O tacômetro mede com precisão a rotação instantânea do eixo motor através da passagem do ímã de neodímio em frente ao sensor Hall `SEN_HALL`:

```mermaid
flowchart LR
    classDef pwr fill:#7f1d1d,stroke:#ef4444,stroke-width:1.5px,color:#fff;
    classDef gnd fill:#1e1b4b,stroke:#3b82f6,stroke-width:1.5px,color:#fff;
    classDef sig fill:#78350f,stroke:#f59e0b,stroke-width:1.5px,color:#fff;
    classDef dev fill:#1e293b,stroke:#64748b,stroke-width:2px,color:#fff;

    subgraph BORNES_DISTRIBUICAO["Bornes de Distribuição DIN"]
        XT1_4["Borne XT1.4 (+24V DC)"]:::pwr
        XT2_6["Borne XT2.6 (0V / GND)"]:::gnd
    end

    subgraph TAC_DISPLAY["Tacômetro Digital 4 Dígitos LED (Painel Frontal DISP1)"]
        T_VCC["Pin 1: VCC (+12V a +24V)"]:::pwr
        T_GND["Pin 2: GND (0V)"]:::gnd
        T_OUT_VCC["Pin 4: +Vcc Sensor"]:::pwr
        T_SIG["Pin 3: Signal IN (Pulso NPN)"]:::sig
        T_OUT_GND["Pin 5: GND Sensor"]:::gnd
    end

    subgraph SENSOR_HALL["Sensor Hall M12 NJK-5002C (Bancada Mecânica)"]
        H_BRN["Pino 1: Marrom (+VCC)"]:::pwr
        H_BLK["Pino 2: Preto (Sinal NPN)"]:::sig
        H_BLU["Pino 3: Azul (GND 0V)"]:::gnd
    end

    subgraph EIXO_BANCADA["Eixo Rotativo"]
        MAGNET["Ímã de Neodímio N50<br/>Afixado na polia"]:::dev
    end

    XT1_4 -->|"[W38] +24V DC"| T_VCC
    XT2_6 -->|"[W39] 0V DC"| T_GND

    T_OUT_VCC -->|"[W40] Marrom (+24V)"| H_BRN
    T_SIG <--|"[W41] Preto (Pulso NPN)"| H_BLK
    T_OUT_GND -->|"[W42] Azul (GND)"| H_BLU

    MAGNET -.->|"Passagem a 1-8mm do sensor"| SENSOR_HALL
```

---

## 6. Tabela De-Para Definitiva da Fiação (Wiring Schedule [W01] a [W42])

Esta tabela é o guia prático de montagem em bancada para o painelista crimpar e anilhar cada condutor, em total sincronismo com o esquema KiCad `amemiya_bancada_painel.kicad_sch` (versão v2.3):

| ID Cabo | Origem (De) | Destino (Para) | Função / Descrição | Bitola | Cor Normatizada | Terminal Recomendado |
| :---: | :--- | :--- | :--- | :---: | :---: | :---: |
| **[W01]** | Borne `TB1:2` (Entrada L1) | Disjuntor `Q1:1` | Fase 1 220V entrada geral | $1.5\text{ mm}^2$ | Preto | Ilhós 1.5mm² |
| **[W02]** | Borne `TB2:2` (Entrada L2/N) | Disjuntor `Q1:3` | Fase 2 / Neutro 220V entrada | $1.5\text{ mm}^2$ | Azul-claro | Ilhós 1.5mm² |
| **[W03]** | Borne `TB3:2` (Entrada PE) | Barramento Terra `PE` (DIN) | Aterramento geral de proteção | $1.5\text{ mm}^2$ | Verde/Amarelo | Ilhós 1.5mm² |
| **[W04]** | Disjuntor `Q1:2` (Saída L1) | Derivação: `K1:1L1` e `F1:1` | Fase L1 protegida por Q1 | $1.5\text{ mm}^2$ | Preto | Ilhós Duplo 2x1.5mm² |
| **[W05]** | Disjuntor `Q1:4` (Saída L2) | Derivação: `K1:3L2` e `F2:1` | Fase L2 protegida por Q1 | $1.5\text{ mm}^2$ | Azul-claro | Ilhós Duplo 2x1.5mm² |
| **[W06]** | Contator `K1:2T1` (Saída L1) | Dimmer `DIM1:L_IN` | Fase comutada pelo contator | $1.5\text{ mm}^2$ | Preto | Ilhós 1.5mm² |
| **[W07]** | Contator `K1:4T2` (Saída L2) | Dimmer `DIM1:N_IN` | Neutro comutado por K1 | $1.5\text{ mm}^2$ | Azul-claro | Ilhós 1.5mm² |
| **[W08]** | Dimmer `DIM1:L_OUT` | Borne `TB4:1` (Saída Motor L) | Fase regulada (0-220V) para motor | $1.5\text{ mm}^2$ | Preto | Ilhós 1.5mm² |
| **[W09]** | Dimmer `DIM1:N_OUT` | Borne `TB5:1` (Saída Motor N) | Retorno neutro regulado | $1.5\text{ mm}^2$ | Azul-claro | Ilhós 1.5mm² |
| **[W10]** | Borne `TB4:2` (Saída Painel) | Motor `M1:Fase` (Chicote ext.) | Alimentação fase motor bancada | $1.5\text{ mm}^2$ | Preto (Cabo PP) | Ilhós / Faston motor |
| **[W11]** | Borne `TB5:2` (Saída Painel) | Motor `M1:Neutro` (Chicote ext.) | Retorno neutro motor bancada | $1.5\text{ mm}^2$ | Azul (Cabo PP) | Ilhós / Faston motor |
| **[W12]** | Borne `TB6:2` (Saída Terra) | Motor `M1:Carcaça PE` | Aterramento carcaça do motor | $1.5\text{ mm}^2$ | Verde/Amarelo | Terminal Olhal M4/M5 |
| **[W13]** | Barramento Terra `PE` (DIN) | Borne `TB6:1` | Interligação PE painel ao borne motor | $1.5\text{ mm}^2$ | Verde/Amarelo | Ilhós 1.5mm² |
| **[W14]** | Porta-Fusível `F1:2` (Saída 2A) | Fonte `T1:L` (Pino 1) | Alimentação AC L da fonte 24V | $0.75\text{ mm}^2$ | Preto | Ilhós 0.75mm² |
| **[W15]** | Porta-Fusível `F2:2` (Saída 2A) | Fonte `T1:N` (Pino 2) | Alimentação AC N da fonte 24V | $0.75\text{ mm}^2$ | Azul-claro | Ilhós 0.75mm² |
| **[W16]** | Fonte `T1:PE` (Pino 3) | Barramento Terra `PE` (DIN) | Aterramento carcaça da fonte DIN | $0.75\text{ mm}^2$ | Verde/Amarelo | Ilhós 0.75mm² |
| **[W17]** | Fonte `T1:+V` (Pino 4, +24V) | Borne Distribuição `XT1.1:1` | Alimentação principal barramento +24V | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W18]** | Fonte `T1:-V` (Pino 5, 0V) | Borne Distribuição `XT2.1:1` | Retorno principal barramento 0V | $0.75\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.75mm² |
| **[W19]** | Borne Distribuição `XT1.2:1` | Emergência `S0:11` (NF entrada) | Alimentação cadeia de segurança NR-12 | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W20]** | Borne Distribuição `XT1.3:1` | Emergência `S0:23` (NA entrada) | Alimentação sinal alarme emergência | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W21]** | Borne Distribuição `XT1.4:1` | Sinaleiro Branco `H1:X1` (+) | Alimentação LED Standby (Painel ON) | $0.5\text{ mm}^2$ | Vermelho / Branco | Ilhós 0.5mm² |
| **[W22]** | Emergência `S0:12` (NF saída) | Microswitch `S_TAMPA:COM` | Série de segurança porta/tampa | $0.75\text{ mm}^2$ | Vermelho | Faston isolado 6.3mm |
| **[W23]** | Microswitch `S_TAMPA:NC` (NF) | Botão Desliga `S_OFF:1` (NF) | Série de segurança para porta painel | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W24]** | Botão Desliga `S_OFF:2` (NF) | Nó de Comando `(212, 112)` | Saída do botão desliga | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W25]** | Nó de Comando `(212, 112)` | Botão Liga `S_ON:3` (NA entrada) | Entrada do pulso liga | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W26]** | Nó de Comando `(212, 112)` | Contator `K1_SELO:13` (NA) | Alimentação contato de auto-retenção | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W27]** | Botão Liga `S_ON:4` (NA saída) | Nó Bobina `(212, 136)` | Pulso liga para bobina | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W28]** | Contator `K1_SELO:14` (NA) | Nó Bobina `(212, 136)` | Fechamento do selo de retenção | $0.75\text{ mm}^2$ | Vermelho | Ilhós Duplo 2x0.75mm² |
| **[W29]** | Nó Bobina `(212, 136)` | Contator `K1:A1` (+ Bobina 24V) | Alimentação positiva da bobina K1 | $0.75\text{ mm}^2$ | Vermelho | Ilhós 0.75mm² |
| **[W30]** | Nó Bobina `(212, 136)` | Sinaleiro Verde `H2:X1` (+) | Paralelo: LED "Motor Operando" | $0.5\text{ mm}^2$ | Vermelho | Ilhós 0.5mm² |
| **[W31]** | Contator `K1:A2` (- Bobina 0V) | Borne Distribuição `XT2.2:1` | Retorno 0V bobina do contator | $0.75\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.75mm² |
| **[W32]** | Sinaleiro Verde `H2:X2` (-) | Borne Distribuição `XT2.3:1` | Retorno 0V sinaleiro motor | $0.5\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.5mm² |
| **[W33]** | Sinaleiro Amarelo `H3:X2` (-) | Borne Distribuição `XT2.4:1` | Retorno 0V sinaleiro tampa | $0.5\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.5mm² |
| **[W34]** | Sinaleiro Vermelho `H4:X2` (-) | Borne Distribuição `XT2.5:1` | Retorno 0V sinaleiro emergência | $0.5\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.5mm² |
| **[W35]** | Sinaleiro Branco `H1:X2` (-) | Borne Distribuição `XT2.6:1` | Retorno 0V sinaleiro painel energizado | $0.5\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.5mm² |
| **[W36]** | Microswitch `S_TAMPA:NO` (NA) | Sinaleiro Amarelo `H3:X1` (+) | Sinal de alerta "Tampa Aberta" | $0.5\text{ mm}^2$ | Amarelo / Laranja | Faston / Ilhós 0.5mm² |
| **[W37]** | Emergência `S0:24` (NA saída) | Sinaleiro Vermelho `H4:X1` (+) | Sinal de alarme "Emergência Acionada" | $0.5\text{ mm}^2$ | Vermelho | Ilhós 0.5mm² |
| **[W38]** | Borne Distribuição `XT1.4` (+24V) | Tacômetro `DISP1:1` (VCC +24V) | Alimentação display digital RPM | $0.5\text{ mm}^2$ | Vermelho | Ilhós 0.5mm² |
| **[W39]** | Borne Distribuição `XT2.6` (0V) | Tacômetro `DISP1:2` (GND 0V) | Retorno terra 0V display RPM | $0.5\text{ mm}^2$ | Azul-escuro / Preto | Ilhós 0.5mm² |
| **[W40]** | Tacômetro `DISP1:4` (+V Sensor) | Sensor Hall `SEN_HALL:1` (Marrom)| Alimentação +24V sensor magnético M12 | $0.5\text{ mm}^2$ | Marrom | Terminal Prensa / Ilhós |
| **[W41]** | Tacômetro `DISP1:3` (Signal In) | Sensor Hall `SEN_HALL:2` (Preto) | Sinal de pulso rotação NPN coletor aberto | $0.5\text{ mm}^2$ | Preto | Terminal Prensa / Ilhós |
| **[W42]** | Tacômetro `DISP1:5` (GND Sensor) | Sensor Hall `SEN_HALL:3` (Azul) | Retorno 0V GND sensor magnético M12 | $0.5\text{ mm}^2$ | Azul | Terminal Prensa / Ilhós |

---

## 7. Recomendações Críticas de Montagem e Segurança (NR-10 & NR-12)

> [!IMPORTANT]
> 1. **Separação Galvânica e Fiação em Canaletas:**
>    - Nunca passe cabos de potência 220V AC junto com os cabos de sinal do sensor Hall ou de comunicação da sonda IoT na mesma canaleta ranhurada. Mantenha canaletas separadas para evitar indução eletromagnética e ruído de leitura no tacômetro.
>
> 2. **Espaguete Espiral na Porta do Painel:**
>    - Toda a fiação flexível que vai da placa de montagem para a tampa frontal articulada (botão de emergência, botão duplo, dimmer e tacômetro) deve ser protegida por espaguete espiral plástico para evitar desgaste por abrasão mecânica nas dobradiças.
>
> 3. **Aterramento Equipotencial da Placa Metálica e da Porta:**
>    - A placa de montagem de fundo e a porta de chapa de aço devem ser aterradas ao borne PE através de uma cordoalha de cobre flexível de $2.5\text{ mm}^2$.
>
> 4. **Ação Positiva de Segurança:**
>    - Se a tampa de acrílico for levantada durante a operação do motor, o contato NF do microswitch se abre instantaneamente, derrubando a retenção da bobina do contator em menos de $15\text{ ms}$. Mesmo se a tampa for fechada novamente, o motor **NÃO** religa sozinho (exige um novo aperto deliberado no botão verde de Liga), cumprindo a exigência de proteção contra partida intempestiva da NR-12.
