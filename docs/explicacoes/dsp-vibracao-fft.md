# Processamento de Sinais: Amostragem a 3200 Hz e Janelamento Hann

Esta página fundamenta a teoria de processamento digital de sinais (DSP) aplicada na análise de vibração no nó sensor Amemiya, explicando o Teorema de Nyquist-Shannon, a taxa de amostragem de 3200 Hz e a escolha da janela de Hann para a FFT.

---

## 1. Critério de Nyquist e Frequência de Amostragem

Em máquinas rotativas industriais operando em faixas usuais de rotação (ex: 1800 a 3600 RPM):
* A frequência fundamental de rotação ($1X$) situa-se entre **$30\text{ Hz}$ e $60\text{ Hz}$**.
* Falhas típicas em pistas de rolamentos (BPFO - *Ball Pass Frequency Outer* e BPFI - *Inner*) manifestam harmônicos de impacto em frequências de **$3X$ a $10X$** da rotação fundamental, ou seja, até **$600\text{ Hz}$**.
* Fenômenos de fricção e microfissuras de esferas estendem-se até a faixa de **$1000\text{ Hz}$ a $1200\text{ Hz}$**.

Pelo **Teorema de Amostragem de Nyquist-Shannon**:

$$f_s \ge 2 \cdot f_{max}$$

Para capturar com fidelidade harmônicos de vibração de até $f_{max} = 1000\text{ Hz}$ sem *aliasing* (falso rebatimento de alta frequência na banda base), a taxa de amostragem mínima teórica é de $2000\text{ Hz}$.

O acelerômetro digital ADXL345 possui um modo de alta velocidade com amostragem configurada em **$f_s = 3200\text{ Hz}$**, oferecendo uma frequência de corte de Nyquist de:

$$f_{Nyquist} = \frac{3200\text{ Hz}}{2} = 1600\text{ Hz}$$

Essa margem de $600\text{ Hz}$ acima da banda de interesse assegura conformidade integral com a norma **ISO 20816-3**, que exige banda de medição de velocidade entre **$10\text{ Hz}$ e $1000\text{ Hz}$**.

---

## 2. Janelamento de Hann (*Hanning Window*)

A aplicação direta da Transformada Rápida de Fourier (FFT) em blocos de tempo finitos causa descontinuidade nas bordas do sinal, resultando em dispersão espectral (*spectral leakage*).

Para atenuar essa distorção, o vetor de $N = 1024$ amostras no domínio do tempo é multiplicado pela função de janelamento de Hann antes do processamento espectral:

$$w(n) = 0{,}5 \cdot \left[1 - \cos\left(\frac{2\pi n}{N - 1}\right)\right], \quad 0 \le n \le N - 1$$

### Vantagens do Janelamento Hann em Rotação
* **Atenuação de Lóbulos Laterais:** O primeiro lóbulo lateral atinge $-31{,}5\text{ dB}$, impedindo que a amplitude dominante de rotação encubra picos adjacentes de falha incipiente.
* **Resolução em Frequência:** Com $N = 1024$ pontos a $f_s = 3200\text{ Hz}$, a resolução espectral em cada *bin* da FFT é:
  $$\Delta f = \frac{f_s}{N} = \frac{3200\text{ Hz}}{1024} = 3{,}125\text{ Hz/bin}$$
  Essa resolução é perfeitamente adequada para discriminar componentes de rotação em 30 Hz (1800 RPM) e seus múltiplos exatos.

---

## 3. Conversão de Aceleração para Velocidade RMS

A norma ISO 20816-3 adota a **velocidade RMS** ($\text{mm/s}$) como grandeza padrão de severidade de vibração:

No domínio da frequência, a conversão da aceleração espectral $A(f)$ para velocidade $V(f)$ é obtida por integração matemática direta:

$$V(f) = \frac{A(f)}{2\pi f}$$

O valor RMS global é então integrado na faixa de $10\text{ Hz}$ a $1000\text{ Hz}$:

$$V_{RMS} = \sqrt{\sum_{f = 10\text{ Hz}}^{1000\text{ Hz}} |V(f)|^2}$$
