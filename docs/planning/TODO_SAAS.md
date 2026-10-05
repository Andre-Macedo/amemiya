# SaaS & Admin Refactoring Roadmap (Amemiya)

## 🟠 Priority 1 to 4: Foundation & Metrology Core - COMPLETED ✅
- [x] **Multi-tenancy & ULID Overhaul**
- [x] **Subscription Engine & Plans**
- [x] **Advanced Calibration Math (BCMath)**
- [x] **Audit Trail (Spatie Activity Log)**
- [x] **Procedure Versioning (ISO 17025)**
- [x] **Digital Signatures (Re-auth)**
- [x] **Public Verification & White-Label Portal**

## 🟡 Priority 5: Operational Logistics & Proactive Workflow - COMPLETED ✅
- [x] **Kanban Board:** Visual status stages with drag-and-drop.
- [x] **Location Handover:** Chain of custody tracking.
- [x] **Maintenance History:** Technical interventions integrated.
- [x] **RFID/NFC Logistics:** Movement tracking foundation.

## 🔵 Priority 6: Enterprise Observability & Governance - COMPLETED ✅
- [x] **Error Tracking:** Sentry/GlitchTip integration.
- [x] **Health Dashboard:** Infrastructure monitoring.
- [x] **Legal Compliance:** Terms of Use & Privacy acceptance.

## 🛡️ Priority 7: Security Hardening & OWASP Compliance (Critical)
- [ ] **MFA / 2FA Support:** Enable Two-Factor Authentication (TOTP) for Admin and Quality Manager roles.
- [ ] **API Rate Limiting:** Progressive throttling on login and sensitive endpoints.
- [ ] **Encrypted Attributes:** Encrypt technical measurements at rest in the database.
- [ ] **Security Headers:** HSTS, CSP, and X-Frame-Options configuration.
- [ ] **Anomaly Detection:** Configure GlitchTip alerts for suspicious behavior (e.g., massive document downloads).
- [ ] **Session Hardening:** Automatic timeout for inactive sessions (Compliance requirement).

## 🟢 Priority 8: Sovereign Infrastructure & Scaling
- [ ] **Ambiente Remoto de Staging (`dev.leantech.andremacedo.dev.br`):**
  - [ ] Bloco de configuração Nginx dedicado na VPS com certificado SSL Let's Encrypt.
  - [ ] Banco de dados MySQL isolado (`amemiya_dev`) para testes sem risco à base de produção.
  - [ ] Containers Docker isolados rodando a branch `develop` (app, worker, reverb e frontend).
  - [ ] Pipeline de CD (Continuous Deployment) no GitHub Actions acionado automaticamente em pushes para `develop`.
- [ ] **Sovereign Storage:** Deploy e configure **MinIO** (S3 compatible) para arquivos e certificados.
- [ ] **Enterprise Webhooks:** Sistema para notificar ERPs externos (SAP, TOTVS) sobre eventos de calibração e alertas.
- [x] **Industrial-Grade Backups:** `spatie/laravel-backup` configurado e operacional.
- [ ] **Enterprise SSO (OIDC/SAML):** Integração com Azure AD, Okta e Google Workspace.

## ⚖️ Priority 9: ISO 17025 & Regulatory Rigor - COMPLETED ✅
- [x] **Document Integrity (SHA-256):** Store and validate cryptographic hashes of every issued PDF to prevent file tampering.
- [x] **Immutable Audit Chaining:** Cryptographically chain audit log entries to detect database manipulation.
- [x] **Software Validation Report:** System-generated math precision validation certificate.
- [x] **CMC Engine:** Block certificate issuance if uncertainty is below authorized scope.
- [x] **Reason for Change:** Mandatory justification popup for editing historical data.
- [ ] **Technical Evolution (Audit ISO 17025 - Rodada 1):**
  - [ ] **MPE Composto / Escala Mista (Benchmark Fluke/Beamex):** Suportar fórmula $\pm (a\% \text{ leitura} + b\% \text{ escala} + c \text{ dígitos})$ no cadastro de instrumentos, evitando tolerância nula quando nominal é zero.
  - [ ] **Truncamento Conservador de $\nu_{eff}$ (EA-4/02 & GUM §G.4.2):** Aplicar $\lfloor \nu_{eff} \rfloor$ ou interpolação linear na tabela Student-$t$ garantindo probabilidade de cobertura estrita $\ge 95,45\%$.
  - [ ] **Múltiplos Padrões no Balanço GUM:** Suporte a combinação quadrática de múltiplos padrões de referência ativos para o mesmo ponto de calibração.
- [ ] **Conformidade de Laudos e Certificados (Audit ISO 17025 - Rodada 2):**
  - [ ] **Dados do Solicitante / Cliente no PDF (§7.8.2.1e):** Renderizar seção dedicada com Razão Social, CNPJ/CPF, endereço e contato do cliente no certificado PDF quando associado a `lab_client_id`.
  - [x] **Estado de Recebimento do Instrumento (§7.8.2.1g):** Adicionado campo `as_received_condition` (ex: íntegro, limpo, desgastado) na tabela `calibrations` e renderizado no laudo PDF.
  - [x] **Data de Recebimento do Item (§7.8.2.1h):** Incluído campo `received_date` no fluxo e exibido formalmente no certificado PDF.
  - [x] **Distinção de Datas de Execução e Emissão (§7.8.2.1j):** Evidenciado no cabeçalho do laudo a separação clara entre a data de realização (`calibration_date`) e a data formal de emissão/publicação do documento (`approved_at`).
  - [x] **Toggle de Validade / Próxima Calibração (§7.8.4.3):** Adicionado controle `show_calibration_due` na calibração e no laudo PDF para omitir a recomendação de periodicidade quando não acordada previamente com o cliente.
- [ ] **Governança, Custódia e Auditoria Forense (Audit ISO 17025 - Rodada 3):**
  - [ ] **Ancoragem Externa do Chain Head (FDA 21 CFR Part 11 / WORM):** Permitir exportação programada ou assinatura externa do `chain_head_hash` para armazenamento imutável à prova de adulteração por DBA root (Object Lock / RFC 3161).
  - [ ] **Interface Visual de Custódia / Leitor de Câmera (F-08):** Criar tela de movimentação e checkout de instrumentos no Filament compatível com scanner de código de barras e câmera móvel além do RFID/NFC existente.
- [ ] **Segurança de Dados e Multi-Tenancy (Audit ISO 17025 - Rodada 4):**
  - [ ] **Middleware de Guarda `EnsureTenantIsInitialized`:** Abortar com erro HTTP 400 em rotas de API protegidas caso o header `X-Tenant-ID` não seja fornecido, prevenindo bypass acidental do Global Scope `BelongsToTenant`.
  - [ ] **Criptografia em Repouso de Chaves Privadas PEM:** Criptografar simetricamente o arquivo `certificate.pem` no disco local com a `APP_KEY`, fornecendo defesa em profundidade contra extração não autorizada no filesystem.
- [ ] **Rastreabilidade Reversa e Recall Metrológico (Audit ISO 17025 - Rodada 5):**
  - [ ] **Integração de Recall com ERPs (SAP / TOTVS):** Disparo de webhooks industriais com a lista de ativos em risco CRITICAL para busca retroativa e bloqueio automático de lotes de produção (OPs) manufaturados na janela de exposição.
  - [ ] **Simulador Interativo de Janela de Exposição na UI:** Permitir aos gestores da qualidade simular no frontend cenários "what-if" de deriva do padrão antes de formalizar o laudo de recall.
- [ ] **Frontend Next.js & Metrologia Operacional (Audit ISO 17025 - Rodada 6):**
  - [x] **Endpoint `POST /calibrations/calculate` (Correção de Bug 404):** Exposto endpoint na API conectado a `CalculateCalibrationUncertaintyAction` retornando incerteza GUM e orçamento em tempo real.
  - [x] **Geração de Etiquetas no Fluxo de Calibração Aprovada:** Botão direto de impressão de etiqueta adicionado na tela de calibração aprovada (`calibrations/[id]/page.tsx`).
  - [x] **Override de MPE no Instrumento Individual:** Suporte completo aos campos `mpe_value`, `mpe_type`, `resolution` e `measuring_range` no formulário e no modelo.
  - [x] **Validador Estrito de CNPJ/CPF (Módulo 11):** Implementada regra `App\Rules\CpfCnpj` no backend e `lib/validators/cpf-cnpj.ts` no frontend.
  - [ ] **Modo de Coleta Offline (PWA / IndexedDB):** Suporte à coleta de dados de calibração em áreas industriais sem conexão de rede (benchmark Beamex bMobile / IndySoft).
  - [ ] **Importação em Lote de Leituras (Clipboard / CSV):** Recurso de copiar/colar matrizes de repetições diretamente de planilhas para agilizar calibrações multiponto.







## 🚀 Priority 10: Enterprise Ecosystem & SAP Integration
- [ ] **Secure API Key Management:** Rotatable, scoped API keys for machine-to-machine integrations.
- [ ] **Enterprise OData API:** Standardized connectors for SAP BTP.
- [ ] **Visual Certificate Designer:** Drag-and-drop PDF template editor.

## 🏁 Priority 11: Pre-Flight & Market Launch
- [ ] **Billing Integration:** Finalize connection with Asaas/MercadoPago.
- [ ] **Marketing Website:** Professional sales landing page.
- [ ] **Data Retention Policy:** Automated archiving of old records (5+ years).
