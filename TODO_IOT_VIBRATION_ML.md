# Roadmap de Implementação: Análise de Vibração, IoT e IA Preditiva (Amemiya)

Este documento consolida o plano de ação técnico baseado na revisão da literatura acadêmica recente (2022–2025) arquivada na pasta [`artigos/`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/artigos).

---

## 📚 Base Teórica e Referências Acadêmicas
* **HardwareX (2024)** — *Low cost MEMS accelerometer and microphone based condition monitoring sensor, with LoRa* (M. O. Jakobsen): Valida a arquitetura híbrida de baixo custo (ADXL345 para vibração estrutural < 1.5 kHz + Microfone INMP441 para atrito e emissões acústicas de alta frequência > 5 kHz).
* **Kibrete et al. (Review, 2024)** — *Vibration-based condition monitoring and fault diagnosis of rotating machinery*: Metanálise com 238 artigos. Estabelece a conformidade com a norma **ISO 20816-3** (velocidade RMS em mm/s) e extração estatística de atributos.
* **Jardine et al. (MSSP, 2006)** — *A review on machinery diagnostics and prognostics implementing condition-based maintenance*: Define os princípios de fusão sensorial (Vibração + Temperatura + Corrente) e alerta contra modelos puramente supervisionados em ativos sem histórico de falhas.
* **Brandao & Costa (IFSP / Eletrônica de Potência, 2022)** — *Fault Diagnosis of Rotary Machines Using Machine Learning*: Valida o uso de algoritmos de aprendizado de máquina tabulares com extração via FFT (98,67% de acurácia).
* **Al-Haddad et al. (Electrical Engineering, 2023)**: Comprova que a fusão de **Vibração + Corrente (MCSA)** eleva a acurácia de diagnóstico de **$74{,}5\%$ para $90{,}7\%$** (+16.2% de ganho com Gradient Boosting).
* **Wu et al. (IEEE SDEMPED, 2007)**: Metodologia clássica de MCSA para separar oscilações mecânicas de carga de defeitos de rotor em motores de indução via harmônicos de sequência negativa e bandas laterais de escorregamento.
* **Wang & Heyns (MSSP, 2011) / Lin & Zhao (IEEE, 2014)**: *Computed Order Tracking (COT)* no firmware para normalização angular do espectro sob rotação variável (inversor de frequência).
* **Sci-Bot Compendiums (2025)** — [`artigos/sci_bot_literature_review.txt`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/artigos/sci_bot_literature_review.txt) e [`artigos/sci_bot_multisensor_fusion_review.txt`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/artigos/sci_bot_multisensor_fusion_review.txt): Comprova o **ineditismo da combinação quádrupla/quíntupla** de sensores do Amemiya (ADXL345 + INMP441 + Tacômetro GX12 + Garra SCT-013 + DS18B20).

---

## 🚀 Tarefas de Implementação

### 1. DSP e Processamento de Sinal de Borda (Firmware ESP32-S3)
- [ ] **Arquitetura Dual-Core com Extensões Vetoriais SIMD (Xtensa LX7):**
  - [ ] **Core 0 (Aquisição Contínua via DMA):**
    - Amostragem I2S contínua do microfone acústico INMP441 (44.1 kHz).
    - Amostragem periódica via ADC / Jack P2 da garra de corrente SCT-013 (1 a 2 kHz).
    - Contagem de pulsos por interrupção/PCNT da porta GX12 do tacômetro.
  - [ ] **Core 1 (Processamento Digital de Sinais e Telecom):**
    - Utilizar a biblioteca oficial **`ESP-DSP`** da Espressif com instruções assembly vetoriais de 128 bits para cálculo rápido de FFT real (`dsps_fft2r_fc32_ansi`).
- [ ] **Conformidade Metrológica ISO 20816-3:**
  - [ ] Conversão espectral de aceleração $A(f)$ para velocidade $V(f) = \frac{A(f)}{2\pi f}$.
  - [ ] Cálculo da **Velocidade RMS ($v_{RMS}$ em mm/s)** integrada na faixa de $10\text{ Hz a }1.000\text{ Hz}$.
  - [ ] Classificação nas 4 Zonas de Severidade ISO (A: Nova/Excelente, B: Aceitável, C: Alerta, D: Perigo/Parada).
- [ ] **Computed Order Tracking (COT) com o Tacômetro (GX12-3):**
  - [ ] Amostragem síncrona com interpolação angular baseada nos pulsos do eixo.
  - [ ] Conversão do espectro de Hertz ($Hz$) para Ordens ($X$):
    - Ordem $1{,}0X$: Desbalanceamento mecânico.
    - Ordem $2{,}0X$: Desalinhamento angular/paralelo.
    - Múltiplos de $1X$: Folgas mecânicas estruturais.
- [ ] **Módulo MCSA na Garra de Corrente (Jack P2 - SCT-013):**
  - [ ] Amostragem da corrente alternada do motor (50/60 Hz).
  - [ ] Extração de `current_rms` (corrente de carga), `current_thd` (distorção harmônica) e energia nas bandas laterais de escorregamento $f_{sideband} = (1 \pm 2ks) \cdot f_s$.
- [ ] **Processamento Acústico de Alta Frequência (INMP441):**
  - [ ] Extração do Fator de Crista Acústico e energia em bandas de alta frequência ($5\text{ a }10\text{ kHz}$ e $> 10\text{ kHz}$) para atrito inicial e microfissuras de rolamentos.

---

### 2. Infraestrutura de Rede e Telemetria (LoRaWAN + MQTT)
- [ ] **Transição de Hardware para LoRaWAN Padrão:**
  - [ ] Substituir a previsão do Ebyte E32 por módulo LoRaWAN homologado (RAK3172 / Seeed Wio-E5 via comandos AT ou SX1262 SPI).
  - [ ] Configurar plano de canais brasileiro Anatel (**AU915 / LA915**).
- [ ] **Otimização do Pacote de Telemetria (Airtime):**
  - [ ] Compactar o vetor de features multissensorial em struct binária em C ($\approx 36\text{ a }44\text{ bytes}$).
  - [ ] Taxa periódica configurável (ex: 5 a 15 minutos) com disparo imediato sob alarme ISO 20816 (Zona C/D) ou anomalia acústica.
- [ ] **Ponte Backend (ChirpStack $\to$ Mosquitto $\to$ Laravel):**
  - [ ] Subir container do ChirpStack LoRaWAN Network Server no `docker-compose`.
  - [ ] Configurar a integração MQTT nativa do ChirpStack para publicar os dados decodificados no broker Mosquitto.
  - [ ] Ajustar o comando Artisan `iot:mqtt-bridge` para processar o JSON padronizado do ChirpStack.

---

### 3. Reformulação do Serviço de Machine Learning (`Modules/IoT/ml_service`)
- [ ] **Expansão do Vetor Multissensorial (`FEATURE_NAMES` no `main.py`):**
  - [ ] Vibração ADXL345: `x_rms`, `x_kurtosis`, `x_skewness`, `y_rms`, ..., `z_rms`, `v_rms_iso`.
  - [ ] Acústica INMP441: `mic_rms`, `mic_crest_factor`, `mic_energy_5k_10k`, `mic_energy_above_10k`.
  - [ ] Elétrica SCT-013 (MCSA): `current_rms`, `current_thd`, `current_sideband_energy`.
  - [ ] Cinemática GX12: `shaft_rpm`, `order_1x_energy`, `order_2x_energy`.
- [ ] **Lógica de Desempate Falha Mecânica vs. Falha Elétrica (Wu et al., 2007):**
  - [ ] Vibração elevada com corrente estável $\to$ Diagnóstico: **Falha Mecânica** (Rolamento, Desbalanceamento, Desalinhamento).
  - [ ] Vibração elevada com bandas laterais em $60\text{ Hz} \pm 2s f_s$ $\to$ Diagnóstico: **Falha Eletromecânica** (Barras de rotor quebradas, Excentricidade estator/rotor).
- [ ] **Arquitetura Híbrida em 2 Estágios:**
  - [ ] **Estágio 1 (Cold-Start Não-Supervisionado):** Isolation Forest / Autoencoder treinado nas primeiras 72 horas para gerar *Anomaly Score* (0 a 100%).
  - [ ] **Estágio 2 (Diagnóstico Especialista Supervisionado):** Modelo XGBoost (`modelo_xgboost_especialista.json`) acionado quando houver anomalia para tipificar o defeito.

---

### 4. Ciclo de Aprendizado Contínuo e Feedback Humano (Fase Quente / Human-in-the-Loop)
- [ ] **Interface de Feedback no Filament (Módulo Metrology / IoT):**
  - [ ] Ação de **"Validar Diagnóstico da IA"** no card de Alerta Preditivo ou Ordem de Manutenção.
  - [ ] Modal de fechamento com seleção estruturada de desfecho pelo técnico:
    - *Falha Confirmada:* Seleção da tipologia real (Rolamento BPFO/BPFI, Desbalanceamento, Desalinhamento, Falta de Lubrificação, Falha Elétrica de Rotor).
    - *Falso Positivo:* Justificativa padronizada (Transitório de partida, Choque mecânico externo, Operação em sobrecarga pontual).
    - *Outro Problema:* Reclassificação da falha identificada em campo.
  - [ ] Controle de acesso via Filament Shield (somente perfis técnicos autorizados).
- [ ] **Modelagem e Persistência do Dataset Rotulado Local (Data Flywheel):**
  - [ ] Criar migração no módulo IoT para a tabela `iot_machine_learning_feedbacks`:
    - `id` (ULID)
    - `machine_id` / `device_node_id`
    - `telemetry_snapshot` (JSON contendo o snapshot das features no instante do alerta)
    - `ai_prediction` (hipótese e probabilidade calculadas pela IA)
    - `user_ground_truth` (rótulo real atestado pelo técnico)
    - `validated_by` (ID do usuário responsável)
    - `validated_at` (timestamp da intervenção)
    - `used_in_retraining` (boolean)
- [ ] **Pipeline de Retreinamento Incremental (Worker / MLOps):**
  - [ ] Comando Artisan `iot:ml-retrain-incremental` executado periodicamente quando acumular lote de novos feedbacks confirmados (ex: 20 intervenções).
  - [ ] Retreino incremental do XGBoost via parâmetro `xgb_model`:
    - Preserva o conhecimento prévio do modelo especialista e calibra os pesos para o parque de máquinas local.
    - Marca os registros como `used_in_retraining = true`.
- [ ] **Governança e Versionamento de Modelos:**
  - [ ] Salvar checkpoints versionados dos modelos gerados (`models/modelo_xgb_v1_base.json`, `models/modelo_xgb_v2_tenant_X.json`).
  - [ ] Validação de métricas (F1-Score / Acurácia) em conjunto de teste antes de promover o modelo para produção.
  - [ ] Mecanismo de rollback automático caso o novo modelo aumente falsos positivos.

---

### 5. Validação Experimental e Benchmarking
- [ ] **Benchmarking com Bases Abertas:**
  - [ ] Testar o modelo treinado contra os datasets de referência da literatura:
    - *Case Western Reserve University (CWRU) Bearing Dataset* (Mancais com defeitos reais).
    - *NASA IMS Bearing Dataset* (Degradação contínua *run-to-failure*).
- [ ] **Validação em Bancada Prática:**
  - [ ] Testar a Sonda Torre montada no mancal UCP 204 com motor elétrico trifásico e inversor de frequência.
  - [ ] Simular desbalanceamento (peso no disco) e verificar se o $1X$ RPM sobe proporcionalmente na telemetria.
  - [ ] Simular assimetria de corrente e validar a separação entre falha mecânica e elétrica.
