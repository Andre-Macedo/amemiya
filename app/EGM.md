# Application Core (Infraestrutura Central & Multi-Tenancy SaaS)

## Overview
Camada central da aplicação Laravel 12. Responsável pelo ciclo de vida global do SaaS, resolução de multi-tenancy (por domínio e por cabeçalho HTTP), provedor de painel administrativo Filament 4, autenticação Sanctum, faturamento/planos e aplicação de cabeçalhos de segurança corporativa.

## Interface
- **Middleware de Multi-Tenancy**:
  - `InitializeTenancyByHeader.php`: Intercepta requisições API contendo o cabeçalho `X-Tenant` e inicializa o contexto operacional do tenant correspondente.
  - `EnsureTenantIsInitialized.php`: Garante que rotas protegidas não executem sem contexto de tenant válido.
  - `VerifySubscription.php`: Bloqueia recursos avançados caso o plano do tenant esteja suspenso ou inadimplente.
  - `SecurityHeaders.php`: Injeta cabeçalhos HSTS, X-Content-Type-Options e CSP.
- **Traits de Domínio**:
  - `BelongsToTenant.php`: Trait Eloquent que aplica escopo global de tenant (`tenant_id`) automaticamente em todas as consultas e define o `tenant_id` na criação de entidades.
  - `LogsActivity.php`: Trait para registro automático de eventos no log de auditoria.
- **Filament Painel**:
  - `AdminPanelProvider.php`: Configuração central do Filament 4, temas, plugins e navegação.
  - `NavigationGroup.php`: Enum padronizado para agrupamento dos clusters Filament no menu lateral.

## Invariants
- **Vazamento Zero de Tenant**: Toda entidade de negócio filha que dependa de tenant DEVE usar a trait `BelongsToTenant`.
- **Validação de Assinatura Eletrônica**: Nenhum cabeçalho de autenticação ou token Sanctum substitui a confirmação com senha exigida para assinaturas 21 CFR Part 11.
- **Headers HTTP Corporativos**: Toda resposta gerada pela API deve conter cabeçalhos de segurança estritos injetados por `SecurityHeaders.php`.
- **PHP 8.2+ e Spatie Rules**: Proibido o uso de docblocks para substituir propriedades tipadas; métodos que não retornam valor devem declarar explicitamente `: void`.

## Side Effects
- Resolução e injeção do objeto `Tenant` atual no container de injeção de dependência do Laravel.
- Criação e validação de tokens Sanctum em `personal_access_tokens`.
- Bloqueio de requisições que não apresentem tenant válido em endpoints protegidos.

## Verification
- Run: `docker-compose exec app php artisan test --filter=Tenant`
