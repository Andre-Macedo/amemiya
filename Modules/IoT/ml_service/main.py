import os
import io
import base64
import json
import numpy as np
import pandas as pd
import librosa
import joblib
import tensorflow as tf
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List
from scipy.stats import kurtosis, skew
from scipy.fft import fft, fftfreq
from scipy.signal import butter, filtfilt, hilbert
import matplotlib.pyplot as plt
import matplotlib.cm as cm
from PIL import Image
from contextlib import asynccontextmanager

# Configurações do modelo
TAXA_AMOSTRAGEM = 51200
TAMANHO_JANELA = 8192

# Variáveis globais para o modelo e o scaler
ml_models = {}

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Carregamento do modelo e do scaler
    model_path = "models/modelo_multimodal_incremental_mod3.keras"
    scaler_path = "models/scaler_multimodal_mod3.pkl"
    
    if os.path.exists(model_path) and os.path.exists(scaler_path):
        ml_models["model"] = tf.keras.models.load_model(model_path)
        ml_models["scaler"] = joblib.load(scaler_path)
        print("Modelos carregados com sucesso!")
    else:
        print(f"Erro: Arquivos de modelo não encontrados em {model_path} ou {scaler_path}")
    
    yield
    ml_models.clear()

app = FastAPI(lifespan=lifespan)

class AnomalyRequest(BaseModel):
    radial: List[float]
    tangential: List[float]
    axial: List[float]
    microphone: List[float]
    inicio_janela: int = 0

def obter_features_espectrais(yf_pos, xf_pos):
    features = {}
    limites_bandas = [
        (0, 50), (50, 100), (100, 200),
        (200, 400), (400, 800), (800, 1500),
        (1500, 3000), (3000, 5000), (5000, 10000)
    ]

    for limite_inf, limite_sup in limites_bandas:
        mascara = (xf_pos >= limite_inf) & (xf_pos < limite_sup)
        nome_banda = f"b_{limite_inf}_{limite_sup}"
        if np.any(mascara):
            features[f'{nome_banda}_energia'] = np.sum(yf_pos[mascara])
            features[f'{nome_banda}_pico'] = np.max(yf_pos[mascara])
        else:
            features[f'{nome_banda}_energia'] = 0.0
            features[f'{nome_banda}_pico'] = 0.0
    return features

def extrair_picos_envelope(sinal_array):
    nyquist = 0.5 * TAXA_AMOSTRAGEM
    corte_normalizado = 2000.0 / nyquist
    b, a = butter(4, corte_normalizado, btype='high')
    sinal_filtrado = filtfilt(b, a, sinal_array)

    sinal_analitico = hilbert(sinal_filtrado)
    envelope = np.abs(sinal_analitico)

    yf_env = np.abs(fft(envelope))
    xf_env = fftfreq(TAMANHO_JANELA, 1 / TAXA_AMOSTRAGEM)
    yf_env_pos = yf_env[:TAMANHO_JANELA//2]
    xf_env_pos = xf_env[:TAMANHO_JANELA//2]
    yf_env_pos[0] = 0

    indices_top = np.argsort(yf_env_pos)[-3:][::-1]
    freqs = np.round(xf_env_pos[indices_top], 2)
    amps = np.round(yf_env_pos[indices_top] / (TAMANHO_JANELA//2), 4)

    # Garante que sempre retornamos 3 picos (mesmo que duplicados se houver falha de sinal)
    while len(freqs) < 3:
        freqs = np.append(freqs, 0.0)
        amps = np.append(amps, 0.0)

    return freqs, amps

@app.post("/predict-anomalia")
async def predict_anomalia(request: AnomalyRequest):
    if "model" not in ml_models:
        raise HTTPException(status_code=503, detail="Modelo não carregado")

    try:
        # Converter listas para numpy
        rad = np.array(request.radial)
        tan = np.array(request.tangential)
        ax = np.array(request.axial)
        mic = np.array(request.microphone)

        # 1. Extração de Features Tabulares (Conforme Colab)
        rms_x = np.sqrt(np.mean(rad**2))
        rms_y = np.sqrt(np.mean(tan**2))
        rms_z = np.sqrt(np.mean(ax**2))
        rms_global = np.sqrt(rms_x**2 + rms_y**2 + rms_z**2)

        kurt_x = float(kurtosis(rad, fisher=False))
        kurt_y = float(kurtosis(tan, fisher=False))
        kurt_z = float(kurtosis(ax, fisher=False))

        skew_x = float(skew(rad, bias=False))
        skew_y = float(skew(tan, bias=False))
        skew_z = float(skew(ax, bias=False))

        yf_x = np.abs(fft(rad))
        xf_x = fftfreq(TAMANHO_JANELA, 1 / TAXA_AMOSTRAGEM)
        bandas_x = obter_features_espectrais(yf_x[:TAMANHO_JANELA//2], xf_x[:TAMANHO_JANELA//2])

        env_x_freq, env_x_amp = extrair_picos_envelope(rad)
        env_z_freq, env_z_amp = extrair_picos_envelope(ax)

        mic_rms = np.sqrt(np.mean(mic**2))
        mic_pico = np.max(np.abs(mic))
        fator_crista = float(mic_pico / mic_rms) if mic_rms > 0 else 0.0

        # Montar o vetor de features na ordem exata do treinamento
        features_list = [
            rms_global, rms_x, rms_y, rms_z,
            kurt_x, kurt_y, kurt_z,
            skew_x, skew_y, skew_z
        ]
        
        # Inserir as 18 colunas de bandas (energia, pico, energia, pico...)
        for key in bandas_x:
            features_list.append(bandas_x[key])
            
        # Picos de envelope
        features_list.extend([env_x_freq[0], env_x_amp[0], env_x_freq[1], env_x_amp[1]])
        features_list.extend([env_z_freq[0], env_z_amp[0], env_z_freq[1], env_z_amp[1]])
        
        # Piezo e Início Janela
        features_list.extend([mic_rms, fator_crista, float(request.inicio_janela)])

        # Escalar as features
        features_np = np.array([features_list])
        features_scaled = ml_models["scaler"].transform(features_np)

        # 2. Geração do Espectrograma Mel
        espectrograma = librosa.feature.melspectrogram(
            y=rad, sr=TAXA_AMOSTRAGEM, n_fft=TAMANHO_JANELA, hop_length=256, n_mels=64
        )
        imagem_db = librosa.power_to_db(espectrograma, ref=np.max)
        
        # Normalizar para 0-1 e aplicar viridis
        # O librosa.power_to_db(ref=np.max) gera valores de -80 a 0.
        # Vamos normalizar para [0, 1] antes de aplicar o cm.viridis
        imagem_norm = (imagem_db - np.min(imagem_db)) / (np.max(imagem_db) - np.min(imagem_db) + 1e-9)
        mapped_img = cm.viridis(imagem_norm)
        rgb_img = (mapped_img[..., :3] * 255).astype(np.uint8)
        
        # Preparar para o Keras (Input 2)
        keras_img_input = np.expand_dims(rgb_img.astype(np.float32) / 255.0, axis=0)

        # 3. Predição Multimodal
        prediction = ml_models["model"].predict([features_scaled, keras_img_input])
        class_idx = int(np.argmax(prediction, axis=1)[0])
        confidence = float(np.max(prediction))

        # Mapeamento de nomes de classes (Modo 3)
        nomes_classes = [
            'Normal', 'Desbal.', 'Desalinh. H', 'Desalinh. V',
            'Rol_Over/Esf', 'Rol_Over/Gaiola', 'Rol_Over/PistaExt',
            'Rol_Under/Esf', 'Rol_Under/Gaiola', 'Rol_Under/PistaExt'
        ]
        status = nomes_classes[class_idx]

        # 4. Converter Espectrograma para Base64
        pil_img = Image.fromarray(rgb_img)
        buffered = io.BytesIO()
        pil_img.save(buffered, format="PNG")
        spectrogram_b64 = base64.b64encode(buffered.getvalue()).decode("utf-8")

        return {
            "status": status,
            "confidence": confidence,
            "class_id": class_idx,
            "spectrogram_b64": spectrogram_b64
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
