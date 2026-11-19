# Norma ISO 20816-3 e Zonas de Severidade

Esta página apresenta os critérios normativos da **ISO 20816-3:2022** (*Mechanical vibration — Measurement and evaluation of machine vibration*), detalhando as 4 zonas de severidade vibracional utilizadas pelo sistema para monitoramento contínuo de máquinas industriais.

---

## 1. Escopo e Classificação de Grupos de Máquinas

A norma **ISO 20816-3** se aplica exclusivamente a conjuntos industriais com potência nominal superior a $15\text{ kW}$ e velocidades operacionais entre $120\text{ RPM}$ e $30.000\text{ RPM}$. As máquinas são categorizadas em grupos conforme seu tipo e potência:

* **Grupo 1:** Grandes máquinas com potência acima de $300\text{ kW}$ (ex: turbogeradores, motores elétricos pesados com altura de eixo $H \ge 315\text{ mm}$).
* **Grupo 2:** Máquinas de porte médio com potência nominal entre $15\text{ kW}$ e $300\text{ kW}$ (ou motores elétricos com altura de eixo $160\text{ mm} \le H < 315\text{ mm}$).

Para ativos fora dessa faixa de potência ($< 15\text{ kW}$):
* **Motores fracionários / bancada didática (`baseline_only` — ex.: motor $1/4\text{ CV} \approx 0{,}18\text{ kW}$):** A norma ISO 20816-3 **não se aplica**. O sistema continua calculando e exibindo continuamente a **velocidade RMS ($V_{RMS}$ em $\text{mm/s}$)** no painel e nos logs, mas **oculta a régua fixa de zonas ISO (A–D)** e delega a avaliação de severidade à linha de base do ativo + modelos de IA (`Isolation Forest` / `XGBoost`).
* **Referência opcional para pequenas máquinas (`small_machine`, $1\text{–}15\text{ kW}$):** Caso o gestor habilite explicitamente no cadastro da máquina, adota-se o critério histórico da **ISO 10816-1 Classe I** ($0{,}71 / 1{,}80 / 4{,}50\text{ mm/s}$ em base rígida).

A classe da fundação mecânica também determina os limites:
* **Suporte Rígido (*Rigid Support*):** A primeira frequência natural do conjunto montado é maior que a frequência fundamental de rotação em pelo menos $25\%$.
* **Suporte Flexível (*Flexible Support*):** A primeira frequência natural está abaixo da frequência de rotação.

---

## 2. Limites de Severidade Vibracional (ISO 20816-3 · Grupo 2 - Suporte Rígido)

Quando o ativo pertence ao escopo normativo de máquinas industriais ($\ge 15\text{ kW}$, Grupo 2 em suporte rígido), a régua de severidade e os limites de velocidade RMS ($V_{RMS}$) entre $10\text{ Hz}$ e $1000\text{ Hz}$ são exibidos conforme a tabela abaixo:

| Zona | Faixa de Velocidade ($V_{RMS}$) | Significado Operacional | Ação Recomendada pelo Sistema |
| :---: | :---: | :--- | :--- |
| **Zona A** | $0{,}00$ a $1{,}40\text{ mm/s}$ | **Nova / Excelente:** Vibração típica de máquinas recém-instaladas ou em perfeito estado mecânico. | Nenhuma ação necessária. Status normal (LED Verde). |
| **Zona B** | $1{,}40$ a $2{,}80\text{ mm/s}$ | **Aceitável:** Vibração permissível para operação contínua e irrestrita a longo prazo. | Monitoramento periódico de rotina. |
| **Zona C** | $2{,}80$ a $4{,}50\text{ mm/s}$ | **Alerta:** Vibração insatisfatória para operação contínua. Pode operar por período limitado até a manutenção programada. | Disparo de alerta preventivo (LED Amarelo). Agendamento de intervenção. |
| **Zona D** | $> 4{,}50\text{ mm/s}$ | **Perigo / Crítico:** A severidade é alta o suficiente para causar danos imediatos ao ativo ou colapso de mancal. | Disparo de alarme crítico (LED Vermelho + Buzzer). Recomendação de desligamento imediato. |

---

## 3. Implementação no Nó Amemiya e no Backend

O firmware calcula e envia sempre a velocidade RMS em banda larga (`10 Hz – 1000 Hz`, em `mm/s`), independentemente do porte da máquina. A classificação em Zonas A–D só é ativada quando o ativo está configurado em um grupo normativo aplicável (`group_2`, `group_1` ou referência `small_machine`):

```c
typedef enum {
    ISO_NOT_APPLICABLE = 0, // Motores fracionários (< 15 kW): usa apenas v_rms (mm/s) + IA/Baseline
    ISO_ZONE_A = 1,
    ISO_ZONE_B = 2,
    ISO_ZONE_C = 3,
    ISO_ZONE_D = 4
} IsoSeverityZone_t;

IsoSeverityZone_t evaluateIso20816Group2(float vrms_max_axis, bool iso_applicable) {
    if (!iso_applicable) {
        return ISO_NOT_APPLICABLE;
    }
    if (vrms_max_axis <= 1.40f) {
        return ISO_ZONE_A;
    }
    if (vrms_max_axis <= 2.80f) {
        return ISO_ZONE_B;
    }
    if (vrms_max_axis <= 4.50f) {
        return ISO_ZONE_C;
    }
    return ISO_ZONE_D;
}
```

A velocidade em `mm/s` é sempre persistida e mostrada nos dashboards e gráficos temporais; a régua visual de Zonas (Verde, Azul, Amarelo e Vermelho) no frontend Next.js aparece apenas quando a norma se aplica ao perfil da máquina.

---

## 4. Referências Científicas e Normativas

* **ISO 20816-3:2022:** *Mechanical vibration — Measurement and evaluation of machine vibration — Industrial machines with nominal power above 15 kW*.
* **Pedotti, L. A. S.; Zago, R. M.; Fruett, F. (2017):** *Fault diagnostics in rotary machines through spectral vibration analysis using low-cost MEMS devices*, *IEEE I&M Magazine* — [`doi:10.1109/mim.2017.8121950`](https://doi.org/10.1109/mim.2017.8121950)
* **Pagar, N.; Gawde, S.; Sanap, S. (2023):** *Online condition monitoring system for rotating machine elements using edge computing* — [`doi:10.1080/14484846.2023.2215493`](https://doi.org/10.1080/14484846.2023.2215493)
* Veja também [Referências Científicas e Normativas](/explicacoes/referencias-cientificas).
