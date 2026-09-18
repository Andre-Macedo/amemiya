# Scripts de Automação de PCBs KiCad 9 (Python / pcbnew)

Esta pasta contém os scripts Python atualizados para geração procedural, roteamento e pós-processamento das placas de circuito impresso do ecossistema **Amemiya IoT**.

---

## Estrutura e Papel dos Scripts

| Script | Versão | Descrição |
|---|---|---|
| [`generate_zero_drc_pcbs_v35.py`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/kicad/scripts/generate_zero_drc_pcbs_v35.py) | **v35.0 (Master)** | Gera proceduralmente do zero as placas **Main Node** (ESP32-S3) e **Probe Sensor** (ADXL345/INMP441/DS18B20) com pegadas 100% Through-Hole (PTH), furações M3, serigrafia de montagem e netlists completas sem erros de DRC. |
| [`astar_router_v13.py`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/kicad/scripts/astar_router_v13.py) | **v13.0 (Final)** | Algoritmo de roteamento baseado em malha A* (A-Star Grid Maze Router) para o **Main Node**. Realiza a passagem otimizada de trilhas em dupla camada (`F.Cu` e `B.Cu`), transições verticais com vias padronizadas, alívio térmico e planos contínuos de terra (`GND`) com preenchimento automático de zonas e 0 violações de DRC. |
| [`generate_probe_tower_v5.py`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/kicad/scripts/generate_probe_tower_v5.py) | **v5.0 (Final)** | Gera proceduralmente a PCB de formato cartucho torre cilíndrico (estilo Tractian, $24 \times 48\text{ mm}$), 100% PTH com roteamento planar e barramento GX12. |
| [`route_pcbnew.py`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/kicad/scripts/route_pcbnew.py) | — | Script auxiliar para manipulação direta de trilhas na Sonda Torre via API do `pcbnew`. |
| [`update_tower_pcb.py`](file:///C:/Users/andrl/OneDrive/Documentos/Projetos%20Pessoal/amemiya/hardware/kicad/scripts/update_tower_pcb.py) | — | Script de injeção direta de segmentos s-expression na PCB da torre. |

---

## Como Executar

Os scripts utilizam a biblioteca nativa `pcbnew` do KiCad. Eles contêm detecção automática do binário do KiCad 9 ou 8 para Windows e Linux.

### Via Python do KiCad (Recomendado):
```powershell
& "C:\Program Files\KiCad\9.0\bin\python.exe" hardware/kicad/scripts/generate_zero_drc_pcbs_v35.py
& "C:\Program Files\KiCad\9.0\bin\python.exe" hardware/kicad/scripts/astar_router_v13.py
& "C:\Program Files\KiCad\9.0\bin\python.exe" hardware/kicad/scripts/generate_probe_tower_v5.py
```
