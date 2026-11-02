# Apresentação Pré-Banca — Projeto Integrador (Engenharia de Computação - 5º Semestre)
**Data de Avaliação:** Pré-Banca Intermediária  
**Empresa Parceira:** Metalúrgica Amemiya  
**Demanda Escolhida:** Controle e Monitoramento Preditivo de Maquinário Crítico (Tornos e Centros de Usinagem CNC)  
**Formato Obrigatório:** Exatamente 5 Slides | Pitch Executivo de 5 Minutos (Cronometrado) | Sem blocos de texto contínuo  

---

## SLIDE 1: Capa e Contexto do Desafio
* **Tempo Previsto:** 30 segundos  
* **Objetivo:** Identificar o projeto, a empresa parceira e o foco de evolução tecnológica na linha de usinagem.

### Layout Visual do Slide (Tópicos Diretos e Dados Objetivos)

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        PROJETO INTEGRADOR — ENGENHARIA DE COMPUTAÇÃO                  │
│                               SENAC SÃO PAULO — 5º SEMESTRE                            │
│                                                                                        │
│  TEMA: Plataforma IoT de Metrologia e Manutenção Preditiva na Indústria 4.0 (V2)       │
│  PARCEIRO INDUSTRIAL: Metalúrgica Amemiya                                             │
│  PROCESSO IMPACTADO: Linha de Usinagem Mecânica de Precisão (Tornos e Fresadoras CNC) │
├────────────────────────────────────────────────────────────────────────────────────────┤
│  INTEGRANTES DO GRUPO:                                                                 │
│  • André Macedo [e equipe]                                                             │
├────────────────────────────────────────────────────────────────────────────────────────┤
│  FOCO DE ENGENHARIA DA EVOLUÇÃO TECNOLÓGICA (V2):                                      │
│  • Sensoriamento Multimodal: Vibração Triaxial + Tacômetro Síncrono + Ruído + Térmico   │
│  • Validação Prática em Bancada V2: Injeção de desalinhamento (calços) e falhas NR-12  │
│  • Diagnóstico Preditivo V2: Classificação Multiclasse (4 falhas) com IA e Alertas Push│
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Roteiro de Fala do Porta-Voz (30 Segundos)
> *"Boa noite, professores. Nosso projeto integrador atende à Metalúrgica Amemiya na linha de usinagem e tornearia CNC. Nesta evolução tecnológica (V2), nosso foco central de engenharia é expandir a inteligência do sistema para diagnosticar novas falhas mecânicas reais — como desalinhamento de eixo, folgas estruturais e danos em mancais —, superando o modelo anterior que só monitorava desbalanceamento. Para isso, integramos uma nova fusão multissensorial, comunicação LoRa imune a ruídos fabris e uma nova bancada física com injeção controlada de defeitos para comprovar os resultados."*

---

## SLIDE 2: Diagnóstico e Gap Analysis
* **Tempo Previsto:** 1 minuto  
* **Objetivo:** Demonstrar senso crítico detalhando as limitações e falhas do protótipo do ciclo anterior.

### Layout Visual do Slide (Tabela Comparativa Direta)

| Dimensão Crítica | Protótipo Anterior (Baseline V1) | Falha / Gargalo Técnico Diagnosticado | Nova Solução Adotada (Ciclo V2) |
| :--- | :--- | :--- | :--- |
| **Escopo de Diagnóstico** | Modelo restrito a 1 falha (apenas desbalanceamento) | Incapacidade de detectar desalinhamento de eixo, folga ou defeito de mancal | **Fusão Multissensorial:** Tacômetro KY-003 + INMP441 + DS18B20 para 4 tipos de falha mecânica |
| **Bancada de Validação** | Bancada restrita a parafusos excêntricos | Impossibilidade de simular desalinhamento angular/paralelo e ausência de proteção | **Nova Bancada Modular 40x80/2020:** Injeção de desalinhamento por calços, mancais UCP204 e NR-12 |
| **Integridade de Hardware**| Protoboard e jumpers soltos sobre o motor | Fios desconectavam com a vibração; altíssimo ruído eletromagnético nos barramentos | **Placa PCB Dedicada:** Roteamento com planos de terra blindados e fixação mecânica rígida |
| **Alimentação & Enlace RF** | Cabos de tomada 110/220V + Wi-Fi 2.4 GHz | Risco de acidentes em eixos girantes e alta atenuação do Wi-Fi na carcaça metálica | **Baterias Li-Ion 18650 internas (Deep Sleep) + Rádio LoRa (915 MHz):** Imunidade a ruídos EMI |
| **Ação da Manutenção** | Verificação manual em dashboard web passivo | Mecânico não fica parado em frente a computadores; falta de mobilidade operacional | **App Android + Alertas Push em < 2s:** Diagnóstico preditivo entregue no bolso do técnico |

### Roteiro de Fala do Porta-Voz (1 Minuto)
> *"Ao auditarmos criticamente o projeto do ciclo anterior, identificamos os gargalos reais: nosso modelo de IA era preciso, mas só detectava desbalanceamento de massa, ignorando desalinhamentos de eixo, folgas estruturais e danos em mancais. Nossa bancada antiga só permitia fixar parafusos excêntricos, inviabilizando testes com outras falhas mecânicas. Além disso, o hardware em protoboard sofria com mau contato vibratório, fios de tomada ofereciam riscos perto de eixos girantes e o Wi-Fi sofria atenuação na estrutura dos tornos. Na V2, superamos todos esses pontos: criamos a nova bancada modular com injeção de desalinhamento por calços e mancais intercambiáveis, expandimos o sensoriamento para tacômetro, ruído acústico e temperatura, e projetamos uma PCB industrial com rádio LoRa e alimentação a baterias 18650."*

---

## SLIDE 3: Arquitetura IoT e Indústria 4.0 (Slide Principal)
* **Tempo Previsto:** 1 minuto e 30 segundos  
* **Objetivo:** Apresentar o diagrama unificado de ponta a ponta e justificar tecnicamente cada tecnologia adotada.

### Diagrama Único de Arquitetura

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [1. SENSING NA MÁQUINA]                                                                                │
│ • Vibração Triaxial (ADXL345 / I2C)       ──┐                                                          │
│ • Rotação Real RPM (KY-003 Hall / Digital)──┼──> [NÓ DE BORDA ESP32-S3]                                │
│ • Ruído de Atrito (INMP441 / I2S Digital) ──┤    • Edge DSP: FFT 1024 pts Síncrona                     │
│ • Temperatura Contato (DS18B20 / 1-Wire)  ──┘    • Extração: RMS Global + Kurtose                      │
│                                                  • Operação em Bateria 18650 / Deep Sleep              │
└───────────────────────────────────────────────────┬────────────────────────────────────────────────────┘
                                                    │ Enlace LoRa (915 MHz)
                                                    ▼
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [2. CONCENTRAÇÃO & INGESTÃO]                                                                           │
│   [GATEWAY LORA DE PAREDE] ──(MQTT / IP Industrial QoS 1)──> [BROKER MQTT MOSQUITTO]                   │
│   • Decodificação do Payload RF                              • Laravel IoT Bridge Daemon               │
│   • Conexão Ethernet / Wi-Fi de Fábrica                      • Buffer Não Bloqueante em Redis          │
└───────────────────────────────────────────────────┬────────────────────────────────────────────────────┘
                                                    │
                                                    ▼
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [3. BACKEND, IA & CLIENTES]                                                                            │
│   [CORE LARAVEL 12] ───(HTTP Assíncrono)───> [MICROSSERVIÇO PYTHON FASTAPI]                            │
│   • Motor Normativo ISO 20816                 • Classificação Preditiva (XGBoost)                      │
│   • WebSockets Laravel Reverb                 • Detecção de Anomalias (Isolation Forest)               │
│         │                                                                                              │
│         ├─────────────────────────────────────────────┐                                                │
│         ▼                                             ▼                                                │
│   [APP ANDROID REACT NATIVE]                  [API PÚBLICA RESTful]                                    │
│   • IHM Digital de Bolso (3.5 pol)            • Abertura Automática de Ordens de Serviço (OS)          │
│   • Alertas Push de Falha Iminente            • Interoperabilidade com ERPs Industriais (TOTVS / SAP)  │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Tabela de Justificativa Técnica das Tecnologias

| Tecnologia | Função no Sistema | Por que foi escolhida para a Metalúrgica Amemiya? |
| :--- | :--- | :--- |
| **ADXL345 + INMP441 + KY-003** | Fusão Sensorial de Borda | Cruza vibração mecânica, ruído acústico e velocidade angular para isolar falhas de rolamento de desbalanceamento de eixo. |
| **Rádio LoRa (915 MHz)** | Transmissão Nó ⇄ Gateway | Frequência Sub-GHz com penetração em estruturas de aço, sem sobrecarregar a rede Wi-Fi corporativa da empresa. |
| **Broker MQTT + Redis Buffer** | Ingestão de Mensageria | Protocolo Pub/Sub ultraleve; o Redis absorve rajadas de telemetria em memória, blindando o MySQL contra picos de escrita. |
| **FastAPI + XGBoost** | Inteligência Preditiva | Modelo supervisionado com altíssima acurácia e baixo custo computacional para classificar os tipos específicos de defeito. |
| **API Pública RESTful** | Interoperabilidade Corporativa | Conecta o chão de fábrica ao ERP corporativo: ao diagnosticar falha iminente, emite requisição automática de manutenção. |

### Elementos Visuais Destacados no Slide 3
* **Renderizações 3D das Placas Próprias (KiCad):** Exibição das placas de circuito impresso proprietárias projetadas pelo grupo no KiCad, superando circuitos genéricos em protoboard:
  * **Carrier Board do Nó Sensor (ESP32-S3):** Placa com soquetes para ESP32-S3, rádio LoRa, conector industrial GX16 para as sondas de mancal, circuito de recarga USB-C/BMS e medição de bateria.
  * **Placa Gateway LoRa:** Concentrador de parede com rádio Sub-GHz e conexão de rede industrial para o broker MQTT.

### Roteiro de Fala do Porta-Voz (1 Minuto e 30 Segundos)
> *"Este é o diagrama integrado da nossa arquitetura. No motor e mancais da máquina, desenvolvemos hardware proprietário no KiCad — a Carrier Board do Nó Principal com ESP32-S3 e as Torres Probe de Mancal com ADXL345 e DS18B20. A placa de borda executa a amostragem síncrona, calcula a FFT de 1024 pontos e os descritores no tempo e envia o pacote via rádio LoRa 915 MHz para o Gateway concentrador na parede da fábrica. O Gateway repassa o payload via MQTT para o broker Mosquitto com QoS 1. No servidor, um daemon em Laravel consome as mensagens através de um buffer em memória no Redis, avalia os limites da norma internacional ISO 20816 e consulta o modelo XGBoost em Python. Se uma anomalia for confirmada, o Laravel emite instantaneamente um alerta Push para o aplicativo Android do mecânico de manutenção e disponibiliza o evento através de uma API Pública RESTful, permitindo que o ERP da metalúrgica abra automaticamente uma Ordem de Serviço."*

---

## SLIDE 4: Viabilidade Técnica e Matriz "Antes x Depois"
* **Tempo Previsto:** 1 minuto  
* **Objetivo:** Comprovar a viabilidade na planta fabril, ilustrar o protótipo físico da bancada (novo GIF 3D de 40° capturado no Fusion) e comparar os critérios de engenharia V1 vs. V2.

### Tabela "Antes x Depois" (Critérios de Engenharia e Metas)

| Critério de Engenharia / Meta | Projeto Anterior (V1) | Nova Evolução Tecnológica (V2) | Impacto Técnico no Processo Produtivo |
| :--- | :---: | :---: | :--- |
| **Espectro de Falhas Cobertas** | 1 modo (apenas desbalanceamento) | **4 modos** (desbalanceamento, desalinhamento, folga e rolamento) | Cobertura integral das causas de quebra catastrófica em usinagem |
| **Fusão Sensorial de Borda** | Mono-sensorial (apenas acelerômetro) | **Multimodal** (vibração + tacômetro + microfone + temperatura) | Elimina ambiguidades espectrais separando atrito de folga mecânica |
| **Validação Prática na Bancada** | Injeção restrita a peso excêntrico | **Injeção de desalinhamento (push-pull), folga e mancais + NR-12** | Permite ensaiar e comprovar falhas controladas com segurança em aula |
| **Classificação do Modelo IA** | Binário preciso (~96% em desbalanceamento) | **Multiclasse com meta > 93%** nas 4 classes de falha | Diagnóstico preditivo automático discriminando o defeito exato |
| **Autonomia & Enlace de Dados** | Cabo contínuo + Wi-Fi fabril instável | **Bateria Li-Ion (90-120 dias) + Rádio LoRa (915 MHz)** | Imunidade a interferências eletromagnéticas e instalação sem fiação |

### Elemento Visual Destacado no Slide 4
* **Novo GIF Animado 3D da Bancada Nova (Fusion 360):** Varredura angular suave de 40° em vista diagonal isométrica da montagem completa da bancada:
  * Perfis de alumínio estrutural 40x80 e 2020;
  * Cúpula de proteção articulada em acrílico com microswitch de intertravamento (NR-12) e botão de emergência tipo cogumelo;
  * Motor elétrico WEG sobre subplaca com blocos de ajuste fino push-pull e parafusos jacking de elevação vertical;
  * Mancais pillow block UCP204 com torres verticais de probes montadas diretamente sobre a pista;
  * Eixo retificado de 20 mm com disco de desbalanceamento e tacômetro óptico em colar bipartido.
* **Badge da Placa Torre Probe (KiCad):** Render 3D da PCB miniatura com sensor ADXL345, sensor de temperatura DS18B20 e microfone acústico que opera no topo dos mancais.

### Matriz Direta de Viabilidade e Mitigação de Riscos na Planta

| Risco Mapeado no Chão de Fábrica | Severidade | Estratégia de Mitigação Implementada |
| :--- | :---: | :--- |
| **Ambiente Agressivo (óleo solúvel e cavacos)** | Média | Gabinete vedado IP65 com fixação magnética e sonda direta de mancal sem fios expostos. |
| **Ruído Eletromagnético (EMI de inversores)** | Alta | Rádio LoRa 915 MHz imune a ruídos fabris + PCB dedicada de 2 camadas com plano de terra blindado. |
| **Validação Prática com a Banca** | Média | **Bancada de Testes Portátil** com injeção controlada de desalinhamento e folgas para teste ao vivo perante a banca. |

### Roteiro de Fala do Porta-Voz (1 Minuto)
> *"A viabilidade da nossa proposta na V2 se apoia na expansão do espectro de diagnóstico e na nova bancada de ensaios: no projeto anterior, nosso modelo de IA já era bastante preciso (~96%), porém estava restrito exclusivamente ao desbalanceamento de massa. Com a nova fusão de sensores — unindo aceleração triaxial, tacômetro síncrono, microfone acústico e temperatura —, passamos a cobrir 4 modalidades críticas de falha: desbalanceamento, desalinhamento angular e paralelo, folgas mecânicas e defeitos em mancais. Para validar isso perante a banca sem riscos operacionais na fábrica, projetamos a nova bancada didática em perfis 40x80 e 2020 que vocês veem em rotação 3D na tela. Com mancais industriais UCP204, placas de desalinhamento ajustadas por calços e parafusos push-pull, e enclausuramento NR-12, conseguimos injetar falhas mecânicas reais e comprovar a resposta da IA e dos alertas ao vivo perante os professores."*

---

## SLIDE 5: Evolução do Back-end V2: Cronograma das Próximas 2 Semanas
* **Tempo Previsto:** 30 segundos  
* **Objetivo:** Destacar a base de software já consolidada e apresentar as tarefas focadas em novos sensores, dataset na nova bancada e APIs.

> **Base Arquitetural Já Consolidada:** O banco de dados relacional MySQL, o broker MQTT Mosquitto, o buffer assíncrono em Redis e os WebSockets no Laravel Reverb já estão totalmente implementados e funcionais na infraestrutura Amemiya.

### Cronograma de Atividades das Próximas 2 Semanas (Evolução V2)

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│ SEMANA 1 (Dias 1 a 7): DTOs Multissensoriais & Coleta de Dataset na Nova Bancada       │
│ • Sprint 1.1: Adaptação de Esquema & DTOs no Laravel para persistir e processar os     │
│               novos campos (temp_celsius, battery_voltage, acústica e tacômetro).      │
│ • Sprint 1.2: Campanha de Coleta na Nova Bancada: injeção física de defeitos (calços  │
│               de desalinhamento, folga e mancais) gravando o dataset multiclasse.      │
│ • Sprint 1.3: Retreinamento do Modelo XGBoost no microsserviço Python com as features │
│               espectrais/temporais extraídas para classificar as 4 falhas mecânicas.   │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ SEMANA 2 (Dias 8 a 14): Regras Multi-Falha, APIs e Validação End-to-End                │
│ • Sprint 2.1: Expansão do Motor ISO 20816 cruzando limites de vibração global com a   │
│               curva de elevação térmica do mancal e energia acústica de alta frequência│
│ • Sprint 2.2: Atualização dos Contratos & Endpoints da API RESTful v1 (rotas Sanctum) │
│               para disponibilizar diagnósticos multiclasse para o ERP e App Mobile.   │
│ • Sprint 2.3: Ensaio Integrado de Ponta a Ponta: Bancada física -> Gateway LoRa ->    │
│               Broker Mosquitto -> Laravel -> IA FastAPI -> Reverb -> API Pública.      │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Roteiro de Fala do Porta-Voz (30 Segundos)
> *"Importante destacar que a arquitetura estrutural de software — o banco relacional MySQL, o broker MQTT Mosquitto, as filas no Redis e os WebSockets no Laravel Reverb — já está totalmente implementada e funcional. O trabalho destas próximas duas semanas foca estritamente na evolução para a V2: na Semana 1, expandimos os DTOs para os novos sensores de temperatura e acústica e realizamos a coleta de dados com a nova bancada física para retreinar o modelo XGBoost nas 4 classes de falha. Na Semana 2, refinamos as regras normativas ISO cruzadas com temperatura, ajustamos os contratos da API RESTful v1 e executamos o teste integrado de ponta a ponta. Com isso, entregamos o back-end V2 100% homologado para a etapa do aplicativo móvel. Muito obrigado, encerramos nossa apresentação e estamos abertos à arguição."*

---

## Guia de Respostas para a Arguição da Banca Docente (3 a 4 Minutos)

* **Pergunta 1:** *"Se o modelo anterior já tinha boa acurácia, por que adicionar tacômetro, microfone e temperatura?"*  
  * **Resposta Técnica:** *"Nosso modelo anterior era binário e identificava apenas desbalanceamento de massa com cerca de 96% de acurácia. Porém, no ambiente real de usinagem, desbalanceamento responde por menos de um terço das paradas. Desalinhamento de eixo exige o tacômetro para Análise de Ordem síncrona (frequência 2X do RPM real), defeitos incipientes em pistas de rolamento manifestam ruído acústico de alta frequência detectado pelo microfone I2S, e atrito excessivo por perda de lubrificação gera elevação na curva térmica medida pelo DS18B20. A fusão sensorial permite classificar essas falhas antes que o mancal entre em colapso."*
* **Pergunta 2:** *"A arquitetura de software e banco de dados já está pronta ou vai ser feita agora?"*  
  * **Resposta Técnica:** *"A infraestrutura base já está totalmente construída e em produção nos containers Docker: banco relacional MySQL, broker Mosquitto, fila Redis e broadcasting em tempo real via WebSockets no Laravel Reverb. O escopo das próximas duas semanas é a evolução de regras de negócio: adequação dos DTOs para os novos canais de telemetria, retreinamento do XGBoost com os dados coletados na nova bancada para 4 classes e atualização dos contratos da API Pública RESTful."*
* **Pergunta 3:** *"Como a nova bancada garante que os dados de teste refletem a realidade da fábrica?"*  
  * **Resposta Técnica:** *"A bancada foi construída em perfis modulares rígidos 40x80 e 2020 com mancais de ferro fundido UCP204 idênticos aos aplicados em eixos de transmissão de tornos. Desenvolvemos um suporte com placas de calço calibradas para injetar desalinhamento angular e paralelo em milímetros conhecidos, e mancais intercambiáveis para ensaio de defeitos reais, respeitando enclausuramento seguro NR-12 para demonstração ao vivo perante a banca."*
