📋 Prompt de Verificação e Documentação Completa
CONTEXTO: Estou a atuar como Tech Lead. Finalizámos uma refatoração completa do módulo Metrology para uma arquitetura Enterprise usando Laravel Modules, Filament, DTOs, Actions/Services e Polimorfismo. Preciso criar a documentação final BUSINESS_RULES.md. O utilizador exige que seja extremamente detalhado, explicando cada classe importante, cada regra de negócio e a estrutura dos dados.

TAREFA: Antes de escreveres o documento final, verifica mentalmente se tens informações suficientes para cobrir todos os pontos do checklist abaixo. Se faltar algo, analisa o código novamente. Em seguida, gera o documento Markdown cobrindo 100% destes pontos.

✅ CHECKLIST DE CONTEÚDO OBRIGATÓRIO
1. Introdução e Glossário

[ ] O documento explica que o sistema segue a norma ISO/IEC 17025?

[ ] Existe um glossário definindo: Incerteza (Tipo A/B), Viés/Tendência, MPE (Erro Máximo Permissível), Rastreabilidade, Padrão de Referência, Fator k.

2. Arquitetura do Código (Explicação Técnica)

[ ] Design Pattern: Explica a separação estrita entre Actions (Verbos/Escrita) e Services (Substantivos/Cálculo)?

[ ] Polimorfismo: Detalha a interface CalibratableItem. Explica por que Instrument e ReferenceStandard a implementam (para permitir calibração agnóstica).

[ ] Strategy Pattern: Explica como a DecisionRuleStrategy seleciona entre Simple Acceptance, Guard Band, etc.

[ ] DTOs: Explica o uso de MeasurementReading para garantir integridade matemática (evitando arrays soltos).

3. Catálogo de Ficheiros e Responsabilidades (Deep Dive)

Explique a função exata de cada um destes ficheiros:

[ ] UncertaintyCalculator.php (O motor matemático).

[ ] ProcessCalibrationAction.php (O orquestrador do fluxo).

[ ] CreateChecklistAction.php (A geração dinâmica de inputs).

[ ] CalibrationValidator.php (As regras de bloqueio "Guard Clauses").

[ ] CalibrationResult & ItemStatus (Enums).

4. Detalhes dos Models (Entidades)

[ ] Instrument: Explica o tratamento do campo mpe (filtro de string para float), relação com InstrumentType e cálculo de vencimento.

[ ] ReferenceStandard: Explica a lógica de "Pai/Filho" (Kits), rastreabilidade e atualização automática de valores reais após calibração.

[ ] Calibration: Explica o polimorfismo (calibratable_type) e os campos de resultado.

5. Regras de Negócio e Matemática (Explícito)

[ ] Transcreve as fórmulas usadas no MetrologyMath:

Cálculo do Viés (Erro).

Cálculo de Incerteza Combinada (soma quadrática).

Cálculo de Incerteza Expandida (multiplicação por k).

Cálculo de Graus de Liberdade (Veff).

[ ] Lista as regras de validação: "Não pode calibrar se status for 'Lost'", "Não pode apagar Template se usado (Restrict)".

6. Workflows (Passo-a-Passo)

[ ] Fluxo 1: Input de Dados (Filament Form -> DTO).

[ ] Fluxo 2: Cálculo (Service processa DTO -> Resultado Matemático).

[ ] Fluxo 3: Decisão (Comparação Resultado vs MPE via Strategy).

[ ] Fluxo 4: Persistência (Action salva Calibração -> Dispara Evento -> Atualiza Item).

INSTRUÇÃO DE SAÍDA: Gera o conteúdo do ficheiro Modules/Metrology/docs/BUSINESS_RULES.md.

Usa formatação Markdown rica (Tabelas, Listas, Código, Citações).

Sê didático: Imagina que estás a explicar o sistema a um novo Senior Developer que acabou de entrar na equipa.

Não saltes nenhuma secção do checklist.