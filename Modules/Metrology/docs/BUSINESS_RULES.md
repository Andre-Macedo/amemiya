# Manual do Desenvolvedor - Módulo Metrologia

> **Versão do Documento:** 4.0 (The Code Atlas)
> **Módulo:** Metrologia (`Modules/Metrology`)
> **Norma Referência:** ISO/IEC 17025:2017
> **Status:** Referência Canônica

Este documento é a "Bíblia" do sistema de Metrologia. Cobre desde a arquitetura de alto nível até a explicação detalhada de cada arquivo importante no repositório.

---

## Índice

1.  [Visão Geral e Objetivos](#1-visão-geral-e-objetivos)
2.  [Glossário e Linguagem Ubíqua](#2-glossário-e-linguagem-ubíqua)
3.  [Arquitetura do Sistema (Core Patterns)](#3-arquitetura-do-sistema-core-patterns)
4.  [Banco de Dados (Schema Reference)](#4-banco-de-dados-schema-reference)
5.  [UI & UX (Filament Logic)](#5-ui--ux-filament-logic)
6.  [Motor Matemático (GUM)](#6-motor-matemático-gum)
7.  [Eventos e Listeners](#7-eventos-e-listeners)
8.  [Referência da API Interna (Core)](#8-referência-da-api-interna-core)
9.  [Atlas do Código (File-by-File)](#9-atlas-do-código-file-by-file)
10. [Guia de Troubleshooting](#10-guia-de-troubleshooting)

---

## 1. Visão Geral e Objetivos

O Módulo de Metrologia gerencia todo o ciclo de vida de equipamentos de medição (Instrumentos e Padrões). Ele garante que as medições realizadas pela empresa sejam rastreáveis e confiáveis.

### Objetivos Técnicos
*   **Idempotência**: Cálculos estatísticos devem produzir sempre o mesmo resultado para as mesmas entradas.
*   **Rastreabilidade**: Todo resultado salvo deve apontar para o Padrão utilizado.
*   **Agnosticismo**: O motor de cálculo não diferencia "Instrumento" de "Padrão"; ambos são itens calibráveis polimórficos.

---

## 2. Glossário e Linguagem Ubíqua

Termos fundamentais para compreensão do código:

*   **Bias (Tendência)**: Erro sistemático. $ \text{Bias} = \bar{x} - V_{ref} $.
*   **MPE (Maximum Permissible Error)**: O limite de erro aceitável para um instrumento. Definido pelo fabricante ou norma.
*   **Uncertainty (Incerteza)**: Dúvida associada ao resultado da medição.
*   **Tur**: Test Uncertainty Ratio (Ideal > 4:1).

### Enums de Referência

#### `ItemStatus` (`Modules\Metrology\Enums\ItemStatus.php`)
*   `Active`: Em uso normal.
*   `Maintenance`: Fora de uso, em reparo.
*   `InCalibration`: Saiu para laboratório externo.
*   `Lost`: Extraviado (Bloqueia uso - Exception).
*   `Scrapped`: Descartado/Sucata (Bloqueia uso - Exception).

#### `CalibrationResult` (`Modules\Metrology\Enums\CalibrationResult.php`)
*   `Approved`: Erro + Incerteza <= MPE.
*   `ApprovedWithRestrictions`: Erro <= MPE, mas Erro + Incerteza > MPE.
*   `Rejected`: Erro > MPE.

---

## 3. Arquitetura do Sistema (Core Patterns)

### 3.1. Action Pattern
Todas as operações de escrita passam por **Actions**.
*   **Regra**: Controllers ou Livewire Components **nunca** contêm lógica de negócio. Apenas delegam.
*   **Exemplo**: `SubmitInstrumentChecklistAction`.

### 3.2. Service Pattern
Cálculos e validações residem em **Services**. (Stateless).
*   **Exemplo**: `UncertaintyCalculator`.

### 3.3. Strategy Pattern (Regras de Decisão)
Alterna a lógica de aprovação.
*   **Interface**: `DecisionRuleStrategy`
*   **Implementações**: `SimpleAcceptance` (Padrão), `GuardBand`.

---

## 4. Banco de Dados (Schema Reference)

### `instruments`
*   `id` (PK)
*   `mpe` (string): Campo texto flexível ("0.02mm").
*   `resolution` (decimal).
*   `next_calibration` (date): Calculado automaticamente.

### `reference_standards`
*   `parent_id` (FK): Define kits.
*   `actual_value` (decimal): Valor verdadeiro calibrado (Móvel).
*   `uncertainty` (decimal): Incerteza do padrão (Influi no Tipo B).

### `calibrations`
*   `calibrated_item_type/id`: Polimorfismo.
*   `readings`, `uncertainty_sources`: JSON fields.

---

## 5. UI & UX (Filament Logic)

O formulário de calibração (`CalibrationForm`) contém lógica avançada:
*   **Smart Filtering**: Filtra padrões pelo valor nominal da etapa do checklist.
*   **Wizard**: 3 Passos (Dados -> Checklist -> Resultados).
*   **Reactive**: Campos `live()` recalculam estado instantaneamente.

---

## 6. Motor Matemático (GUM)
Implementado em `MetrologyMath`.

**1. Média Aritmética**
```php
$avg = array_sum($readings) / count($readings);
```

**2. Incerteza Combinada (uc)**
```php
$u_c = sqrt(pow($uA, 2) + pow($uRes, 2) + pow($uStd, 2));
```

---

## 7. Eventos e Listeners

### `CalibrationSaved`
O evento gatilho do sistema.
*   **Listener**: `ProcessCalibrationListener`.
*   **Pipeline**:
    1.  `ProcessCalibrationAction` (Decisão de Aprovação).
    2.  `CreateChecklistAction` (Persistência detalhada).
    3.  `UpdateReferenceStandardKitAction` (Efeito Cascata em Kits).
    4.  Notification Dispatch (Se reprovado).

---

## 8. Referência da API Interna (Core)

### Actions (Write)
*   **`ProcessCalibrationAction`**: Maestro. Decide Aprovado/Reprovado.
*   **`SubmitInstrumentChecklistAction`**: Processa DTOs de input.
*   **`UpdateReferenceStandardKitAction`**: Atualiza filhos de um Kit.

### Services (Read)
*   **`UncertaintyCalculator`**: Fachada para matemática GUM.
*   **`CalibrationValidator`**: Guard clauses anti-erro.

---

## 9. Atlas do Código (File-by-File)

Aqui detalhamos a função de CADA arquivo relevante na estrutura do módulo.

### 9.1. API & Controllers (`Http/Controllers/Api/V1`)

*   **`CalibrationApiController.php`**:
    *   *Função*: Expoe endpoints para Apps Móveis realizarem calibrações offline.
    *   *Métodos*: `store()` (Recebe JSON, hidrata DTO, chama Action).
*   **`InstrumentApiController.php`**:
    *   *Função*: Listagem de instrumentos para o App (Sync).
    *   *Métodos*: `index()` (Lista paginada), `show()` (Detalhes + Histórico).
*   **`AccessLogApiController.php`**:
    *   *Função*: Registra quem acessou o q.

### 9.2. Filament Resources (`Filament/.../Resources`)

#### Instruments (`InstrumentsResource`)
*   **`Resources/Instruments/Schemas/InstrumentForm.php`**: Definição do formulário de Criar/Editar Instrumento. Contém máscaras de input.
*   **`Resources/Instruments/Tables/InstrumentsTable.php`**: Tabela principal. Contém as Actions de "Enviar para Manutenção", "Registrar Movimentação" e filtros por Status.

#### Calibrations (`CalibrationResource`)
*   **`Resources/Calibrations/Schemas/CalibrationForm.php`**: O arquivo mais complexo de UI. Contém o Wizard, lógica de `Repeater` para checklist e cálculo via AJAX (`live`).
*   **`Resources/Calibrations/Tables/CalibrationsTable.php`**: Histórico de certificados. Colunas com `badge()` coloridos por `CalibrationResult`.

#### ReferenceStandards (`ReferenceStandardResource`)
*   **`Resources/ReferenceStandards/Tables/ReferenceStandardsTable.php`**: Exibe hierarquia. Mostra a árvore Pai/Filho.

### 9.3. Database Factories (`Database/Factories`)

Usadas estensivamente nos testes.

*   **`InstrumentFactory.php`**: Gera instrumentos com MPEs aleatórios válidos.
*   **`CalibrationFactory.php`**: Gera calibrações passadas/futuras para testes de vencimento.
*   **`ReferenceStandardFactory.php`**: Gera padrões. Contém state `isKit()` para criar pais com filhos.

### 9.4. Testes (`Tests`)

*   **`Unit/DecisionRuleLogicTest.php`**: Testa isoladamente se a lógica matemática de aprovação (Simple vs GuardBand) está correta.
*   **`Unit/InstrumentUnitLogicTest.php`**: Testa getters/setters e parsers de MPE do model Instrument.
*   **`Feature/InstrumentTest.php`**: Teste de integração (Banco + Model). Verifica fluxo de criação.
*   **`Feature/CalibrationFlowTest.php`**: Simula o ciclo completo: Cria Instrumento -> Calibra -> Verifica Data Vencimento.

### 9.6. Traits (`app/Traits`)

*   **`HasAssetIdentity.php`**:
    *   *Função*: Padroniza a gestão de identificadores únicos (Patrimônio, Tag, Serial).
    *   *Uso*: Usada em `Instrument` e `ReferenceStandard`. Garante que não existam dois ativos com o mesmo Tag ativo na empresa.

### 9.7. Recursos Auxiliares (`Filament/...`)

#### Templates
*   **`ChecklistTemplateResource.php`**: CRUD de modelos de checklist. Define os pontos de teste padrão para um tipo de instrumento (ex: "Paquímetro 150mm").

#### Verificações Intermediárias
*   **`IntermediateCheckResource.php`**: Registro simplificado de verificação diária (não é uma calibração completa). Não gera certificado oficial, apenas log de conformidade.

### 9.5. DTOs (`app/DTOs`)

*   **`MeasurementCalculationData.php`**: O DTO mais importante. Padroniza a entrada para o motor de cálculo. Garante que `readings` seja sempre array de floats e `resolution` seja > 0.
*   **`ChecklistCreationData.php`**: Valida a estrutura de entrada de um checklist completo vindo da API/UI.
*   **`ChecklistItemData.php`**: Representa uma linha única do checklist (Leitura, Padrão Usado, Resultado).
*   **`InstrumentChecklistSubmissionData.php`**: Agregador que contém o cabeçalho da calibração e a lista de itens.
*   **`KitUpdateData.php`**: Estrutura para atualização em lote dos filhos de um kit.
*   **`UncertaintyResult.php`**: Output imutável do cálculo. Usado para evitar arrays mágicos de retorno.

---

## 10. Guia de Troubleshooting

### Erro: "Call to undefined method getCalibrationFrequencyMonths"
*   **Causa**: O Item (Instrument ou Padrão) não está implementando corretamente a interface `CalibratableItem`.
*   **Solução**: Verifique se a classe tem `implements CalibratableItem` e se a trait ou método existe.

### Erro: "MPE Parsing Error" ou Divisão por Zero
*   **Causa**: O campo `mpe` no banco contém caracteres não numéricos estranhos ou resolução é 0.
*   **Solução**: O Model `Instrument` possui um accessor `getMpeAttribute` que tenta limpar. Verifique o cadastro do instrumento.

### Erro: "Status Locked"
*   **Causa**: Tentativa de calibrar item `Lost`.
*   **Solução**: Use a Action de "Retorno de Manutenção" primeiro para tornar o item `Active`.

### UI: Filtro de Padrão vazio no Checklist
*   **Causa**: O "Smart Filtering" não achou padrão com `nominal_value` igual à etapa.
*   **Solução**: Cadastre um Padrão de Referência com o valor nominal exato (casas decimais importam).

---

## 11. Roadmap Futuro

*   [ ] **Cartas de Controle (CEP)**: Implementar gráficos de tendência de Viés ao longo do tempo.
*   [ ] **Incerteza Expandida Dinâmica**: Permitir k diferente de 2 via configuração.
*   [ ] **API Externa**: Permitir que clientes consultem certificados via token.
