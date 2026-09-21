# Roadmap: Sistema de Gestão da Qualidade (QMS) & Indústria 4.0

Este documento define a estratégia para transformar o `amemiya` em um QMS completo, integrando Metrologia, Controle de Qualidade e IoT.

## Arquitetura de Módulos

| Módulo | Sigla Indústria | Função Principal | Status |
| :--- | :--- | :--- | :--- |
| **Metrology** | **MSA** | Gestão de Instrumentos e Calibração. | ✅ Existente |
| **QualityControl** | **QC / SPC** | Inspeção de Peças, Lotes e Não Conformidades. | 🚧 A Criar |
| **IoT** | **PdM / EHS** | Monitoramento de Máquinas (Preditiva) e Ambiente. | 🚧 A Criar |

---

## 1. Módulo: QualityControl (O Coração da Rastreabilidade)

Este módulo responde à pergunta: *"O produto está bom?"* e *"Qual instrumento mediu isso?"*.

### 1.1. Features Indispensáveis (MVP)

1.  **Gestão de Produtos e Especificações:**
    *   Cadastro de Peças (`Product`) com suas cotas críticas (ex: Diâmetro: 10mm ±0.1).
    *   Controle de Versão de Desenho (Revisão A, B, C).

2.  **Rastreabilidade de Lotes (Traceability):**
    *   Criação de Ordens de Produção / Lotes (`ProductionBatch`).
    *   Vínculo: Lote -> Máquina -> Data/Hora.

3.  **Inspeção (Data Collection):**
    *   Formulário de coleta de dados no chão de fábrica.
    *   **Obrigatório:** O operador deve selecionar o Instrumento (`asset_id` do módulo Metrology) usado na medição.
    *   *Validação Automática:* Se o instrumento estiver vencido, o sistema bloqueia a inspeção.

4.  **Gestão de Não Conformidades (RNC / NC):**
    *   Se uma medição falha, o sistema abre automaticamente uma RNC.
    *   Workflow de Disposição: *Refugar*, *Retrabalhar* ou *Aprovar Condicionalmente*.

### 1.2. Features Avançadas (Futuro)

*   **SPC (Statistical Process Control):** Cálculo automático de Cp e Cpk baseados nas inspeções.
*   **CAPA:** Gestão de Ações Corretivas para problemas sistêmicos.

---

## 2. Módulo: IoT (A Inteligência da Máquina)

Este módulo responde à pergunta: *"A máquina e o ambiente estão propícios para produzir com qualidade?"*.

### 2.1. Monitoramento de Máquinas (Preventiva/Preditiva)
*   **Sensores:** Vibração (Acelerômetro), Temperatura do Motor, Corrente Elétrica.
*   **Regra de Negócio:**
    *   Se `Vibração > Limite`: Gerar Alerta de Manutenção.
    *   Se `Vibração > Crítico`: Bloquear criação de novos Lotes no `QualityControl`.

### 2.2. Monitoramento Ambiental (Metrologia & EHS)
*   **Sensores:** Ruído (dB), Temperatura (°C), Umidade (%).
*   **Aplicação na Metrologia:**
    *   Monitorar a sala de calibração 24/7.
    *   Se a temperatura variou > 1°C durante uma calibração, invalidar o certificado automaticamente.
*   **Aplicação em Segurança (EHS):**
    *   Mapa de calor de ruído na fábrica para proteção auditiva dos operadores.

---

## 3. Fluxo de Integração: O "Recall" Automático

O fluxo que resolve sua dor principal (Rastreabilidade Reversa):

1.  **Produção:** O Lote `L-100` é inspecionado usando o Paquímetro `P-01`. Tudo aprovado.
2.  **Tempo passa...** (10 dias depois).
3.  **Metrologia:** O Paquímetro `P-01` vai para calibração e é **REPROVADO**.
4.  **Sistema (QualityControl):**
    *   Busca todas as inspeções feitas com `P-01` desde a última calibração válida.
    *   Encontra o Lote `L-100`.
    *   Muda status do Lote `L-100` para **"SUSPEITO"**.
    *   Notifica o Engenheiro da Qualidade: *"O Lote L-100 pode conter peças ruins. O instrumento P-01 estava descalibrado."*

---

## Próximos Passos Recomendados

1.  **Criar Módulo `QualityControl`:**
    *   Focar na estrutura: `Product` -> `Batch` -> `Inspection`.
    *   Criar a ligação com `Metrology` (Foreign Key para `instruments`).

2.  **Criar Módulo `IoT`:**
    *   Focar na ingestão de dados (API para receber JSON dos sensores).
    *   Dashboard simples de "Semáforo" (Verde/Amarelo/Vermelho) para as máquinas.
