# Norma ISO 20816-3 e Zonas de Severidade

Esta página apresenta os critérios normativos da **ISO 20816-3:2022** (*Mechanical vibration — Measurement and evaluation of machine vibration*), detalhando as 4 zonas de severidade vibracional utilizadas pelo sistema para monitoramento contínuo de máquinas industriais.

---

## 1. Escopo e Classificação de Grupos de Máquinas

A norma ISO 20816-3 se aplica a conjuntos industriais com potência nominal superior a $15\text{ kW}$ e velocidades operacionais entre $120\text{ RPM}$ e $30.000\text{ RPM}$. As máquinas são categorizadas em grupos conforme seu tipo e potência:

* **Grupo 1:** Grandes máquinas com potência acima de $300\text{ kW}$ (ex: turbogeradores, motores elétricos pesados com altura de eixo $H \ge 315\text{ mm}$).
* **Grupo 2:** Máquinas de porte médio com potência nominal entre $15\text{ kW}$ e $300\text{ kW}$ (ou motores elétricos com altura de eixo $160\text{ mm} \le H < 315\text{ mm}$).

A classe da fundação mecânica também determina os limites:
* **Suporte Rígido (*Rigid Support*):** A primeira frequência natural do conjunto montado é maior que a frequência fundamental de rotação em pelo menos $25\%$.
* **Suporte Flexível (*Flexible Support*):** A primeira frequência natural está abaixo da frequência de rotação.

---

## 2. Limites de Severidade Vibracional (Grupo 2 - Suporte Rígido)

Para a bancada de ensaio e máquinas do Grupo 2 em suporte rígido (típico da maioria dos motores e mancais industriais em estudo), os limites de velocidade RMS ($V_{RMS}$) entre $10\text{ Hz}$ e $1000\text{ Hz}$ são:

| Zona | Faixa de Velocidade ($V_{RMS}$) | Significado Operacional | Ação Recomendada pelo Sistema |
| :---: | :---: | :--- | :--- |
| **Zona A** | $0{,}00$ a $1{,}40\text{ mm/s}$ | **Nova / Excelente:** Vibração típica de máquinas recém-instaladas ou em perfeito estado mecânico. | Nenhuma ação necessária. Status normal (LED Verde). |
| **Zona B** | $1{,}40$ a $2{,}80\text{ mm/s}$ | **Aceitável:** Vibração permissível para operação contínua e irrestrita a longo prazo. | Monitoramento periódico de rotina. |
| **Zona C** | $2{,}80$ a $4{,}50\text{ mm/s}$ | **Alerta:** Vibração insatisfatória para operação contínua. Pode operar por período limitado até a manutenção programada. | Disparo de alerta preventivo (LED Amarelo). Agendamento de intervenção. |
| **Zona D** | $> 4{,}50\text{ mm/s}$ | **Perigo / Crítico:** A severidade é alta o suficiente para causar danos imediatos ao ativo ou colapso de mancal. | Disparo de alarme crítico (LED Vermelho + Buzzer). Recomendação de desligamento imediato. |

---

## 3. Implementação Algorítmica no Nó Amemiya

O classificador embutido no firmware do ESP32-S3 executa a avaliação diretamente após o cálculo da FFT:

```c
typedef enum {
    ISO_ZONE_A = 1,
    ISO_ZONE_B = 2,
    ISO_ZONE_C = 3,
    ISO_ZONE_D = 4
} IsoSeverityZone_t;

IsoSeverityZone_t evaluateIso20816(float vrms_max_axis) {
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

O valor resultante é transmitido no byte 19 da estrutura binária e refletido visualmente no dashboard Filament via gráficos codificados em cores oficiais (Verde, Azul, Amarelo e Vermelho).
