### **1\. Resumo Executivo e Diagnóstico do Projeto Anterior (Gap Analysis)**

**Contextualização**

O projeto consiste em uma plataforma de metrologia preditiva para motores elétricos. No semestre passado, o sistema validou com sucesso a arquitetura base de Edge Computing, realizando o processamento local da Transformada Rápida de Fourier (FFT) no microcontrolador e transmitindo os dados via MQTT para um servidor externo.

**Auditoria de Gargalos**

Apesar do êxito na validação do software e processamento na borda, o projeto anterior apresentou três gargalos que impedem sua aplicação como produto industrial:

1. **Limitação Analítica (Falta de Referência de RPM):** O sistema analisava a vibração sem saber a rotação exata do motor. Devido ao "escorregamento" natural dos motores de indução, a ausência de um Tacômetro impedia a identificação precisa das frequências de 1X e 2X RPM, limitando o diagnóstico apenas a desbalanceamentos simples e cegando a IA para problemas de desalinhamento e rolamentos.  
2. **Baixa Precisão e Ausência de Calibração Sensorial:** Conforme apontado pela banca no ciclo anterior, o projeto original utilizava os sensores (como o acelerômetro e o microfone) lendo dados brutos, sem rotinas de calibração de *baseline* (tara). A ausência de calibração metrológica e de conversão precisa para unidades de engenharia padronizadas comprometia a exatidão das grandezas físicas medidas, inserindo incertezas que prejudicavam a confiabilidade do diagnóstico.   
3. **Dependência Energética e de Rede:** O nó sensor dependia de cabeamento de energia contínua e conexão Wi-Fi. No chão de fábrica, fios de energia amarrados a máquinas vibrando geram riscos de segurança e inviabilizam a instalação rápida, enquanto o Wi-Fi sofre quedas por interferência e obstáculos físicos.  
4. **Gaps de Interação:** O monitoramento estava restrito a *dashboards* web passivos, sem um sistema descentralizado e móvel que alertasse os operadores de manutenção ativamente em campo.

### **2\. Proposta de Evolução Tecnológica e Justificativa de Impacto**

A nova fase propõe um salto focado em **Autonomia (IoT Sem Fio)**, **Fusão de Sensores** e arquitetura de rede desacoplada.

* **Fusão Sensorial e o Tacômetro (Precisão Diagnóstica):**  
  A  melhoria técnica na borda é a inclusão de um novo sensor: Tacômetro. O Tacômetro permite normalizar os dados da FFT (Análise de Ordem). Cruzando a vibração sincronizada com o RPM, a acústica e o esforço elétrico (corrente), o sistema terá os dados necessários para que a Inteligência Artificial detecte falhas complexas.  
* **Rede LoRa \+ Baterias de Lítio (Verdadeiro IoT Sem Fio):**  
  Para resolver a dependência de cabos e powerbanks, o nó sensor (Edge)  vai ser redesenhado para operar 100% a bateria (células 18650). Aliando rotinas de *Deep Sleep* no microcontrolador com a transmissão via rede LoRa, o dispositivo funcionará por meses sem necessidade de recarga. Um Gateway fixado à parede receberá os dados LoRa, eliminando a dependência de Wi-Fi no motor e tornando a instalação do sensor simples e prática.  
* **MQTT (Orquestração do novo Payload):**  
  O MQTT (Pub/Sub) já validado será mantido e escalado. Ele fará a ponte entre o Gateway de parede e a nuvem, garantindo que o volume denso de telemetria gerado pelos novos sensores seja entregue à API de forma leve, assíncrona e sem perdas, mesmo com oscilações na rede de internet da fábrica.  
* **Ecossistema Mobile (Android):**  
  O aplicativo Android descentraliza a operação de manutenção. Quando o modelo preditivo (IA) detectar um desvio, o aplicativo emitirá alertas *Push* com o diagnóstico cruzado, permitindo que a equipe de chão de fábrica receba a informação de falha mecânica no celular, em tempo real e envie comandos para o gateway se necessário.  
* **API Pública (Integrações com Sistemas ERP):** Para consolidar a arquitetura Cliente/Servidor e eliminar gaps informacionais na gestão da empresa, o *backend* em Laravel irá expor uma API RESTful pública e documentada. Isso viabiliza a interoperabilidade direta com softwares de gestão corporativa (ERPs como SAP, TOTVS ou Sankhya) e sistemas de manutenção (CMMS). A partir da detecção de uma falha iminente pela IA, a plataforma poderá acionar o ERP automaticamente para abrir Ordens de Serviço (OS), solicitar peças ao estoque e programar paradas de máquina sem intervenção humana.   
* **Design de Hardware Dedicado (Transição de Protoboard para PCB):** Para garantir a integridade dos dados e a sobrevivência do hardware em ambiente hostil, o projeto abandonará o uso de protoboards e jumpers, que são criticamente vulneráveis à vibração do motor. A nova fase contempla o roteamento e a manufatura de uma Placa de Circuito Impresso (PCB) customizada. Essa evolução mecânica e elétrica elimina falhas de mau contato, reduz drasticamente o ruído nos sinais de medição (através de planos de terra blindados) e confere ao dispositivo a robustez estrutural exigida para operação contínua no chão de fábrica.  
* **Bancada de Testes Modular e Portátil:** Além da evolução do hardware eletrônico, a própria infraestrutura física de validação será redesenhada. A bancada mecânica, contendo o motor de 1/4 CV, o eixo e os mancais, será reconstruída utilizando perfis estruturais de alumínio, priorizando um design compacto e leve. Essa portabilidade permite não apenas realizar os testes em diferentes ambientes de forma segura (com proteções acrílicas), mas viabiliza o transporte do maquinário para a universidade, possibilitando a demonstração ao vivo da injeção de falhas e da resposta da IA durante a defesa do projeto.  

### **3\. Análise de Viabilidade Técnica e Operacional**

* **Viabilidade Técnica:** A evolução é exequível dentro do semestre. O grupo já domina o processamento vetorial (FFT) e a mensageria MQTT. Os desafios técnicos absorvíveis para esta fase englobam o desenho e roteamento de uma Placa de Circuito Impresso (PCB) customizada para eliminar a instabilidade dos protoboards, o gerenciamento de energia (Deep Sleep \+ chip BMS para as baterias) no ESP32, e o desenvolvimento do app Mobile. A orquestração do sistema será centralizada no *backend* (Laravel), que além de se comunicar com o microsserviço de Machine Learning (FastAPI), ficará responsável por expor a nova API Pública RESTful.  
* **Viabilidade Operacional e Bancada de Testes Portátil:** A validação física ocorrerá em uma bancada aprimorada e agora totalmente portátil. Estruturada em perfis modulares de alumínio para facilitar o transporte, ela abrigará o motor de 1/4 CV, acoplamentos, mancais (série 204\) e o eixo secundário. Esse arranjo compacto permitirá a injeção mecânica de falhas controladas (rolamentos defeituosos e calços para desalinhamento angular) em qualquer ambiente. Durante os testes, a substituição dos jumpers e protoboards pela nova PCB garantirá imunidade a ruídos mecânicos. O Tacômetro aferirá a leitura exata da rotação, gerando *datasets* fidedignos para treinar a IA, espelhando os desafios de chão de fábrica em um equipamento que a banca poderá ver operando ao vivo. 

### **4\. Projeção de Ganhos e Metas (KPIs Estimados)**

#### A tabela abaixo evidencia o salto de um protótipo estático e vulnerável para um dispositivo IoT preditivo maduro, integrável e robusto.

| Indicador de Performance | Projeto Anterior (Baseline) | Nova Proposta IoT / Indústria 4.0 | Impacto Tecnológico (Por Quê?) |
| :---- | :---- | :---- | :---- |
| **Referência de Análise** | FFT assíncrona (Sem RPM) | FFT síncrona (Tacômetro) \+ Fusão de Sensores | Precisão na detecção via XGBoost (Análise de Ordem) |
| **Robustez de Hardware** | Protoboard e Fios (Jumpers) | Placa de Circuito Impresso (PCB) dedicada | Eliminação de mau contato, redução de ruído (GND) e adequação à vibração |
| **Autonomia de Energia** | Dependente de fonte e tomada ou powerbanks | Operação 100% a baterias 18650 \+ Deep Sleep | Instalação Plug-and-Play; meses de autonomia |
| **Conectividade Local** | Wi-Fi no motor (instável) | Nó LoRa (bateria) comunicando com Gateway fixo | Imunidade a gaiolas de Faraday e queda de sinal no maquinário |
| **Integração Corporativa** | Sistema Fechado | API Pública documentada para ERP/CMMS | Possibilidade de geração automática de Ordens de Serviço (OS) e gestão de estoque |
| **Agilidade de Intervenção** | Verificação manual (Dashboard) | Sistema descentralizado Mobile (Android \+ Push) | Decisão em tempo real no chão de fábrica |
| **Infraestrutura de Validação** | Bancada de testes pesada e fixa | Bancada compacta e portátil em perfil de alumínio | Viabiliza testes móveis e demonstração ao vivo da detecção de falhas |

#### 

#### **Cronograma Macro (Sprints)**

1. **Mês 1:** Adaptação para bancada portátil (perfis estruturais de alumínio) com proteções físicas de acrílico (adequação à NR-12); Integração do Tacômetro e pack de baterias; Projeto e roteamento da placa (PCB) em extrabaixa tensão (NR-10).  
2. **Mês 2:** Manufatura e montagem da PCB; Implementação das rotinas de calibração de *baseline* e filtros digitais (Edge Computing); Configuração do Deep Sleep, transmissão LoRa e roteamento via Gateway.  
3. **Mês 3:** Refatoração do tráfego MQTT e banco de dados (Laravel); Construção da API Pública para integração com ERPs e desenvolvimento do aplicativo Android (alertas *Push*).  
4. **Mês 4:** Coleta de *datasets* limpos via injeção de falhas na bancada portátil definitiva; Treinamento e integração final dos modelos preditivos (XGBoost/Isolation Forest via FastAPI).

