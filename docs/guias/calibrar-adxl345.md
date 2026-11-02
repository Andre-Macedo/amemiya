# Calibração Estática do ADXL345 (±1g)

Este guia prático descreve o procedimento de calibração metrológica estática de 6 posições para determinação do offset ($O$) e fator de sensibilidade ($S$) dos 3 eixos ($X, Y, Z$) do acelerômetro MEMS ADXL345.

---

## 1. Princípio Metrológico

Na calibração estática sob aceleração gravitacional local ($g \approx 9{,}80665\text{ m/s}^2$), o sensor é orientado sequencialmente de forma que cada eixo fique alinhado com $+1g$ e $-1g$.

A equação de calibração para cada eixo $i \in \{x, y, z\}$ é:

$$A_i = (V_i - O_i) \cdot S_i$$

Onde:
* $V_i$: leitura digital bruta (*raw LSB*) obtida pelo conversor analógico-digital de 13 bits.
* $O_i$: offset (deslocamento de zero), calculado como:
  $$O_i = \frac{V_{i,+1g} + V_{i,-1g}}{2}$$
* $S_i$: fator de ganho/sensibilidade, calculado como:
  $$S_i = \frac{2 \cdot 1{,}000}{V_{i,+1g} - V_{i,-1g}}$$

---

## 2. Materiais Necessários

1. **Torre de Sensores Amemiya** com acelerômetro ADXL345 instalado.
2. **Bloco padrão de esquadro de granito** ou superfície de calibração usinada plana (precisão $\le 5\ \mu\text{m}$).
3. Nível digital de precisão metrológica (resolução $\le 0{,}02^\circ$).
4. Script de aquisição de ensaio estático no firmware ou CLI `dev.py`.

---

## 3. Procedimento Passo a Passo

### Posição 1 e 2: Eixo Z (+1g e -1g)
1. Coloque a torre perfeitamente na vertical sobre a base plana com a face do conector para cima (Eixo $Z$ apontando para o centro da Terra: $+1g$).
2. Colete 500 amostras com taxa de 100 Hz e calcule a média: anote $V_{z,+1g}$.
3. Inverta a torre de ponta-cabeça, mantendo a perpendicularidade (Eixo $Z$ em $-1g$).
4. Colete 500 amostras e calcule a média: anote $V_{z,-1g}$.

### Posição 3 e 4: Eixo X (+1g e -1g)
1. Apoie a torre sobre a lateral de referência $X+$ perpendicular à gravidade ($+1g$).
2. Colete as 500 amostras: anote $V_{x,+1g}$.
3. Gire $180^\circ$ sobre a lateral oposta ($X-$ em $-1g$): anote $V_{x,-1g}$.

### Posição 5 e 6: Eixo Y (+1g e -1g)
1. Apoie a torre sobre a lateral de referência $Y+$ perpendicular à gravidade ($+1g$): anote $V_{y,+1g}$.
2. Gire $180^\circ$ sobre a lateral oposta ($Y-$ em $-1g$): anote $V_{y,-1g}$.

---

## 4. Gravação dos Coeficientes no Nó IoT

Com os valores medidos, registre os coeficientes no cadastro do instrumento via API REST ou Painel Filament:

```json
{
  "instrument_code": "ACCEL-001",
  "calibration_data": {
    "offset_x": 4.2,
    "offset_y": -2.8,
    "offset_z": 12.1,
    "scale_x": 0.00392,
    "scale_y": 0.00391,
    "scale_z": 0.00394,
    "expanded_uncertainty_g": 0.015,
    "coverage_factor_k": 2.0
  }
}
```

O firmware aplicará as correções diretamente na amostragem em tempo real antes de calcular o RMS de vibração de acordo com a norma **ISO 20816-3**.
