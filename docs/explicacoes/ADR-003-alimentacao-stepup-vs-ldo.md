# ADR-003: Arquitetura de Alimentação: Regulador Chaveado Step-Down vs LDO

* **Status:** Aceito
* **Data:** 2026-10-20
* **Decisores:** André Macedo, Equipe de Engenharia Lean Tech

---

## Contexto

A placa Carrier Board necessita operar em dois cenários distintos de instalação:
1. **Alimentação Industrial Fixa (Bancada ou Painel):** Alimentada por fonte externa de $12\text{V}$ ou $24\text{V CC}$, com capacidade de corrente ilimitada.
2. **Alimentação por Bateria Autônoma:** Alimentada por pack de bateria química Li-Ion / LiFePO4 ($3{,}2\text{V}$ a $4{,}2\text{V}$).

O microcontrolador ESP32-S3 e os periféricos operam a uma tensão nominal rigorosamente estabilizada de $3{,}3\text{V CC}$. Picos de corrente de até $400\text{ mA}$ ocorrem durante transmissões via rádio.

---

## Decisão

Adotou-se uma **arquitetura de regulação híbrida em 2 estágios em cascata**:
1. **Estágio 1 (Para fontes industriais 9V a 24V):** Conversor chaveado síncrono *Buck/Step-Down* de alta eficiência derivando barramento primário de **$+5\text{V CC}$**.
2. **Estágio 2 (Regulação Final para o ESP32 e Sensores):** Regulador linear de baixa queda (*Low-Dropout - LDO*) de alta precisão derivando o barramento ultra-estável de **$+3{,}3\text{V CC}$**.

---

## Justificativa Técnica

1. **Eficiência Térmica sob Entrada de 24V:**
   - Se utilizássemos um LDO diretamente de $24\text{V}$ para $3{,}3\text{V}$, a queda de tensão seria de $\Delta V = 20{,}7\text{V}$.
   - Com uma corrente ativa de $150\text{ mA}$, a potência dissipada em forma de calor puro seria:
     $$P_{dissipada} = 20{,}7\text{V} \cdot 0{,}15\text{A} \approx 3{,}1\text{ Watts}$$
     Isso exigiria dissipadores de calor de alumínio volumosos e aqueceria o interior do invólucro estanque (IP65), alterando a leitura térmica dos sensores.
   - O regulador chaveado síncrono atinge eficiência de $88\%$ a $92\%$, dissipando menos de $0{,}08\text{ W}$.

2. **Ruído de Chaveamento e Ruído Metrológico:**
   - Conversores chaveados introduzem *ripple* de alta frequência ($300\text{ kHz}$ a $1{,}5\text{ MHz}$) nas linhas de alimentação.
   - Esse ruído chaveado afeta negativamente o conversor analógico-digital (ADC) do ESP32 e o ruído de fundo do acelerômetro ADXL345.
   - O regulador LDO no Estágio 2 oferece alto índice de rejeição de fonte de alimentação (*Power Supply Rejection Ratio - PSRR* $> 65\text{ dB}$), fornecendo uma linha limpa de ruído para as medições metrológicas.

---

## Consequências

* **Positivas:**
  - Placa compacta sem aquecimento mecânico e sem dissipadores de calor.
  - Imunidade a ruídos elétricos com conformidade EMC/EMI industrial.
  - Tensão estável garantindo repetibilidade de medição em conformidade com ISO 17025.
