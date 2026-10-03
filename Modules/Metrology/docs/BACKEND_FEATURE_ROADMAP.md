# Backend Feature Roadmap (Enterprise Metrology)

Este documento descreve o mapa de evolução técnica e conformidade normativa (ISO 17025, ISO 9001, ILAC-G8, OIML D10) do Módulo de Metrologia.

---

## 1. Regras de Decisão e Banda de Guarda (ISO 17025:2017 / ILAC-G8:09/2019)
**Status:** **Implementado ✅**
- **Implementação:** [`GuardBandEngine.php`](../Services/GuardBandEngine.php)
- **Funcionalidades:**
    - Suporte a regras de decisão binárias e não-binárias com cálculo da banda de guarda:
      $$w = U \cdot (1 - \alpha) \quad \text{ou} \quad w = k \cdot u_c$$
    - Zona de dúvida metrológica (Accepted with Restrictions / Guard Band).
    - Cálculo automatizado de TUR (Test Uncertainty Ratio) para triagem de risco.

## 2. Gestão de Rastreabilidade Hierárquica
**Status:** **Implementado ✅**
- **Implementação:** [`ReferenceStandard.php`](../Models/ReferenceStandard.php), [`CalibrationChainService.php`](../Services/CalibrationChainService.php)
- **Funcionalidades:**
    - Cadeia ininterrupta de rastreabilidade RBC conectando instrumento ao padrão primário/nacional.
    - Suporte a kits de padrões compostos com sincronização hierárquica em cascata.
    - Validação de escopo e CMC de fornecedores externos.

## 3. Monitoramento de Condições Ambientais (Integração IoT)
**Status:** **Em Integração 🟡**
- **Implementação:** Módulo [`Modules/IoT`](../../IoT) e [`Station.php`](../../../app/Models/Station.php)
- **Funcionalidades:**
    - Telemetria de temperatura, umidade e vibração capturada via MQTT/Mosquitto e persistida em banco temporal.
    - Validação de faixa normativa durante o preenchimento de checklists metrológicos.

## 4. Análise de Estabilidade e Otimização de Intervalos (OIML D10 / ILAC-G24)
**Status:** **Implementado ✅**
- **Implementação:** [`IntervalOptimizationService.php`](../Services/IntervalOptimizationService.php)
- **Funcionalidades:**
    - Algoritmo baseado no Método da Carta de Controle (Método 1) e Tempo de Calendário (Método 2).
    - Regressão linear sobre histórico de erros instrumentais para cálculo de taxa de deriva (*drift*).
    - Sugestão estatística para extensão ou encurtamento do intervalo de calibração.

## 5. Assinatura Eletrônica e Selo de Integridade Forense (FDA 21 CFR Part 11)
**Status:** **Implementado ✅**
- **Implementação:** [`SignatureService.php`](../Services/SignatureService.php), [`PdfService.php`](../Services/PdfService.php)
- **Funcionalidades:**
    - Re-autenticação obrigatória com senha do usuário para assinatura formal.
    - Geração de hash SHA-256 canônico gravado no banco e carimbado no PDF.
    - Trilha encadeada criptograficamente (*tamper-evident audit chain*).
    - Portal público de verificação de autenticidade via QR Code.

## 6. Versionamento de Procedimentos (Checklist Templates)
**Status:** **Implementado ✅**
- **Implementação:** [`ProcedureVersioningService.php`](../Services/ProcedureVersioningService.php), tabela `procedure_revisions`
- **Funcionalidades:**
    - Ciclo de vida: Rascunho -> Em Revisão -> Aprovado -> Obsoleto.
    - Histórico preservado: calibrações executadas retêm vínculo à versão do procedimento vigente na data de execução.

## 7. Evolução Contínua do Motor Matemático & Incerteza (Auditoria ISO 17025 / Benchmark Fluke & Beamex)
**Status:** **Planejado / Roadmap ⏳**
- **Metas Técnicas:**
    - **MPE de Escala Composta:** Suporte a tolerância parametrizada $\pm (a\% \text{ leitura} + b\% \text{ escala} + c \text{ dígitos})$ no modelo `Instrument` e `MpeCalculator`, eliminando o caso degenerado de tolerância zero quando $nominal = 0$.
    - **Conservadorismo Estrito de $\nu_{eff}$ (EA-4/02 & GUM §G.4.2):** Aplicar truncamento inferior ($\lfloor \nu_{eff} \rfloor$) ou interpolação linear decrescente na tabela Student-$t$ de [`MetrologyMath::getKFromVeff()`](../Services/MetrologyMath.php), garantindo matematicamente $p \ge 95,45\%$.
    - **Balanço Multivariado de Padrões RBC:** Permitir a inclusão simultânea de múltiplos padrões de referência (ex: banho termostático + termômetro padrão) no somatório quadrático da incerteza Tipo B em [`UncertaintyCalculator`](../Services/UncertaintyCalculator.php).

## 8. Conformidade Estrita de Laudos e Certificados de Calibração (ISO/IEC 17025 §7.8.2 e §7.8.4)
**Status:** **Planejado / Roadmap ⏳**
- **Metas Técnicas:**
    - **Dados do Solicitante / Cliente no Layout PDF (§7.8.2.1e):** Inclusão de bloco de identificação cadastral completa do cliente tomador do serviço no laudo compilado por [`GenerateCertificatePdfAction`](../Actions/GenerateCertificatePdfAction.php).
    - **Condição e Data de Recebimento do Instrumento (§7.8.2.1g e §7.8.2.1h):** Persistência e exibição formal dos atributos `as_received_condition` e `received_date` no ciclo de vida e impressão do laudo.
    - **Data de Emissão Distinta (§7.8.2.1j):** Discriminar no PDF a data de realização técnica (`calibration_date`) da data de emissão/autorização (`issued_at` / `approved_at`).
    - **Controle de Declaração de Validade / Vencimento (§7.8.4.3):** Toggle configurável para ocultar ou exibir a recomendação de periodicidade/próxima calibração, assegurando cumprimento da norma que veda estipular validade sem consentimento formal prévio do cliente.


