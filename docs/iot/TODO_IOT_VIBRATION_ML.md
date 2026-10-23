# Roadmap Sequencial de Implementação: IoT, Vibração, Metrologia e IA (Amemiya)

Este documento consolida o plano de ação técnico em uma **sequência lógica e cronológica de dependências**, desde a montagem física e calibração dos sensores na bancada até o backend, automação de APIs com **Dedoc Scramble** e documentação pública.

---

## 📚 Base Teórica e Referências Acadêmicas
* **HardwareX (2024)** — *Low cost MEMS accelerometer and microphone based condition monitoring sensor, with LoRa* (M. O. Jakobsen): Valida a arquitetura de baixo custo para mancais industriais. Alerta na pág. 2 que microfones acústicos no ar sofrem severa interferência de ruído de fundo e distância em ambiente fabril real.
* **Auditoria de Decisão Amemiya (2024/2026)** — Avaliação das 500 árvores do modelo especialista real (`modelo_xgboost_especialista.json`): O acelerômetro ADXL345 concentrou **99,70%** da relevância diagnóstica, enquanto o microfone INMP441 respondeu por apenas **0,30%**, justificando sua remoção física em prol de vedação IP65/IP67 e robustez a ruído de chão de fábrica.
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
- [ ] **Montagem dos Componentes na Protoboard / Sonda de 5 Pinos:**
  - [ ] Interligar ESP32-S3 DevKit ao barramento de sensores da sonda do mancal (`+3V3_SW`, `GND`, `SDA`, `SCL`, `1W_DQ` com resistores de pull-up de $4{,}7\text{ k}\Omega$).
  - [ ] Conectar o ADXL345 (I2C: GPIOs 8/9) e a sonda térmica DS18B20 (OneWire: GPIO 10). *(Microfone INMP441 e tacômetro físico KY-003 eliminados com base na auditoria do XGBoost e adoção de RPM virtual).*
  - [ ] Conectar o transceptor LoRa E32-915T20D com os capacitores de desacoplamento ($470\ \mu\text{F}$ eletrolítico + $100\text{ nF}$ cerâmico) no barramento de alimentação.
  - [ ] Ligar os pinos de controle de modo do LoRa: `M0` no GPIO 48 e `M1` no GPIO 45 com resistores de pull-up de $10\text{ k}\Omega$ para $+3\text{V}3$.
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
    - Integrar leitura periódica dos sensores de temperatura de contato dos mancais dianteiro e traseiro via barramento 1-Wire (GPIO 10) com validação de CRC de 8 bits.
- [ ] **Tacholess Speed Estimation (RPM Virtual):**
  - [ ] Cálculo da Real FFT (RFFT) de 1024 pontos para o sinal radial.
  - [ ] Busca do pico de máxima magnitude na banda síncrona $B1$ ($10\text{ Hz}$ a $65\text{ Hz}$).
  - [ ] Interpolação parabólica de 3 pontos nos bins $[k-1, k, k+1]$ para precisão decimal ($\pm 1\text{ RPM}$):
    $$\delta = \frac{A_{k+1} - A_{k-1}}{2(2A_k - A_{k-1} - A_{k+1})}, \quad \text{RPM} = 60 \cdot (k + \delta) \cdot \Delta f$$
- [ ] **Processamento de Sinais na Borda (Edge DSP):**
  - [ ] Extração das features estatísticas e espectrais limpas de vibração: RMS, Kurtose, Fator de Crista e energias em 4 bandas de frequência ($B1, B2, B3, B4$).
  - [ ] Vetor otimizado de features (sem o overhead das 12 colunas de áudio do microfone).
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
  - [ ] Conectar ao Wi-Fi e publicar a telemetria em formato JSON no broker Mosquitto em `leantech.andremacedo.dev.br:1883` nos tópicos `v1/gateways/{id}/nodes/{id}/telemetry`.
  - [ ] Mapear campos reais no payload: `rpm`, `temp_celsius`, `v_bat`, `status_ia`, `confianca`, `rms_global`.
- [ ] **Handshake e Atuadores Locais:**
  - [ ] Escutar tópico de retorno MQTT e controlar os LEDs do painel (Verde = Normal, Amarelo = Em análise, Vermelho = Falha confirmada) e o Buzzer físico.

---

### ETAPA 5: Backend, Fila Assíncrona e Avaliação Metrológica ISO 20816-3
> *Processamento centralizado em nuvem no Laravel 12.*
- [x] **Ponte MQTT $\to$ Redis:**
  - [x] Comando Artisan daemon `iot:mqtt-bridge` ativo escutando o Mosquitto e despachando mensagens para o Redis via `ProcessTelemetryJob`.
- [x] **Conformidade Metrológica ISO 20816-3 (ABNT NBR ISO 20816-3):**
  - [x] Conversão analítica de aceleração para velocidade $v_{RMS} = \frac{a_{RMS} \cdot 9806.65 \cdot 60}{2\pi \cdot \text{RPM}}$ em `ISO20816SeverityService.php`.
  - [x] Classificação estrita nas 4 Zonas de Severidade da norma (Zona A: Excelente, Zona B: Aceitável, Zona C: Alerta, Zona D: Crítico).
  - [x] Gravação estruturada nos logs (`IoTDeviceLog`) com tenant isolation e persistência das zonas.
- [ ] **Módulo de Cinemática de Rolamentos (`BearingKinematicsService`):**
  - [ ] Cadastro de rolamentos industriais (SKF 6204, UCP204, 2206) com $N$ esferas, diâmetro $d$, primitivo $D$ e ângulo $\alpha$.
  - [ ] Cálculo automático em tempo de execução das frequências de falha: BPFO, BPFI, BSF e FTF alimentadas pelo RPM estimado.
- [ ] **Motor de Diagnóstico Prescritivo de Causa-Raiz (IA Especialista + Limites Físicos):**
  - [ ] O modelo XGBoost e as regras espectrais determinam a **Causa-Raiz da Falha** (Desbalanceamento, Desalinhamento, Folga Mecânica, Falha de Rolamento, Falta de Lubrificação).
  - [ ] A **Severidade do Ativo** é sempre atribuída pela norma técnica **ISO 20816-3 (Zonas A, B, C, D)** e pelo limite térmico do fabricante do rolamento (SKF: normal 45-70°C, alerta > 75°C, perigo > 90°C).
  - [ ] Geração de recomendação prescritiva em linguagem natural (ex: *"Severidade: Zona C (ISO 20816-3) | Causa-Raiz: Desbalanceamento de Rotor (1X) | Ação: Programar balanceamento dinâmico em 1 plano"*).
- [ ] **Buffer de Burst Espectral em Redis:**
  - [ ] Armazenar o último espectro bruto completo (FFT X, Y, Z de 1024 bins) em cache Redis (`iot:node:{id}:spectrum:latest`) com TTL de 24h para alimentar o Spectrum Studio do frontend sem onerar o banco relacional.
- [ ] **Handshake Bidirecional com a Borda:**
  - [ ] Envio automático de comando MQTT de retorno (`sendMqttCommand`) para o Gateway atualizar a sinalização física na máquina.
- [ ] **Microserviço de Machine Learning Cloud (FastAPI):**
  - [ ] Auditoria sob demanda: acionar o serviço Python FastAPI apenas quando a confiança da borda for $< 80\%$ ou houver indicação de falha.

---

### ETAPA 6: Telemetria em Tempo Real & Interfaces Web (Next.js Cockpit + Filament Backoffice)
> *Separação arquitetural: Filament 4 para Backoffice administrativo e Next.js para Cockpit Operacional e Spectrum Studio.*
- [x] **WebSockets com Laravel Reverb:**
  - [x] Evento `SensorDataReceived` transmitindo no canal `tenant.{slug}.iot` com nome `sensor.data`.
  - [x] Evento `AnomalyDetected` disparando alertas push imediatos em anomalias ou Zonas ISO C/D.
- [x] **Backoffice Administrativo no Filament 4 (`amemiya`):**
  - [x] Gestão de estações, máquinas, nós IoT, certificados de calibração ISO 17025 e auditoria.
  - [x] Cadastro dos rolamentos instalados nos mancais das máquinas.
- [ ] **Cockpit Preditivo e Spectrum Studio no Next.js (`metrology-sass-front`):**
  - [ ] Dashboard com visão em semáforo (Verde, Amarelo, Vermelho) baseado nas Zonas A/B, C e D da ISO 20816-3.
  - [ ] Componente `SpectrumStudio.tsx` com Apache ECharts (Canvas/WebGL):
    - Sobreposição triaxial simultânea (X: Horizontal, Y: Vertical, Z: Axial) com toggle individual;
    - Seletor de Harmônicos automáticos ($1X, 2X, 3X, 4X, 0.5X$) com cursor arrastável;
    - Linhas tracejadas de frequências de rolamento (BPFO, BPFI, BSF);
    - Seletor de Bandas Laterais (Sideband Picker: $f_c \pm n \cdot 1X$);
    - Comparador de Coletas (+ Comparar Coletas) sobrepondo a linha de base em transparência;
    - Alternância de unidades ($g \leftrightarrow mm/s$, linear $\leftrightarrow$ dB).
  - [ ] Card de Diagnóstico Prescritivo (Causa-raiz identificada pelo XGBoost, Zona ISO 20816-3 e ação recomendada de manutenção).
  - [ ] Gráfico de Tendência de Longo Prazo (30 dias de RMS com faixas de cor ISO 20816, temperatura do mancal e horímetro diário).

---

### ETAPA 7: Documentação Técnica & Arquitetura de 3 Camadas (Dedoc Scramble + Docs-as-Code)
> *Documentação profissional, pública e acessível à banca examinadora, recrutadores e comunidade.*
- [ ] **Camada 1: Portal Público Dedicado com VitePress (Docs-as-Code / Diátaxis):**
  - [ ] Inicializar o VitePress na pasta `docs/` e configurar workflow do GitHub Actions (`deploy-docs.yml`) para build e deploy automatizado no GitHub Pages (`docs.leantech.andremacedo.dev.br`).
  - [ ] Acesso 100% público e aberto (sem barreira de login/senha para a banca examinadora e professores).
  - [ ] **Quadrante 1 — Tutoriais (Comece Aqui — Aprendizado Prático):**
    - [ ] `inicio-rapido-docker.md`: Subir ambiente completo em 3 passos e simular telemetria.
    - [ ] `primeira-bancada-esp32.md`: Guia de conexão física na protoboard e primeiro pacote LoRa.
  - [ ] **Quadrante 2 — Guias Práticos (How-To — Focado em Tarefas Específicas):**
    - [ ] `calibrar-adxl345.md`: Procedimento passo a passo do ensaio estático $\pm 1g$.
    - [ ] `medir-rpm-tacometro.md`: Alinhamento do sensor Hall e debounce.
    - [ ] `emitir-certificado-web.md`: Cadastro metrológico e emissão de laudo PDF com incerteza $U$.
    - [ ] `configurar-broker-mqtt.md`: Pareamento de novos gateways e chaves de segurança.
  - [ ] **Quadrante 3 — Referência Técnica (Consultas Secas & Fatos):**
    - [ ] `pinagem-hardware.md`: Pinout completo do ESP32-S3, LoRa E32 e conector GX16-10.
    - [ ] `payload-lora-binario.md`: Especificação da struct C de 27 bytes (campos, tipos e escalas).
    - [ ] `dicionario-dados-er.md`: DDL do MySQL, tabelas, ULIDs e relacionamentos.
    - [ ] `api-mqtt-telemetria.md`: Estrutura JSON e tópicos do Mosquitto.
  - [ ] **Quadrante 4 — Explicações e Decisões de Arquitetura (ADRs):**
    - [ ] `arquitetura-distribuida.md`: As 5 camadas e desacoplamento assíncrono via Redis.
    - [ ] `tinyml-vs-cloud.md`: Teoria da inferência híbrida em 2 estágios.
    - [ ] `dsp-vibracao-fft.md`: Fundamentação da amostragem a 3200Hz e janelamento Hann.
    - [ ] `norma-iso-20816.md`: Fundamentação física das Zonas A, B, C e D.
    - [ ] `ADR-001-lora-vs-espnow.md`: Justificativa da adoção de LoRa 915 MHz com payload binário.
    - [ ] `ADR-002-deep-sleep-power-gating.md`: Estratégia de sono profundo e chaveamento de alimentação.
    - [ ] `ADR-003-alimentacao-stepup-vs-ldo.md`: Análise de consumo de repouso e escolha do regulador.
- [ ] **Camada 2: Documentação Automatizada de API com Dedoc Scramble:**
  - [ ] Instalar o pacote oficial no Laravel: `composer require dedoc/scramble`.
  - [ ] Configurar rotas da API em `config/scramble.php` para inspecionar rotas `/api/...`, Form Requests e API Resources.
  - [ ] Expor a interface interativa OpenAPI 3.1 / Swagger em `/docs/api` (zero anotações manuais).
  - [ ] Adicionar botão de atalho direto no portal VitePress: `[Explorar API Interativa (Scramble) ->]`.
- [ ] **Camada 3: Catálogo Vivo e Documentação do Front-End (Next.js & Storybook):**
  - [ ] Configurar o build e publicação estática do Storybook (`npm run build-storybook`) como catálogo vivo de componentes em `storybook.leantech.andremacedo.dev.br` ou subpasta dos docs.
  - [ ] Embutir iframes/componentes interativos do Storybook (ex: `ISO20816SeverityGauge.tsx`, medidores e tabelas) dentro das páginas conceituais do VitePress.
  - [ ] Padronizar anotações **TSDoc** (`/** ... */`) nas interfaces de `Props` dos componentes centrais de medição e formulários para auto-documentação na IDE.
  - [ ] Documentar no guia de arquitetura de estado (`03-STATE_AND_DATA.md`) o fluxo reativo entre **TanStack Query** e WebSockets (**Laravel Reverb**).
- [ ] **Camada 4: Integração de Atalhos nos Sistemas (Next.js & Filament):**
  - [ ] **No Frontend SaaS Next.js (`metrology-sass-front`):** Inserir link no Navbar e rodapé (`"Documentação & Ajuda"`) abrindo o portal VitePress em nova aba.
  - [ ] **No Backoffice Filament (`amemiya`):** Registrar item de navegação global com ícone `heroicon-o-book-open` (`"Central de Documentação"`) direcionando para o portal externo.



---

### ETAPA 8: Ensaio Dinâmico na Bancada & Fechamento dos Gerbers KiCad
> *Apresentação final para a banca com dados reais e placa pronta para fabricação.*
- [ ] **Ensaio Dinâmico de Desbalanceamento:**
  - [ ] Ligar o motor elétrico com rotação constante (ex: 1800 RPM) e verificar leitura estável (Zona A/B).
  - [ ] Parafusar arruela de massa excêntrica no disco e verificar se o $1X$ RPM sobe proporcionalmente na telemetria.
  - [ ] Comprovar que a IA na borda detecta o desbalanceamento, atualiza a Zona ISO para C/D, acende o LED vermelho na bancada e dispara o alarme no dashboard.
- [ ] **Fechamento e Produção dos Gerbers (Carrier Board 100% THT):**
  - [ ] Atualizar esquemáticos KiCad (`02_Sensors_AFE.kicad_sch`): remoção definitiva do Jack P2 (SCT-013), microfone INMP441 e tacômetro físico `J_TACHO`.
  - [ ] Consolidar conector da Sonda Externa (`J_PROBE` de 5 pinos THT: `+3V3_SW`, `GND`, `SDA`, `SCL`, `1W_DQ`) com pull-ups de $4{,}7\text{ k}\Omega$.
  - [ ] Manter periféricos de bancada: Buzzer (`J_BUZ`), 3 LEDs de status (`J_LEDS`: Normal, Alerta, Falha) e conector OLED (`J_OLED`).
  - [ ] Executar checagem de regras de design (DRC) e exportar os arquivos Gerber/Drill para fabricação em placa compacta 100% DIP.
