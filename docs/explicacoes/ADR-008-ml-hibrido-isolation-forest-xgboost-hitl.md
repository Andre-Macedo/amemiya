# ADR-008: Pipeline de ML Híbrido (`Isolation Forest` + `XGBoost`) e Retroalimentação *Human-in-the-Loop*

* **Status:** Aceito *(Infraestrutura de rotulação, datasets e Model Registry prontas no Laravel; integração do `Isolation Forest` e atualização de features sem áudio pendentes no `ml_service`)*
* **Data:** 2026-11-16
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

Classificadores puramente supervisionados (como `XGBoost`) sofrem de um problema clássico em chão de fábrica (*cold-start*): ao instalar o sensor em uma máquina nova, ainda não existem amostras reais rotuladas de cada falha possível naquele ativo específico. Além disso, limiares fixos isolados geram falsos alarmes em transientes operacionais (partidas, variação de carga) e podem sofrer degradação se choques externos acidentais entrarem na linha de base (*baseline poisoning*).

---

## Decisão

Adota-se uma arquitetura de **Machine Learning em Dois Estágios** combinada a um ciclo de **curadoria humana (*Human-in-the-Loop*)** e **governança de datasets** (documentada em `REGRAS_DE_NEGOCIO_MLOPS_E_AMOSTRAGEM.md`):

### 1. Inferência Híbrida + Desempate Normativo
* **Estágio Não Supervisionado (`Isolation Forest` — *Cold-Start*):** treinado exclusivamente sobre o dataset `baseline_normal` (primeiros ensaios com a máquina saudável e comissionada) para detectar desvios anômalos desconhecidos sem precisar de rótulos prévios de falha.
* **Estágio Supervisionado (`XGBoost` — Fase Quente):** classifica causa-raiz específica (`saudavel`, `desbalanceamento`, `desalinhamento`, `folga_mecanica`, `falha_rolamento`) à medida que há massa curada na tabela `iot_ml_bursts`.
* **Autoridade Metrológica (`ISO 20816-3`):** o serviço `PrescriptiveDiagnosticsService` cruza a predição da IA com as Zonas A–D da `ISO20816SeverityService` (ex.: `IA normal + Zona D` continua exigindo intervenção imediata; `IA anômala + Zona A` gera alerta preventivo incipiente).

### 2. Retroalimentação *Human-in-the-Loop* (Triagem via API / Painel)
Através do endpoint `POST /api/v1/iot-logs/{id}/label` (`IoTMLDatasetApiController::labelLog()`), o engenheiro/operador audita anomalias com 3 destinos rigorosos:
1. **Confirmação de Defeito (`desbalanceamento`, `folga_mecanica`, `falha_rolamento`, etc.):** grava janela validada em dataset `diagnostic_multiclass`.
2. **Falso Alarme Operacional (`falso_positivo_operacional`):** convertido em `saudavel` com origem `hard_negative_triaged`, ensinando o modelo a tolerar regimes normais da fábrica.
3. **Descarte de Outlier (`descarte_outlier`):** arquivado **sem** entrar nem no `baseline_normal` nem no dataset de treino (impede *baseline poisoning* por batida mecânica externa).

### 3. Segregação de Datasets, Retreino e *Model Registry*
* Datasets separados em `baseline_normal`, `diagnostic_multiclass` e `benchmark_golden_set` (conjunto cego congelado para bloquear regressão entre versões).
* Validação cruzada obrigatoriamente agrupada por sessão (`GroupKFold` por `session_id`) evitando vazamento temporal entre janelas vizinhas de `320 ms`.
* Registro e promoção controlada de artefatos em `IoTMLModelApiController` (`candidate` $\rightarrow$ `shadow` $\rightarrow$ `in_production`).

---

## Justificativa Técnica

* **Base Científica:** fundamentado em Di Maggio, Brusa & Delprete (2025, [`doi:10.1109/iccad64771.2025.11099203`](https://doi.org/10.1109/iccad64771.2025.11099203)) sobre *cold-start* não supervisionado com *Isolation Forest*, combinado à separação determinística da `ISO 20816-3`.
* **Melhoria Contínua Segura:** a triagem diferenciada entre *Hard Negative* e *Outlier* permite evoluir os datasets com os novos ensaios da bancada V2 sem contaminar a sensibilidade do detector.

---

## Consequências e Estado Atual Real

* **Já implementado e testado no Backend Laravel:**
  * API de rotulação *Human-in-the-Loop*, criação automática de datasets, cálculo de métricas, exportação CSV para treino e *Model Registry* (`IoTMLDatasetApiController`, `IoTMLModelApiController`, cobertos em `IoTMLDatasetTest`).
* **Lacunas reais pendentes no Microserviço Python (`Modules/IoT/ml_service/main.py`):**
  * Atualmente o `main.py` carrega **apenas** `modelo_xgboost_especialista.json` e ainda utiliza `FEATURE_NAMES` com 36 colunas (incluindo 12 colunas acústicas `mic_*` legadas do `INMP441`, removido na [ADR-005](/explicacoes/ADR-005-remocao-inmp441-e-tacometro)).
  * O serviço de predição com **`Isolation Forest`** em produção e o disparo **automático** de retreinamento (hoje feito manualmente via exportação CSV + notebooks Jupyter em `ml_service/notebooks/`) permanecem como entregas pendentes da etapa de MLOps com os dados reais da bancada V2.
