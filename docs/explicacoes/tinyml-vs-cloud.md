# Inferência Híbrida em 2 Estágios: TinyML vs Cloud

Esta página explica a fundamentação teórica por trás da decisão de adotar uma **arquitetura híbrida de inteligência artificial em dois estágios** para o diagnóstico preditivo em ativos rotativos industriais.

---

## 1. O Problema da Inferência Centralizada Pura

Em aplicações de manutenção preditiva industrial baseadas em análise de vibração triaxial com amostragem a 3200 Hz:
* Amostrar 1024 pontos por eixo gera 3072 pontos flutuantes por leitura.
* Transmitir toda a forma de onda bruta (*raw waveform*) por redes de rádio de baixa potência (LoRaWAN ou satélite) é energeticamente inviável: consome dezenas de segundos de transmissão e drena uma bateria em poucas semanas.
* Por outro lado, confiar exclusivamente em IA simples na borda limita a capacidade de cruzar tendências de semanas com histórico de manutenção e parâmetros térmicos operacionais.

---

## 2. A Solução: Arquitetura em 2 Estágios

```
[Forma de Onda 3200 Hz] 
           |
           v
[ESTÁGIO 1: Borda (ESP32-S3)] ----> Filtro Físico & TinyML Rápido
           |                        - Alarme instantâneo (< 50 ms)
           |                        - Zona ISO 20816-3 (A, B, C, D)
           |                        - Redução de dados: 27 bytes
           v
[ESTÁGIO 2: Nuvem (Python XGBoost)] -> Diagnóstico Prescritivo Profundo
                                    - Análise multivariada com histórico
                                    - Identificação de causa-raiz (BPFO, BPFI)
                                    - Estimativa de Vida Útil Restante (RUL)
```

---

## 3. Comparativo Funcional dos Estágios

| Critério | Estágio 1: TinyML na Borda (ESP32-S3) | Estágio 2: Cloud ML (FastAPI + XGBoost) |
| :--- | :--- | :--- |
| **Tempo de Resposta** | Ultrarrápido ($< 50\text{ ms}$) | Rápido ($< 300\text{ ms}$) |
| **Dependência de Conectividade** | Zero (opera 100% autônomo e isolado) | Requer conectividade com o broker |
| **Ação em Caso de Emergência** | Disparo de relé mecânico, alarme sonoro e LED vermelho | Disparo de Notificação Push, E-mail e Ordem de Serviço |
| **Entrada do Modelo** | Janela temporal de aceleração e FFT instantânea | Vetor com 14 features extraídas + histórico móvel de 30 dias |
| **Algoritmo Utilizado** | Árvore de decisão quantizada e regras ISO | Classificador Gradiente Boosted (**XGBoost**) com SHAP values |
| **Consumo Computacional** | $\approx 120\text{ kB}$ RAM / int8 | Modelo completo em Python 3.12 com inferência paralela |

---

## 4. Conclusão da Abordagem

Essa abordagem híbrida garante o melhor dos dois mundos:
1. **Segurança de campo ininterrupta:** A máquina é protegida em tempo real contra catástrofes mecânicas mesmo com queda total do roteador ou do link de internet da fábrica.
2. **Inteligência prescritiva robusta:** Os engenheiros recebem diagnósticos ricos identificando exatamente qual pista do rolamento está desgastada e qual a gravidade da evolução de desbalanceamento.
