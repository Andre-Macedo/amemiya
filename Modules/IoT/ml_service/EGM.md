# ML Service (FastAPI / XGBoost Specialist)

## Overview
Microserviço independente de inferência de Machine Learning voltado para predição de anomalias mecânicas e integridade preditiva industrial em máquinas rotativas. Executa em container dedicado (`amemiya-ml-service`), recebendo vetores de telemetria processados pelo broker/backend e aplicando modelo especialista XGBoost treinado em 36 features extraídas de acelerômetro triaxial (ADXL345) e microfone acústico MEMS (INMP441).

## Interface
- `POST /predict-anomalia`: Recebe payload JSON `{"features": {...}}` contendo até 36 features e retorna `{ "status": string, "confidence": float, "prob_defect": float, "model_type": string }`.
- `GET /health`: Endpoint de liveness/readiness probe para orquestração Docker.
- `main.py`: Ponto de entrada FastAPI com ciclo de vida de carregamento do modelo `modelo_xgboost_especialista.json`.

## Invariants
- **Vetor de Features (`FEATURE_NAMES`)**: O modelo especialista espera 36 métricas em ordem fixa (24 de vibração triaxial ADXL345 e 12 bandas acústicas INMP441, preenchendo chaves ausentes com `0.0` até eventual retreino sem microfone).
- **Estimação Sem Tacômetro**: A inferência não depende de leitura de tacômetro físico no ESP32.
- **Tratamento de Exceções**: Se o modelo não estiver carregado (`503`) ou a inferência falhar (`500`), o backend (`MachineLearningService`) aplica o fallback metrológico `indeterminado` / `pending_analysis`.
- **Stateless Execution**: O serviço não mantém sessão de nós ou histórico em memória; cada requisição é determinística.

## Side Effects
- Consumo de CPU/GPU leve durante inferência XGBoost.
- Escrita de logs estruturados em `stdout` para agregação Docker.
- Nenhuma mutação de banco de dados direta (o serviço não possui conexão direta com MySQL).

## Verification
- Run: `curl -f http://localhost:8000/health` (porta interna do container: `8000`)
