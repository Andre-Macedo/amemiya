# Referências Científicas e Normativas

Esta página centraliza os artigos científicos, normas técnicas e links DOI que fundamentam o processamento de sinais (DSP), o diagnóstico cinemático de rolamentos, a arquitetura IoT de baixo consumo e os modelos de Machine Learning do projeto **Amemiya**.

::: info Acesso aos Textos Completos (PDFs)
Artigos publicados em periódicos fechados (IEEE / Elsevier / Taylor & Francis / Springer) possuem direitos autorais e **não** devem ser redistribuídos diretamente no repositório Git. Utilize os links DOI abaixo para acesso oficial (ou via rede institucional / Portal de Periódicos CAPES). Publicações em acesso aberto (*Open Access*, como *HardwareX*) permitem leitura e download direto no link da editora.
:::

---

## 1. Vibração MEMS, Cinemática de Rolamentos e DSP

| Referência | Periódico / Ano | Contribuição no Projeto Amemiya | Link Oficial (DOI) |
| :--- | :--- | :--- | :--- |
| **Randall, R. B.; Antoni, J.** — *Rolling element bearing diagnostics—A tutorial* | *MSSP*, 2011 | Equações cinemáticas `BPFO`, `BPFI`, `BSF`, `FTF`, modulação por carga e bandas laterais (`BearingKinematicsService`) | [`10.1016/j.ymssp.2010.07.017`](https://doi.org/10.1016/j.ymssp.2010.07.017) |
| **Sharma, A. et al.** — *MEMS Approach for Rolling Bearing Fault Diagnosis Using Vibration Signal Analysis* | *J. Vib. Eng. Technol.*, 2025 | Validação de acelerômetros MEMS de baixo custo com fator de crista, curtose e RMS | [`10.1007/s42417-024-01730-4`](https://doi.org/10.1007/s42417-024-01730-4) |
| **Pedotti, L. A. S.; Zago, R. M.; Fruett, F.** — *Fault diagnostics in rotary machines through spectral vibration analysis using low-cost MEMS devices* | *IEEE Instrum. Meas. Mag.*, 2017 | Diagnóstico de desbalanceamento ($1\times\text{RPM}$), desalinhamento e baseline ISO 10816/20816 | [`10.1109/mim.2017.8121950`](https://doi.org/10.1109/mim.2017.8121950) |
| **ISO 20816-3:2022** — *Mechanical vibration — Measurement and evaluation of machine vibration (Industrial machines > 15 kW)* | *ISO Standard*, 2022 | Classificação normativa de severidade em Zonas A, B, C e D ($V_{RMS}$ em $10\text{--}1000\text{ Hz}$) | [Página da ISO](/explicacoes/norma-iso-20816) |

---

## 2. Estimação de RPM (*Tacholess*) e Rastreamento Harmônico

| Referência | Periódico / Ano | Contribuição no Projeto Amemiya | Link Oficial (DOI) |
| :--- | :--- | :--- | :--- |
| **Lu, S. et al.** — *Tacholess Speed Estimation in Order Tracking: A Review With Application to Rotating Machine Fault Diagnosis* | *IEEE Trans. Instrum. Meas.*, 2019 | Fundamentação da extração espectral de rotação ($1\times\text{RPM}$) sem conexão elétrica de tacômetro ao nó IoT | [`10.1109/tim.2019.2902806`](https://doi.org/10.1109/tim.2019.2902806) |
| **Wang, K. S.; Heyns, P. S.** — *Application of computed order tracking, Vold-Kalman filtering and EMD in rotating machine vibration* | *MSSP*, 2011 | Base teórica para normalização de ordens ($1\times, 2\times, 3\times$) sob variação de velocidade | [`10.1016/j.ymssp.2010.09.003`](https://doi.org/10.1016/j.ymssp.2010.09.003) |

---

## 3. Hardware de Borda, LoRa e Fusão Sensorial

| Referência | Periódico / Ano | Contribuição no Projeto Amemiya | Link Oficial (DOI) |
| :--- | :--- | :--- | :--- |
| **Jakobsen, M. O.** — *Low cost MEMS accelerometer and microphone based condition monitoring sensor, with LoRa* *(Open Access)* | *HardwareX*, 2024 | Referência de arquitetura LoRa + MEMS e alerta sobre contaminação acústica fabril (apoia a [ADR-005](/explicacoes/ADR-005-remocao-inmp441-e-tacometro)) | [`10.1016/j.ohx.2024.e00525`](https://doi.org/10.1016/j.ohx.2024.e00525) |
| **Pagar, N.; Gawde, S.; Sanap, S.** — *Online condition monitoring system for rotating machine elements using edge computing* | *Aust. J. Mech. Eng.*, 2023 | Referência de nó embarcado multimodal com FFT local e severidade ISO (adaptado no Amemiya para vibração + temperatura, sem sensor de corrente na V2) | [`10.1080/14484846.2023.2215493`](https://doi.org/10.1080/14484846.2023.2215493) |
| **Janssens, O.; Loccufier, M.; Van Hoecke, S.** — *Thermal Imaging and Vibration-Based Multisensor Fault Detection for Rotating Machinery* | *IEEE Trans. Ind. Inf.*, 2019 | Comprova complementaridade entre vibração e temperatura no diagnóstico de atrito/lubrificação (`ADXL345` + `DS18B20`) | [`10.1109/tii.2018.2873175`](https://doi.org/10.1109/tii.2018.2873175) |
| **Wang, H. et al.** — *Efficient Data Reduction at the Edge of Industrial Internet of Things for PMSM Bearing Fault Diagnosis* | *IEEE Trans. Instrum. Meas.*, 2021 | Justifica envio de vetor compacto de características em vez de forma de onda bruta via rádio Sub-GHz | [`10.1109/tim.2021.3051668`](https://doi.org/10.1109/tim.2021.3051668) |
| **Katsoulis, S. et al.** — *A LoRaWAN Vibration Detection Sensor for IoT Applications* | *IEEE PACET*, 2024 | Compressão de telemetria vibracional para enlaces LoRa de longo alcance | [`10.1109/pacet60398.2024.10497014`](https://doi.org/10.1109/pacet60398.2024.10497014) |
| **Tang, B. et al.** — *Mechanical vibration signal compression based on speech codecs for intelligent manufacturing* | *Int. J. Comput. Integr. Manuf.*, 2024 | Técnicas de redução de largura de banda em monitoramento vibracional | [`10.1080/0951192x.2024.2382191`](https://doi.org/10.1080/0951192x.2024.2382191) |

---

## 4. Machine Learning (Cold-Start, Novidade e Fusão de Atributos)

| Referência | Periódico / Ano | Contribuição no Projeto Amemiya | Link Oficial (DOI) |
| :--- | :--- | :--- | :--- |
| **Di Maggio, L.; Brusa, E.; Delprete, C.** — *Novelty Detection in Rotating Machinery: Assessment of Unsupervised Machine Learning Models for Medium-Sized Industrial Bearings* | *IEEE ICCAD*, 2025 | Fundamenta a fase fria (*cold-start* não supervisionado com Isolation Forest / Autoencoder) antes do XGBoost rotulado | [`10.1109/iccad64771.2025.11099203`](https://doi.org/10.1109/iccad64771.2025.11099203) |
| **Al-Haddad, L. A. et al.** — *Vibration-current data fusion and gradient boosting classifier for enhanced stator fault diagnosis in three-phase PMSMs* | *Electrical Engineering*, 2023 | Uso de Gradient Boosting (XGBoost) sobre fusão a nível de features (adaptado no Amemiya para vibração triaxial + temperatura `DS18B20`, sem alicate `SCT-013` na V2) | [`10.1007/s00202-023-02148-z`](https://doi.org/10.1007/s00202-023-02148-z) |
