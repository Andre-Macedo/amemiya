# Projeto Elétrico Industrial: Diagramas Unifilar e Multifilar
## Bancada Metrológica de Testes M1 — Sistema Lean Tech

![Render 3D do Painel de Comando Industrial Montado](./painel_comando_3d_render.jpg)

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

O circuito de comando opera em **tensão contínua de segurança (24V DC)**, isolado galvanicamente da rede 220V através da fonte chaveada. Se qualquer dispositivo de segurança abrir (botão de emergência acionado ou tampa acrílica aberta), a bobina do contator desenergiza instantaneamente por gravidade mecânica.

```mermaid
flowchart TD
    classDef dc_pos fill:#7f1d1d,stroke:#ef4444,stroke-width:2px,color:#fff;
    classDef dc_neg fill:#1e1b4b,stroke:#3b82f6,stroke-width:2px,color:#fff;
    classDef safe fill:#991b1b,stroke:#f87171,stroke-width:2px,color:#fff;
    classDef button fill:#064e3b,stroke:#34d399,stroke-width:2px,color:#fff;
    classDef coil fill:#78350f,stroke:#fbbf24,stroke-width:2px,color:#fff;
    classDef white fill:#334155,stroke:#e2e8f0,stroke-width:2px,color:#fff;

    FONTE_POS["Fonte PS1 (+24V DC)<br/>Borne Vermelho"]:::dc_pos
    FONTE_NEG["Fonte PS1 (0V / GND)<br/>Borne Azul"]:::dc_neg

    subgraph SEGURANCA_EMERGENCIA["1. Emergência com Trava (Painel Frontal)"]
        subgraph BLOCO_EMERG_NF["Bloco 1: NF (Série de Segurança)"]
            S0_11["S0: Term. 11 (NF)"]:::safe
            S0_12["S0: Term. 12 (NF)"]:::safe
        end
        subgraph BLOCO_EMERG_NA["Bloco 2: NA (Sinalização de Bloqueio)"]
            S0_23["S0: Term. 23 (NA)"]:::button
            S0_24["S0: Term. 24 (NA)"]:::button
        end
    end

    subgraph SEGURANCA_TAMPA["2. Microswitch da Tampa Acrílica (Bancada)"]
        S1_C["S1: Comum (C)"]:::safe
        S1_NC["S1: Contato NF / Segurança<br/>(Fechado com a tampa abaixada)"]:::safe
        S1_NO["S1: Contato NA / Alarme<br/>(Fecha com a tampa levantada)"]:::ctrl
    end

    subgraph BOTAO_DUPLO_22MM["3. Botão Duplo 22mm Metaltex / EL8425"]
        subgraph SECAO_DESLIGA["Botão Vermelho (0 / Desliga)"]
            S2_11["Term. 11 (NF)"]:::safe
            S2_12["Term. 12 (NF)"]:::safe
        end
        subgraph SECAO_LIGA["Botão Verde (I / Liga)"]
            S2_13["Term. 13 (NA)"]:::button
            S2_14["Term. 14 (NA)"]:::button
        end
    end

    subgraph CONTATOR_SELO_BOBINA["4. Contator Steck K1"]
        K1_13["Contato Auxiliar 13 (NA)"]:::button
        K1_14["Contato Auxiliar 14 (NA)"]:::button
        K1_A1["Bobina K1 (+A1)"]:::coil
        K1_A2["Bobina K1 (-A2)"]:::coil
    end

    subgraph SINALIZACAO["5. Sinalização Visual LED 22mm (Porta do Painel)"]
        H0_LED["Sinaleiro LED Branco H0<br/>'PAINEL ENERGIZADO'"]:::white
        H1_LED["Sinaleiro LED Verde H1<br/>'MOTOR EM MARCHA'"]:::button
        H2_LED["Sinaleiro LED Amarelo H2<br/>'TAMPA ABERTA / ALERTA'"]:::ctrl
        H3_LED["Sinaleiro LED Vermelho H3<br/>'EMERGÊNCIA ACIONADA'"]:::safe
    end

    %% Fiação do Circuito de Segurança em Série
    FONTE_POS -->|"Vermelho 0.75mm²"| S0_11
    S0_12 -->|"Vermelho 0.75mm²"| S1_C
    S1_NC -->|"Vermelho 0.75mm²"| S2_11

    %% Jumper no Bloco do Botão Duplo (12 NF -> 13 NA)
    S2_12 -->|"Jumper Rápido (Preto 0.75mm²)"| S2_13

    %% Paralelo do Selo: 13 do botão -> 13 do contator
    S2_12 -->|"Derivação Selo (Vermelho)"| K1_13

    %% Saída do Pulso Liga: 14 do botão -> Bobina A1
    S2_14 -->|"Comando Liga (Vermelho 0.75mm²)"| K1_A1
    
    %% Retenção: 14 do contator -> Bobina A1
    K1_14 -->|"Retorno de Selo (Vermelho)"| K1_A1

    %% Fechamento do Neutro DC (0V)
    K1_A2 -->|"Azul 0.75mm²"| FONTE_NEG

    %% Lógica do Sinaleiro Branco H0 (Painel Energizado / 24V OK)
    FONTE_POS ==>|"Direto +24V (Branco/Vermelho 0.5mm²)"| H0_LED
    H0_LED -->|"Azul 0.5mm²"| FONTE_NEG

    %% Lógica do Sinaleiro Verde H1 (Motor Ligado)
    K1_A1 -.->|"Em paralelo com A1"| H1_LED
    H1_LED -->|"Azul 0.5mm²"| FONTE_NEG

    %% Lógica do Sinaleiro Amarelo H2 (Tampa Aberta / Alerta)
    S1_C -.->|"Alimentação Comum 24V"| S1_NO
    S1_NO -->|"Contato NA (Fecha ao abrir tampa) - Amarelo/Laranja"| H2_LED
    H2_LED -->|"Azul 0.5mm²"| FONTE_NEG

    %% Lógica do Sinaleiro Vermelho H3 (Emergência Acionada / Perigo)
    FONTE_POS -.->|"Derivação +24V (Vermelho 0.75mm²)"| S0_23
    S0_23 -.->|"Contato NA (Fecha ao travar emergência)"| S0_24
    S0_24 -->|"Linha Sinal Emergência (Vermelho 0.5mm²)"| H3_LED
    H3_LED -->|"Azul 0.5mm²"| FONTE_NEG
```

---

## 5. Circuito do Tacômetro Digital & Sensor Hall

O tacômetro mede com precisão milimétrica a rotação instantânea do eixo motor através da passagem do ímã de neodímio em frente ao sensor Hall:

```mermaid
flowchart LR
    classDef pwr fill:#7f1d1d,stroke:#ef4444,stroke-width:1.5px,color:#fff;
    classDef gnd fill:#1e1b4b,stroke:#3b82f6,stroke-width:1.5px,color:#fff;
    classDef sig fill:#78350f,stroke:#f59e0b,stroke-width:1.5px,color:#fff;
    classDef dev fill:#1e293b,stroke:#64748b,stroke-width:2px,color:#fff;

    subgraph FONTE_24V["Fonte Chaveada PS1"]
        VCC_OUT["+24V DC"]:::pwr
        GND_OUT["0V (GND)"]:::gnd
    end

    subgraph TAC_DISPLAY["Tacômetro Digital 4 Dígitos LED (Painel)"]
        T_VCC["Pin 1: VCC (+12 a 24V)"]:::pwr
        T_GND["Pin 2: GND (0V)"]:::gnd
        T_SIG["Pin 3: Signal IN (Pulso)"]:::sig
    end

    subgraph SENSOR_HALL["Sensor Hall NPN M12 (Bancada Mecânica)"]
        H_BRN["Marrom (VCC +24V)"]:::pwr
        H_BLU["Azul (GND 0V)"]:::gnd
        H_BLK["Preto (Sinal NPN Pulso)"]:::sig
    end

    subgraph EIXO_BANCADA["Eixo Rotativo"]
        MAGNET["Ímã de Neodímio N52<br/>Afixado na polia"]:::dev
    end

    VCC_OUT ==> T_VCC
    VCC_OUT ==> H_BRN

    GND_OUT ==> T_GND
    GND_OUT ==> H_BLU

    H_BLK -->|"Pulso por rotação (NPN Aberto)"| T_SIG
    MAGNET -.->|"Passagem a 2mm do sensor"| SENSOR_HALL
```

---

## 6. Tabela De-Para Definitiva da Fiação (Wiring Schedule)

Esta tabela é o guia prático de bancada para o eletrotécnico crimpar e conectar cada condutor:

| ID Cabo | Origem (De)                           | Destino (Para)                        | Função                             |       Bitola       | Cor Normatizada |  Terminal Recomendado   |
| :-----: | :------------------------------------ | :------------------------------------ | :--------------------------------- | :----------------: | :-------------: | :---------------------: |
| **W01** | Prensa-cabo entrada L                 | Disjuntor Q1: Borne 1                 | Fase 220V Geral                    | $1.5\text{ mm}^2$  |      Preto      |  Ilhós Tubular 1.5mm²   |
| **W02** | Prensa-cabo entrada N                 | Disjuntor Q1: Borne 3                 | Neutro 220V Geral                  | $1.5\text{ mm}^2$  |   Azul-claro    |  Ilhós Tubular 1.5mm²   |
| **W03** | Prensa-cabo entrada PE                | Borne de Aterramento DIN (PE)         | Terra Geral da Rede                | $1.5\text{ mm}^2$  |  Verde/Amarelo  |  Olhal ou Ilhós 1.5mm²  |
| **W04** | Disjuntor Q1: Borne 2                 | Contator K1: Borne 1 (L1)             | Fase Potência Comutada             | $1.5\text{ mm}^2$  |      Preto      |  Ilhós Tubular 1.5mm²   |
| **W05** | Disjuntor Q1: Borne 4                 | Contator K1: Borne 3 (L2)             | Neutro Potência Comutado           | $1.5\text{ mm}^2$  |   Azul-claro    |  Ilhós Tubular 1.5mm²   |
| **W06** | Disjuntor Q1: Borne 2                 | Porta-Fusível F1: Entrada             | Alimentação da Fonte 24V           | $0.75\text{ mm}^2$ |      Preto      |  Ilhós Tubular 0.75mm²  |
| **W07** | Disjuntor Q1: Borne 4                 | Fonte PS1: Terminal N                 | Neutro da Fonte 24V                | $0.75\text{ mm}^2$ |   Azul-claro    |  Ilhós Tubular 0.75mm²  |
| **W08** | Porta-Fusível F1: Saída               | Fonte PS1: Terminal L                 | Fase Protegida por Fusível 2A      | $0.75\text{ mm}^2$ |      Preto      |  Ilhós Tubular 0.75mm²  |
| **W09** | Contator K1: Borne 2 (T1)             | Módulo Dimmer: Terminal IN-L          | Fase Potência para Regulador       | $1.5\text{ mm}^2$  |      Preto      |  Ilhós Tubular 1.5mm²   |
| **W10** | Contator K1: Borne 4 (T2)             | Módulo Dimmer: Terminal IN-N          | Neutro para Regulador              | $1.5\text{ mm}^2$  |   Azul-claro    |  Ilhós Tubular 1.5mm²   |
| **W11** | Dimmer: Terminal OUT-L                | Prensa-cabo Motor: Pino 1             | Fase Regulada Motor (0-220V)       | $1.5\text{ mm}^2$  |      Preto      |  Ilhós Tubular 1.5mm²   |
| **W12** | Dimmer: Terminal OUT-N                | Prensa-cabo Motor: Pino 2             | Retorno Neutro Motor               | $1.5\text{ mm}^2$  |   Azul-claro    |  Ilhós Tubular 1.5mm²   |
| **W13** | Borne Terra DIN (PE)                  | Prensa-cabo Motor: Carcaça            | Aterramento de Carcaça Motor       | $1.5\text{ mm}^2$  |  Verde/Amarelo  |  Ilhós Tubular 1.5mm²   |
| **W14** | Fonte PS1: Borne (+V)                 | Botão Emergência S0: Term. 11         | Alimentação da Cadeia de Segurança | $0.75\text{ mm}^2$ |    Vermelho     |  Ilhós Tubular 0.75mm²  |
| **W15** | Botão Emergência S0: Term. 12         | Microswitch Tampa S1: Comum (C)       | Continuidade de Segurança          | $0.75\text{ mm}^2$ |    Vermelho     | Terminal Faston / Ilhós |
| **W16** | Microswitch Tampa S1: Term. NF        | Botão Duplo S2: Term. 11 (NF Desliga) | Linha Segura para Painel           | $0.75\text{ mm}^2$ |    Vermelho     | Terminal Ilhós 0.75mm²  |
| **W17** | Botão Duplo S2: Term. 12 (NF Desliga) | Botão Duplo S2: Term. 13 (NA Liga)    | Jumper de Retenção no Bloco        | $0.75\text{ mm}^2$ |      Preto      |  Ilhós Duplo 2x0.75mm²  |
| **W18** | Botão Duplo S2: Term. 12 (NF Desliga) | Contator K1: Borne 13 (NA Selo)       | Linha de Alimentação do Selo       | $0.75\text{ mm}^2$ |    Vermelho     |  Ilhós Tubular 0.75mm²  |
| **W19** | Botão Duplo S2: Term. 14 (NA Liga)    | Contator K1: Borne A1 (+ Bobina)      | Comando de Pulso Liga              | $0.75\text{ mm}^2$ |    Vermelho     |  Ilhós Tubular 0.75mm²  |
| **W20** | Contator K1: Borne 14 (NA Selo)       | Contator K1: Borne A1 (+ Bobina)      | Retorno do Selo de Retenção        | $0.75\text{ mm}^2$ |    Vermelho     |  Ilhós Duplo 2x0.75mm²  |
| **W21** | Contator K1: Borne A2 (- Bobina)      | Fonte PS1: Borne (-V / GND)           | Retorno 0V DC da Bobina            | $0.75\text{ mm}^2$ |   Azul-escuro   |  Ilhós Tubular 0.75mm²  |
| **W22** | Contator K1: Borne A1                 | Sinaleiro LED Verde H1 (+)            | Alimentação Sinaleiro "Motor ON"   | $0.5\text{ mm}^2$  |    Vermelho     |  Ilhós Tubular 0.5mm²   |
| **W23** | Sinaleiro LED Verde H1 (-)            | Fonte PS1: Borne (-V / GND)           | Retorno 0V Sinaleiro Verde         | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |
| **W24** | Microswitch Tampa S1: Term. NA        | Sinaleiro LED Amarelo H2 (+)          | Alimentação "Tampa Aberta" (Alerta)| $0.5\text{ mm}^2$  | Amarelo / Laranja|  Ilhós Tubular 0.5mm²   |
| **W25** | Sinaleiro LED Amarelo H2 (-)          | Fonte PS1: Borne (-V / GND)           | Retorno 0V Sinaleiro Amarelo       | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |
| **W26** | Fonte PS1: Borne (+V)                 | Tacômetro TAC1: Pin 1 (VCC)           | Alimentação 24V do Display         | $0.5\text{ mm}^2$  |    Vermelho     |  Ilhós Tubular 0.5mm²   |
| **W27** | Fonte PS1: Borne (-V / GND)           | Tacômetro TAC1: Pin 2 (GND)           | Terra 0V do Display                | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |
| **W28** | Fonte PS1: Borne (+V)                 | Sensor Hall: Fio Marrom (+VCC)        | Alimentação 24V do Sensor Hall     | $0.5\text{ mm}^2$  |     Marrom      |  Ilhós Tubular 0.5mm²   |
| **W29** | Fonte PS1: Borne (-V / GND)           | Sensor Hall: Fio Azul (GND)           | Terra 0V do Sensor Hall            | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |
| **W30** | Sensor Hall: Fio Preto (Sinal NPN)    | Tacômetro TAC1: Pin 3 (Signal)        | Pulso Digital por Rotação          | $0.5\text{ mm}^2$  |      Preto      |  Ilhós Tubular 0.5mm²   |
| **W31** | Fonte PS1: Borne (+V)                 | Botão Emergência S0: Term. 23 (NA)    | Alimentação Bloco Auxiliar Emerg.  | $0.75\text{ mm}^2$ |    Vermelho     |  Ilhós Tubular 0.75mm²  |
| **W32** | Botão Emergência S0: Term. 24 (NA)    | Sinaleiro LED Vermelho H3 (+)         | Alimentação "Emergência Acionada"  | $0.5\text{ mm}^2$  |    Vermelho     |  Ilhós Tubular 0.5mm²   |
| **W33** | Sinaleiro LED Vermelho H3 (-)         | Fonte PS1: Borne (-V / GND)           | Retorno 0V Sinaleiro Vermelho      | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |
| **W34** | Fonte PS1: Borne (+V)                 | Sinaleiro LED Branco H0 (+)           | Alimentação "Painel Energizado"    | $0.5\text{ mm}^2$  |Branco / Vermelho|  Ilhós Tubular 0.5mm²   |
| **W35** | Sinaleiro LED Branco H0 (-)           | Fonte PS1: Borne (-V / GND)           | Retorno 0V Sinaleiro Branco        | $0.5\text{ mm}^2$  |   Azul-escuro   |  Ilhós Tubular 0.5mm²   |

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
