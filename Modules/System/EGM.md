# Module System (Gestão de Acesso, Multi-Tenancy & Trilha de Auditoria)

## Overview
Módulo base de segurança, multi-tenancy, governança de identidade e trilha de auditoria criptográfica. Gerencia a hierarquia de plantas industriais (estações de trabalho, linhas de produção e máquinas), o controle de acesso baseado em papéis (RBAC com Filament Shield) e o encadeamento de auditoria indelével (*Audit Chain*).

## Interface
- **Entidades de Domínio**:
  - `User.php`: Usuário autenticado, papéis de metrologista, técnico, auditor e administrador.
  - `Station.php`: Posto de trabalho ou célula metrológica que hospeda máquinas e instrumentos.
  - `Machine.php`: Máquina industrial (torno CNC, fresadora, motor) monitorada pela sonda IoT.
  - `Supplier.php`: Laboratórios externos e fornecedores de serviços metrológicos.
  - `AuditLog.php` & `AuditChainAnchor.php`: Registros de auditoria com hash do bloco anterior para garantia de não-repúdio.
- **Serviços de Governança**:
  - `AuditChainService.php`: Gera assinaturas SHA-256 encadeadas para eventos críticos (calibrações aprovadas, exclusões de dados, alterações de tolerância).
  - `TenantCertificateStorage.php`: Gestão de chaves criptográficas isoladas por organização.
  - `TwoFactorAuthenticationService.php`: Autenticação multifator para operadores de chão de fábrica.
- **Políticas de Autorização**:
  - `UserPolicy.php`, `StationPolicy.php`, `SupplierPolicy.php`: Aplicação de permissões granulares via Filament Shield.

## Invariants
- **Isolamento de Tenant Rigoroso**: Nenhum dado de auditoria, usuário ou máquina pode vazar entre tenants distintos; consultas devem aplicar escopo global de tenant.
- **Inalterabilidade da Trilha de Auditoria**: Registros em `audit_logs` e `audit_chain_anchors` são imutáveis (`INSERT-only`). Nenhuma operação de `UPDATE` ou `DELETE` é permitida nessas tabelas.
- **Assinatura Encadeada**: Cada nova âncora de auditoria deve computar seu hash com base no hash do registro imediatamente precedente, garantindo detecção imediata de qualquer adulteração manual no banco de dados.
- **Spatie Coding Guidelines**: Uso estrito de propriedades tipadas, retorno com tipagem explícita e tratamento prévio de erros (cláusulas de guarda).

## Side Effects
- Gravação de logs de acesso e auditoria contínuos em `audit_logs` e `access_logs`.
- Geração de âncoras criptográficas periódicas.
- Emissão de tokens de autenticação Sanctum para clientes mobile e IoT.

## Verification
- Run: `docker-compose exec app php artisan test --filter=System`
