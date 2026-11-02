# Emissão de Certificado Metrológico (ISO 17025)

Este guia orienta o metrologista ou auditor da qualidade na execução de um ensaio de calibração, cálculo do balanço de incertezas segundo o GUM / EA-4/02 e emissão do laudo técnico oficial em PDF autenticado.

---

## 1. Cadastro do Instrumento e Associação de Padrões

1. Acesse o painel web em [http://localhost:8000/admin](http://localhost:8000/admin).
2. No menu lateral, navegue até **Metrologia** → **Instrumentos**.
3. Selecione o instrumento em calibração ou crie um novo registro informando:
   - Tag / Código de identificação único.
   - Fabricante, Modelo e Número de Série.
   - Faixa de medição nominal e resolução.
   - Critério de aceitação / Erro Máximo Permissível (EMP).

---

## 2. Inserção dos Pontos de Medição

1. Abra a aba **Calibrações** e clique em **Nova Calibração**.
2. Selecione o **Procedimento Metrológico** aplicável (Checklist Template).
3. Selecione os **Padrões de Referência** rastreáveis RBC/Inmetro ativos (o sistema valida automaticamente se os padrões estão dentro do prazo de validade da calibração).
4. Insira os ciclos de leitura direta para cada ponto de medição (ex: 5 repetições ascendentes e 5 descendentes).

---

## 3. Cálculo Automático do Balanço de Incerteza (GUM)

Ao clicar em **Calcular Resultados**, o motor metrológico do Lean Tech processa via biblioteca de alta precisão matemática (`BCMath`):

1. **Média Aritmética e Erro Sistemático (Tendência):**
   $$E = \bar{X} - X_{padrão}$$
2. **Incerteza Tipo A ($u_a$):**
   $$u_a = \frac{s}{\sqrt{n}}$$
3. **Incerteza Tipo B dos Padrões e Resolução ($u_b$):**
   $$u_b = \sqrt{\left(\frac{U_{padrão}}{k_{padrão}}\right)^2 + \left(\frac{\text{Resolução}}{2\sqrt{3}}\right)^2}$$
4. **Incerteza Combinada ($u_c$) e Graus de Liberdade Efetivos ($\nu_{eff}$):**
   Fórmula de Welch-Satterthwaite:
   $$\nu_{eff} = \frac{u_c^4}{\sum \frac{u_i^4}{\nu_i}}$$
5. **Incerteza Expandida ($U$):**
   Determinação de $k$ com probabilidade de abrangência de $95{,}45\%$ e cálculo:
   $$U = k \cdot u_c$$

---

## 4. Aprovação e Assinatura Digital

1. O Responsável Técnico confere os resultados na tela de prévia.
2. Clica no botão **Aprovar Calibração**:
   - É solicitada a re-autenticação por senha/token (assinatura digital em conformidade com ISO 17025 e FDA 21 CFR Part 11).
   - O sistema gera a versão final imutável do documento.
3. É gerado o hash criptográfico **SHA-256** do PDF gravado na cadeia de auditoria blockchain-like.
4. O certificado é disponibilizado imediatamente para download em PDF com QR Code de verificação pública externa (`/public/certificates/verify/{hash}`).
