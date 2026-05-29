# Guia de Testes de Aceitação do Usuário (UAT) - MetroLab

Este documento descreve os fluxos de teste manual passo-a-passo para validar se o sistema está "redondo" antes da transição para SaaS Multi-Tenancy.

---

## 1. Configurações e Dados Mestre (Settings)
**Objetivo:** Garantir que a fundação do laboratório está correta.

### 1.1 Materiais e Expansão Térmica
1. Vá em **Configurações > Materiais**.
2. Crie um novo material (ex: `Aço Carbono`, CTE: `11.5`).
3. Edite um material existente e verifique se o valor salvou.
4. Delete um material de teste.

### 1.2 Regras de Decisão (ISO 17025)
1. Vá em **Configurações > Tipos de Instrumentos**.
2. Crie um tipo (ex: `Micrômetro`) e defina a regra como `Banda de Guarda (Erro <= MPE - U)`.
3. Crie outro tipo (ex: `Régua`) com `Aceitação Simples`.

### 1.3 Matriz de Competências
1. Vá em **Sistema > Usuários**.
2. No menu de ações (três pontos), clique em **Training & Competences**.
3. Atribua competência para `Micrômetro` ao seu usuário com uma data de validade futura.
4. Tente desmarcar outra categoria e salvar.

---

## 2. Gestão de Ativos (Instruments & Standards)

### 2.1 Cadastro de Instrumentos
1. Vá em **Instrumentos > Novo**.
2. Vincule ao `Material` criado no passo 1.1.
3. Verifique se ao escolher o `Tipo`, a próxima data de calibração é calculada automaticamente.
4. Salve e verifique se o QR Code aparece na página de detalhes.

### 2.2 Hierarquia de Kits (Padrões)
1. Vá em **Padrões de Referência**.
2. Crie um padrão do tipo "Kit" (ex: `Jogo de Blocos`).
3. Crie um segundo padrão e no campo **Kit Pai**, selecione o primeiro padrão.
4. Vá na página de detalhes do "Pai" e verifique se o "Filho" aparece na tabela de itens do kit.

---

## 3. Fluxo Logístico (Work Orders)

### 3.1 Check-in de Instrumento
1. Vá em **Ordens de Serviço > Receber Instrumento**.
2. Selecione um instrumento da lista.
3. Preencha a **Inspeção Visual** (ex: "Sem estojo, com riscos").
4. Salve e verifique se o status ficou como `RECEIVED`.

---

## 4. Calibração e Motor Metrológico (Wizard)

### 4.1 Calibração com Ajuste (As Found / As Left)
1. Inicie uma calibração para um instrumento.
2. **Passo 1:** Verifique se o alerta de competência (verde/amarelo) aparece conforme configurado no passo 1.3.
3. **Passo 2 (Medições):** Digite um valor que gere erro (ex: Nominal 10, lido 10.10).
4. Marque a checkbox **"Ajuste Realizado?"**.
5. Verifique se abriu a nova coluna **As Left**. Digite o valor corrigido (ex: 10.01).
6. **Passo 3 (GUM):** Clique em **Calculate**.
7. Clique no botão **ISO GUM** para abrir a calculadora dinâmica. Adicione uma "Fonte Customizada" e veja a Incerteza Expandida (U) mudar em tempo real.
8. Salve o registro.

---

## 5. Qualidade e Aprovação (Review & RNC)

### 5.1 Aprovação CFR 21 Part 11
1. Vá em **Calibrações > Fila de Revisão**.
2. Clique no botão de **Aprovar** (Check verde).
3. Verifique se o sistema **bloqueia** e pede sua senha.
4. Digite a senha errada e veja o bloqueio. Digite a correta e veja o status mudar para `Published`.

### 5.2 Não-Conformidade Automática (RNC)
1. Faça uma calibração que resulte em **Reprovado** (Erro > MPE).
2. Vá em **Não-Conformidades**. Verifique se uma RNC foi aberta automaticamente.
3. Entre na RNC e tente clicar em **Fechar RNC** sem preencher a Causa Raiz. O sistema deve impedir.
4. Preencha o plano CAPA, salve como rascunho e depois feche a RNC.

---

## 6. Verificação Pública (QR Code)
1. Saia do sistema (Logout) ou use uma aba anônima.
2. Acesse a URL `/en/verify/{id_do_instrumento}` (use o ID de um instrumento aprovado).
3. Verifique se a página mostra o selo **CONFORME** e os dados básicos sem pedir login.

---

## 7. Trilha de Auditoria (Compliance)
1. Vá em **Sistema > Trilha de Auditoria Global**.
2. Verifique se todas as suas ações anteriores (criação de material, alteração de status, etc) estão registradas com o detalhamento de "De" / "Para".
