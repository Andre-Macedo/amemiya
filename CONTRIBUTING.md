# Guia de Contribuição — Backend Amemiya (Lean Tech Metrologia)

Seja bem-vindo ao repositório backend do **Sistema de Metrologia Lean Tech**. Este guia orienta o fluxo de trabalho, os padrões de código e os procedimentos de entrega para novos colaboradores.

---

## 1. Fluxo de Branches

Adotamos um fluxo baseado em **GitFlow simplificado**:

- **`main`**: Branch de produção. É estritamente protegida e reflete o código estável em execução no ambiente produtivo.
- **`develop`**: Branch principal de desenvolvimento e integração contínua. **Todas as Pull Requests devem ter `develop` como branch base.**
- **Branches de feature/fix**: Devem ser criadas sempre a partir de `develop`:
  - Novas features: `git checkout -b feat/nome-da-funcionalidade develop`
  - Correções de bugs: `git checkout -b fix/descricao-do-bug develop`
  - Refatorações: `git checkout -b refactor/nome-do-modulo develop`

---

## 2. Padrão de Commits

Todos os commits devem seguir rigorosamente o padrão **Conventional Commits** redigidos em **português**:

```text
<tipo>(<escopo opcional>): <descrição no imperativo/presente em minúsculas>
```

### Tipos Comuns:
- `feat`: Nova funcionalidade (ex: `feat(metrology): adiciona cálculo de banda de guarda ILAC-G8`)
- `fix`: Correção de bug (ex: `fix(iot): corrige reconexão automática do broker mqtt`)
- `refactor`: Refatoração sem alteração de comportamento externo
- `docs`: Atualização de documentação
- `test`: Adição ou modificação de testes
- `style`: Formatação de código (espaçamentos, indentação)
- `chore`: Atualizações de build, dependências ou ferramentas de CI

---

## 3. Ambiente de Desenvolvimento

### Opção 1: Docker Compose (Recomendado)
O projeto conta com uma stack completa via Docker (PHP 8.3 FPM, Nginx, MySQL 8.0, Redis, Mosquitto MQTT, Worker, MailHog, phpMyAdmin e Frontend Next.js).

```bash
# 1. Copiar variáveis de ambiente
cp .env.example .env

# 2. Subir containers em segundo plano
docker compose up -d

# 3. Instalar dependências PHP
docker compose exec app composer install

# 4. Gerar chave da aplicação
docker compose exec app php artisan key:generate

# 5. Executar migrações dos módulos
docker compose exec app php artisan module:migrate Metrology
docker compose exec app php artisan module:seed Metrology
```

Acessos úteis:
- **Painel Filament / API**: [http://localhost:8000](http://localhost:8000)
- **phpMyAdmin**: [http://localhost:8080](http://localhost:8080)
- **MailHog (Webmail Local)**: [http://localhost:8025](http://localhost:8025)
- **Frontend Next.js**: [http://localhost:3000](http://localhost:3000)

---

## 4. Padrões de Código e Qualidade

O projeto segue as diretrizes da **PSR-12** e os padrões da **Spatie**.
Antes de submeter sua contribuição, execute a suíte de qualidade:

```bash
# Formatação de código (Laravel Pint)
docker compose exec app ./vendor/bin/pint

# Análise Estática (PHPStan / Larastan)
docker compose exec app ./vendor/bin/phpstan analyse

# Execução de Testes Automatizados (Pest)
docker compose exec app php artisan test Modules/Metrology/tests
```

Ou diretamente pelo Composer:
```bash
composer run quality
```

---

## 5. Diretrizes para Novos Módulos e Recursos

1. **Arquitetura Modular:** Recursos específicos do domínio de metrologia devem ser implementados dentro de `Modules/Metrology/` e recursos de sensores em `Modules/IoT/`.
2. **DTOs e Actions:** A lógica de negócio deve ser encapsulada em *Actions* ou *Services* tipados, utilizando *DTOs* para transporte de dados.
3. **Migrações:** Crie migrações dentro do respectivo módulo:
   ```bash
   php artisan module:make-migration nome_da_migracao Modulo
   ```
4. **Testes:** Toda nova Action ou Service deve ser acompanhada de testes unitários ou de integração com Pest em `Modules/<Modulo>/tests`.
