import os
import re
import shutil
import subprocess
import fitz

BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\docs"
SENAC_DIR = r"C:\Users\andrl\OneDrive - SENAC - SP\Senac\Eng. Computação\SENAC 5º Semestre\Projeto"
LOGO_PATH = os.path.join(SENAC_DIR, "senac_logo.png").replace("\\", "/")
EDGE_PATH = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

def run_edge_pdf(html_path, pdf_path):
    cmd = [
        EDGE_PATH,
        "--headless",
        "--disable-gpu",
        "--no-pdf-header-footer",
        "--run-all-compositor-stages-before-draw",
        "--virtual-time-budget=7000",
        f"--print-to-pdf={pdf_path}",
        html_path
    ]
    subprocess.run(cmd, check=True)

# ==============================================================================
# 1. DIAGRAMA 1: ARQUITETURA DE SOFTWARE EM CAMADAS (PDF INDIVIDUAL)
# ==============================================================================
def generate_diagram_1():
    html_file = os.path.join(BASE_DIR, "temp_diag_arch.html")
    pdf_local = os.path.join(BASE_DIR, "Diagrama_Arquitetura_Software_IoT.pdf")
    pdf_senac = os.path.join(SENAC_DIR, "Diagrama_Arquitetura_Software_IoT.pdf")

    html_content = """<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Diagrama de Arquitetura de Software — IoT 4.0</title>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mermaid.initialize({
                startOnLoad: true,
                theme: 'neutral',
                fontFamily: 'Segoe UI, system-ui, sans-serif',
                flowchart: {
                    useMaxWidth: false,
                    curve: 'basis',
                    nodeSpacing: 20,
                    rankSpacing: 24
                }
            });
        });
    </script>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 8mm 12mm;
            @bottom-right {
                content: "Página 1 de 1";
                font-size: 8pt;
                color: #94a3b8;
            }
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            margin: 0;
            padding: 0;
            color: #0f172a;
            background: #ffffff;
        }

        .header {
            border-bottom: 2.5px solid #004a8f;
            padding-bottom: 5px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .title-box h1 {
            font-size: 15.5pt;
            color: #004a8f;
            margin: 0 0 2px 0;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .title-box .meta {
            font-size: 8.5pt;
            color: #64748b;
            line-height: 1.35;
        }

        .logo {
            max-height: 46px;
            object-fit: contain;
        }

        .diagram-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .diagram-wrapper svg {
            max-height: 248mm;
            width: 100% !important;
        }

        .footer {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            font-size: 7.5pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-box">
            <h1>Diagrama de Arquitetura de Software em Camadas</h1>
            <div class="meta">
                <strong>Projeto Integrador:</strong> Engenharia de Computação — 5º Semestre (SENAC SP)<br>
                <strong>Empresa Parceira:</strong> Metalúrgica Amemiya &nbsp;|&nbsp; <strong>Subsistema:</strong> Monitoramento Preditivo e IoT 4.0
            </div>
        </div>
        <div>
            <img class="logo" src="LOGO_PLACEHOLDER" alt="SENAC">
        </div>
    </div>

    <div class="diagram-wrapper">
        <div class="mermaid">
flowchart TD
    classDef layerTitle fill:#004a8f,stroke:#002b5c,stroke-width:1.5px,color:#ffffff,font-weight:bold,font-size:11.5px;
    classDef compNode fill:#f0f9ff,stroke:#0284c7,stroke-width:1.2px,color:#0f172a,font-size:9.8px;
    classDef busNode fill:#fef3c7,stroke:#d97706,stroke-width:1.2px,color:#78350f,font-size:9.8px;
    classDef dbNode fill:#ecfdf5,stroke:#059669,stroke-width:1.2px,color:#064e3b,font-size:9.8px;

    L1["1. CAMADA DE BORDA — EDGE SENSING E DSP (ESP32-S3)"]:::layerTitle
    S_VIB["ADXL345: Vibração Triaxial (±16g, 3.2 kHz)"]:::compNode
    S_TAC["KY-003: Tacômetro Hall (RPM Eixo)"]:::compNode
    S_MIC["INMP441: Microfone MEMS (I2S Digital)"]:::compNode
    S_TMP["DS18B20: Temperatura Mancal (1-Wire)"]:::compNode
    ESP["ESP32-S3 MCU: FFT 1024 pts • RMS • Kurtose • Sincronização RPM • Deep Sleep"]:::compNode
    LORA_TX["Transmissor LoRa 915 MHz (E32-TTL-100)"]:::busNode

    L2["2. CAMADA DE CONCENTRAÇÃO — GATEWAY DE PAREDE (ESP32)"]:::layerTitle
    LORA_RX["Receptor LoRa 915 MHz"]:::busNode
    GW_MCU["Gateway MCU ESP32 (Alimentação 24V DC Industrial)"]:::compNode
    MQTT_PUB["Cliente MQTT Industrial (Ethernet / Wi-Fi QoS 1)"]:::busNode

    L3["3. CAMADA DE INGESTÃO E MENSAGERIA"]:::layerTitle
    BROKER["Broker MQTT Mosquitto (amemiya/telemetry/{node_id})"]:::busNode
    BRIDGE["Laravel IoT Bridge Daemon (Console php-mqtt Contínuo)"]:::compNode
    REDIS["Fila e Cache Redis (Buffer Não Bloqueante LPUSH)"]:::busNode

    L4["4. CAMADA DE BACKEND E INTELIGÊNCIA ARTIFICIAL (LARAVEL 12 + FASTAPI)"]:::layerTitle
    WORKERS["Laravel Queue Workers (ProcessTelemetryJob)"]:::compNode
    ISO["Motor Normativo ISO 20816 (Zonas A, B, C, D)"]:::compNode
    ML["Microsserviço ML FastAPI (XGBoost e Isolation Forest)"]:::compNode
    DB[("Banco Relacional MySQL (Módulo IoT • Chaves ULID)")]:::dbNode
    REVERB["Laravel Reverb WebSockets (Broadcasting Realtime)"]:::busNode

    L5["5. CAMADA DE APRESENTAÇÃO E INTEROPERABILIDADE"]:::layerTitle
    WEB["Painel Web Filament 4 (Gestão de Dispositivos e MLOps)"]:::compNode
    APP["App Mobile Android React Native (IHM de Bolso e Push Alerts)"]:::compNode
    API["API Pública RESTful v1 Sanctum (Integração ERP / OS)"]:::compNode

    L1 --- S_VIB & S_TAC & S_MIC & S_TMP
    S_VIB & S_TAC & S_MIC & S_TMP --> ESP --> LORA_TX
    LORA_TX ==>|"Pacote Binário LoRa RF"| L2
    L2 --- LORA_RX --> GW_MCU --> MQTT_PUB
    MQTT_PUB ==>|"Tópicos MQTT QoS 1"| L3
    L3 --- BROKER --> BRIDGE --> REDIS
    REDIS ==>|"Jobs Desacoplados"| L4
    L4 --- WORKERS
    WORKERS --> ISO & ML
    ISO & ML --> DB
    ISO & ML --> REVERB
    DB & REVERB ==> L5
    L5 --- WEB & APP & API
        </div>
    </div>

    <div class="footer">
        <span>Metalúrgica Amemiya & SENAC São Paulo • Documentação Técnica de Engenharia</span>
        <span>Diagrama 1 / 2 — Arquitetura de Software</span>
    </div>
</body>
</html>""".replace("LOGO_PLACEHOLDER", f"file:///{LOGO_PATH}")

    with open(html_file, "w", encoding="utf-8") as f:
        f.write(html_content)

    print("Renderizando Diagrama 1 (Arquitetura de Software)...")
    run_edge_pdf(html_file, pdf_local)
    shutil.copy2(pdf_local, pdf_senac)
    print(f" -> Gerado: {pdf_local}")
    print(f" -> Copiado: {pdf_senac}")
    return pdf_local

# ==============================================================================
# 2. DIAGRAMA 2: MODELO ENTIDADE-RELACIONAMENTO DER (PDF INDIVIDUAL)
# ==============================================================================
def generate_diagram_2():
    html_file = os.path.join(BASE_DIR, "temp_diag_er.html")
    pdf_local = os.path.join(BASE_DIR, "Diagrama_Modelo_Banco_de_Dados_ER_IoT.pdf")
    pdf_senac = os.path.join(SENAC_DIR, "Diagrama_Modelo_Banco_de_Dados_ER_IoT.pdf")

    html_content = """<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Diagrama Entidade-Relacionamento (DER) — IoT 4.0</title>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mermaid.initialize({
                startOnLoad: true,
                theme: 'neutral',
                fontFamily: 'Segoe UI, system-ui, sans-serif',
                er: {
                    useMaxWidth: false,
                    fontSize: 12.5
                }
            });
        });
    </script>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 10mm 6mm 10mm;
            @bottom-right {
                content: "Página 1 de 1";
                font-size: 8pt;
                color: #94a3b8;
            }
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            margin: 0;
            padding: 0;
            color: #0f172a;
            background: #ffffff;
        }

        .header {
            border-bottom: 2px solid #004a8f;
            padding-bottom: 4px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .title-box h1 {
            font-size: 14pt;
            color: #004a8f;
            margin: 0 0 2px 0;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .title-box .meta {
            font-size: 8pt;
            color: #64748b;
            line-height: 1.3;
        }

        .logo {
            max-height: 40px;
            object-fit: contain;
        }

        .diagram-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .diagram-wrapper svg {
            max-height: 244mm !important;
            width: 100% !important;
        }

        .footer {
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px solid #cbd5e1;
            font-size: 7.2pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-box">
            <h1>Diagrama Entidade-Relacionamento (DER) — Modelo de Dados IoT</h1>
            <div class="meta">
                <strong>Projeto Integrador:</strong> Engenharia de Computação — 5º Semestre (SENAC SP) &nbsp;|&nbsp; 
                <strong>Banco de Dados:</strong> MySQL Relacional &nbsp;|&nbsp; 
                <strong>Chaves:</strong> ULID (Universally Unique Lexicographically Sortable Identifier)
            </div>
        </div>
        <div>
            <img class="logo" src="LOGO_PLACEHOLDER" alt="SENAC">
        </div>
    </div>

    <div class="diagram-wrapper">
        <div class="mermaid">
erDiagram
    MACHINES ||--o{ IOT_NODES : "monitorada por"
    MACHINES ||--o{ IOT_DEVICE_LOGS : "registra eventos em"
    MACHINES ||--o{ IOT_ML_DATASETS : "associada a"
    MACHINES ||--o{ IOT_ML_BURSTS : "origina amostras em"

    STATIONS ||--o{ IOT_GATEWAYS : "localizado em"

    IOT_GATEWAYS ||--o{ IOT_NODES : "concentra"
    IOT_GATEWAYS ||--o{ IOT_DEVICE_LOGS : "registra eventos em"

    IOT_NODES ||--o{ IOT_SENSOR_DATA : "emite leituras"
    IOT_NODES ||--o{ IOT_DEVICE_LOGS : "gera ocorrencias"
    IOT_NODES ||--o{ IOT_ML_BURSTS : "produz ensaios"

    IOT_DEVICE_LOGS ||--o{ IOT_ML_BURSTS : "triageada para"

    IOT_ML_DATASETS ||--o{ IOT_ML_BURSTS : "agrupa amostras"
    IOT_ML_DATASETS ||--o{ IOT_ML_MODELS : "origina treino de"

    USERS ||--o{ IOT_ML_BURSTS : "valida rotulo de"

    STATIONS {
        ulid id PK
        string name
        string sector
        string description
        timestamps timestamps
    }

    MACHINES {
        ulid id PK
        string code UK
        string name
        string model
        string serial_number
        string status
        timestamps timestamps
    }

    IOT_GATEWAYS {
        ulid id PK
        ulid station_id FK
        string name
        string device_id UK
        string status
        timestamps timestamps
        softDeletes deleted_at
    }

    IOT_NODES {
        ulid id PK
        ulid gateway_id FK
        ulid machine_id FK
        string name
        string node_id UK
        string status
        decimal battery_voltage
        timestamps timestamps
        softDeletes deleted_at
    }

    IOT_SENSOR_DATA {
        ulid id PK
        ulid node_id FK
        bigint msg_id
        int rpm
        decimal rms_global
        decimal rms_x
        decimal rms_y
        decimal rms_z
        decimal kurt_x
        decimal kurt_y
        decimal kurt_z
        decimal mic_rms
        decimal temp_celsius
        json fft_data
        string ml_status
        decimal ml_confidence
        timestamp measured_at
        timestamps timestamps
    }

    IOT_DEVICE_LOGS {
        ulid id PK
        ulid gateway_id FK
        ulid node_id FK
        ulid machine_id FK
        string level
        string event_type
        string iso20816_severity
        string ml_status
        decimal ml_confidence
        int rpm
        decimal rms_global
        json raw_payload
        json features
        string message
        timestamp measured_at
        timestamps timestamps
    }

    IOT_ML_DATASETS {
        ulid id PK
        ulid target_machine_id FK
        string name
        string slug UK
        string type
        string status
        json class_distribution
        int total_samples
        timestamps timestamps
    }

    IOT_ML_BURSTS {
        ulid id PK
        ulid dataset_id FK
        ulid device_log_id FK
        ulid node_id FK
        ulid machine_id FK
        string session_id
        string origin
        int rpm
        decimal rms_global
        string predicted_label
        decimal predicted_confidence
        string ground_truth_label
        boolean is_validated
        ulid validated_by_user_id FK
        timestamp validated_at
        json features_summary
        string burst_storage_path
        timestamps timestamps
    }

    IOT_ML_MODELS {
        ulid id PK
        ulid dataset_id FK
        string name
        string model_type
        string version
        string target_device
        string status
        string artifact_path
        json metrics
        timestamp deployed_at
        timestamps timestamps
    }

    USERS {
        ulid id PK
        string name
        string email UK
        timestamps timestamps
    }
        </div>
    </div>

    <div class="footer">
        <span>Metalúrgica Amemiya & SENAC São Paulo • Documentação Técnica de Banco de Dados</span>
        <span>Diagrama 2 / 2 — Modelo Entidade-Relacionamento (DER)</span>
    </div>
</body>
</html>""".replace("LOGO_PLACEHOLDER", f"file:///{LOGO_PATH}")

    with open(html_file, "w", encoding="utf-8") as f:
        f.write(html_content)

    print("Renderizando Diagrama 2 (Modelo Entidade-Relacionamento DER)...")
    run_edge_pdf(html_file, pdf_local)
    shutil.copy2(pdf_local, pdf_senac)
    print(f" -> Gerado: {pdf_local}")
    print(f" -> Copiado: {pdf_senac}")
    return pdf_local

# ==============================================================================
# 3. TEXTO SEPARADO: ESPECIFICAÇÃO TÉCNICA E DICIONÁRIO DE DADOS (PDF INDIVIDUAL)
# ==============================================================================
def generate_text_specification():
    html_file = os.path.join(BASE_DIR, "temp_text_spec.html")
    pdf_local = os.path.join(BASE_DIR, "Especificacao_Arquitetura_e_Banco_de_Dados_IoT.pdf")
    pdf_senac = os.path.join(SENAC_DIR, "Especificacao_Arquitetura_e_Banco_de_Dados_IoT.pdf")

    html_content = """<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Especificação Técnica de Arquitetura e Dicionário de Dados — IoT 4.0</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 15mm 20mm 15mm;
            @bottom-right {
                content: counter(page);
                font-size: 8.5pt;
                color: #64748b;
            }
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.55;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header-cover {
            border-bottom: 3px solid #004a8f;
            padding-bottom: 12px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title-area h1 {
            font-size: 19pt;
            color: #004a8f;
            margin: 0 0 6px 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header-meta {
            font-size: 9pt;
            color: #64748b;
            line-height: 1.45;
        }

        .header-logo {
            max-height: 56px;
            object-fit: contain;
        }

        h2 {
            font-size: 13.5pt;
            color: #0f2240;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 5px;
            margin-top: 26px;
            margin-bottom: 12px;
            page-break-after: avoid;
        }

        h3 {
            font-size: 11pt;
            color: #004a8f;
            margin-top: 18px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }

        p, li {
            font-size: 9.8pt;
            color: #334155;
            text-align: justify;
        }

        ul, ol {
            margin-top: 5px;
            margin-bottom: 10px;
            padding-left: 22px;
        }

        li {
            margin-bottom: 4px;
        }

        strong {
            color: #0f2240;
        }

        .diagram-ref-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1.5px solid #0284c7;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 14px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            page-break-inside: avoid;
        }

        .diagram-ref-box .icon {
            font-size: 22pt;
            line-height: 1;
        }

        .diagram-ref-box .text {
            font-size: 9.5pt;
            color: #0369a1;
            line-height: 1.4;
        }

        .diagram-ref-box strong {
            color: #0c4a6e;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin: 16px 0 22px 0;
            overflow: hidden;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .table-card-header {
            background-color: #0f2240;
            color: #ffffff;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 10pt;
            display: flex;
            justify-content: space-between;
        }

        .table-card-desc {
            background-color: #f8fafc;
            padding: 6px 12px;
            font-size: 8.8pt;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
        }

        th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-align: left;
            padding: 6px 10px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 8.5pt;
        }

        td {
            padding: 5px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            color: #1e293b;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(even) td {
            background-color: #fcfdfe;
        }

        code {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 8.2pt;
            background-color: #f1f5f9;
            padding: 2px 4px;
            border-radius: 3px;
            color: #0369a1;
            font-weight: 600;
        }

        .badge-pk {
            background: #fee2e2;
            color: #991b1b;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: 700;
        }

        .badge-fk {
            background: #e0e7ff;
            color: #3730a3;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: 700;
        }

        .badge-uk {
            background: #fef3c7;
            color: #92400e;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: 700;
        }

        blockquote {
            border-left: 4px solid #f37021;
            background-color: #fffaf0;
            margin: 12px 0;
            padding: 8px 12px;
            color: #9a3412;
            font-size: 9.2pt;
            page-break-inside: avoid;
        }

        hr {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        .footer-note {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header-cover">
        <div class="header-title-area">
            <h1>Especificação de Arquitetura & Dicionário de Dados</h1>
            <div class="header-meta">
                <strong>Projeto Integrador:</strong> Engenharia de Computação — 5º Semestre (SENAC SP)<br>
                <strong>Empresa Parceira:</strong> Metalúrgica Amemiya &nbsp;|&nbsp; <strong>Subsistema:</strong> Monitoramento Preditivo e IoT 4.0 &nbsp;|&nbsp; <strong>Data:</strong> Outubro / 2026
            </div>
        </div>
        <div>
            <img class="header-logo" src="LOGO_PLACEHOLDER" alt="SENAC Logo">
        </div>
    </div>

    <h2>1. Visão Geral do Sistema IoT</h2>
    <p>
        O subsistema de IoT da plataforma <strong>Amemiya</strong> foi projetado e desenvolvido para executar o monitoramento contínuo da integridade mecânica de máquinas operatrizes (tornos mecânicos, fresadoras e centros de usinagem CNC). A solução coleta variáveis físicas fundamentais (<strong>vibração triaxial de banda larga, rotação por minuto real via efeito Hall, nível de pressão sonora e temperatura de contato dos mancais</strong>), executa descritores estatísticos e processamento espectral diretamente na borda (<em>Edge Computing</em>), transmite os pacotes por telemetria de rádio de longo alcance (<em>LoRa</em>) e orquestra a inferência preditiva de falhas mecânicas em tempo real (classificador <em>XGBoost</em> e motor normativo <em>ISO 20816</em>).
    </p>

    <h2>2. Arquitetura de Software em Camadas</h2>
    <p>
        A arquitetura de software implementa o paradigma de microsserviços e mensageria assíncrona orientada a eventos (<em>Event-Driven Architecture</em>), estruturada em 5 camadas lógicas desacopladas:
    </p>

    <div class="diagram-ref-box">
        <div class="icon">📊</div>
        <div class="text">
            <strong>Diagrama Esquemático em Alta Definição:</strong> O fluxo completo e visual de dados da arquitetura em 5 camadas (Borda ➔ Gateway ➔ Ingestão ➔ Backend/IA ➔ Apresentação) encontra-se renderizado em formato vetorial individual no arquivo complementar <strong><code>Diagrama_Arquitetura_Software_IoT.pdf</code></strong>.
        </div>
    </div>

    <h3>2.1 Detalhamento Técnico das 5 Camadas</h3>
    <ol>
        <li>
            <strong>Camada de Borda (Edge Sensing & DSP):</strong>
            <ul>
                <li><strong>Microcontrolador Central:</strong> ESP32-S3 operando em extrabaixa tensão, energizado por pack de baterias Li-Ion 18650 com circuito de carga integrado e rotinas de <em>Deep Sleep</em> inteligente baseadas no ciclo de corte da máquina.</li>
                <li><strong>Instrumentação e Sensores:</strong>
                    <ul>
                        <li><strong>ADXL345:</strong> Acelerômetro MEMS triaxial operando com faixa dinâmica de ±16g e taxa de amostragem configurada a 3,2 kHz via barramento I2C.</li>
                        <li><strong>KY-003:</strong> Sensor magnético de efeito Hall para captura dos pulsos de passagem do ímã de neodímio fixado no fuso, fornecendo a velocidade angular instantânea (RPM).</li>
                        <li><strong>INMP441:</strong> Microfone acústico MEMS com interface de áudio digital I2S de 24 bits para identificação de ruídos de alta frequência resultantes de atrito metálico seco e cavitação.</li>
                        <li><strong>DS18B20:</strong> Termômetro digital 1-Wire em cápsula metálica para medição de temperatura por contato na carcaça do rolamento do fuso.</li>
                    </ul>
                </li>
                <li><strong>Processamento de Sinal Digital (DSP):</strong> Execução de Transformada Rápida de Fourier (FFT de 1024 pontos) síncrona à velocidade de rotação (Análise de Ordens 1X a 10X RPM), cálculo do valor eficaz triaxial (RMS), Kurtose estatística para pulsos de impacto e Fator de Crista.</li>
                <li><strong>Enlace de Rádio:</strong> Transmissor LoRa 915 MHz (módulo E32-TTL-100) com modulação de espectro espalhado, cobrindo o chão de fábrica sem interferências ou dependência da rede Wi-Fi corporativa.</li>
            </ul>
        </li>
        <li>
            <strong>Camada de Concentração (Gateway de Parede):</strong>
            <ul>
                <li>Dispositivo concentrador alimentado por rede industrial 24V DC, instalado na parede da oficina.</li>
                <li>Recebe pacotes binários LoRa dos nós sensores e converte os dados em mensagens JSON estruturadas, publicando no broker MQTT corporativo com garantia de entrega <em>QoS 1</em> no tópico <code>amemiya/telemetry/{node_id}</code>.</li>
            </ul>
        </li>
        <li>
            <strong>Camada de Ingestão e Mensageria:</strong>
            <ul>
                <li><strong>Broker MQTT Mosquitto:</strong> Servidor de mensageria leve de alto rendimento responsável pelo roteamento dos tópicos industriais.</li>
                <li><strong>Laravel IoT Bridge Daemon:</strong> Processo contínuo em PHP (<code>php artisan iot:mqtt-bridge</code>) executando como serviço de background que consome os tópicos em tempo real sem latência.</li>
                <li><strong>Buffer em Memória Redis:</strong> Desacoplamento não bloqueante que armazena as mensagens em filas em memória (<code>LPUSH</code>), protegendo o banco relacional contra picos de carga.</li>
            </ul>
        </li>
        <li>
            <strong>Camada de Backend & Inteligência (Laravel 12 + FastAPI):</strong>
            <ul>
                <li><strong>Queue Workers Assíncronos:</strong> Processamento em paralelo através de workers dedicados (<code>ProcessTelemetryJob</code>).</li>
                <li><strong>Motor Normativo ISO 20816:</strong> Avaliação paramétrica dos níveis de vibração RMS global, enquadrando o estado operacional em quatro faixas normalizadas: Zona A (equipamento novo), Zona B (operação irrestrita), Zona C (alerta de manutenção preventiva) e Zona D (risco iminente de colapso mecânico / parada mandatória).</li>
                <li><strong>Microsserviço de Machine Learning (FastAPI / XGBoost):</strong> Pipeline em Python que executa inferência supervisionada em milissegundos para classificar assinaturas espectrais de: <em>Saudável, Desbalanceamento, Desalinhamento, Folga Mecânica</em> e <em>Falha em Pista de Rolamento</em>.</li>
                <li><strong>Laravel Reverb (WebSockets):</strong> Servidor nativo de sockets em tempo real que despacha os eventos <code>SensorDataReceived</code> e <code>AnomalyDetected</code> aos clientes conectados com latência inferior a 200 ms.</li>
            </ul>
        </li>
        <li>
            <strong>Camada de Apresentação e Interoperabilidade:</strong>
            <ul>
                <li><strong>Painel Web Administrativo (Filament 4):</strong> Interface administrativa corporativa para gestão de nós, gateways, calibragem dos modelos de IA e governança de dados.</li>
                <li><strong>Aplicativo Mobile Android (React Native / Expo):</strong> Terminal portátil para mecânicos e supervisores com visualização das variáveis de processo em tempo real e recebimento de alertas sonoros e notificações <em>Push</em>.</li>
                <li><strong>API Pública RESTful v1:</strong> Endpoints protegidos por autenticação Sanctum para integração direta com ERPs (SAP/TOTVS) e geração automática de Ordens de Serviço (OS).</li>
            </ul>
        </li>
    </ol>

    <hr>

    <h2>3. Modelo de Banco de Dados Relacional (Módulo IoT)</h2>
    <p>
        O esquema de dados relacional foi desenvolvido para o banco de dados <strong>MySQL</strong>. Todas as entidades adotam identificadores únicos universais cronologicamente ordenáveis (<strong>ULID</strong> — <em>Universally Unique Lexicographically Sortable Identifier</em>) como chave primária (<code>CHAR(26)</code>). Isso proporciona as vantagens de não expor IDs sequenciais previsíveis, viabilizar geração descentralizada e manter altíssima velocidade de indexação em árvores B+ sem a fragmentação típica do UUID v4.
    </p>

    <div class="diagram-ref-box">
        <div class="icon">📐</div>
        <div class="text">
            <strong>Modelo Visual Entidade-Relacionamento (DER):</strong> A estrutura completa de tabelas, chaves primárias/estrangeiras e cardinalidades 1:N encontra-se documentada no diagrama vetorial individual <strong><code>Diagrama_Modelo_Banco_de_Dados_ER_IoT.pdf</code></strong>.
        </div>
    </div>

    <hr>

    <h2>4. Dicionário de Dados Detalhado (Tabelas do Módulo IoT)</h2>

    <!-- TABELA 1: iot_gateways -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_gateways</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Armazena os concentradores físicos de rádio LoRa instalados nas paredes da oficina mecânica.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>station_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Chave estrangeira vinculando ao posto/setor fabril da tabela <code>stations</code>.</td>
                </tr>
                <tr>
                    <td><code>name</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Nome descritivo amigável do gateway concentrador.</td>
                </tr>
                <tr>
                    <td><code>device_id</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td><span class="badge-uk">UK</span> NOT NULL</td>
                    <td>Identificador único transmitido nos pacotes MQTT (ex: <code>gw_oficina_01</code>).</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Valores: <code>'online'</code>, <code>'offline'</code>, <code>'maintenance'</code>.</td>
                </tr>
                <tr>
                    <td><code>created_at</code> / <code>updated_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Carimbos de data/hora de criação e última modificação.</td>
                </tr>
                <tr>
                    <td><code>deleted_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Carimbo de exclusão lógica (<em>Soft Deletes</em>).</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 2: iot_nodes -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_nodes</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Armazena os nós sensores inteligentes instalados no cabeçote/mancal das máquinas operatrizes.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>gateway_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> NOT NULL</td>
                    <td>Concentrador responsável pelo enlace LoRa deste nó (tabela <code>iot_gateways</code>).</td>
                </tr>
                <tr>
                    <td><code>machine_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> NOT NULL</td>
                    <td>Máquina operatriz instrumentada (tabela <code>machines</code>).</td>
                </tr>
                <tr>
                    <td><code>name</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Nome descritivo amigável do dispositivo sensor.</td>
                </tr>
                <tr>
                    <td><code>node_id</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td><span class="badge-uk">UK</span> NOT NULL</td>
                    <td>Identificador único de hardware gravado no firmware (ex: <code>node_vib_torno_01</code>).</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Valores: <code>'active'</code>, <code>'inactive'</code>.</td>
                </tr>
                <tr>
                    <td><code>battery_voltage</code></td>
                    <td><code>DECIMAL(4,2)</code></td>
                    <td>Nullable</td>
                    <td>Nível de tensão do pack de baterias Li-Ion 18650 em Volts (ex: 4.12 V).</td>
                </tr>
                <tr>
                    <td><code>created_at</code> / <code>updated_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Controle temporal de criação e edição.</td>
                </tr>
                <tr>
                    <td><code>deleted_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Carimbo de exclusão lógica (<em>Soft Deletes</em>).</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 3: iot_sensor_data -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_sensor_data</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Séries temporais de alta densidade registrando cada pacote consolidado de telemetria física.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>node_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> NOT NULL</td>
                    <td>Nó sensor que originou a leitura (tabela <code>iot_nodes</code>).</td>
                </tr>
                <tr>
                    <td><code>msg_id</code></td>
                    <td><code>BIGINT</code></td>
                    <td>NOT NULL</td>
                    <td>Contador sequencial do pacote para cálculo de taxa de perda de pacotes de rádio.</td>
                </tr>
                <tr>
                    <td><code>rpm</code></td>
                    <td><code>INT</code></td>
                    <td>NOT NULL</td>
                    <td>Velocidade angular do eixo medida pelo sensor Hall KY-003.</td>
                </tr>
                <tr>
                    <td><code>rms_global</code></td>
                    <td><code>DECIMAL(10,4)</code></td>
                    <td>NOT NULL</td>
                    <td>Velocidade de vibração global eficaz em mm/s (norma ISO 20816).</td>
                </tr>
                <tr>
                    <td><code>rms_x</code>, <code>rms_y</code>, <code>rms_z</code></td>
                    <td><code>DECIMAL(10,4)</code></td>
                    <td>NOT NULL</td>
                    <td>Velocidade RMS individual decomposta nos eixos cartesianos X, Y e Z.</td>
                </tr>
                <tr>
                    <td><code>kurt_x</code>, <code>kurt_y</code>, <code>kurt_z</code></td>
                    <td><code>DECIMAL(10,4)</code></td>
                    <td>NOT NULL</td>
                    <td>Kurtose estatística triaxial para detecção de pulsos de impacto de rolamento.</td>
                </tr>
                <tr>
                    <td><code>mic_rms</code></td>
                    <td><code>DECIMAL(10,4)</code></td>
                    <td>NOT NULL</td>
                    <td>Nível de pressão sonora do microfone acústico digital MEMS INMP441.</td>
                </tr>
                <tr>
                    <td><code>temp_celsius</code></td>
                    <td><code>DECIMAL(6,2)</code></td>
                    <td>NOT NULL</td>
                    <td>Temperatura por contato do mancal medida pelo sensor DS18B20 em °C.</td>
                </tr>
                <tr>
                    <td><code>fft_data</code></td>
                    <td><code>JSON</code></td>
                    <td>Nullable</td>
                    <td>Picos espectrais nas ordens harmônicas (1X, 2X, 3X, 4X RPM) em documento JSON nativo.</td>
                </tr>
                <tr>
                    <td><code>ml_status</code></td>
                    <td><code>VARCHAR(50)</code></td>
                    <td>Nullable</td>
                    <td>Classificação preditiva inferida pelo modelo (ex: <code>healthy</code>, <code>unbalance</code>).</td>
                </tr>
                <tr>
                    <td><code>ml_confidence</code></td>
                    <td><code>DECIMAL(5,4)</code></td>
                    <td>Nullable</td>
                    <td>Probabilidade estatística computada pela IA (0,0000 a 1,0000).</td>
                </tr>
                <tr>
                    <td><code>measured_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>NOT NULL</td>
                    <td>Carimbo de tempo físico exato da amostragem na borda.</td>
                </tr>
                <tr>
                    <td><code>created_at</code> / <code>updated_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Carimbos de auditoria interna da base.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 4: iot_device_logs -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_device_logs</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Log operacional de eventos, alarmes normativos e ocorrências críticas de manutenção.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>gateway_id</code> / <code>node_id</code> / <code>machine_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Vínculos contextuais polimórficos de rastreamento do dispositivo envolvido.</td>
                </tr>
                <tr>
                    <td><code>level</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Severidade operacional: <code>'info'</code>, <code>'warning'</code>, <code>'error'</code>, <code>'critical'</code>.</td>
                </tr>
                <tr>
                    <td><code>event_type</code></td>
                    <td><code>VARCHAR(50)</code></td>
                    <td>NOT NULL</td>
                    <td>Classificação do evento: <code>anomaly_detected</code>, <code>telemetry</code>, <code>command</code>.</td>
                </tr>
                <tr>
                    <td><code>iso20816_severity</code></td>
                    <td><code>ENUM</code></td>
                    <td>Nullable</td>
                    <td>Enquadramento na norma ISO 20816: <code>'zone_a'</code>, <code>'zone_b'</code>, <code>'zone_c'</code>, <code>'zone_d'</code>.</td>
                </tr>
                <tr>
                    <td><code>raw_payload</code></td>
                    <td><code>JSON</code></td>
                    <td>Nullable</td>
                    <td>Cópia integral inalterada do payload JSON/MQTT para fins de auditoria de engenharia.</td>
                </tr>
                <tr>
                    <td><code>features</code></td>
                    <td><code>JSON</code></td>
                    <td>Nullable</td>
                    <td>Vetor de variáveis numéricas extraídas para reprodutibilidade.</td>
                </tr>
                <tr>
                    <td><code>message</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Mensagem textual de alerta descritivo para visualização na IHM.</td>
                </tr>
                <tr>
                    <td><code>measured_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>NOT NULL</td>
                    <td>Carimbo de tempo físico da ocorrência.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 5: iot_ml_datasets -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_ml_datasets</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Catálogo de conjuntos de treinamento e validação para governança de Machine Learning.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>target_machine_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Máquina ou bancada experimental alvo de coleta do dataset.</td>
                </tr>
                <tr>
                    <td><code>name</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Nome descritivo (ex: <code>dataset_bancada_desbalanceamento_v2</code>).</td>
                </tr>
                <tr>
                    <td><code>slug</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td><span class="badge-uk">UK</span> NOT NULL</td>
                    <td>Identificador URL-friendly único.</td>
                </tr>
                <tr>
                    <td><code>type</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Paradigma do modelo: <code>'supervised_xgboost'</code>, <code>'unsupervised_iforest'</code>.</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Ciclo de vida do dataset: <code>'collecting'</code>, <code>'ready'</code>, <code>'archived'</code>.</td>
                </tr>
                <tr>
                    <td><code>class_distribution</code></td>
                    <td><code>JSON</code></td>
                    <td>Nullable</td>
                    <td>Contagem agregada de janelas por rótulo mecânico.</td>
                </tr>
                <tr>
                    <td><code>total_samples</code></td>
                    <td><code>INT UNSIGNED</code></td>
                    <td>DEFAULT 0</td>
                    <td>Volume total consolidado de janelas temporais de medição.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 6: iot_ml_bursts -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_ml_bursts</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Ensaios e amostras pontuais rotuladas (Ground Truth) para treinamento supervisionado.</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>dataset_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> NOT NULL</td>
                    <td>Dataset associado da tabela <code>iot_ml_datasets</code>.</td>
                </tr>
                <tr>
                    <td><code>device_log_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Log original que motivou a inclusão no conjunto de treino.</td>
                </tr>
                <tr>
                    <td><code>node_id</code> / <code>machine_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> NOT NULL</td>
                    <td>Origem de hardware e máquina de usinagem instrumentada.</td>
                </tr>
                <tr>
                    <td><code>session_id</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Código identificador do ensaio mecânico na bancada de testes.</td>
                </tr>
                <tr>
                    <td><code>origin</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Origem da amostra: <code>'bench_guided'</code>, <code>'drawer_triaged'</code>, <code>'maintenance_baseline'</code>.</td>
                </tr>
                <tr>
                    <td><code>predicted_label</code></td>
                    <td><code>VARCHAR(50)</code></td>
                    <td>Nullable</td>
                    <td>Diagnóstico preliminar inferido pelo algoritmo de IA.</td>
                </tr>
                <tr>
                    <td><code>ground_truth_label</code></td>
                    <td><code>VARCHAR(50)</code></td>
                    <td>NOT NULL</td>
                    <td>Rótulo real certificado pelo engenheiro (<em>saudável, desbalanceamento, folga, rolamento</em>).</td>
                </tr>
                <tr>
                    <td><code>is_validated</code></td>
                    <td><code>BOOLEAN</code></td>
                    <td>DEFAULT FALSE</td>
                    <td>Indica aprovação e auditoria formal por especialista humano.</td>
                </tr>
                <tr>
                    <td><code>validated_by_user_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Usuário validador da tabela central <code>users</code>.</td>
                </tr>
                <tr>
                    <td><code>features_summary</code></td>
                    <td><code>JSON</code></td>
                    <td>NOT NULL</td>
                    <td>Vetor consolidado de atributos estatísticos e espectrais normalizados.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABELA 7: iot_ml_models -->
    <div class="table-card">
        <div class="table-card-header">
            <span>Tabela: iot_ml_models</span>
            <span>Módulo IoT</span>
        </div>
        <div class="table-card-desc">Repositório de modelos treinados para governança e versionamento (MLOps).</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Coluna</th>
                    <th style="width: 22%;">Tipo de Dado</th>
                    <th style="width: 16%;">Restrições</th>
                    <th>Descrição Funcional</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-pk">PK</span></td>
                    <td>Identificador primário universal ordenável (ULID).</td>
                </tr>
                <tr>
                    <td><code>dataset_id</code></td>
                    <td><code>CHAR(26)</code></td>
                    <td><span class="badge-fk">FK</span> Nullable</td>
                    <td>Conjunto de dados utilizado para treinamento da versão.</td>
                </tr>
                <tr>
                    <td><code>name</code></td>
                    <td><code>VARCHAR(255)</code></td>
                    <td>NOT NULL</td>
                    <td>Nome descritivo da versão (ex: <code>xgb_vibracao_amemiya_prod</code>).</td>
                </tr>
                <tr>
                    <td><code>model_type</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Algoritmo de modelagem: <code>'xgboost_cloud'</code>, <code>'iforest_edge'</code>.</td>
                </tr>
                <tr>
                    <td><code>version</code></td>
                    <td><code>VARCHAR(50)</code></td>
                    <td>NOT NULL</td>
                    <td>Versão semântica do artefato (ex: <code>v2.1.0</code>).</td>
                </tr>
                <tr>
                    <td><code>target_device</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Ambiente de execução do modelo: <code>'cloud'</code> ou <code>'edge_esp32'</code>.</td>
                </tr>
                <tr>
                    <td><code>status</code></td>
                    <td><code>ENUM</code></td>
                    <td>NOT NULL</td>
                    <td>Governança MLOps: <code>'in_production'</code>, <code>'candidate'</code>, <code>'shadow'</code>, <code>'deprecated'</code>.</td>
                </tr>
                <tr>
                    <td><code>metrics</code></td>
                    <td><code>JSON</code></td>
                    <td>Nullable</td>
                    <td>Métricas calculadas: Acurácia, F1-Score, Precisão, Recall e Matriz de Confusão.</td>
                </tr>
                <tr>
                    <td><code>deployed_at</code></td>
                    <td><code>TIMESTAMP</code></td>
                    <td>Nullable</td>
                    <td>Data e hora de liberação em ambiente de produção industrial.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr>

    <h2>5. Estratégias de Desempenho e Confiabilidade</h2>
    <ol>
        <li>
            <strong>Desacoplamento Assíncrono com Redis:</strong>
            O tráfego MQTT contínuo não executa operações diretas de escrita no disco do MySQL. O daemon <code>iot:mqtt-bridge</code> recebe as mensagens binárias, valida a integridade do pacote e faz a inserção em uma fila em memória de alta performance no Redis (<code>LPUSH</code>). Workers assíncronos do Laravel realizam a descompressão e gravação em blocos consolidados (<em>bulk insert</em>), eliminando a contenção de disco e suportando picos de tráfego sem perda de pacotes.
        </li>
        <li>
            <strong>Consultas Temporais Sub-Segundos com Índices Compostos:</strong>
            A tabela <code>iot_sensor_data</code> conta com índice composto em <code>[node_id, measured_at]</code>. Essa otimização estrutural permite que os gráficos analíticos do aplicativo mobile e do painel web recuperem curvas de tendência contínuas de 24 horas em menos de 80 ms.
        </li>
        <li>
            <strong>Serialização Espectral de FFT em JSON Nativo:</strong>
            Os picos harmônicos de rotação calculados na borda (1X a 10X RPM) são armazenados em coluna nativa do tipo <code>JSON</code>. Essa abordagem viabiliza tanto o consumo direto pelos componentes de visualização gráfica do frontend quanto a busca indexada por caminhos de chave específicos (<code>fft_data->'$.picos'</code>) sem onerar a modelagem com esquemas excessivamente complexos ou esparsos.
        </li>
    </ol>

    <div class="footer-note">
        Metalúrgica Amemiya & SENAC São Paulo • Plataforma IoT de Metrologia e Manutenção Preditiva • Documento Técnico de Engenharia
    </div>
</body>
</html>""".replace("LOGO_PLACEHOLDER", f"file:///{LOGO_PATH}")

    with open(html_file, "w", encoding="utf-8") as f:
        f.write(html_content)

    print("Renderizando Especificação Técnica Textual (PDF)...")
    run_edge_pdf(html_file, pdf_local)
    shutil.copy2(pdf_local, pdf_senac)
    print(f" -> Gerado: {pdf_local}")
    print(f" -> Copiado: {pdf_senac}")
    return pdf_local

if __name__ == "__main__":
    p1 = generate_diagram_1()
    p2 = generate_diagram_2()
    p3 = generate_text_specification()
    print("\nTODOS OS 3 PDFs FORAM GERADOS COM SUCESSO!")
