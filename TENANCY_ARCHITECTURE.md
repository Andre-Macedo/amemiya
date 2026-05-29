# Arquitetura Multi-tenant: Amemiya SaaS & On-Premises

Este documento descreve a estratégia técnica para transformar o Amemiya em uma plataforma SaaS escalável, mantendo a compatibilidade com futuras instalações locais (On-premises).

## 1. Estratégia de Tenancy: Single Database (Multi-tenancy por Coluna)

Utilizaremos o pacote **`stancl/tenancy`** configurado para o modo de **Banco de Dados Único**. 

- **Por que esta escolha?** 
    - Facilita a manutenção (uma única migração para todos os clientes).
    - Compatível com ambientes industriais onde a infraestrutura de múltiplos bancos de dados pode ser complexa.
    - O isolamento é feito via coluna `tenant_id` em todas as tabelas sensíveis, injetada automaticamente por Scopes Globais do Eloquent.

---

## 2. Identificação do Tenant

O sistema suportará dois métodos de identificação simultâneos para atender ao SaaS e ao On-premises:

### A. SaaS (Nuvem) - Subdomínios
- **Identificação:** `slug.amemiya.com.br`
- **Fluxo:** O Laravel identifica o tenant através do subdomínio da requisição.
- **Frontend:** O Next.js redireciona o usuário para o subdomínio correspondente após o login central.

### B. On-premises / Executável (Windows/macOS) - Header Customizado
- **Identificação:** Header HTTP `X-Tenant-ID`.
- **Fluxo:** Em ambientes locais (onde subdomínios DNS podem não existir), o executável enviará o ID do tenant em todas as chamadas de API.
- **Configuração:** No `.env` da instalação local, o `TENANT_ID` será fixado.

---

## 3. Estrutura de Modelos

### Modelos Centrais (Super Admin)
Estes modelos **não** possuem `tenant_id` e são gerenciados apenas pelo painel de controle global (Filament).
- `Tenant` (Dados da empresa, plano, status).
- `Domain` (Vínculo do subdomínio ao tenant).
- `Plan` (Configurações de limites e assinaturas).

### Modelos de Tenant (Isolados)
Estes modelos utilizarão a Trait `BelongsToTenant` e serão filtrados automaticamente.
- `User` (Usuários específicos da empresa).
- `Instrument`, `Calibration`, `WorkOrder`.
- `ReferenceStandard`, `ChecklistTemplate`.
- `Station`, `Supplier`.

---

## 4. Fluxo de Autenticação (Next.js + Sanctum)

Para suportar o **Login Central** e o **Redirecionamento**, seguiremos este fluxo:

1.  **Login:** O usuário acessa `amemiya.com/login`.
2.  **Verificação:** O backend (domínio central) autentica as credenciais e identifica a qual Tenant o usuário pertence.
3.  **Token & Redirecionamento:** 
    - O backend retorna o `slug` do Tenant e um **Personal Access Token (Sanctum)**.
    - O Next.js armazena o token e redireciona o browser para `slug.amemiya.com`.
4.  **Consumo da API:** O Next.js passa a fazer requisições para `api.slug.amemiya.com` enviando o `Bearer Token`.

### A. Identificadores Únicos (ULIDs)
Adotaremos o padrão **ULID** (Universally Unique Lexicographically Sortable Identifier) para todas as chaves primárias (`id`) e estrangeiras (`tenant_id`).
- **Vantagens:** 
    - **Ordenação Natural:** Ao contrário do UUID v4, o ULID é ordenável por tempo. Isso mantém a performance dos índices do banco de dados próxima à dos IDs incrementais.
    - **Sincronização Offline:** Essencial para o futuro On-premises. Registros criados localmente nunca colidirão com registros na nuvem.
    - **Segurança e Auditoria:** Impede a descoberta sequencial de registros e facilita a rastreabilidade em logs de auditoria industrial.
    - **URL Friendly:** São mais curtos (26 caracteres) e legíveis que UUIDs tradicionais.

---

## 5. Implementação Técnica (Passo a Passo)

### Fase 1: Fundação do Tenancy & Identificadores
1. Instalar `stancl/tenancy`.
2. Criar migrations para as tabelas `tenants` e `domains` usando **ULIDs** como PK.
3. Configurar o `TenancyServiceProvider` para lidar com identificação por subdomínio e header.
4. **Refatoração de IDs:** Preparar a migração das chaves primárias existentes (User, Instrument, etc.) de `BigInt` para `ULID`.

### Fase 2: Migração de Dados
1. Criar uma migração para adicionar `tenant_id` (ULID) em todas as tabelas de negócio.
2. Criar a Trait `App\Traits\BelongsToTenant` que aplica o escopo global.
3. Vincular a Trait e a Trait `HasUlids` do Laravel aos modelos existentes.

### Fase 3: Refatoração do Filament (Super Admin)
1. Configurar o Painel Admin do Filament para rodar apenas em domínios centrais (ex: `admin.amemiya.com`).
2. Criar o Resource de `Tenants` no Filament.
3. Remover os menus de metrologia do Painel Admin (já que agora pertencem ao Tenant via Next.js).

### Fase 4: API & Onboarding
1. Ajustar o `AuthApiController` para incluir informações de tenancy no retorno do login.
2. Criar a lógica de "Master Data Templates" (permitir que um novo tenant opte por importar tipos de instrumentos e procedimentos padrões no primeiro acesso).

---

## 6. Considerações para o On-premises (Executáveis)

- **Sincronização:** No futuro, se a instalação local precisar sincronizar dados com uma nuvem central, o `tenant_id` garantirá que os dados subam para o lugar certo sem colisões de IDs (usaremos UUIDs em vez de IDs incrementais para chaves primárias).
- **Offline First:** A arquitetura por coluna facilita a implementação de replicação de dados entre instâncias locais e nuvem.
