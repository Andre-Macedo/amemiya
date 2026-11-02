# Medição de RPM e Alinhamento do Sensor Hall

Este guia explica como calibrar mecanicamente e configurar o sensor de efeito Hall (A3144 / SS49E) para medição precisa de rotação por minuto (RPM) em eixos rotativos industriais.

---

## 1. Princípio de Funcionamento

O sistema utiliza um ímã de neodímio ($N35$ ou $N52$) fixado no disco rotativo do eixo e um sensor de efeito Hall posicionado estaticamente no suporte.

A cada volta completa do eixo, o ímã passa em frente ao sensor Hall, gerando um pulso em nível lógico baixo na entrada de interrupção externa do ESP32-S3 (GPIO 10).

$$\text{RPM} = \frac{60 \cdot 10^6}{\Delta t_{\mu\text{s}}}$$

Onde $\Delta t_{\mu\text{s}}$ é o período de tempo medido em microssegundos pelo timer de alta precisão entre duas bordas de descida consecutivas.

---

## 2. Alinhamento Mecânico

Para evitar falso disparo ou perda de pulsos sob rotações elevadas:

1. **Distância de *Air Gap* (Folga):**
   - Mantenha a distância entre a face do ímã e o sensor Hall entre **$1{,}5\text{ mm}$ e $3{,}0\text{ mm}$**.
   - Distâncias superiores a $4\text{ mm}$ causam atenuação do campo magnético e perda de leitura.
   - Distâncias inferiores a $1\text{ mm}$ apresentam risco mecânico de colisão por vibração axial.
2. **Polaridade do Ímã:**
   - O sensor unipolar (A3144) é sensível apenas ao polo **Sul** do ímã.
   - Se a saída do sensor não comutar para $0\text{V}$ na passagem, inverta a face do ímã fixada no disco.

---

## 3. Tratamento de *Debounce* por Hardware e Software

Vibrações mecânicas intensas no mancal podem provocar repique elétrico falso (*chatter*) no momento da passagem magnética. O sistema implementa uma camada dupla de proteção:

### Hardware (Carrier Board)
* Filtro RC passa-baixas na linha de entrada: resistor série de $1\text{ k}\Omega$ e capacitor cerâmico de $10\text{ nF}$ para terra ($f_c \approx 16\text{ kHz}$).

### Software (Firmware ESP32-S3)
Janela de tempo de supressão (*dead-time lockout*) na rotina de interrupção:

```c
#define MIN_PERIOD_US 6000 // Limite físico para 10.000 RPM máximo

void IRAM_ATTR onHallPulse() {
    uint64_t now = esp_timer_get_time();
    uint64_t diff = now - last_pulse_time;
    
    if (diff > MIN_PERIOD_US) {
        pulse_period_us = diff;
        last_pulse_time = now;
        valid_pulses_count++;
    }
}
```

---

## 4. Validação com Tacômetro Padrão Óptico

Durante o processo de qualificação metrológica:
1. Aponte um tacômetro óptico calibrado padrão para uma fita reflexiva no mesmo eixo.
2. Varie a rotação do motor em 5 patamares: 600 RPM, 1200 RPM, 1800 RPM, 2400 RPM e 3000 RPM.
3. Compare o valor registrado no painel IoT com o padrão: o erro relativo $\delta$ deve ser rigorosamente inferior a $\pm 0{,}5\%$.
