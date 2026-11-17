# Roadmap Sequencial de Implementação: IoT, Vibração, Metrologia e IA (Amemiya)

Este documento consolida o plano de ação técnico em uma **sequência lógica e cronológica de dependências**, desde a montagem física e calibração dos sensores na bancada até o backend, automação de APIs com **Dedoc Scramble** e documentação pública.

---

## 📚 Base Teórica e Referências Acadêmicas
* **HardwareX (2024)** — *Low cost MEMS accelerometer and microphone based condition monitoring sensor, with LoRa* (M. O. Jakobsen): Valida a arquitetura de baixo custo para mancais industriais. Alerta na pág. 2 que microfones acústicos no ar sofrem severa interferência de ruído de fundo e distância em ambiente fabril real.
* **Auditoria de Decisão Amemiya (2024/2026)** — Avaliação das 500 árvores do modelo especialista real (`modelo_xgboost_especialista.json`): O acelerômetro ADXL345 concentrou **99,70%** da relevância diagnóstica, enquanto o microfone INMP441 respondeu por apenas **0,30%** (mantido como **opcional / decisão pendente** em favor de vedação IP54+ e robustez a ruído fabril).
* **Janssens et al. (IEEE Trans. Ind. Inf., 2019)** — *Thermal Imaging and Vibration-Based Multisensor Fault Detection for Rotating Machinery* (DOI: `10.1109/TII.2018.2873175`): Comprova que a fusão entre descritores de vibração do acelerômetro e curva térmica de contato no mancal atinge **100% de acurácia** na detecção de defeitos de rolamentos e falta de lubrificação (atrito metal-metal).
* **Lu et al. (IEEE Trans. Instrum. Meas., 2019)** — *Tacholess Speed Estimation in Order Tracking: A Review With Application to Rotating Machine Fault Diagnosis* (DOI: `10.1109/TIM.2019.2902806`): Artigo canônico que estabelece a eliminação de tacômetros físicos em prol da extração matemática de rotação ($1\times\text{RPM}$) diretamente da aceleração mecânica com interpolação de picos.
* **Peeters, Leclère, Antoni et al. (MSSP, 2019)** — *Review and comparison of tacholess instantaneous speed estimation methods on experimental vibration data* (DOI: `10.1016/j.ymssp.2019.02.031`): Benchmark global de extração de velocidade angular instantânea a partir de acelerômetros.
* **Kibrete et al. (Review, 2024)** — *Vibration-based condition monitoring and fault diagnosis of rotating machinery*: Metanálise com 238 artigos. Estabelece a conformidade com a norma **ISO 20816-3** (velocidade RMS em mm/s) e extração estatística de atributos.
* **ISO 20816-3 & Tractian Benchmark (2024/2026)** — Adoção estrita da norma técnica internacional **ISO 20816-3 (Zonas A, B, C, D)** para classificação normativa de severidade vibratória global, combinada ao modelo especialista para identificação de causa-raiz (desbalanceamento, desalinhamento, folga, rolamento) e estúdio de análise espectral interativo (Tractian: sobreposição triaxial, harmônicos $1X, 2X, 3X, 0.5X$ e marcadores BPFO/BPFI).

---

## 🚀 Roteiro de Execução Sequencial

```
[Etapa 1: Hardware & Protoboard] 
       │
       ▼
[Etapa 2: Calibração Metrológica ISO 17025] 
       │
       ▼
[Etapa 3: Firmware do Nó Sensor (DSP + TinyML + LoRa)] 
       │
       ▼
[Etapa 4: Gateway de Borda (LoRa -> MQTT Mosquitto)] 
       │
       ▼
[Etapa 5: Backend, Redis & Severidade ISO 20816-3] 
       │
       ▼
[Etapa 6: Telemetria em Tempo Real & Dashboard Web] 
       │
       ▼
[Etapa 7: Documentação Técnica (Dedoc Scramble + Docs-as-Code)] 
       │
       ▼
[Etapa 8: Ensaio Dinâmico na Bancada & Fechamento dos Gerbers KiCad]
```

---

### ETAPA 1: Hardware, Bancada Física e Validação Elétrica (Protoboard & Sonda)
> *Pré-requisito para qualquer linha de código: montar e validar o circuito elétrico e a autonomia.*
- [ ] **Montagem dos Componentes (Alinhada à Pinagem Oficial `amemiya_main_node`):**
  - [ ] Interligar ESP32-S3 DevKitC-1 ao conector da sonda (`+3V3_SWITCHED`, `GND`, `I2C_SDA`, `I2C_SCL`, `1W_DQ` com pull-ups de $4{,}7\text{ k}\Omega$, padrão `JST-XH 5P` sem `INMP441` conforme ADR-005).
  - [ ] Conectar o ADXL345 (`I2C`: `GPIO41` SDA / `GPIO42` SCL) e a sonda térmica DS18B20 (`1-Wire`: `GPIO5`). *(O microfone `INMP441` foi removido; `SPI_MOSI` do TFT utiliza `GPIO7`, mantendo `GPIO11` desconectado e `GPIO6`/`GPIO8` livres; o sensor Hall `BG1` fica ligado apenas ao display local da bancada, sem conexão ao ESP32).*
  - [ ] Conectar o transceptor LoRa (`UART`: `GPIO43` TX / `GPIO44` RX) com capacitores de desacoplamento ($470\ \mu\text{F}$ eletrolítico + $100\text{ nF}$ cerâmico) no barramento de alimentação.
  - [ ] Ligar os pinos de controle do LoRa conforme PCB (`M0` no `GPIO38`, `M1` no `GPIO39` com pull-up $10\text{ k}\Omega$ e `AUX` no `GPIO47` — mantendo `GPIO45` livre por ser *strapping pin* de `VDD_SPI`).
  - [ ] Ligar o pino de interrupção `INT1` do ADXL345 à GPIO de RTC do ESP32 (GPIO 3) para habilitar o despertar híbrido por choque mecânico (EXT0).
  - [ ] Implementar chave de corte de alimentação (*Power Gating* via P-MOSFET AO3401A ou transistor PNP em GPIO 21) para desligar a sonda de sensores durante o sono profundo.
- [ ] **Alimentação por Bateria e Medição de Consumo:**
  - [ ] Conectar 2 baterias 18650 em paralelo (1S2P: $3{,}7\text{ V}-4{,}2\text{ V}$) através da chave geral ON/OFF e do regulador LDO de ultrabaixo $I_q$ ($< 25\ \mu\text{A}$).
  - [ ] Montar o divisor de tensão $100\text{ k}\Omega / 100\text{ k}\Omega$ no canal ADC do ESP32 (GPIO 1) para monitorar $V_{\text{bat}}$.
  - [ ] Medir com multímetro em escala de $\mu\text{A}$ o consumo em repouso durante o Deep Sleep (meta: $< 50\ \mu\text{A}$).

---

### ETAPA 2: Calibração Metrológica dos Sensores (ISO 17025 & Rastreabilidade)
> *Pré-requisito para aquisição de dados confiáveis: calibrar e caracterizar os sensores antes do processamento.*
- [ ] **Calibração Estática do Acelerômetro ADXL345 ($\pm 1g$):**
  - [ ] Posicionar o sensor sobre superfície retificada com nível de precisão nas 6 orientações ortogonais ($+X, -X, +Y, -Y, +Z, -Z$).
  - [ ] Calcular os offsets de zero ($Offset_i$) e fatores de escala ($Ganho_i$) para cada eixo.
  - [ ] Gravar os coeficientes de compensação nos registradores de hardware do ADXL345 (`OFSX 0x1E`, `OFSY 0x1F`, `OFSZ 0x20`) ou na Flash NVS.
- [ ] **Validação Metrológica da Estimação de RPM (Tacholess):**
  - [ ] Comparar a rotação calculada matematicamente pelo algoritmo do ESP32 com Tacômetro Óptico Digital calibrado em 5 patamares de velocidade (ex: 900, 1200, 1500, 1800 e 3600 RPM).
  - [ ] Determinar o erro de indicação e a incerteza do algoritmo de interpolação parabólica sub-bin.
- [ ] **Calibração da Sonda de Temperatura DS18B20:**
  - [ ] Comparar com termômetro padrão do laboratório em 3 pontos ($22^\circ\text{C}$, $40^\circ\text{C}$, $60^\circ\text{C}$).
- [ ] **Formalização no Módulo Metrology:**
  - [ ] Cadastrar os sensores como instrumentos sob teste no sistema Amemiya (`Modules/Metrology`).
  - [ ] Lançar as leituras coletadas, calcular a incerteza expandida $U$ ($k=2$) e emitir os Certificados de Calibração com rastreabilidade formal.

---

### ETAPA 3: Firmware do Nó Sensor (Edge DSP + TinyML + LoRa + Deep Sleep)
> *Integração do código embarcado no ESP32-S3 operando com baixo consumo.*
- [ ] **Aquisição e Sensores (ADXL345 a 3.200 Hz & Dual DS18B20):**
  - [ ] **Configuração do Barramento I2C Fast-Mode (400 kHz):**
    - Configurar clock I2C do ESP32 para 400 kHz (`Wire.setClock(400000)`) para reduzir o tempo por byte de 100 µs para 25 µs e evitar saturação do barramento.
  - [ ] **Parametrização dos Registradores do ADXL345 para 3.200 Hz:**
    - `BW_RATE (0x2C) = 0x0F`: Taxa de amostragem de 3.200 Hz ODR com banda analógica de 1.600 Hz (cobre 160% da faixa de 1.000 Hz exigida pela norma ISO 20816-3).
    - `DATA_FORMAT (0x31) = 0x0B`: Modo Full-Resolution (13-bit, 3,9 mg/LSB) e escala de medição $\pm 16\text{g}$.
    - `FIFO_CTL (0x38) = 0x99`: Ativação do modo Stream (`0x80`) com Watermark de 25 amostras (`0x19`), permitindo buffer contínuo em hardware sem perda de dados por jitter de CPU.
    - `POWER_CTL (0x2D) = 0x08`: Habilitação da medição contínua (*Measure = 1*).
  - [ ] **Rotina de Leitura em Bloco (*Burst Read*) da FIFO:**
    - Fazer polling do registrador `FIFO_STATUS (0x39)` e disparar leituras em lote de 6 bytes (`DATAX0` até `DATAZ1`) quando `entries >= 20`.
    - Desempenho: Leitura de bloco de 25 amostras leva $\sim 3,5\text{ ms}$ a cada $8\text{ ms}$, liberando $> 50\%$ da CPU do ESP32 para processamento de sinais e rede.
  - [ ] **Buffer de Janelamento para FFT (1.024 Pontos):**
    - Acumular janelas contínuas de 1.024 pontos nos eixos X, Y e Z para cálculo da Real FFT (RFFT) e extração de RMS, Kurtose e Fator de Crista.
  - [ ] **Aquisição Térmica OneWire (Dual DS18B20):**
    - Integrar leitura periódica dos sensores de temperatura de contato dos mancais dianteiro e traseiro via barramento 1-Wire (`GPIO5` / `1W_DQ`) com validação de CRC de 8 bits.
- [ ] **Tacholess Speed Estimation (RPM Virtual via Acelerômetro — sem `BG1` ligado ao ESP32):**
  - *Contexto físico:* O sensor Hall `BG1` da bancada fica ligado **exclusivamente ao display local** (integração ao sistema prevista apenas via CLP em uma v3).
  - [ ] Calcular RFFT em paralelo com **decimação para baixa frequência** (ex.: filtrar passa-baixa + decimar `3200 Hz` por `8x` para `400 Hz` com `N = 1024`, atingindo $\Delta f \approx 0{,}39\text{ Hz} \approx 23{,}4\text{ RPM/bin}$, em vez de $\Delta f = 3{,}125\text{ Hz} \approx 187{,}5\text{ RPM/bin}$) — ou janela `N >= 4096` — para manter o erro de RPM abaixo da tolerância de `2,5%` da cinemática de rolamentos quando o dimmer `TC1` variar a rotação.
  - [ ] Aplicar **pontuação harmônica (`1X + 2X + 3X`) / HPS** na banda operacional esperada (`400–3800 RPM` / `6,7–63,3 Hz`) em vez de pegar cegamente o maior pico absoluto (evita confundir `2X` por desalinhamento ou ressonância de alta frequência com `1X`).
  - [ ] Aplicar interpolação parabólica de 3 pontos nos bins $[k-1, k, k+1]$ para refinar a frequência estimada ($\pm 1\text{ RPM}$):
    $$\delta = \frac{A_{k+1} - A_{k-1}}{2(2A_k - A_{k-1} - A_{k+1})}, \quad \text{RPM} = 60 \cdot (k + \delta) \cdot \Delta f$$
  - [ ] Enviar `rpm_estimated` (ou `rpm` com `"rpm_source": "accelerometer_firmware"`) + `peaks` (top picos espectrais detectados) no payload para o backend.
- [ ] **Processamento de Sinais na Borda (Edge DSP & ISO 20816-3):**
  - [ ] Calcular **`v_rms` (mm/s)** diretamente no firmware por **integração em banda larga (`10 Hz – 1000 Hz`)** no domínio da frequência ($V(f_k) = A(f_k) / 2\pi f_k$) e enviar no campo `features.v_rms` / `velocity_rms` (evita o fallback monofrequencial `1X` do backend).
  - [ ] Extração das features estatísticas e espectrais limpas de vibração: RMS, Kurtose, Fator de Crista e energias em 4 bandas de frequência ($B1, B2, B3, B4$).
  - [ ] Validação de integridade na coleta antes do empacotamento LoRa: validar CRC do `DS18B20` e descartar sentinelas de erro (`-127.0 °C` / `85.0 °C` no boot), garantindo zero `NaN`/`Inf` e `rms_global >= 0`.
  - [ ] Geração e envio do burst espectral (`peaks` top-K já interpolados + vetor `fft_x`/`fft_y`/`fft_z` com `sample_rate`) via JSON MQTT sob demanda ou em janela periódica para plotagem dos gráficos espectrais no front-end Next.js sem sobrecarregar o link LoRa contínuo.
  - [ ] Vetor otimizado de features (`ADXL345` + `DS18B20`), alinhado ao modelo `ml_service` sem dependências acústicas do `INMP441` (ADR-005).
- [ ] **TinyML Embarcado:**
  - [ ] Inferência local via modelo XGBoost transcompilado em C (`embarcado_xgboost.h`).
  - [ ] Inércia temporal com buffer de 3 amostras consecutivas na memória RTC (`RTC_DATA_ATTR`) para evitar alarmes falsos.
- [ ] **Transmissão LoRa e Gerenciamento de Energia:**
  - [ ] Transmissão serial do LoRa E32-915T20D com pacote binário compacto contendo: status IA, RPM estimado, temperatura do mancal, vibração RMS triaxial e $V_{\text{bat}}$.
  - [ ] Rotina de sono: passar M0 e M1 para nível HIGH (Sleep Mode do E32), desligar o Power Gate dos sensores e chamar `esp_deep_sleep_start()`.
  - [ ] Lógica de despertar híbrido: Timer adaptativo (15 min $\to$ 30 min) + interrupção externa por aceleração crítica no pino `INT1` do ADXL345 (EXT0).

---

### ETAPA 4: Gateway de Borda (LoRa $\to$ Wi-Fi $\to$ Mosquitto MQTT)
> *Recepção no segundo microcontrolador e encaminhamento à infraestrutura de rede.*
- [ ] **Recepção LoRa:**
  - [ ] Configurar UART do ESP32 Gateway para escutar pacotes de 27 bytes do LoRa E32.
  - [ ] Desserializar os dados brutos e validar checksum do pacote.
- [ ] **Enfileiramento e Publicação MQTT:**
  - [ ] Buffer circular seguro em RAM (`TAM_FILA 20` com seção crítica) para desacoplar a recepção LoRa do Wi-Fi.
  - [ ] Conectar ao Wi-Fi e publicar a telemetria em formato JSON via **MQTT TLS (`8883`)** no broker Mosquitto embutindo o certificado raiz (`ca.crt`) na Flash do ESP32 (`WiFiClientSecure`) e autenticando como `gateway_user` (restrito via `acl_file`) nos tópicos `v1/gateways/{id}/nodes/{id}/telemetry`.
  - [ ] Configurar cliente MQTT com **`QoS 1`**, `clean_session = false` e **LWT (*Last Will and Testament*)** para detectar desconexão abrupta em conjunto com o *watchdog* `last_seen_at` (`iot:mark-offline-devices`) no backend.
  - [ ] Mapear campos reais alinhados ao `ProcessTelemetryJob` e `TelemetryPayloadValidator`: `device_id`, `node_id`, `rpm_estimated` (`rpm_source: "accelerometer_firmware"`), `velocity_rms` / `features.v_rms`, `peaks`, `fft_x`/`sample_rate`, `temp_celsius`, `battery_voltage`, `status_ia`, `confianca`, `rms_global`, `timestamp` (NTP sincronizado).
- [ ] **Handshake e Atuadores Locais:**
  - [ ] Escutar o tópico de retorno `v1/gateways/{id}/nodes/{id}/commands` (`set_alarm_state` com `ttl_seconds`) e controlar exclusivamente a sinalização visual/sonora da bancada (`Verde = healthy`, `Amarelo = pending_analysis`, `Vermelho = fault_confirmed`, `Buzzer`), mantendo o acionamento de força isolado na lógica eletromecânica `NR-12`.

---

### ETAPA 5: Backend, Fila Assíncrona e Avaliação Metrológica ISO 20816-3
> *Processamento centralizado em nuvem no Laravel 12.*
- [x] **Ponte MQTT $\to$ Redis:**
  - [x] Comando Artisan daemon `iot:mqtt-bridge` ativo escutando o Mosquitto e despachando mensagens para o Redis via `ProcessTelemetryJob`.
- [x] **Conformidade Metrológica ISO 20816-3 (ABNT NBR ISO 20816-3):**
  - [x] Conversão analítica de aceleração para velocidade $v_{RMS} = \frac{a_{RMS} \cdot 9806.65 \cdot 60}{2\pi \cdot \text{RPM}}$ em `ISO20816SeverityService.php`.
  - [x] Classificação estrita nas 4 Zonas de Severidade da norma (Zona A: Excelente, Zona B: Aceitável, Zona C: Alerta, Zona D: Crítico).
  - [x] Gravação estruturada nos logs (`IoTDeviceLog`) com tenant isolation e persistência das zonas.
- [x] **Módulo de Cinemática de Rolamentos (`BearingKinematicsService`):**
  - [x] Cadastro de rolamentos industriais (SKF 6204, UCP204, SKF 6205, SKF 6304, SKF 2206) com $N$ esferas, diâmetro $d$, primitivo $D$ e ângulo $\alpha$.
  - [x] Cálculo automático em tempo de execução das frequências de falha (`BPFO`, `BPFI`, `BSF`, `FTF`) usando `resolveSpectralRpm()` alimentado pelo RPM medido, `rpm_estimated` do firmware ou estimação harmônica (`peaks`/`fft_x`) com registro de `rpm_source`.
  - [x] Suporte a catálogo extensível/customizado de rolamentos na máquina (`MachineForm` estruturado no Filament em vez de `metadata` livre) e modelagem da regra híbrida: **cinemática como indicação espectral não conclusiva da possível região** (incluindo bandas laterais `BPFI ± 1X`) + **diagnóstico macro `Desgaste / Falha em Rolamento` confirmado via ML ou severidade ISO (`C/D`)**.
- [x] **Motor de Diagnóstico Prescritivo de Causa-Raiz (IA Especialista + Limites Físicos):**
  - [x] O modelo XGBoost e as regras espectrais determinam a **Causa-Raiz da Falha** (Desbalanceamento, Desalinhamento, Folga Mecânica, Falha de Rolamento, Falta de Lubrificação).
  - [x] A **Severidade do Ativo** é sempre atribuída pela norma técnica **ISO 20816-3 (Zonas A, B, C, D)** e pelo limite térmico do fabricante do rolamento (SKF: normal 45-70°C, alerta > 75°C, perigo > 90°C).
  - [x] Geração de recomendação prescritiva em linguagem natural (ex: *"Severidade: Zona C (ISO 20816-3) | Causa-Raiz: Desbalanceamento de Rotor (1X) | Ação: Programar balanceamento dinâmico em 1 plano"*).
- [x] **Buffer de Burst Espectral em Redis:**
  - [x] Armazenar o último espectro bruto completo (FFT X, Y, Z de 1024 bins) em cache Redis (`iot:node:{id}:spectrum:latest`) com TTL de 24h para alimentar o Spectrum Studio do frontend sem onerar o banco relacional.
- [x] **Handshake Bidirecional com a Borda:**
  - [x] Envio automático de comando MQTT de retorno (`sendMqttCommand`) para o Gateway atualizar a sinalização física na máquina.
- [x] **Microserviço de Machine Learning Cloud (FastAPI):**
  - [x] Auditoria sob demanda: acionar o serviço Python FastAPI apenas quando a confiança da borda for $< 80\%$ ou houver indicação de falha.

---

### ETAPA 6: Telemetria em Tempo Real & Interfaces Web (Next.js Cockpit + Filament Backoffice)
> *Separação arquitetural: Filament 4 para Backoffice administrativo e Next.js para Cockpit Operacional e Gráficos Espectrais.*
- [x] **WebSockets com Laravel Reverb:**
  - [x] Evento `SensorDataReceived` transmitindo no canal `tenant.{slug}.iot` com nome `sensor.data`.
  - [x] Evento `AnomalyDetected` disparando alertas push imediatos em anomalias ou Zonas ISO C/D.
- [x] **Backoffice Administrativo no Filament 4 (`amemiya`):**
  - [x] Gestão de estações, máquinas, nós IoT, certificados de calibração ISO 17025 e auditoria.
  - [x] Cadastro dos rolamentos instalados nos mancais das máquinas.
- [x] **Cockpit Preditivo e Gráficos Espectrais no Front-end Next.js (`metrology-sass-front`):**
  - [x] Dashboard com visão em semáforo (Verde, Amarelo, Vermelho) baseado nas Zonas A/B, C e D da ISO 20816-3.
  - [x] Gráficos espectrais (`SpectrumStudio.tsx`) conectados ao endpoint `/api/v1/iot-nodes/{iotNode}/spectrum` e simulador interativo:
    - Sobreposição triaxial simultânea (X: Horizontal, Y: Vertical, Z: Axial) com toggle individual;
    - Seletor de Harmônicos automáticos ($1X, 2X, 3X, 4X, 0.5X$) com cursor;
    - Linhas tracejadas dinâmicas de frequências de rolamento (`BPFO`, `2x BPFO`, `BPFI`, bandas laterais `BPFI ± 1X`, `BSF`, `FTF`);
    - Seletor de Bandas Laterais (Sideband Picker: $f_c \pm n \cdot 1X$);
    - Comparador de Coletas (+ Comparar Coletas) sobrepondo a linha de base em transparência;
    - Alternância de unidades ($g \leftrightarrow mm/s$, linear $\leftrightarrow$ dB).
  - [x] Card de Diagnóstico Prescritivo (Causa-raiz identificada pelo XGBoost, Zona ISO 20816-3 e ação recomendada de manutenção).
  - [x] Gráfico de Tendência de Longo Prazo (30 dias de RMS com faixas de cor ISO 20816, temperatura do mancal e horímetro diário).

---

### ETAPA 7: Documentação Técnica & Arquitetura de 3 Camadas (Dedoc Scramble + Docs-as-Code)
> *Documentação profissional, pública e acessível à banca examinadora, recrutadores e comunidade.*
- [x] **Camada 1: Portal Público Dedicado com VitePress (Docs-as-Code / Diátaxis):**
  - [x] Inicializar o VitePress na pasta `docs/` e configurar workflow do GitHub Actions (`deploy-docs.yml`) para build e deploy automatizado no GitHub Pages (`docs.leantech.andremacedo.dev.br`).
  - [x] Acesso 100% público e aberto (sem barreira de login/senha para a banca examinadora e professores).
  - [x] **Quadrante 1 — Tutoriais (Comece Aqui — Aprendizado Prático):**
    - [x] `inicio-rapido-docker.md`: Subir ambiente completo em 3 passos e simular telemetria.
    - [x] `primeira-bancada-esp32.md`: Guia de conexão física na protoboard e primeiro pacote LoRa.
  - [x] **Quadrante 2 — Guias Práticos (How-To — Focado em Tarefas Específicas):**
    - [x] `calibrar-adxl345.md`: Procedimento passo a passo do ensaio estático $\pm 1g$.
    - [x] `medir-rpm-tacometro.md`: Alinhamento do sensor Hall e debounce.
    - [x] `emitir-certificado-web.md`: Cadastro metrológico e emissão de laudo PDF com incerteza $U$.
    - [x] `configurar-broker-mqtt.md`: Pareamento de novos gateways e chaves de segurança.
  - [x] **Quadrante 3 — Referência Técnica (Consultas Secas & Fatos):**
    - [x] `pinagem-hardware.md`: Pinout completo do ESP32-S3 (`SPI_MOSI` em `GPIO7`, `GPIO11` `NC`), LoRa E32 e conector `J_PROBE`.
    - [x] `payload-lora-binario.md`: Especificação da struct C de 27 bytes (campos, tipos e escalas).
    - [x] `dicionario-dados-er.md`: DDL do MySQL, tabelas, ULIDs e relacionamentos.
    - [x] `api-mqtt-telemetria.md`: Estrutura JSON e tópicos do Mosquitto.
  - [x] **Quadrante 4 — Explicações e Decisões de Arquitetura (ADRs):**
    - [x] `arquitetura-distribuida.md`: As 5 camadas e desacoplamento assíncrono via Redis.
    - [x] `tinyml-vs-cloud.md`: Teoria da inferência híbrida em 2 estágios.
    - [x] `dsp-vibracao-fft.md`: Fundamentação da amostragem a 3200Hz e janelamento Hann.
    - [x] `norma-iso-20816.md`: Fundamentação física das Zonas A, B, C e D.
    - [x] `ADR-001-lora-vs-espnow.md`: Justificativa da adoção de LoRa 915 MHz com payload binário.
    - [x] `ADR-002-deep-sleep-power-gating.md`: Estratégia de sono profundo e chaveamento de alimentação.
    - [x] `ADR-003-alimentacao-stepup-vs-ldo.md`: Análise de consumo de repouso e escolha do regulador.
    - [x] `ADR-004-ihm-tft-spi-vs-oled.md`: Adoção do TFT 3.2" SPI (`ILI9341` + `XPT2046` via `LovyanGFX`) e `SPI_MOSI` em `GPIO7`.
    - [x] `ADR-005-remocao-inmp441-e-tacometro.md`: Remoção definitiva do `INMP441` (I2S) e tacômetro no nó, padronizando sonda de 5 vias.
    - [x] `ADR-006-bancada-experimental-nr12-e-modos-falha.md`: Painel NR-10/NR-12 (sem fusíveis) e indução de desbalanceamento, desalinhamento e rolamento defeituoso.
    - [x] `ADR-007-espectro-hibrido-wifi-e-fallback-lora.md`: Telemetria LoRa 27B vs burst espectral Wi-Fi MQTT e fallback LoRa (Top-12 picos / 0–1000 Hz).
    - [x] `ADR-008-ml-hibrido-isolation-forest-xgboost-hitl.md`: Arquitetura `Isolation Forest` + `XGBoost` + triagem *Human-in-the-Loop* e lacunas pendentes no `ml_service`.
    - [x] `referencias-cientificas.md`: Página centralizada com DOIs dos artigos científicos e normas.
  - [ ] **Governança Contínua da Documentação:**
    - [x] Adicionar checagem de ADR órfão e sincronismo KiCad ↔ `pinagem-hardware.md` em `scripts/test_docs.py` + hooks `pre-push` locais (`amemiya` e `amemiya-hardware`).
    - [ ] **Ativar Branch Protection Rules no GitHub (`Andre-Macedo/amemiya`):** em *Settings → Branches* (`main` e `develop`), marcar *Require status checks to pass before merging* e exigir **`Documentation & Contracts Quality (VitePress + EGM)`** (job `docs-quality` de `.github/workflows/ci.yml`).
- [x] **Camada 2: Documentação Automatizada de API com Dedoc Scramble:**
  - [x] Instalar o pacote oficial no Laravel: `composer require dedoc/scramble`.
  - [x] Configurar rotas da API em `config/scramble.php` para inspecionar rotas `/api/...`, Form Requests e API Resources.
  - [x] Expor a interface interativa OpenAPI 3.1 / Swagger em `/docs/api` (zero anotações manuais).
  - [x] Adicionar botão de atalho direto no portal VitePress: `[Explorar API Interativa (Scramble) ->]`.
- [ ] **Camada 3: Catálogo Vivo e Documentação do Front-End (Next.js & Storybook):**
  - [ ] Configurar o build e publicação estática do Storybook (`npm run build-storybook`) como catálogo vivo de componentes em `storybook.leantech.andremacedo.dev.br` ou subpasta dos docs.
  - [ ] Embutir iframes/componentes interativos do Storybook (ex: `ISO20816SeverityGauge.tsx`, medidores e tabelas) dentro das páginas conceituais do VitePress.
  - [ ] Padronizar anotações **TSDoc** (`/** ... */`) nas interfaces de `Props` dos componentes centrais de medição e formulários para auto-documentação na IDE.
  - [ ] Documentar no guia de arquitetura de estado (`03-STATE_AND_DATA.md`) o fluxo reativo entre **TanStack Query** e WebSockets (**Laravel Reverb**).
- [ ] **Camada 4: Integração de Atalhos nos Sistemas (Next.js & Filament):**
  - [ ] **No Frontend SaaS Next.js (`metrology-sass-front`):** Inserir link no Navbar e rodapé (`"Documentação & Ajuda"`) abrindo o portal VitePress em nova aba.
  - [x] **No Backoffice Filament (`amemiya`):** Registrar item de navegação global com ícone `heroicon-o-book-open` (`"Documentação da API"`) direcionando para `/docs/api`.



---

### ETAPA 8: Ensaio Dinâmico na Bancada & Fechamento dos Gerbers KiCad
> *Apresentação final para a banca com dados reais e placa pronta para fabricação.*
- [ ] **Ensaio Dinâmico de Desbalanceamento:**
  - [ ] Ligar o motor elétrico com rotação constante (ex: 1800 RPM) e verificar leitura estável (Zona A/B).
  - [ ] Parafusar arruela de massa excêntrica no disco e verificar se o $1X$ RPM sobe proporcionalmente na telemetria.
  - [ ] Comprovar que a IA na borda detecta o desbalanceamento, atualiza a Zona ISO para C/D, acende o LED vermelho na bancada e dispara o alarme no dashboard.
- [ ] **Fechamento e Produção dos Gerbers (`amemiya_main_node`):**
  - [x] Atualizar esquemáticos KiCad (`02_Sensors_AFE.kicad_sch` / `03_MCU_ESP32S3.kicad_sch`): remoção definitiva de `J_TACHO` e do `INMP441` (ADR-005), e remapeamento de `SPI_MOSI` para `GPIO7` (`GPIO11` `NC`).
  - [x] Consolidar conector da Sonda Externa (`J_PROBE`: 5 pinos `GX16-5` / `JST-XH 5P` 1-para-1 com `amemiya_probe_tower_dual`) com pull-ups de $4{,}7\text{ k}\Omega$.
  - [ ] Manter periféricos de bancada: Buzzer (`J_BUZ`), 3 LEDs de status (`J_LEDS`: Normal, Alerta, Falha) e conector TFT SPI (`J_TFT` 1x14 — `ILI9341` + `XPT2046`, ADR-004).
  - [ ] Executar checagem de regras de design (DRC) e exportar os arquivos Gerber/Drill para fabricação em placa compacta 100% DIP.

---

### ETAPA 9: Roadmap v3 (Automação em Malha Fechada com CLP / ESP32 PLC Shield)
> *Evolução futura pós-TCC para controle ativo da bancada e integração industrial 24V DC.*
- [ ] **Integração com CLP ou ESP32 com Shield de CLP (24V DC):**
  - [ ] Leitura direta do tacômetro óptico industrial `BG1` via entrada rápida de contador (HSC 24V PNP/NPN) no CLP/Shield para *ground truth* contínuo em malha fechada.
  - [ ] Controle automatizado de velocidade do motor (substituindo o ajuste manual do dimmer `TC1` por saída analógica 0–10V / PWM / inversor de frequência comandado pelo CLP).
  - [ ] Automação de rampas de ensaio (varredura automática em 600, 900, 1200, 1500 e 1750 RPM) para geração de curvas de validação metrológica sem intervenção manual.
