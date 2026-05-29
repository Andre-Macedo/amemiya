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
- [ ] **Sovereign Storage:** Deploy and configure **MinIO** (S3 compatible) for files.
- [ ] **Enterprise Webhooks:** System to notify external ERPs (SAP, TOTVS) on events.
- [x] **Industrial-Grade Backups:** `spatie/laravel-backup` configured in code.
- [ ] **Enterprise SSO (OIDC/SAML):** Integration with Azure AD, Okta, and Google Workspace.

## ⚖️ Priority 9: ISO 17025 & Regulatory Rigor
- [ ] **Document Integrity (SHA-256):** Store and validate cryptographic hashes of every issued PDF to prevent file tampering.
- [ ] **Immutable Audit Chaining:** Cryptographically chain audit log entries to detect database manipulation.
- [ ] **Software Validation Report:** System-generated math precision validation certificate.
- [ ] **CMC Engine:** Block certificate issuance if uncertainty is below authorized scope.
- [ ] **Reason for Change:** Mandatory justification popup for editing historical data.

## 🚀 Priority 10: Enterprise Ecosystem & SAP Integration
- [ ] **Secure API Key Management:** Rotatable, scoped API keys for machine-to-machine integrations.
- [ ] **Enterprise OData API:** Standardized connectors for SAP BTP.
- [ ] **Visual Certificate Designer:** Drag-and-drop PDF template editor.

## 🏁 Priority 11: Pre-Flight & Market Launch
- [ ] **Billing Integration:** Finalize connection with Asaas/MercadoPago.
- [ ] **Marketing Website:** Professional sales landing page.
- [ ] **Data Retention Policy:** Automated archiving of old records (5+ years).
