# Lista de Materiais Completa (BOM) - Painel da Bancada de Metrologia

Este documento consolida todos os itens necessários para montagem do painel elétrico de comando e potência da bancada, com especificações industriais, quantidades, localização física e termos exatos de busca para compras no mercado brasileiro.

---

## 1. Caixa de Comando (Painel) & Critério Dimensional

> [!WARNING]
> **Atenção à Profundidade Mínima da Caixa (Efeito de Colisão da Porta):**  
> Os botões de 22 mm e os sinaleiros LED ocupam de 45 a 60 mm de profundidade atrás da tampa. O contator Steck e a fonte em cima do trilho DIN têm cerca de 75 mm a 80 mm de altura a partir da placa de fundo.  
> Se você comprar uma caixa com profundidade de 100 mm ou 120 mm, **a porta não fechará porque os blocos de contato dos botões colidirão contra o contator**.  
> **Exigência dimensional:** A profundidade útil mínima recomendada é de **150 mm** (ideal entre **150 mm e 200 mm**).

### Opções Recomendadas de Caixas no Brasil

| Opção | Material / Linha | Dimensões Externas (A x L x P) | Vantagens | Termo de Busca Recomendado |
| :--- | :--- | :--- | :--- | :--- |
| **Opção A (Mais indicada)** | Chapa de Aço Carbono c/ Placa Laranja (Grau IP54/IP65) | **300 x 200 x 150 mm** ou **300 x 300 x 200 mm** | Robusta, ótimo aterramento metálico, furação de 22mm e rasgo do tacômetro fáceis de furar/vazar. | `quadro de comando 300x200x150 placa montagem` ou `quadro comando 300x300x200 steck brum` |
| **Opção B (Plástica/Hobby)** | Termoplástico ABS c/ Placa de Montagem Interna | **300 x 200 x 160 mm** ou **350 x 250 x 150 mm** | Não enferruja, isolação elétrica externa total, corte plástico mais leve com serra copo. | `caixa hermetica abs placa montagem 300x200x160` (Marcas: Strahl, Brum, Cemar) |

---

## 2. Componentes da Placa de Montagem (Trilho DIN Interno)

| Item | Identificador KiCad | Descrição Técnica / Especificação | Qtd | Termo Exato de Busca |
| :--- | :--- | :--- | :---: | :--- |
| **Disjuntor Bipolar** | `Q1` | 2 Polos, Curva C, 10A, 4.5kA/6kA, Fixação Trilho DIN 35mm | 1 | `disjuntor bipolar 10a curva c steck weg` |
| **Contator Tripolar/Bipolar** | `K1` | Bobina **24V DC (CC)**, Contatos de Força 9A a 12A AC-3, **1 Contato Auxiliar NA embutido (13-14)** | 1 | `contator steck 24vcc 9a 1na sk1` ou `contator weg cwb9 24vcc` |
| **Supressor de Bobina** | — | Supressor de surto tipo diodo para bobina CC (1N4007 ou bloco Steck/WEG DI-D) | 1 | `bloco supressor diodo contator 24vcc` ou `diodo 1n4007` |
| **Fonte Chaveada 24V** | `T1` | Entrada 100-240V AC, Saída 24V DC / 2.5A a 3A (60W a 75W). *Preferência formato Trilho DIN tipo HDR-60-24 ou colmeia c/ suporte DIN* | 1 | `fonte trilho din 24v 2.5a` ou `fonte colmeia 24v 3a` |
| **Porta-Fusível Seccionável** | `F1`, `F2` | Base porta-fusível 10x38 mm para trilho DIN (bipolar ou 2 unidades unipolar) | 1 kit | `porta fusivel trilho din 10x38` |
| **Fusível Cerâmico/Vidro** | `F1`, `F2` | Fusível 10x38 mm tipo gG / ação rápida 2A (250V / 500V) | 2 | `fusivel 10x38 2a` |
| **Módulo Dimmer AC 220V** | `DIM1` | Regulador de Tensão TRIAC 2000W ou 4000W 220V AC, com dissipador e cabo para potenciômetro | 1 | `modulo dimmer 220v 2000w triac bta41` ou `dimmer 4000w potenciometro` |
| **Bornes de Passagem (Fase/Neutro)** | `TB1`, `TB2`, `TB4`, `TB5` | Borne tipo SAK / Mola para trilho DIN, secção 2.5 mm² a 4 mm², Cor Cinza ou Azul | 4 | `borne passagem sak 2.5mm cinza` |
| **Borne de Passagem (Terra)** | `TB3`, `TB6` | Borne de aterramento para fixação direta no trilho DIN (contato elétrico no trilho), Verde/Amarelo, 2.5 mm² a 4 mm² | 2 | `borne terra trilho din verde amarelo 4mm` |
| **Bornes de Comando (Opcional)** | — | Borne SAK 2.5 mm² para derivações de comando (Tampa, Sensor, 24V/0V) | 6 a 8 | `borne sak 2.5mm cinza conexel wago` |
| **Poste de Fixação (Parada)** | — | Poste terminal plástico / metálico para travar os bornes nas extremidades do trilho | 4 | `poste final trilho din parada borne sak` |
| **Tampa Final de Borne** | — | Placa de fechamento isolante para fechar o último borne aberto da régua | 2 | `tampa final borne sak 2.5mm` |
| **Trilho DIN 35 mm** | — | Trilho DIN TS-35 perfurado em aço zincado (comprimento 30 a 50 cm) | 1 | `trilho din 35mm perfurado 50cm` |
| **Canaleta Ranhurada** | — | Canaleta plástica vazada/ranhurada cinza (20x20 mm ou 30x30 mm) para organização | 1 barra (1m) | `canaleta ranhurada 30x30 cinza dutoplast` |

---

## 3. Componentes de Interface na Porta / Tampa

| Item | Identificador KiCad | Descrição Técnica / Especificação | Qtd | Termo Exato de Busca |
| :--- | :--- | :--- | :---: | :--- |
| **Botão de Emergência** | `S0` | Furação 22 mm, Cabeça tipo Cogumelo 40 mm, Com retenção (Girar para destravar), **1 Bloco NF (Segurança) + 1 Bloco NA (Sinalização LED)** | 1 | `botao emergencia 22mm girar destravar 1nf 1na` |
| **Botão Pulsador Liga** | `S_ON` | Furação 22 mm, Faceado, Cor Verde, Mola de retorno, **1 Bloco NA (pinos 13-14)** | 1 | `botao pulsador 22mm verde 1na` |
| **Botão Pulsador Desliga** | `S_OFF` | Furação 22 mm, Faceado, Cor Vermelha, Mola de retorno, **1 Bloco NF (pinos 11-12)** | 1 | `botao pulsador 22mm vermelho 1nf` |
| **Sinaleiro LED Branco** | `H1` | Monobloco LED 22 mm, Alimentação **24V DC**, Cor Branca (Indica: Painel 24V Energizado / Standby) | 1 | `sinaleiro led 22mm 24v branco` |
| **Sinaleiro LED Verde** | `H2` | Monobloco LED 22 mm, Alimentação **24V DC**, Cor Verde (Indica: Motor em Marcha) | 1 | `sinaleiro led 22mm 24v verde` |
| **Sinaleiro LED Amarelo** | `H3` | Monobloco LED 22 mm, Alimentação **24V DC**, Cor Amarela/Âmbar (Indica: Alarme Tampa Aberta) | 1 | `sinaleiro led 22mm 24v amarelo` |
| **Sinaleiro LED Vermelho** | `H4` | Monobloco LED 22 mm, Alimentação **24V DC**, Cor Vermelha (Indica: Emergência Acionada / Bloqueio) | 1 | `sinaleiro led 22mm 24v vermelho` |
| **Tacômetro Digital de Painel** | `DISP1` | Modelo CF5135B (ou similar 4 dígitos LED vermelho/azul), Tensão 12V-24V DC, Entrada de pulso NPN. Rasgo frontal ~ 79 x 42 mm | 1 | `tacometro digital cf5135b rpm led` |
| **Knob / Dial de Potenciômetro** | — | Knob graduado (0-100%) para eixo de 6 mm do potenciômetro do Dimmer | 1 | `knob potenciometro 6mm dial escala` |

---

## 4. Componentes Externos na Máquina / Bancada

| Item | Identificador KiCad | Descrição Técnica / Especificação | Qtd | Termo Exato de Busca |
| :--- | :--- | :--- | :---: | :--- |
| **Motor Monofásico** | `M1` | Motor de Tanquinho / Lavadora 220V, Potência ~1/4 CV (180W a 250W), 4 polos (~1650-1750 RPM) | 1 | `motor tanquinho 1/4 cv 220v` |
| **Capacitor Permanente** | `C_MOT` | Capacitor de Partida/Trabalho permanente em polipropileno, **12 µF ou 15 µF / 400-450 VAC** com chicote | 1 | `capacitor permanente 12uf 450vac motor tanquinho` |
| **Microswitch da Tampa** | `SQ_TAMPA` | Chave fim de curso tipo SPDT (COM, NC, NO) com alavanca e roldana metálica (ex: modelo tipo KW11-3Z, V-156-1C25 ou fim de curso industrial ME-8108) | 1 | `micro switch alavanca roldana spdt 15a` ou `fim de curso me-8108` |
| **Sensor Hall RPM** | `SEN_HALL` | Sensor Magnético Efeito Hall NJK-5002C, Rosca M12, 3 Fios (Marrom +, Preto Sinal, Azul 0V), NPN NA, Tensão 6-36V DC | 1 | `sensor hall njk-5002c m12 npn` |
| **Ímã de Neodímio** | — | Pastilha magnética de neodímio N35/N50 (Diâmetro 8 a 10 mm, espessura 2 a 3 mm) para fixação no eixo/polia | 2 | `ima neodimio disco 10x3mm` |

---

## 5. Materiais de Fixação, Passagem e Cabeamento (Acessórios de Montagem)

| Item | Especificação | Qtd Estimada | Finalidade |
| :--- | :--- | :---: | :--- |
| **Prensa-cabo M20 (ou PG13.5)** | Plástico c/ porca e anel de borracha | 2 un | Entrada do cabo de força AC da tomada e saída do cabo do motor |
| **Prensa-cabo M16 (ou PG9/PG11)** | Plástico c/ porca e anel de borracha | 2 un | Passagem do cabo do microswitch da tampa e cabo do sensor Hall |
| **Cabo Flexível 2.5 mm² Preto/Marrom** | 750V / 1000V Cobre Antichama | 5 metros | Fases do circuito de força 220V (Rede, Disjuntor, Contator, Dimmer, Motor) |
| **Cabo Flexível 2.5 mm² Azul Claro** | 750V / 1000V Cobre Antichama | 3 metros | Neutro / Fase 2 da alimentação e do motor |
| **Cabo Flexível 2.5 mm² Verde/Amarelo** | 750V / 1000V Cobre Antichama | 4 metros | Aterramento de Proteção PE (Carcaças, Painel, Trilho DIN e Motor) |
| **Cabo Flexível 0.75 mm² Vermelho** | 300V / 750V Cobre | 10 metros | Linha de +24V DC do comando, botões, microswitch e sinaleiros |
| **Cabo Flexível 0.75 mm² Preto ou Azul Escuro** | 300V / 750V Cobre | 10 metros | Linha de 0V DC / GND (Retorno da bobina K1, sinaleiros e display) |
| **Terminais Ilhós Tubular 2.5 mm²** | Pré-isolado simples (Azul ou Cinza) | 25 un | Conexão nos bornes SAK, disjuntor e contator de força |
| **Terminais Ilhós Tubular 0.75 mm²** | Pré-isolado simples (Cinza ou Branco) | 50 un | Conexão nas abotoadeiras 22mm, sinaleiros, bobina e microswitch |
| **Espiral Organizador (Espaguete)** | Tipo Spiral-Tube 1/2" ou 3/8" | 1 metro | Proteção do chicote de fios móvel que vai da placa de fundo para a porta |
| **Parafusos Brocantes 4.2 x 13 mm** | Cabeça panela zincada | 1 pacote | Fixação do trilho DIN e canaletas na placa de montagem |

---

## 6. Dicas Práticas de Compra e Montagem

1. **Kit Tacômetro + Sensor:**  
   No Mercado Livre e AliExpress é muito comum encontrar o anúncio conjunto:  
   *`"Tacômetro Digital CF5135B + Sensor Hall NJK-5002C + Ímã"`*.  
   Comprar o conjunto pronto sai mais barato e garante compatibilidade direta do sinal NPN e tensão.
2. **Potenciômetro do Dimmer na Porta:**  
   A maioria dos módulos dimmer TRIAC 2000W/4000W vem com o potenciômetro soldado direto na plaquinha ou com chicote curto. Se vier soldado, solde 3 fios de 20 cm do potenciômetro até a placa para poder fixar o potenciômetro na furação da porta do painel com seu knob frontal.
3. **Fita Isolante & Abraçadeiras Nylon:**  
   Tenha à mão abraçadeiras plásticas (enforca-gato) de 100 mm e 150 mm para amarrar os cabos dentro das canaletas e na descida para a dobradiça da porta.
