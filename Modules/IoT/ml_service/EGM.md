# ML Service (FastAPI / XGBoost Specialist)

## Overview
Microserviço independente de inferência de Machine Learning voltado para predição de anomalias mecânicas e integridade preditiva industrial em máquinas rotativas. Executa em container dedicado (`amemiya-ml-service`), recebendo vetores de telemetria processados pelo broker/backend e aplicando modelo especialista XGBoost treinado em 36 features extraídas de acelerômetro triaxial (ADXL345) e microfone acústico MEMS (INMP441).

## Interface
- `POST /predict`: Recebe payload JSON contendo o vetor de 36 features normalizadas e retorna `{ "anomaly_detected": bool, "probability": float, "classification": string, "confidence": float }`.
- `GET /health`: Endpoint de liveness/readiness probe para orquestração Docker/Kubernetes.
- `main.py`: Ponto de entrada FastAPI com ciclo de vida de carregamento do modelo `xgb_specialist.json`.

## Invariants
- **Vetor Estrito de 36 Features**: O payload deve conter exatamente as 36 métricas estatísticas e espectrais definidas no contrato metrológico (RMS, curtose, fator de crista, assimetria, densidade espectral triaxial e bandas acústicas). Vetores com tamanho diferente devem ser rejeitados com HTTP 422.
- **Independência de Rotação Óptica**: A inferência não depende de leitura de RPM do tacômetro óptico (depreciado na v37.0).
- **Tratamento de Exceções**: Se o modelo não estiver carregado ou a inferência falhar por timeout, o microserviço deve retornar HTTP 500 para permitir que o backend aplique o fallback metrológico `pending_analysis`.
- **Stateless Execution**: O serviço não mantém sessão de nós ou histórico em memória; cada requisição é determinística.

## Side Effects
- Consumo de CPU/GPU leve durante inferência XGBoost.
- Escrita de logs estruturados em `stdout` para agregação Docker.
- Nenhuma mutação de banco de dados direta (o serviço não possui conexão direta com MySQL).

## Verification
- Run: `python -m pytest tests/` (ou requisição curl de verificação de liveness: `curl -f http://localhost:5000/health`)
