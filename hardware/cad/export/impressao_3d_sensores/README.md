# Guia de Impressão 3D — Suportes e Acessórios dos Sensores da Bancada

Este diretório contém os modelos **STL** e **STEP** prontos para fatiamento e impressão 3D (FDM / SLA) dos suportes dos sensores da Bancada de Testes de Vibração.

---

## 1. Inventário de Arquivos para Impressão 3D

| Arquivo STL | Componente | Quantidade | Material Recomendado | Função Mecânica |
| :--- | :--- | :---: | :---: | :--- |
| [`suporte_tacometro_ajustavel.stl`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/cad/export/impressao_3d_sensores/suporte_tacometro_ajustavel.stl) | Suporte de Trilho para Tacômetro | 1x | PETG ou ABS Preto | Fixação no Canal 8 do perfil 40x80 com rasgo oblongo radial |
| [`colar_bipartido_eixo_20mm.stl`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/cad/export/impressao_3d_sensores/colar_bipartido_eixo_20mm.stl) | Colar Bipartido com Alojamento de Ímã | 1x (2 metades) | PETG ou ABS Laranja | Abraçadeira para eixo $\varnothing 20\text{ mm}$ com ímã de neodímio $\varnothing 5 \times 2\text{ mm}$ |
| [`berco_adaptador_mancal_probe.stl`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/cad/export/impressao_3d_sensores/berco_adaptador_mancal_probe.stl) | Berço Adaptador de Mancal para Probe | 2x | PETG ou ABS Preto | Assentamento horizontal da Probe Tower sobre o UCP204 com ímãs de base |

---

## 2. Parâmetros de Fatiamento Recomendados (Slicer Settings)

### 2.1 Suporte do Tacômetro (`suporte_tacometro_ajustavel.stl`)
* **Orientação de Mesa:** Imprimir apoiado sobre a face plana da base (com as nervuras subindo em Z).
* **Material:** PETG ou ABS (evitar PLA devido ao calor dissipado pelo motor e rigidez a longo prazo).
* **Altura de Camada:** $0,20\text{ mm}$.
* **Paredes / Perímetros:** 4 perímetros (espessura de casca $\ge 1,6\text{ mm}$ para garantir rigidez contra vibração).
* **Preenchimento (Infill):** $40\%\text{ a }50\%$ Giroide ou Cúbico.
* **Suportes:** Necessário apenas sob o braço horizontal superior em balanço (suporte em árvore/tree support recomendado).

### 2.2 Colar Bipartido do Eixo (`colar_bipartido_eixo_20mm.stl`)
* **Orientação de Mesa:** Imprimir com a face plana da fenda apoiada diretamente na mesa de impressão (sem suportes).
* **Material:** PETG (excelente elasticidade e adesão entre camadas para aperto por tração dos parafusos M3).
* **Altura de Camada:** $0,15\text{ mm}$ ou $0,20\text{ mm}$.
* **Paredes / Perímetros:** 5 perímetros ou preenchimento 100% sólido (peça pequena de esforço mecânico).
* **Fixadores necessários:** 2x Parafusos M3 x 18 mm Allen + 2x Porcas M3 sextavadas + 1x Ímã Neodímio $\varnothing 5 \times 2\text{ mm}$ inserido com gota de cianoacrilato.

### 2.3 Berço Adaptador do Mancal (`berco_adaptador_mancal_probe.stl`)
* **Orientação de Mesa:** Imprimir com a base inferior apoiada na mesa de impressão (sem suportes).
* **Material:** PETG ou TPU 95A (o TPU oferece amortecimento adicional de altas frequências, mas o PETG transmite melhor a vibração estrutural para o acelerômetro).
* **Altura de Camada:** $0,20\text{ mm}$.
* **Paredes / Perímetros:** 4 perímetros.
* **Preenchimento:** $40\%$ Cúbico.
* **Ímãs de Fixação:** 2x Ímãs de Neodímio $\varnothing 10 \times 2\text{ mm}$ colados nos bolsões inferiores para travamento magnético na carcaça de ferro fundido do UCP204.
