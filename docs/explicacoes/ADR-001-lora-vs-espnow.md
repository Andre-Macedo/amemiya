# ADR-001: Adoção de LoRa 915 MHz com Payload Binário em vez de ESP-NOW ou WiFi

* **Status:** Aceito
* **Data:** 2026-10-15
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

Em ambientes industriais pesados (fábricas metalmecânicas, usinas e galpões de manufatura), o monitoramento de vibração e temperatura de motores distribuídos requer uma tecnologia de comunicação sem fio com alta imunidade a ruído, capacidade de atravessar estruturas de concreto e maquinário metálico denso, e suporte a longa distância com baixo consumo de energia.

Foram avaliadas três tecnologias de transmissão sem fio nativas ou comuns no ESP32:
1. **WiFi 2.4 GHz padrão (TCP/IP)**
2. **ESP-NOW (Camada de enlace 2.4 GHz proprietária Espressif)**
3. **LoRa 915 MHz (Semtech SX1276 / E32-915T20D)**

---

## Decisão

Optou-se pela adoção de **LoRa na faixa ISM de 915 MHz** (específica para o Brasil e Américas segundo a regulamentação Anatel) utilizando módulos Ebyte E32-915T20D e transmissão em **payload binário compacto de 27 bytes**.

---

## Justificativa Técnica

1. **Penetração e Propagação Eletromagnética (915 MHz vs 2.4 GHz):**
   - O comprimento de onda na frequência de 915 MHz ($\lambda \approx 32{,}7\text{ cm}$) é mais de 2,6 vezes maior que em 2,4 GHz ($\lambda \approx 12{,}5\text{ cm}$).
   - Essa característica física proporciona atenuação por difração e reflexão significativamente menor ao contornar painéis elétricos, colunas e tubulações industriais metálicas.
   - O alcance obtido em campo com LoRa supera **$800\text{ metros}$ em ambiente industrial confinado**, enquanto ESP-NOW e WiFi sofrem atenuação severa após $30\text{ a }50\text{ metros}$ sob interferência de motores.

2. **Poluição Espectral na Faixa de 2.4 GHz:**
   - A banda de 2,4 GHz é intensamente congestionada por redes corporativas WiFi, Bluetooth e fornos de indução. O ESP-NOW sofre com retransmissões frequentes causadas por colisões CSMA/CA.
   - O rádio LoRa opera com modulação *Chirp Spread Spectrum* (CSS), permitindo demodular sinais até $19{,}5\text{ dB}$ abaixo do nível de ruído térmico ($SNR < 0$).

3. **Eficiência Energética da Célula de Bateria:**
   - O tempo de associação ao ponto de acesso WiFi varia entre 2 e 5 segundos, com corrente de pico de $380\text{ mA}$.
   - O módulo LoRa em modo UART transparente transmite o pacote binário de 27 bytes em apenas **$42\text{ ms}$**, consumindo corrente de transmissão por tempo insignificante.

---

## Consequências

* **Positivas:**
  - Alcance e estabilidade operacional comprovados na fábrica.
  - Autonomia de bateria multiplicada por mais de 5 vezes em comparação com WiFi.
  - Baixa carga sobre a infraestrutura de TI do cliente.
* **Negativas / Mitigações:**
  - Taxa de transferência máxima baixa ($\le 19{,}2\text{ kbps}$), impossibilitando o envio da forma de onda contínua bruta.
  - *Mitigação:* Processamento prévio de FFT e estatísticas descritivas (RMS, Kurtosis, Pico) diretamente no ESP32-S3 antes da transmissão.
