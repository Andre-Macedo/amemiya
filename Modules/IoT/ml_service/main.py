import os
import numpy as np
import xgboost as xgb
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Dict, Any, Optional
from contextlib import asynccontextmanager

# Variáveis globais para os modelos
ml_models = {}

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Carregamento do modelo XGBoost Especialista
    xgb_model_path = "models/modelo_xgboost_especialista.json"
    
    try:
        if os.path.exists(xgb_model_path):
            model = xgb.XGBClassifier()
            model.load_model(xgb_model_path)
            ml_models["xgb_specialist"] = model
            print(f"Modelo XGBoost carregado de {xgb_model_path}")
        else:
            print(f"Aviso: Modelo XGBoost não encontrado em {xgb_model_path}")
    except Exception as e:
        print(f"Erro ao carregar modelo XGBoost: {e}")
    
    yield
    ml_models.clear()

app = FastAPI(lifespan=lifespan, title="Metrology Cloud ML Service")

class FeatureRequest(BaseModel):
    features: Dict[str, float]

# Lista exata das 36 features conforme esperado pelo modelo (Ordem é CRÍTICA)
FEATURE_NAMES = [
    "z_rms", "z_kurtosis", "z_skewness", "z_energy_band_1", "z_peak_band_1", "z_energy_band_2", "z_energy_band_3", "z_energy_band_4",
    "y_rms", "y_kurtosis", "y_skewness", "y_energy_band_1", "y_peak_band_1", "y_energy_band_2", "y_energy_band_3", "y_energy_band_4",
    "x_rms", "x_kurtosis", "x_skewness", "x_energy_band_1", "x_peak_band_1", "x_energy_band_2", "x_energy_band_3", "x_energy_band_4",
    "mic_rms", "mic_crest_factor", "mic_energy_0_500", "mic_peak_0_500", "mic_energy_500_2000", "mic_peak_500_2000", 
    "mic_energy_2000_5000", "mic_peak_2000_5000", "mic_energy_5000_10000", "mic_peak_5000_10000", "mic_energy_above_10000", "mic_peak_above_10000"
]

@app.post("/predict-anomalia")
async def predict_anomalia(request: FeatureRequest):
    if "xgb_specialist" not in ml_models:
        raise HTTPException(status_code=503, detail="Modelo XGBoost não carregado no servidor")

    try:
        # Extrair features na ordem correta
        feat_dict = request.features
        feat_array = []
        
        for name in FEATURE_NAMES:
            # Tenta pegar o valor, se não existir coloca 0.0
            feat_array.append(float(feat_dict.get(name, 0.0)))

        # Converter para formato numpy esperado pelo XGBoost
        X = np.array([feat_array])
        
        model = ml_models["xgb_specialist"]
        
        # predict_proba retorna [[prob_classe_0, prob_classe_1]]
        # Classe 1 é considerada falha/defeito
        probs = model.predict_proba(X)[0]
        prob_defeito = float(probs[1])
        
        # Determinar status baseado em threshold padrão de 0.5
        # Mas vamos devolver a confiança bruta para o Laravel decidir
        status = "saudavel"
        if prob_defeito > 0.8:
            status = "falha_confirmada"
        elif prob_defeito > 0.5:
            status = "analise_pendente"

        return {
            "status": status,
            "confidence": prob_defeito if prob_defeito > 0.5 else float(probs[0]),
            "prob_defect": prob_defeito,
            "model_type": "xgboost_specialist"
        }

    except Exception as e:
        print(f"Erro na predição: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
async def health():
    return {
        "status": "online",
        "models_loaded": list(ml_models.keys())
    }
