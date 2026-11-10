# Module Metrology (Gestão Metrológica & Calibrações ISO/IEC 17025)

## Overview
Módulo de gestão do parque de instrumentos de medição industrial, calibrações rastreáveis, cálculo de incerteza de medição, aplicação de regras de decisão de conformidade e emissão de certificados digitais. Implementa rigorosos controles de integridade metrológica e trilha de auditoria conforme FDA 21 CFR Part 11 e ABNT NBR ISO/IEC 17025.

## Interface
- **Modelos Principais**:
  - `Instrument.php`: Instrumento físico, número de série, localização, resolução, faixa e status operacional.
  - `Calibration.php`: Registro de calibração, incerteza expandida, desvio máximo, histórico *as-found* / *as-left* e hash criptográfico.
  - `ChecklistTemplate.php` & `ChecklistTemplateItem.php`: Estrutura hierárquica de pontos de medição e tolerâncias nominais.
  - `ReferenceStandard.php`: Padrões de referência rastreáveis RBC/Inmetro utilizados no procedimento.
- **Serviços de Cálculo & Conformidade**:
  - `MetrologyMath.php`: Cálculo de incerteza combinada ($u_c$), incerteza expandida ($U = k \cdot u_c$) e graus de liberdade efetivos ($v_{eff}$ via Welch-Satterthwaite).
  - `MpeCalculator.php`: Avaliação do Erro Máximo Permissível (MPE) e julgamento metrológico.
  - `DecisionRules/`: Regras de conformidade (regras com e sem banda de guarda conforme ILAC-G8).
  - `PdfSignerService.php`: Geração de certificado PDF com carimbo de tempo, hash SHA-256 e assinatura eletrônica qualificada.
- **APIs RESTful (`/api/v1/metrology`)**:
  - `CalibrationApiController.php`: Submissão de ensaios (`POST /calibrations`) e consulta de certificados.
  - `InstrumentApiController.php`: Consulta de inventário de instrumentos para uso em bancada e sincronização mobile.
  - `CalibrationPdfController.php`: Renderização sob demanda e streaming de certificados de calibração.

## Invariants
- **21 CFR Part 11 Electronic Signature**: Nenhuma calibração pode ser aprovada sem a reautenticação com senha do operador responsável (`password` obrigatório no `StoreCalibrationRequest.php`), gerando hash criptográfico indelével com carimbo UTC.
- **Imutabilidade de Certificados Emitidos**: Calibrações com status `completed` tornam-se de leitura estrita; alterações de dados exigem abertura de calibração retificadora ou não-conformidade formal.
- **Condições Ambientais Obrigatórias**: Toda calibração metrológica exige registro explícito de temperatura (°C) e umidade relativa (%) da sala de ensaio.
- **Rastreabilidade dos Padrões**: Todo checklist concluído deve associar padrões de referência válidos (data de calibração do padrão não expirada na data do ensaio).
- **Happy Path Last**: Controladores e serviços devem validar erros de permissão, estados e limites antes de persistir o resultado.

## Side Effects
- Gravação nas tabelas `instruments`, `calibrations`, `checklists`, `checklist_items` e `access_logs`.
- Geração de arquivos PDF assinados em `storage/app/public/certificates/`.
- Atualização atômica do status do instrumento (`active`, `expired` ou `rejected`) e recálculo da próxima data de calibração.

## Verification
- Run: `docker-compose exec app php artisan test --filter=Metrology`
