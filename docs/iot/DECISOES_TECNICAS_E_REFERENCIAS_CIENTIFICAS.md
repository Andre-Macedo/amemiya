# Amemiya IoT: Decisões Técnicas e Referencial Científico

Este documento consolida a revisão sistemática da literatura científica realizada com auxílio do **Sci-Bot**, correlacionando os artigos analisados com as decisões arquiteturais de hardware, firmware, processamento de sinais e Machine Learning adotadas no ecossistema **Amemiya Industrial IoT**.

---

## 1. Visão Geral da Arquitetura Desenvolvida

O sistema Amemiya adota uma topologia de sensoriamento multi-modal e computação híbrida (*Edge-to-Cloud*):

1. **Camada de Sensoriamento Físico (Sonda Cartucho / Mancal):**
   * Acelerômetro 3D MEMS (ADXL345 / MPU-6050 via I2C).
   * Microfone Acústico Digital I2S (INMP441 de alta resolução).
   * Termômetro Digital de Contato 1-Wire (DS18B20 TO-92).
   * Conexão industrial blindada via conector circular **GX12 de 6/8 vias**.
2. **Camada de Borda (Nó Principal ESP32-S3):**
   * Entrada dedicada para Tacômetro de Efeito Hall (KY-003) para captura de pulsos de rotação por interrupção de hardware (GPIO com captura por timer).
   * Entrada analógica para Alicate Amperométrico (SCT-013) com resistor de carga (*burden*) e amostragem ADC com amostragem síncrona.
   * Rádio LoRa (E220-900T22D) e Wi-Fi dual-core.
   * Processamento de borda: Extração de vetores estatísticos temporais (RMS, Kurtose, Crista, Skewness), bandas espectrais FFT e severidade ISO 20816 / ISO 10816.
3. **Camada de Inteligência e Nuvem (ML Microservice & Backend Laravel):**
   * Microserviço Python FastAPI com modelo ensemble (XGBoost / Random Forest) e detecção de novidade não supervisionada (Autoencoder / Isolation Forest).
   * Pipeline de ingestão MQTT via broker Mosquitto e ponte assíncrona Laravel Queue + Redis.
   * Visualização em tempo real via WebSockets (Laravel Reverb) e monitoramento de anomalias.

---

## 2. Análise Crítica dos Artigos Científicos e Racional de Engenharia

Abaixo, detalha-se cada estudo referenciado pelo Sci-Bot, o resumo de suas descobertas, o que foi incorporado ao Amemiya e as justificativas técnicas para o que foi adaptado ou descartado.

---

### Artigo 1: Sharma et al. (2025)
* **Título:** *MEMS Approach for Rolling Bearing Fault Diagnosis Using Vibration Signal Analysis*
* **Periódico:** *Journal of Vibration Engineering & Technologies*, vol. 13, n. 1.
* **DOI:** `10.1007/s42417-024-01730-4`
* **Resumo:**
  Compara acelerômetros MEMS de baixo custo (ADXL335, banda de 550 Hz) com MEMS piezoelétricos de alta frequência (ADXL1002, banda de 21 kHz, $\pm 50\text{g}$) acoplados a um microcontrolador STM32 (84 MHz, ADC 12 bits a 5 kHz) em uma caixa de engrenagens. Demonstrou que falhas em pistas internas/externas de rolamentos (BPFI/BPFO) geram pulsos de impacto de banda larga que excitam frequências de ressonância mecânica muito superiores a 2 kHz. O fator de crista subiu de 4.2 para 12.6 e a curtose de 2.2 para 8.6 na presença de defeitos incipientes.
* **O que estamos usando:**
  * Vetor de atributos no domínio do tempo baseado em **RMS**, **Curtose** e **Fator de Crista**.
  * Fórmulas analíticas de frequências de defeito de rolamento (BPFO, BPFI, BSF, FTF) e monitoramento de bandas harmônicas moduladas ($f = m \cdot f_i \pm n \cdot f_r$).
* **O que adaptamos / não adotamos e justificativa:**
  * *Não adotamos o ADXL1002 de 21 kHz na primeira versão:* Sensores MEMS piezoelétricos de 21 kHz (como ADXL1002) são analógicos e custam significativamente mais caro por canal (além de exigir circuitos de condicionamento de sinal complexos e ADCs dedicados de alta velocidade).
  * *Nossa adaptação:* Mantivemos o **ADXL345 / MPU-6050** para as frequências mecânicas baixas e médias ($< 1.6\text{ kHz}$), e compensamos a lacuna de alta frequência adicionando o **microfone digital MEMS INMP441 (I2S)**, que opera com taxa de amostragem acústica de até $44.1 - 48\text{ kHz}$, capturando os impactos de alta frequência do rolamento a uma fração do custo ($<\text{US\$ 2.00}$).

---

### Artigo 2: Pedotti, Zago & Fruett (2017)
* **Título:** *Fault diagnostics in rotary machines through spectral vibration analysis using low-cost MEMS devices*
* **Periódico:** *IEEE Instrumentation & Measurement Magazine*, vol. 20, n. 6, pp. 39–44.
* **DOI:** `10.1109/mim.2017.8121950`
* **Resumo:**
  Demonstra que sensores MEMS de baixo custo ($< 1\text{ kHz}$) são altamente eficazes para diagnosticar problemas cinemáticos de baixa frequência em eixos rotativos (desbalanceamento estático/dinâmico, desalinhamento e folga mecânica), com a amplitude da frequência fundamental ($1\times\text{ RPM}$) crescendo linearmente com o acréscimo de massa desbalanceada. Destaca a norma ISO 10816 como baseline de severidade.
* **O que estamos usando:**
  * Uso prioritário do acelerômetro MEMS triaxial para classificação de desbalanceamento e desalinhamentos (horizontal e vertical).
  * Integração da velocidade RMS global em banda larga conforme **ISO 10816 / ISO 20816** como indicador interpretável de severidade (*Health Indicator*).
* **O que adaptamos / não adotamos e justificativa:**
  * O estudo original utilizou aquisição via PC/USB; no Amemiya, a aquisição e o cálculo de RMS/bandas espectrais foram portados diretamente para a borda no microcontrolador ESP32-S3.

---

### Artigo 3: Pagar, Gawde & Sanap (2023)
* **Título:** *Online condition monitoring system for rotating machine elements using edge computing*
* **Periódico:** *Australian Journal of Mechanical Engineering*, vol. 22, n. 5, pp. 984–997.
* **DOI:** `10.1080/14484846.2023.2215493`
* **Resumo:**
  Desenvolveu um nó de borda industrial com microcontrolador STM32F4 (ARM Cortex-M4), acelerômetro piezoelétrico, sensor de corrente PZEM-004T e sensor de temperatura MAX6675. O firmware executa FFT de 1024 a 8192 pontos via CMSIS-DSP, extrai valores RMS e classifica severidade ISO 10816 localmente antes de enviar dados via Modbus/RS-485 para um dashboard em nuvem.
* **O que estamos usando:**
  * Arquitetura multi-modal combinando **vibração + corrente elétrica + temperatura**.
  * Processamento FFT de borda e disparo de alertas baseados em limites de severidade ISO.
* **O que adaptamos / não adotamos e justificativa:**
  * *Não adotamos o barramento RS-485/Modbus:* Cabear RS-485 em ambiente fabril eleva o custo de infraestrutura física.
  * *Nossa adaptação:* Substituímos o STM32F4 pelo **ESP32-S3 dual-core**, utilizando um núcleo exclusivo para amostragem determinística e cálculo de FFT/RMS, e o segundo núcleo para transmissão sem fio híbrida (**Wi-Fi / MQTT + LoRaWAN**), permitindo instalação *plug-and-play* em máquinas sem necessidade de cabos de comunicação até o painel central.

---

### Artigo 4: Al-Haddad et al. (2023)
* **Título:** *Vibration-current data fusion and gradient boosting classifier for enhanced stator fault diagnosis in three-phase PMSMs*
* **Periódico:** *Electrical Engineering*, vol. 106, n. 3, pp. 3253–3268.
* **DOI:** `10.1007/s00202-023-02148-z`
* **Resumo:**
  Investigou a fusão de dados de sensores de vibração e corrente elétrica para diagnosticar curto-circuito entre espiras em motores elétricos. Resultados comparativos:
  * Apenas corrente: **43.4%** de acurácia.
  * Apenas vibração: **74.5%** de acurácia.
  * **Fusão vibração + corrente com Gradient Boosting:** **90.7% de acurácia (AUC = 95.1%)**.
* **O que estamos usando:**
  * **Fusão a nível de características (*Feature-Level Fusion*):** Concatenamos atributos estatísticos e espectrais de vibração com parâmetros de corrente alternada (medidos pelo alicate SCT-013).
  * Uso do algoritmo **XGBoost (Gradient Boosting)** como classificador primário supervisionado no microserviço ML.
* **O que adaptamos / não adotamos e justificativa:**
  * Al-Haddad focou exclusivamente em motores síncronos de ímã permanente (PMSM). Adaptamos o pipeline para motores de indução trifásicos convencionais (gaiola de esquilo), muito mais comuns na indústria em geral.

---

### Artigo 5: Janssens, Loccufier & Van Hoecke (2019)
* **Título:** *Thermal Imaging and Vibration-Based Multisensor Fault Detection for Rotating Machinery*
* **Periódico:** *IEEE Transactions on Industrial Informatics*, vol. 15, n. 1, pp. 434–444.
* **DOI:** `10.1109/tii.2018.2873175`
* **Resumo:**
  Comprovou que a fusão de dados de vibração (acelerômetros a 51.2 kHz) com termografia infravermelha elevou a acurácia diagnóstica de 87% (apenas vibração) e 88% (apenas térmica) para **100%** quando combinados em um modelo Random Forest.
* **O que estamos usando:**
  * A premissa de que a temperatura do mancal/rolamento fornece informação ortogonal e complementar à vibração para distinguir atrito e falta de lubrificação de desalinhamento puramente mecânico.
* **O que adaptamos / não adotamos e justificativa:**
  * *Não adotamos câmeras térmicas infravermelhas:* Câmeras térmicas industriais custam centenas a milhares de dólares por ponto de medição e são inviáveis para nós IoT autônomos de baixo custo.
  * *Nossa adaptação:* Embutimos o sensor digital **DS18B20** diretamente na base metálica usinada/impressa da sonda em contato direto com a carcaça do mancal, obtendo a derivada temporal da temperatura ($\Delta T / \Delta t$) com precisão de $0.0625^\circ\text{C}$ a um custo inferior a R$ 10,00.

---

### Artigo 6: Di Maggio, Brusa & Delprete (2025)
* **Título:** *Novelty Detection in Rotating Machinery: Assessment of Unsupervised Machine Learning Models for Medium-Sized Industrial Bearings*
* **Periódico:** *IEEE ICCAD 2025*, pp. 1–7.
* **DOI:** `10.1109/iccad64771.2025.11099203`
* **Resumo:**
  Aborda a realidade prática das indústrias: **não existem dados rotulados de falha prévios para todas as máquinas**. Modelos supervisionados falham por ausência de classes de defeito no início da operação (*cold-start*). Avaliou Isolation Forest, One-Class SVM, Local Outlier Factor (LOF) e limiares ISO 20816. Concluiu que limiares estáticos ISO geram falso-positivos excessivos; o Isolation Forest possui o maior *recall* (detecta quase todas as anomalias), enquanto o LOF possui a maior precisão e menor taxa de alarmes falsos.
* **O que estamos usando e decisão técnica:**
  * Adoção de uma **Arquitetura Híbrida de ML em Dois Estágios**:
    1. **Fase Fria (*Cold Start* - Não Supervisionada):** Quando o nó é instalado em um novo motor, o sistema aprende o perfil de vibração saudável da máquina durante os primeiros dias de operação contínua utilizando **Autoencoder (erro de reconstrução)** e **Isolation Forest**.
    2. **Fase Quente (*Warm Phase* - Supervisionada):** À medida que anomalias são confirmadas e rotuladas pelos técnicos de manutenção no painel Filament, o classificador **XGBoost especialista** passa a inferir a classe específica de defeito (desbalanceamento, desalinhamento, folga, pista externa).

---

### Artigo 7: Wang & Heyns (2011) e Lu et al. (2019)
* **Títulos:**
  * *Application of computed order tracking, Vold-Kalman filtering and EMD in rotating machine vibration* (`10.1016/j.ymssp.2010.09.003`)
  * *Tacholess Speed Estimation in Order Tracking: A Review With Application to Rotating Machine Fault Diagnosis* (`10.1109/tim.2019.2902806`)
* **Resumo:**
  Em máquinas com velocidade variável (motores acionados por inversores de frequência - VFD), a FFT convencional sofre com "espalhamento espectral" (*spectral smearing*), pois as frequências dos harmônicos se deslocam no tempo. O rastreamento de ordens (*Computed Order Tracking - COT*) reamostra o sinal no domínio angular constante ($\Delta \theta$), fixando as ordens em múltiplos exatos da rotação ($1\times, 2\times, 3\times$).
* **O que estamos usando:**
  * Entrada dedicada de tacômetro no Nó Principal acoplada ao sensor Hall **KY-003** e ao colar magnético modelado em CAD para o eixo do motor.
  * Normalização da rotação instantânea (RPM) para correlacionar os picos de vibração com múltiplos da frequência de rotação fundamental.
* **O que adaptamos / não adotamos e justificativa:**
  * *Não executamos interpolação polinomial pesada de COT diretamente no microcontrolador:* Reamostragem contínua via spline cúbica consome ciclos excessivos de CPU no ESP32.
  * *Nossa adaptação:* O ESP32 calcula o período exato entre pulsos do tacômetro via interrupção de temporizador e calcula o RPM instantâneo. O espectro FFT e os picos de envelope são normalizados em ordens relativas ($f / f_{rot}$) no backend / microserviço ML.

---

### Artigo 8: Katsoulis et al. (2024), Wang et al. (2021) e Tang et al. (2024)
* **Títulos:**
  * *A LoRaWAN Vibration Detection Sensor for IoT Applications* (`10.1109/pacet60398.2024.10497014`)
  * *Efficient Data Reduction at the Edge of Industrial Internet of Things for PMSM Bearing Fault Diagnosis* (`10.1109/tim.2021.3051668`)
  * *Mechanical vibration signal compression based on speech codecs for intelligent manufacturing* (`10.1080/0951192x.2024.2382191`)
* **Resumo:**
  A transmissão de dados brutos de vibração (amostrados a vários kHz) via redes sem fio industriais de longo alcance (LoRaWAN) é inviável energeticamente e estoura as restrições de *Duty Cycle* e tamanho de pacote ($< 51 - 222\text{ bytes}$). Transmitir resumos de características (*Feature Vectors*) resulta em **$\sim 95\%$ de redução no volume de dados** e **$31\%$ de economia de energia na bateria**, sem perda de capacidade diagnóstica.
* **O que estamos usando:**
  * **Estratégia de Telemetria por Vetor de Características:** O nó de borda transmite apenas o vetor condensado de telemetria contendo:
    * RMS triaxial ($X, Y, Z$) e global ($4 \times 4\text{ bytes}$).
    * Curtose e Fator de Crista ($2 \times 4\text{ bytes}$).
    * Energias das principais bandas espectrais ($4 \times 2\text{ bytes}$).
    * Temperatura e RPM ($2 \times 2\text{ bytes}$).
    * RMS de Corrente ($1 \times 4\text{ bytes}$).
    * *Payload* total: $\sim 44\text{ bytes}$, perfeitamente compatível com uma única mensagem LoRaWAN ou pacote MQTT ultra-leve.
  * Transmissão de forma de onda bruta (*Burst Raw Waveform*) fica reservada para diagnóstico sob demanda via Wi-Fi ou sob trigger de anomalia crítica.

---

## 3. Matriz Resumo de Decisões Técnicas

| Módulo / Camada | Técnica da Literatura | Decisão no Amemiya | Justificativa de Engenharia |
|---|---|---|---|
| **Acelerômetro** | Piezoresistivo / Piezo de 21 kHz (Sharma 2025) | MEMS ADXL345 / MPU-6050 + INMP441 | Custo viável para IoT distribuído; áudio digital I2S supre a faixa de alta frequência. |
| **Fusão Sensorial** | Corrente + Vibração (Al-Haddad 2023) | SCT-013 (ADC) + Acelerômetro + Microfone | Permite distinguir falha mecânica de oscilação de carga elétrica. |
| **Temperatura** | Termografia Infravermelha (Janssens 2019) | Termômetro de contato direto DS18B20 | Redução de custo de US$ 500+ para US$ 1,50 mantendo sensibilidade térmica no mancal. |
| **Tacômetro** | Rastreamento de Ordens COT (Wang 2011) | Módulo Hall KY-003 + Interrupção ESP32 | Medição precisa de RPM sem desvio, viabilizando normalização por ordens cinemáticas. |
| **Transmissão** | Streaming de Onda Bruta vs Vetores (Wang 2021) | Vetor de características (44 bytes) | Viabiliza operação por bateria/LoRa com 95% de redução de consumo e dados. |
| **Modelos de ML** | Supervised Only (Roberts 2025) vs Unsupervised (Di Maggio 2025) | Híbrido: Autoencoder/Isolation Forest (Cold) + XGBoost (Warm) | Resolve o problema da ausência de rótulos de falha no início da operação fabril. |

---
*Documento elaborado para fins de governança técnica, auditoria metrológica e embasamento científico do projeto Amemiya.*
