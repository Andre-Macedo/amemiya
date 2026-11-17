# Arquitetura Distribuída em 5 Camadas

Esta seção detalha o projeto de arquitetura de software e hardware em 5 camadas distribuídas, fundamentado no isolamento de responsabilidades e desacoplamento assíncrono via Redis.

---

## 1. Visão Geral das 5 Camadas

```mermaid
flowchart BT
    subgraph L1["Camada 1 · Sensoriamento & DSP na Borda"]
        NODE["ESP32-S3 + TFT 3.2&quot; SPI + Sonda GX16-5 (ADXL345 3,2 kHz + DS18B20)<br/>RFFT Hann · V_RMS ISO 20816-3 · RPM Tacholess · TinyML"]
    end

    subgraph L2["Camada 2 · Concentrador & Enlace Híbrido (ADR-007)"]
        GW["Gateway ESP32 (LoRa 915 MHz 27B ↔ MQTT) &nbsp;|&nbsp; Canal Direto Wi-Fi (Burst Espectral)"]
    end

    subgraph L3["Camada 3 · Mensageria & ML Preditivo"]
        direction LR
        MQTT["Mosquitto TLS 8883<br/>+ Laravel MQTT Bridge"] -->|"QoS 1"| REDIS[("Redis Buffer & Queue<br/>spectrum:latest")]
        REDIS <-->|"36 features"| ML["ML Service (FastAPI)<br/>Isolation Forest + XGBoost + HITL"]
    end

    subgraph L4["Camada 4 · Backend Modular & Banco de Dados"]
        direction LR
        API["Laravel 12 + BCMath<br/>IoT · Metrology · System"] <-->|"Eloquent ORM"| DB[("MySQL 8.0 Multi-Tenant<br/>Séries Temporais & Laudos")]
    end

    subgraph L5["Camada 5 · Apresentação & Governança"]
        UI["Painel Filament 4 · Front-end Next.js (Gráficos Espectrais) · Portal VitePress · App Mobile"]
    end

    L1 -->|"LoRa 915 MHz (27B / Top-K) ou Wi-Fi"| L2
    L2 -->|"v1/gateways/.../telemetry"| L3
    L3 -->|"ProcessTelemetryJob"| L4
    L4 <-->|"HTTPS REST + WebSockets Reverb"| L5

    style L5 fill:#eff6ff,stroke:#2563eb,stroke-width:1.5px,color:#0f172a
    style L4 fill:#f0fdf4,stroke:#16a34a,stroke-width:1.5px,color:#0f172a
    style L3 fill:#faf5ff,stroke:#7c3aed,stroke-width:1.5px,color:#0f172a
    style L2 fill:#fffbeb,stroke:#d97706,stroke-width:1.5px,color:#0f172a
    style L1 fill:#f8fafc,stroke:#334155,stroke-width:1.5px,color:#0f172a
```

---

## 2. Detalhamento de Cada Camada

### Camada 1: Sensoriamento e DSP na Borda
* **Hardware:** Microcontrolador dual-core ESP32-S3 operando com amostragem direta via barramento I2C a 400 kHz.
* **Processamento:** Amostragem síncrona a 3200 Hz nos 3 eixos, janela de Hann, cálculo de FFT, aceleração de pico, fator de crista e velocidade RMS segundo a norma ISO 20816-3.

### Camada 2: Concentrador de Campo (Gateway)
* **Função:** Ponte de rádio para rede IP. Recebe os pacotes via rádio LoRa com modulação Chirp Spread Spectrum (CSS) a 915 MHz, valida o CRC-16 de hardware e converte os bytes em mensagens MQTT JSON estruturadas enviadas ao broker local.

### Camada 3: Mensageria Assíncrona e Inteligência Preditiva
* **Fila Redis:** Desacopla a recepção MQTT da persistência em disco. Evita contenção de conexões no banco de dados durante rajadas de pacotes de dezenas de nós simultâneos.
* **ML Service:** Microserviço em Python (FastAPI + XGBoost) consumindo as features estatísticas pré-calculadas para classificar padrões complexos de defeito em rolamentos (pista externa, interna, elementos rolantes ou desbalanceamento de massa).

### Camada 4: Backend Central e Regras de Negócio (Laravel 12)
* **Modularidade:** Módulos independentes (`Modules/IoT`, `Modules/Metrology`, `Modules/System`) gerenciados via arquitetura modular.
* **Precisão Matemática:** Uso mandatório da extensão `BCMath` com 6 a 8 casas decimais em todas as integrações numéricas e balanço de incerteza da ISO 17025.

### Camada 5: Apresentação e Governança
* **Painel Administrativo:** Filament 4 com componentes reativos Livewire e autenticação granular baseada em papéis (Filament Shield).
* **Push em Tempo Real:** Servidor WebSockets nativo (Laravel Reverb) transmitindo gráficos de tendência e alarmes instantaneamente sem polling HTTP.
