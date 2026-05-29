# Propostas de Funcionalidades Futuras (Backlog Metrologia)

Este documento centraliza ideias e melhorias propostas para o Módulo de Metrologia, visando conformidade ISO 17025 e Excelência Operacional (World Class Manufacturing).

## 1. Gestão Avançada de Escopo de Fornecedores
**Problema**: Atualmente, um fornecedor é apenas "Ativo" ou "Inativo". Não há controle sobre *quais* serviços ele está habilitado a prestar.
**Risco**: Enviar um equipamento crítico (ex: Padrão de Temperatura) para um laboratório que não possui acreditação RBC para aquela grandeza específica.
**Solução Proposta**:
*   Tabela `supplier_scopes` (Fornecedor, Grandeza, CMC/Melhor Capacidade de Medição, Validade).
*   Trava no envio de calibração externa: "O fornecedor X não possui escopo cadastrado para a grandeza Y".
*   Upload do Certificado de Acreditação do Fornecedor.

## 2. Matriz de Competência Técnica (ISO 17025:6.2)
**Problema**: O sistema permite que qualquer usuário "Técnico" execute qualquer checklist.
**Solução Proposta**:
*   Vincular `ChecklistTemplates` a `Skills` (Habilidades).
*   Cadastro de Técnicos com suas respectivas Habilidades e Validade (Treinamentos).
*   Bloqueio ou Alerta se um técnico tentar executar um procedimento para o qual não está treinado.

## 3. Assistente de Incerteza (Wizard)
**Status**: O cálculo (matemática) já existe (`UncertaintyCalculator`), mas a UX é automática/escondida.
**Melhoria**: Criar um modal "Passo a Passo" onde o técnico insere as fontes de incerteza (Resolução, Repetibilidade, Histerese) de forma guiada, ajudando na educação técnica da equipe.

## 4. Análise de Tendência (Drift)
**Status**: [IMPLEMENTADO PARCIALMENTE] Gráficos básicos adicionados em Instrumentos e Padrões.
**Próximo Nível**:
*   **Detecção de Anomalias**: Algoritmo que alerta se a variação for estatisticamente improvável (ex: salto brusco sem justificativa).
*   **Predição de Vida Útil**: Usar regressão linear para estimar *quando* o instrumento vai falhar, permitindo substituição proativa antes do erro ocorrer.

## 5. Monitoramento Ambiental (ISO 17025:6.3)
**Problema**: As condições ambientais (Temperatura, Umidade, Pressão) afetam drasticamente medições dimensionais (dilatação térmica). Hoje não registramos isso no momento da calibração.
**Solução Proposta**:
*   Campos obrigatórios no início do Checklist: "Temp. Inicial", "Umid. Inicial", "Temp. Final", etc.
*   Validação automática: Se a norma exige 20°C ± 1°C e o técnico insere 23°C, o sistema alerta ou exige justificativa.
*   **Integração IoT**: Ler sensores automaticamente (via API ou MQTT) se o laboratório possuir data loggers.

## 6. MSA (Análise dos Sistemas de Medição)
**Contexto**: Essencial para indústria automotiva (IATF 16949). Calibração diz se o instrumento medi correto; MSA diz se o instrumento é adequado *para o processo*.
**Funcionalidade**:
*   Módulo de **Estudos R&R (Repetibilidade e Reprodutibilidade)**.
*   Cálculo automático de `%GRR`, `ndc` (número de categorias distintas).
*   Gráficos de Carta de CEP (Média e Amplitude) para validação do estudo.

## 7. Registro "Antes e Depois" (As Found / As Left)
**Problema**: Quando um instrumento reprova e é ajustado na mesma bancada, hoje é difícil registrar os dois estados no mesmo "Certificado".
**Solução Proposta**:
*   Permitir que o Checklist tenha dois modos ou colunas: "Recebimento" e "Após Ajuste".
*   Se o "Recebimento" falhar, habilita workflow de ajuste.
*   Gera histórico provando que o ajuste foi eficaz.

## 8. Portal de Solicitações (Self-Service)
**Cenário**: Produção ou Engenharia precisa calibrar um instrumento fora da data prevista (ex: caiu no chão).
**Solução Proposta**:
*   Painel simplificado para usuários "Não-Metrologistas".
*   Fluxo: Solicitar Calibração -> Aprovação do Lab -> Recebimento -> Devolução.
*   Email de notificação automático ("Seu paquímetro está pronto").

## 9. Assinatura Digital e Rastreabilidade Blockchain
**Tendência**: Eliminar papel 100%.
**Solução Proposta**:
*   Assinar os PDFs gerados com certificado digital ICP-Brasil (ou equivalente).
*   Hash do certificado gravado no banco para garantir imutabilidade.

## 10. Gestão de Riscos e Oportunidades (ISO 17025:8.5)
**Requisito de Norma**: O laboratório deve considerar riscos associados às atividades.
**Funcionalidade**:
*   Matriz de Risco vinculada a Processos ou Instrumentos Críticos.
*   Registro de Ações de Mitigação.
