# ADR-002: Estratégia de Deep-Sleep e Power-Gating dos Sensores

* **Status:** Aceito
* **Data:** 2026-10-18
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

Para que os nós sensores industriais operem de forma verdadeiramente autônoma sem necessidade de passagem de eletrodutos de alimentação AC até cada mancal de máquina, a vida útil da bateria precisa ser de pelo menos 12 a 24 meses.

Embora o ESP32-S3 possua modos de baixo consumo (*Deep-Sleep* com corrente residual de $\approx 8\ \mu\text{A}$), periféricos conectados continuamente às linhas de alimentação de $3{,}3\text{V}$ (acelerômetro ADXL345, sensor de temperatura DS18B20, divisor resistivo de bateria e LEDs de status) continuam consumindo corrente estática de fuga (*quiescent current*). Juntos, esses componentes podem drenar entre $1{,}2\text{ mA}$ e $3{,}5\text{ mA}$ de forma contínua, esgotando uma bateria de 2000 mAh em menos de 30 dias.

---

## Decisão

Adotou-se uma arquitetura de **chaveamento de potência por hardware (*Power-Gating*)** acionada pelo pino `GPIO 7` (`PWR_SW_EN`), controlando um MOSFET de potência canal P (ou chave de carga integrada de alta eficiência com slew-rate controlado).

---

## Justificativa Técnica

1. **Topologia do Circuito de Power-Gating:**
   - A linha de alimentação dos sensores foi isolada em um barramento separado denominado `+3V3_SW`.
   - Um transistor bipolar ou canal N pequeno atua como driver de gate para o MOSFET canal P de baixa resistência $R_{DS(on)} < 50\text{ m}\Omega$.
   - Quando o ESP32-S3 entra em *Deep-Sleep*, o pino `GPIO 7` vai para nível baixo, cortando fisicamente a alimentação de todos os sensores da torre externa e dos circuitos de condicionamento.

2. **Balanço de Correntes e Ciclo de Trabalho (*Duty Cycle*):**
   - **Fase de Aquisição e Transmissão (Ativo):**
     - Duração: $1{,}2\text{ segundos}$ (Amostragem de 1024 pontos a 3200 Hz + FFT + Leitura de temperatura + Transmissão LoRa).
     - Corrente média: $85\text{ mA}$.
   - **Fase de Repouso (*Deep-Sleep*):**
     - Duração: $598{,}8\text{ segundos}$ (intervalo de 10 minutos).
     - Corrente residual com Power-Gating: **$< 15\ \mu\text{A}$** total na placa.
   - **Consumo Médio Efetivo:**
     $$I_{medio} = \frac{(85\text{ mA} \cdot 1{,}2\text{ s}) + (0{,}015\text{ mA} \cdot 598{,}8\text{ s})}{600\text{ s}} \approx 0{,}185\text{ mA}$$
   - **Autonomia Estimada com Bateria LiFePO4 de 2000 mAh:**
     $$\text{Autonomia} = \frac{2000\text{ mAh} \cdot 0{,}85}{0{,}185\text{ mA}} \approx 9.189\text{ horas} \approx \mathbf{12{,}7\text{ meses}}$$

---

## Consequências

* **Positivas:**
  - Redução de mais de $98\%$ no consumo de energia em repouso.
  - Viabilização de operação prolongada em campo sem troca de bateria.
* **Negativas / Cuidados:**
  - Ao reenergizar o barramento `+3V3_SW`, deve-se aguardar um tempo de estabilização (*settling time*) de $5\text{ ms}$ antes de iniciar comunicações I2C para evitar travamento do barramento.
