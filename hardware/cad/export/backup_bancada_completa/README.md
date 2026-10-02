# Backup Completo do Projeto da Bancada de Testes de Vibrações e Metrologia

**Data do Backup:** 24/09/2026  
**Status do Projeto:** Projeto Validado, Parametrizado e Aprovado  
**Ambiente CAD:** Autodesk Fusion 360 (Projeto: `Amemiya`, Pasta: `04_Bancada_Components_Backup_Completo`)  

---

## 1. Visão Geral da Arquitetura

Este backup contém a cópia integral, fiel e independente da **Bancada de Testes de Vibrações Mecânicas e Desalinhamento Controlado**:
- **Chassi Híbrido Rígido:** Base de ferro fundido usinada ($700\text{ mm} \times 220\text{ mm} \times 12\text{ mm}$) com duas vigas estruturais de alumínio perfil 40x80 com 4 canais T superiores e canais T laterais.
- **Sistema de Desalinhamento do Motor com Folgas Perfeitas:**
  - Placa base ampliada ($230\text{ mm} \times 160\text{ mm} \times 15\text{ mm}$) em alumínio anodizado ouro.
  - Rasgos oblongos M10 transversais em $Y = \pm 47,5\text{ mm}$, deslocados para $X = \pm 75\text{ mm}$ (vão livre de $20\text{ mm}$ dos pés do motor, aperto 100% superior).
  - Parafusos de elevação jacking M8 em $X = \pm 100\text{ mm}$ e $Y = \pm 67,5\text{ mm}$, assentados exatamente no centro da alma maciça ($32\text{ mm}$) dos perfis 40x80.
  - Blocos de encosto push-pull horizontais afixados diretamente no canal T da face lateral externa ($Y = \pm 107,5\text{ mm}$, $Z = 20\text{ mm}$), liberando totalmente o topo da bancada.
- **Linha de Eixo Coaxial (Eixo X, $Y = 0,0\text{ mm}$, $Z = 105,0\text{ mm}$):**
  - Motor trifásico carcaça IEC 63 com pintura esmalte azul WEG.
  - Acoplamento flexível de mandíbula com aranha de amortecimento em poliuretano vermelho.
  - Eixo retificado $\varnothing 20\text{ mm} \times 420\text{ mm}$ em aço SAE 1045 polido.
  - Mancais pillow block UCP204 em ferro fundido verde assentados sobre subplacas de troca rápida de $15\text{ mm}$.
  - Disco balanceado calibrado em aço inox com furos roscados M8 para inserção de parafusos de desbalanceamento.

---

## 2. Conteúdo do Backup

### Modelos CAD 3D Neutros (STEP / AP214)
1. `01_Mesa_Base_Bancada.step`
2. `02_Sapata_Niveladora_Borracha.step`
3. `03_Placa_Base_Desalinhamento_Motor.step`
4. `04_Motor_Eletrico_Trifasico.step`
5. `05_Acoplamento_Flexivel_Mandibula.step`
6. `06_Eixo_Rotativo_Retificado_20mm.step`
7. `07_Mancal_Pillow_Block_UCP204.step`
8. `08_Disco_Desbalanceamento_Calibrado.step`
9. `09_Subplaca_Troca_Rapida_Mancal.step`
10. `10_Parafuso_Fixacao_M10_Allen.step`
11. `11_Bloco_Encosto_Push_Pull_Lateral.step`
12. `12_Parafuso_Jacking_Elevacao_M8.step`
13. `13_Manipulo_Push_Pull_M8.step`
14. `amemiya_bancada_testes_assembly.step` (Montagem Completa)

### Arquivo Nativo do Autodesk Fusion 360
- `amemiya_bancada_testes_assembly.f3d` (Exportação compacta completa da montagem paramétrica).

### Imagens de Renderização e Animação Cinemática
- `bancada_lateral_top_motor.png` (Vista superior comprovando folgas dos oblongos e jacking centralizado)
- `bancada_lateral_detalhe_motor.png` (Detalhe em perspectiva isométrica do sistema push-pull lateral)
- `bancada_lateral_vista_lateral.png` (Vista lateral demonstrando a fixação no canal T externo)
- `bancada_lateral_iso.png` (Visão geral completa da bancada)
- `bancada_lateral_rotacao.gif` (Animação de 24 frames da rotação pura coaxial no eixo X)

### Scripts de Automação e Parametrização Python
- `assemble_lateral_bancada.py` (Script de montagem, posicionamento cinemático e renderização)
- `remodel_lateral_pushpull_system.py` (Modelagem paramétrica da placa ampliada, bloco lateral e manípulo)
- `remodel_chassis_and_components.py` (Modelagem das vigas 40x80 e base)
- `build_bancada_bottom_up_parts.py` (Modelagem paramétrica bottom-up dos componentes fundamentais)
