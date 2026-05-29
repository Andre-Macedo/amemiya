# Backend Feature Roadmap (Enterprise Metrology)

Este documento descreve as funcionalidades avançadas planejadas para elevar o MetroLab ao nível de referência de mercado, com foco em conformidade normativa (ISO 17025, ISO 9001) e inteligência industrial.

---

## 1. Regras de Decisão e Banda de Guarda (ISO 17025:2017)
**Status:** Planejado (Estrutura inicial existente)
- **Descrição:** Implementar um sistema dinâmico para declarar conformidade baseado em riscos.
- **Requisitos Técnicos:**
    - Tabela `decision_rules` (Nome, Fórmula, Multiplicador `w`).
    - Refatoração da `DecisionRuleStrategy` para retorno tri-estado: `Aprovado`, `Reprovado`, `Aprovado Condicional`.
    - Cálculo automático de conformidade subtraindo a incerteza do limite de tolerância (Banda de Guarda).
- **Referência:** ILAC-G8:2019.

## 2. Gestão de Rastreabilidade Hierárquica
**Status:** Planejado
- **Descrição:** Mapear a "árvore genealógica" da medição.
- **Requisitos Técnicos:**
    - Modelo `StandardCertificate`: Armazenar dados dos certificados externos (RBC) dos padrões de referência.
    - Histórico de validade de incerteza dos padrões (Incerteza muda a cada calibração do padrão).
    - API para gerar o gráfico da cadeia de rastreabilidade (Instrumento -> Padrão Lab -> Padrão Nacional).

## 3. Monitoramento de Condições Ambientais (IoT Integration)
**Status:** Planejado (Depende do módulo IoT)
- **Descrição:** Validar se o ambiente de calibração está dentro dos limites normativos.
- **Requisitos Técnicos:**
    - Modelo `EnvironmentLog`: Registros de temperatura e umidade por estação.
    - Hook de Validação: Impedir a finalização de uma calibração se a temperatura média do período estiver fora do range permitido para aquele tipo de instrumento.

## 4. Análise de Estabilidade e Recomendação de Intervalo (OIML G10)
**Status:** Planejado
- **Descrição:** Algoritmo preditivo para sugerir mudanças na periodicidade de calibração.
- **Requisitos Técnicos:**
    - Implementação do "Método de Ajuste de Intervalo" da OIML G10.
    - Análise de regressão linear baseada no histórico de erros (Drift).
    - Dashboard de ROI: Mostrar ao cliente quanto ele economizou ao aumentar intervalos de instrumentos estáveis.

## 5. Assinatura Eletrônica e Selo de Integridade (CFR 21 Part 11)
**Status:** Planejado
- **Descrição:** Garantir que os certificados de calibração não foram alterados no banco de dados.
- **Requisitos Técnicos:**
    - Geração de SHA-256 Hash combinando: Dados técnicos + ID do Aprovador + Timestamp.
    - Verificação de Integridade: Um Job que roda periodicamente checando se o hash atual dos registros bate com o gravado.
    - Assinatura visual no PDF vinculada ao hash.

## 6. Versionamento de Procedimentos (Checklist Templates)
**Status:** Planejado
- **Descrição:** Manter o histórico de métodos de calibração.
- **Requisitos Técnicos:**
    - Campos `version` (int) e `parent_version_id` em `ChecklistTemplates`.
    - Sistema de "Rascunho -> Revisão -> Publicado".
    - Garantir que calibrações antigas sempre apontem para a versão do template que estava ativa na data da execução.

---
*Documento gerado em 06/03/2026. Alinhado com a estratégia de ERP Industrial.*
