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

## 🚀 Priority 10: Enterprise Ecosystem & SAP Integration
- [ ] **Secure API Key Management:** Rotatable, scoped API keys for machine-to-machine integrations.
- [ ] **Enterprise OData API:** Standardized connectors for SAP BTP.
- [ ] **Visual Certificate Designer:** Drag-and-drop PDF template editor.

## 🏁 Priority 11: Pre-Flight & Market Launch
- [ ] **Billing Integration:** Finalize connection with Asaas/MercadoPago.
- [ ] **Marketing Website:** Professional sales landing page.
- [ ] **Data Retention Policy:** Automated archiving of old records (5+ years).
