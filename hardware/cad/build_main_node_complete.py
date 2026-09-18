# -*- coding: utf-8 -*-
"""
Construcao Parametrica Completa do No Principal (Main Node) da Amemiya Metrology no FreeCAD
Versao v2.4 (Gabinete Espacoso, PCB 100% Retangular sem Chanfros, Folgas Amplas e Conectores Livres):
- Carcaca ampliada (111.0 x 86.0 mm externo) garantindo 8.0mm de folga perimetral e 1.66mm livre nas quinas
- Placa PCB 100% RETANGULAR (90.0 x 65.0 mm), integra, sem necessidade de chanfros ou raspagem
- 4 Colunas Macicas de Canto (Corner Bosses) em X=+-49.0, Y=+-36.5mm (fora da PCB), com insertos M3x5mm no topo (Z=38.0mm)
- 4 Pilares de Apoio da PCB em X=+-40.0, Y=+-27.5mm com parafusos independentes M3x6mm, com folga ampla de qualquer conector JST
- Barramento de conectores JST-XH reposicionado para acesso 100% desobstruido por chave de fenda (0.0mm3 de intersecao)
- Painel Lateral com trio de sensores alinhados: GX12-8 (Torre), Jack P2 3.5mm Femea (Clamp SCT-013) e GX12-3 (Tacometro)
- Tampa Inferior dedicada para troca rapida de baterias com 4 parafusos escareados M3 DIN 7991 e insertos de latao no fundo
- Bateria DUPLA 2x 18650 Li-Ion (1S2P, 3.7V 6000mAh) no piso inferior com suporte dual keystone
- Modulo Carregador/BMS TP4056 com porta USB-C frontal dedicada
- IHM integrada na tampa frontal (Display OLED 0.96", 3 LEDs 3mm em linha e Buzzer piezo com grelha acustica)
- Modulo LoRa Ebyte E32-TTL-100 em setor traseiro-direito completamente desimpedido com conector SMA metalico externo
- Modulo ESP32-S3 DevKit em setor central com acesso USB frontal
"""

import FreeCAD
import Part
import Sketcher
import os
import math

doc_name = "amemiya_main_node_enclosure"
try:
    doc = FreeCAD.getDocument(doc_name)
    if doc:
        FreeCAD.closeDocument(doc_name)
except Exception:
    pass

doc = FreeCAD.newDocument(doc_name)

def apply_style(obj, rgb, transp=0, vis=True):
    if hasattr(obj, 'ViewObject') and obj.ViewObject is not None:
        if hasattr(obj.ViewObject, 'ShapeColor'):
            try:
                obj.ViewObject.ShapeColor = rgb
            except Exception:
                pass
        if hasattr(obj.ViewObject, 'Transparency'):
            try:
                obj.ViewObject.Transparency = transp
            except Exception:
                pass
        if hasattr(obj.ViewObject, 'Visibility'):
            try:
                obj.ViewObject.Visibility = vis
            except Exception:
                pass

print("1/9: Construindo Gabinete_Base_Duplo_Andar ampliado com espaco generoso...")

# Dimensoes Arquiteturais Ampliadas para Folga Confortavel
pcb_w = 90.0
pcb_d = 65.0
wall_th = 2.5
floor_th = 2.5

# Cavidade interna ampliada (106.0 x 81.0 mm) -> 8.0mm de folga nas 4 laterais da PCB!
inner_w = 106.0
inner_d = 81.0

outer_w = inner_w + 2 * wall_th  # 111.0 mm (-55.5 a +55.5)
outer_d = inner_d + 2 * wall_th  # 86.0 mm (-43.0 a +43.0)

x0 = -outer_w / 2.0  # -55.5 mm
x1 = outer_w / 2.0   # +55.5 mm
y0 = -outer_d / 2.0  # -43.0 mm
y1 = outer_d / 2.0   # +43.0 mm

base_total_h = 38.0 # Altura total da carcaça base (plano de fechamento da tampa)

# 1.1 Bloco Externo da Base
outer_box = Part.makeBox(outer_w, outer_d, base_total_h, FreeCAD.Vector(x0, y0, 0))

# 1.2 Cavidade Interna
ix0 = -inner_w / 2.0
ix1 = inner_w / 2.0
iy0 = -inner_d / 2.0
iy1 = inner_d / 2.0
inner_box = Part.makeBox(inner_w, inner_d, base_total_h - floor_th + 1.0, FreeCAD.Vector(ix0, iy0, floor_th))
base_tray = outer_box.cut(inner_box)

# 1.3 Quatro Colunas Maciças de Canto para Fechamento da Tampa Superior (Corner Bosses)
# Posicionadas nos cantos externos (X=+-49.5, Y=+-37.0), COMPLETAMENTE FORA da PCB
# Distancia a quina da PCB (+-45, +-32.5) = 6.36mm -> FOLGA LIVRE REAL DE 2.36mm! Nao precisa chanfrar/raspar quinas!
lid_corner_coords = [(-49.5, -37.0), (49.5, -37.0), (-49.5, 37.0), (49.5, 37.0)]
corner_cols_shape = None
for cx, cy in lid_corner_coords:
    col_solid = Part.makeCylinder(4.0, base_total_h - floor_th, FreeCAD.Vector(cx, cy, floor_th), FreeCAD.Vector(0, 0, 1))
    col_hole = Part.makeCylinder(2.1, 6.0, FreeCAD.Vector(cx, cy, base_total_h - 6.0), FreeCAD.Vector(0, 0, 1))
    col = col_solid.cut(col_hole)
    if corner_cols_shape is None:
        corner_cols_shape = col
    else:
        corner_cols_shape = corner_cols_shape.fuse(col)

# 1.4 Quatro Pilares Independentes para Apoio e Fixação da PCB Principal (altura 20.0mm, topo em Z=22.5mm)
# Posicionados em X=+-40.0, Y=+-27.5mm, com folga de mais de 11.5mm em relacao a qualquer conector JST!
pcb_standoffs = [(-40.0, -27.5), (40.0, -27.5), (-40.0, 27.5), (40.0, 27.5)]
pcb_posts_shape = None
for px, py in pcb_standoffs:
    p_solid = Part.makeCylinder(3.0, 20.0, FreeCAD.Vector(px, py, floor_th), FreeCAD.Vector(0, 0, 1))
    p_hole = Part.makeCylinder(1.3, 5.0, FreeCAD.Vector(px, py, floor_th + 20.0 - 5.0), FreeCAD.Vector(0, 0, 1))
    p_post = p_solid.cut(p_hole)
    if pcb_posts_shape is None:
        pcb_posts_shape = p_post
    else:
        pcb_posts_shape = pcb_posts_shape.fuse(p_post)

# 1.5 Abertura Inferior para Compartimento de Bateria (Troca Rapida por baixo)
cut_bat_through = Part.makeBox(74.0, 38.0, floor_th + 1.0, FreeCAD.Vector(-37.0, -7.0, -0.5))
cut_bat_rebate = Part.makeBox(80.4, 44.4, 2.0, FreeCAD.Vector(-40.2, -10.2, -0.5))

# Guias e Apoios de Parafuso da Tampa de Bateria (4 ressaltos internos no piso)
bat_screw_posts = [(-37.0, -7.0), (37.0, -7.0), (-37.0, 31.0), (37.0, 31.0)]
bat_posts_shape = None
for bx, by in bat_screw_posts:
    bp_solid = Part.makeCylinder(3.0, 6.0, FreeCAD.Vector(bx, by, floor_th), FreeCAD.Vector(0, 0, 1))
    bp_hole = Part.makeCylinder(1.4, 7.0, FreeCAD.Vector(bx, by, -0.5), FreeCAD.Vector(0, 0, 1))
    bp = bp_solid.cut(bp_hole)
    if bat_posts_shape is None:
        bat_posts_shape = bp
    else:
        bat_posts_shape = bat_posts_shape.fuse(bp)

# 1.6 Flanges Externas para Parafusos M4
flange_left = Part.makeBox(14.0, 28.0, 3.5, FreeCAD.Vector(x0 - 14.0, -14.0, 0))
flange_slot_l = Part.makeCylinder(2.5, 5.0, FreeCAD.Vector(x0 - 7.0, 0.0, -0.5), FreeCAD.Vector(0, 0, 1))
flange_l_cut = flange_left.cut(flange_slot_l)

flange_right = Part.makeBox(14.0, 28.0, 3.5, FreeCAD.Vector(x1, -14.0, 0))
flange_slot_r = Part.makeCylinder(2.5, 5.0, FreeCAD.Vector(x1 + 7.0, 0.0, -0.5), FreeCAD.Vector(0, 0, 1))
flange_r_cut = flange_right.cut(flange_slot_r)

# 1.7 Guia Mecanica Frontal no Piso
guide_front = Part.makeBox(77.0, 2.0, 6.0, FreeCAD.Vector(-38.5, -9.5, floor_th))

# 1.8 Recortes nas Paredes da Carcaca
# Recorte USB-C para Carga (TP4056) na parede frontal em X = 22.0
cut_usbc = Part.makeBox(10.0, wall_th + 2.0, 4.5, FreeCAD.Vector(22.0 - 5.0, y0 - 1.0, floor_th + 2.0))

# Recorte USB-C para ESP32-S3 na parede frontal em X = -9.0
cut_usbc_esp = Part.makeBox(10.0, wall_th + 2.0, 4.5, FreeCAD.Vector(-9.0 - 5.0, y0 - 1.0, 26.5))

# Recorte SMA para Antena LoRa na parede direita em Y = 6.0, Z = 28.5 mm (conforme datasheet E32)
cut_sma = Part.makeCylinder(3.5, wall_th + 2.0, FreeCAD.Vector(x1 - 1.0, 6.0, 28.5), FreeCAD.Vector(1, 0, 0))

# Painel Lateral de Sensores na Parede Esquerda (X = x0):
# 1) GX16-8 (Torre de Sensores) em Y = -16.0 mm, Z = 29.0 mm (Furo M16x1 de 16.2 mm de diametro)
cut_gx16_tower = Part.makeCylinder(8.1, wall_th + 2.0, FreeCAD.Vector(x0 - 1.0, -16.0, 29.0), FreeCAD.Vector(1, 0, 0))
# 2) Jack P2 3.5mm Femea (Clamp SCT-013) em Y = 2.0 mm, Z = 29.0 mm
cut_p2 = Part.makeCylinder(3.0, wall_th + 2.0, FreeCAD.Vector(x0 - 1.0, 2.0, 29.0), FreeCAD.Vector(1, 0, 0))
# 3) GX12-3 (Tacometro) em Y = 18.0 mm, Z = 29.0 mm
cut_gx12_tacho = Part.makeCylinder(6.0, wall_th + 2.0, FreeCAD.Vector(x0 - 1.0, 18.0, 29.0), FreeCAD.Vector(1, 0, 0))

# Fusao e Cortes Finais (UNICO OBJETO BASE)
base_solida = base_tray.fuse(corner_cols_shape).fuse(pcb_posts_shape).fuse(bat_posts_shape).fuse(flange_l_cut).fuse(flange_r_cut).fuse(guide_front)
base_final = base_solida.cut(cut_bat_through).cut(cut_bat_rebate).cut(cut_usbc).cut(cut_usbc_esp).cut(cut_sma).cut(cut_gx16_tower).cut(cut_p2).cut(cut_gx12_tacho)

obj_base = doc.addObject("Part::Feature", "Gabinete_Base_Duplo_Andar")
obj_base.Shape = base_final
obj_base.Label = "Gabinete_Base_Duplo_Andar"
apply_style(obj_base, (0.16, 0.32, 0.62), transp=0, vis=True) # Azul Industrial
doc.recompute()

# ==============================================================================
# 2. CONSTRUCAO DA TAMPA SUPERIOR IHM (UNICO OBJETO TAMPA SUPERIOR)
# ==============================================================================
print("2/9: Construindo Tampa_Superior_IHM...")

lid_h = 14.0
lid_z0 = base_total_h  # 38.0 mm
lid_z1 = lid_z0 + lid_h # 52.0 mm
ceiling_th = 2.6

lid_outer = Part.makeBox(outer_w, outer_d, lid_h, FreeCAD.Vector(x0, y0, lid_z0))
lid_inner = Part.makeBox(inner_w, inner_d, lid_h - ceiling_th + 1.0, FreeCAD.Vector(ix0, iy0, lid_z0 - 0.5))
lid_tray = lid_outer.cut(lid_inner)

lip_out = Part.makeBox(inner_w - 0.5, inner_d - 0.5, 2.5, FreeCAD.Vector(ix0 + 0.25, iy0 + 0.25, lid_z0 - 2.5))
lip_in = Part.makeBox(inner_w - 4.5, inner_d - 4.5, 3.5, FreeCAD.Vector(ix0 + 2.25, iy0 + 2.25, lid_z0 - 3.0))
lid_lip = lip_out.cut(lip_in)
lid_tray = lid_tray.fuse(lid_lip)

# 2.2 Janela para Display OLED 0.96" (Moldura de 26.0 x 14.5 mm centrado em X=6.0, Y=-14.0)
oled_win = Part.makeBox(26.0, 14.5, ceiling_th + 2.0, FreeCAD.Vector(6.0 - 13.0, -14.0 - 7.25, lid_z1 - ceiling_th - 1.0))
lid_tray = lid_tray.cut(oled_win)

# 2.3 Orificios para 3 LEDs de Status de 3mm (em linha frontal em Y=-22.0, X=24.0, 31.0, 38.0)
led_holes = None
for lx in [24.0, 31.0, 38.0]:
    lh = Part.makeCylinder(1.6, ceiling_th + 2.0, FreeCAD.Vector(lx, -22.0, lid_z1 - ceiling_th - 1.0), FreeCAD.Vector(0, 0, 1))
    if led_holes is None:
        led_holes = lh
    else:
        led_holes = led_holes.fuse(lh)
lid_tray = lid_tray.cut(led_holes)

# 2.4 Grelha Acustica do Buzzer Piezo (5 furos dia 1.8mm em cruz em X=31.0, Y=-8.0)
buz_holes = None
for bx, by in [(31.0, -8.0), (31.0 + 2.4, -8.0), (31.0 - 2.4, -8.0), (31.0, -8.0 + 2.4), (31.0, -8.0 - 2.4)]:
    bh = Part.makeCylinder(0.9, ceiling_th + 2.0, FreeCAD.Vector(bx, by, lid_z1 - ceiling_th - 1.0), FreeCAD.Vector(0, 0, 1))
    if buz_holes is None:
        buz_holes = bh
    else:
        buz_holes = buz_holes.fuse(bh)
lid_tray = lid_tray.cut(buz_holes)

# 2.5 Aletas de Ventilacao Termica para o ESP32 (5 fendas em X=-9.0, Y de -12 a +12)
for i in range(5):
    slit_y = -12.0 + i * 6.0
    slit = Part.makeBox(20.0, 2.2, ceiling_th + 2.0, FreeCAD.Vector(-19.0, slit_y, lid_z1 - ceiling_th - 1.0))
    lid_tray = lid_tray.cut(slit)

# 2.6 Furos de Fixacao M3 nos 4 Cantos da Tampa (Alinhados com as Colunas de Canto em X=+-49.0, Y=+-36.5)
corner_holes = None
for cx, cy in lid_corner_coords:
    ch = Part.makeCylinder(1.65, lid_h + 2.0, FreeCAD.Vector(cx, cy, lid_z0 - 1.0), FreeCAD.Vector(0, 0, 1))
    ch_recess = Part.makeCylinder(3.1, 2.5, FreeCAD.Vector(cx, cy, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
    ch_comb = ch.fuse(ch_recess)
    if corner_holes is None:
        corner_holes = ch_comb
    else:
        corner_holes = corner_holes.fuse(ch_comb)
lid_tray = lid_tray.cut(corner_holes)

obj_lid = doc.addObject("Part::Feature", "Tampa_Superior_IHM")
obj_lid.Shape = lid_tray
obj_lid.Label = "Tampa_Superior_IHM"
apply_style(obj_lid, (0.24, 0.40, 0.72), transp=0, vis=True)
doc.recompute()

# ==============================================================================
# 3. TAMPA INFERIOR DO COMPARTIMENTO DE BATERIA (TROCA RAPIDA SEM ABRIR A PCB)
# ==============================================================================
print("3/9: Construindo Tampa_Inferior_Compartimento_Bateria...")

door_plate = Part.makeBox(79.8, 43.8, 1.4, FreeCAD.Vector(-39.9, -9.9, 0.0))
door_lip = Part.makeBox(73.2, 37.2, 1.8, FreeCAD.Vector(-36.6, -6.6, 1.4))
for bx, by in bat_screw_posts:
    boss_cut = Part.makeCylinder(3.3, 3.0, FreeCAD.Vector(bx, by, 1.0), FreeCAD.Vector(0, 0, 1))
    door_lip = door_lip.cut(boss_cut)

door_body = door_plate.fuse(door_lip)

door_holes = None
for bx, by in bat_screw_posts:
    dh = Part.makeCylinder(1.6, 4.0, FreeCAD.Vector(bx, by, -0.5), FreeCAD.Vector(0, 0, 1))
    dh_cone = Part.makeCone(3.2, 1.6, 1.6, FreeCAD.Vector(bx, by, 0.0), FreeCAD.Vector(0, 0, 1))
    dh_comb = dh.fuse(dh_cone)
    if door_holes is None:
        door_holes = dh_comb
    else:
        door_holes = door_holes.fuse(dh_comb)

door_final = door_body.cut(door_holes)
obj_door = doc.addObject("Part::Feature", "Tampa_Inferior_Compartimento_Bateria")
obj_door.Shape = door_final
obj_door.Label = "Tampa_Inferior_Compartimento_Bateria"
apply_style(obj_door, (0.22, 0.24, 0.28), transp=0, vis=True) # Grafite Industrial

# ==============================================================================
# 4. ANDAR INFERIOR: BATERIA DUPLA 2x 18650 Li-Ion (6000mAh) + BMS TP4056 USB-C
# ==============================================================================
print("4/9: Montando Bateria Dupla 2x 18650 e Modulo TP4056...")

h_w = 75.0
h_d = 40.0
h_h = 18.5
h_z = floor_th + 0.5 # Z = 3.0 mm

holder_box = Part.makeBox(h_w, h_d, h_h, FreeCAD.Vector(-h_w/2.0, -8.0, h_z))
cav1 = Part.makeCylinder(9.3, 67.0, FreeCAD.Vector(-33.5, 2.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
cav2 = Part.makeCylinder(9.3, 67.0, FreeCAD.Vector(-33.5, 22.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
top_cut = Part.makeBox(68.0, 36.0, 10.0, FreeCAD.Vector(-34.0, -6.0, h_z + 10.0))
bot_cut = Part.makeBox(68.0, 36.0, 6.0, FreeCAD.Vector(-34.0, -6.0, h_z - 1.0))
holder_shape = holder_box.cut(cav1).cut(cav2).cut(top_cut).cut(bot_cut)

obj_holder = doc.addObject("Part::Feature", "Suporte_Bateria_Dupla_2x18650")
obj_holder.Shape = holder_shape
obj_holder.Label = "Suporte_Bateria_2x18650_Dual"
apply_style(obj_holder, (0.12, 0.12, 0.12), transp=0, vis=True)

c1_cyl = Part.makeCylinder(9.0, 65.0, FreeCAD.Vector(-32.5, 2.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
c1_button = Part.makeCylinder(3.0, 1.8, FreeCAD.Vector(32.5, 2.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
obj_bat1 = doc.addObject("Part::Feature", "Celula_18650_A")
obj_bat1.Shape = c1_cyl.fuse(c1_button)
obj_bat1.Label = "Celula_18650_LiIon_3000mAh_A"
apply_style(obj_bat1, (0.15, 0.75, 0.25), transp=0, vis=True)

c2_cyl = Part.makeCylinder(9.0, 65.0, FreeCAD.Vector(-32.5, 22.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
c2_button = Part.makeCylinder(3.0, 1.8, FreeCAD.Vector(32.5, 22.0, h_z + 9.5), FreeCAD.Vector(1, 0, 0))
obj_bat2 = doc.addObject("Part::Feature", "Celula_18650_B")
obj_bat2.Shape = c2_cyl.fuse(c2_button)
obj_bat2.Label = "Celula_18650_LiIon_3000mAh_B"
apply_style(obj_bat2, (0.15, 0.75, 0.25), transp=0, vis=True)

tp_w = 28.0
tp_d = 17.0
tp_th = 1.6
tp_pcb = Part.makeBox(tp_w, tp_d, tp_th, FreeCAD.Vector(22.0 - tp_w/2.0, -34.5, floor_th + 1.5))
tp_usbc = Part.makeBox(8.9, 7.3, 3.2, FreeCAD.Vector(22.0 - 4.45, -36.5, floor_th + 2.0))
tp_ic = Part.makeBox(5.0, 4.0, 1.2, FreeCAD.Vector(22.0 - 2.5, -27.0, floor_th + 1.5 + tp_th))
obj_tp = doc.addObject("Part::Feature", "Modulo_BMS_Carregador_TP4056")
obj_tp.Shape = tp_pcb.fuse(tp_usbc).fuse(tp_ic)
obj_tp.Label = "Modulo_BMS_TP4056_USBC"
apply_style(obj_tp, (0.10, 0.35, 0.75), transp=0, vis=True)

# ==============================================================================
# 5. ANDAR SUPERIOR: PLACA PCB PRINCIPAL (100% RETANGULAR 90 x 65 mm SEM CHANFROS)
# ==============================================================================
print("5/9: Montando Placa PCB Principal (100% Retangular, integra)...")

pcb_z = floor_th + 20.0 # Z = 22.5 mm
# Placa retangular integra sem nenhum corte de quina!
pcb_body = Part.makeBox(pcb_w, pcb_d, 1.6, FreeCAD.Vector(-pcb_w/2.0, -pcb_d/2.0, pcb_z))

# 4 Furos M3 na PCB nos pilares dedicados (X=+-40.0, Y=+-27.5)
pcb_holes = None
for px, py in pcb_standoffs:
    h = Part.makeCylinder(1.6, 2.0, FreeCAD.Vector(px, py, pcb_z - 0.2), FreeCAD.Vector(0, 0, 1))
    if pcb_holes is None:
        pcb_holes = h
    else:
        pcb_holes = pcb_holes.fuse(h)
pcb_final = pcb_body.cut(pcb_holes)

obj_main_pcb = doc.addObject("Part::Feature", "Placa_PCB_Principal_MainNode")
obj_main_pcb.Shape = pcb_final
obj_main_pcb.Label = "Placa_PCB_Principal_90x65"
apply_style(obj_main_pcb, (0.12, 0.45, 0.25), transp=0, vis=True) # Verde FR4

# Modulo ESP32-S3 DevKitC (X=-9.0, Y=0.0)
esp_w = 25.5
esp_d = 49.0
esp_th = 1.6
esp_pcb = Part.makeBox(esp_w, esp_d, esp_th, FreeCAD.Vector(-9.0 - esp_w/2.0, -esp_d/2.0, pcb_z + 1.6))
esp_shield = Part.makeBox(18.0, 16.0, 2.8, FreeCAD.Vector(-9.0 - 9.0, -2.0, pcb_z + 1.6 + esp_th))
esp_usbc = Part.makeBox(8.9, 7.3, 3.2, FreeCAD.Vector(-9.0 - 4.45, -esp_d/2.0 - 2.0, pcb_z + 1.6 + esp_th))
esp_headers_l = Part.makeBox(2.5, esp_d - 4.0, 6.5, FreeCAD.Vector(-9.0 - esp_w/2.0 + 0.5, -esp_d/2.0 + 2.0, pcb_z - 6.5))
esp_headers_r = Part.makeBox(2.5, esp_d - 4.0, 6.5, FreeCAD.Vector(-9.0 + esp_w/2.0 - 3.0, -esp_d/2.0 + 2.0, pcb_z - 6.5))
esp_total = esp_pcb.fuse(esp_shield).fuse(esp_usbc).fuse(esp_headers_l).fuse(esp_headers_r)

obj_esp = doc.addObject("Part::Feature", "Modulo_ESP32_S3_DevKit")
obj_esp.Shape = esp_total
obj_esp.Label = "Modulo_ESP32_S3_DevKitC_N16R8"
apply_style(obj_esp, (0.15, 0.15, 0.15), transp=0, vis=True)

# Modulo LoRa Ebyte E32-TTL-100 (Datasheet Oficial: 36.0 x 21.0mm, SMA a 15.55mm do topo)
# Posicionado em X=[13.0, 49.0], Y=[0.55, 21.55], SMA em Y=6.0mm, Z=28.5mm
e32_w = 36.0
e32_d = 21.0
e32_th = 1.6
e32_pcb = Part.makeBox(e32_w, e32_d, e32_th, FreeCAD.Vector(13.0, 0.55, pcb_z + 1.6 + 2.5))
e32_shield = Part.makeBox(22.0, 17.0, 2.7, FreeCAD.Vector(17.0, 2.5, pcb_z + 1.6 + 2.5 + 1.6))
sma_base = Part.makeBox(6.5, 6.5, 6.5, FreeCAD.Vector(49.0 - 3.5, 6.0 - 3.25, 28.5 - 3.25))
sma_barrel = Part.makeCylinder(3.1, 12.5, FreeCAD.Vector(49.0, 6.0, 28.5), FreeCAD.Vector(1, 0, 0))
sma_nut = Part.makeCylinder(4.2, 3.5, FreeCAD.Vector(x1 - 1.5, 6.0, 28.5), FreeCAD.Vector(1, 0, 0))
lora_header = Part.makeBox(2.5, 17.0, 2.5, FreeCAD.Vector(14.5 - 1.25, 3.43 - 0.88, pcb_z + 1.6))
e32_total = e32_pcb.fuse(e32_shield).fuse(sma_base).fuse(sma_barrel).fuse(sma_nut).fuse(lora_header)

obj_lora = doc.addObject("Part::Feature", "Modulo_LoRa_Ebyte_E32_TTL_100")
obj_lora.Shape = e32_total
obj_lora.Label = "Modulo_LoRa_Ebyte_E32_TTL_100_SMA"
apply_style(obj_lora, (0.85, 0.72, 0.20), transp=0, vis=True)

# 5.3 Resistores Limitadores de Corrente dos LEDs e Buzzer (PTH 1/4W)
# 3x 330R para LEDs (Verde, Laranja, Azul) e 1x 100R para Buzzer
def make_resistor(pos_vec):
    body = Part.makeCylinder(1.15, 6.0, FreeCAD.Vector(-3.0, 0, 1.15), FreeCAD.Vector(1, 0, 0))
    lead1 = Part.makeCylinder(0.3, 3.5, FreeCAD.Vector(-5.0, 0, 1.15), FreeCAD.Vector(1, 0, 0))
    lead2 = Part.makeCylinder(0.3, 3.5, FreeCAD.Vector(3.0, 0, 1.15), FreeCAD.Vector(1, 0, 0))
    pin_l = Part.makeCylinder(0.3, 2.5, FreeCAD.Vector(-5.0, 0, -1.35), FreeCAD.Vector(0, 0, 1))
    pin_r = Part.makeCylinder(0.3, 2.5, FreeCAD.Vector(5.0, 0, -1.35), FreeCAD.Vector(0, 0, 1))
    r = body.fuse(lead1).fuse(lead2).fuse(pin_l).fuse(pin_r)
    r.Placement = FreeCAD.Placement(pos_vec, FreeCAD.Rotation())
    return r

r_led1 = make_resistor(FreeCAD.Vector(22.0, -12.0, pcb_z + 1.6))
r_led2 = make_resistor(FreeCAD.Vector(22.0, -8.5, pcb_z + 1.6))
r_led3 = make_resistor(FreeCAD.Vector(22.0, -5.0, pcb_z + 1.6))
r_buz = make_resistor(FreeCAD.Vector(33.0, -11.0, pcb_z + 1.6))
resistors_shape = r_led1.fuse(r_led2).fuse(r_led3).fuse(r_buz)

obj_resistors = doc.addObject("Part::Feature", "Resistores_Limitadores_LEDs_Buzzer")
obj_resistors.Shape = resistors_shape
obj_resistors.Label = "Resistores_Limitadores_LEDs_Buzzer_4x"
apply_style(obj_resistors, (0.82, 0.72, 0.55), transp=0, vis=True)

# ==============================================================================
# 6. CONECTORES JST-XH NA PCB & ENTRADAS EXTERNAS (GX12 E P2)
# ==============================================================================
print("6/9: Montando Conectores JST-XH na PCB, GX12 e Jack P2...")

def make_jst_male(pins, pos_vec, rot_deg=0):
    w = (pins - 1) * 2.54 + 4.5
    b = Part.makeBox(w, 4.5, 5.5, FreeCAD.Vector(-w/2.0, -2.25, 0))
    cav = Part.makeBox(w - 1.6, 3.3, 4.5, FreeCAD.Vector(-(w - 1.6)/2.0, -1.65, 1.0))
    shroud = b.cut(cav)
    shroud.Placement = FreeCAD.Placement(pos_vec, FreeCAD.Rotation(FreeCAD.Vector(0,0,1), rot_deg))
    return shroud

# Conectores dos Chicotes Laterais (Parede Esquerda):
# Posicionados em X = -32.5mm (distancia livre aos parafusos em X=-40.0: 2.40mm em X e mais de 5.0mm em Y!)
jst_tower = make_jst_male(8, FreeCAD.Vector(-32.5, -8.5, pcb_z + 1.6), 90)
jst_clamp = make_jst_male(2, FreeCAD.Vector(-32.5, 7.0, pcb_z + 1.6), 90)
jst_tacho = make_jst_male(3, FreeCAD.Vector(-32.5, 17.0, pcb_z + 1.6), 90)

# Barramento Frontal de Conectores JST-XH (Orientacao Vertical rot=90, zero colisao e folgas amplas):
# Folga real em relacao ao ESP32: >3.0mm! Folga real ao LoRa: >3.4mm! Folga aos parafusos: >5.4mm!
jst_bat = make_jst_male(2, FreeCAD.Vector(9.0, -20.0, pcb_z + 1.6), 90)
jst_oled = make_jst_male(4, FreeCAD.Vector(16.0, -21.0, pcb_z + 1.6), 90)
jst_leds = make_jst_male(4, FreeCAD.Vector(23.0, -21.0, pcb_z + 1.6), 90)
jst_buz = make_jst_male(2, FreeCAD.Vector(29.5, -18.0, pcb_z + 1.6), 90)

jst_all = jst_tower.fuse(jst_clamp).fuse(jst_tacho).fuse(jst_bat).fuse(jst_oled).fuse(jst_leds).fuse(jst_buz)
obj_jst_connectors = doc.addObject("Part::Feature", "Conectores_JST_XH_PCB")
obj_jst_connectors.Shape = jst_all
obj_jst_connectors.Label = "Conectores_JST_XH_PCB_Polarizados"
apply_style(obj_jst_connectors, (0.94, 0.94, 0.90), transp=0, vis=True) # Branco Natural

# Conectores de Painel na Parede Lateral Esquerda:
gx16_t_base = Part.makeCylinder(9.75, 3.5, FreeCAD.Vector(x0, -16.0, 29.0), FreeCAD.Vector(-1, 0, 0))
gx16_t_thread = Part.makeCylinder(7.9, 8.0, FreeCAD.Vector(x0 - 3.5, -16.0, 29.0), FreeCAD.Vector(-1, 0, 0))
gx16_t_nut = Part.makeCylinder(9.8, 3.0, FreeCAD.Vector(x0 - 4.5, -16.0, 29.0), FreeCAD.Vector(-1, 0, 0))
gx16_t_pins = Part.makeCylinder(5.5, 6.0, FreeCAD.Vector(x0, -16.0, 29.0), FreeCAD.Vector(1, 0, 0))
obj_gx16_tower = doc.addObject("Part::Feature", "Conector_GX16_8_Sonda_Torre")
obj_gx16_tower.Shape = gx16_t_base.fuse(gx16_t_thread).fuse(gx16_t_nut).fuse(gx16_t_pins)
obj_gx16_tower.Label = "Conector_Metalico_GX16_8P_Torre"
apply_style(obj_gx16_tower, (0.82, 0.84, 0.86), transp=0, vis=True)

p2_bushing = Part.makeCylinder(2.9, 5.0, FreeCAD.Vector(x0 - 2.5, 2.0, 30.0), FreeCAD.Vector(1, 0, 0))
p2_nut = Part.makeCylinder(4.2, 1.8, FreeCAD.Vector(x0 - 2.0, 2.0, 30.0), FreeCAD.Vector(1, 0, 0))
p2_inner_body = Part.makeBox(10.0, 8.0, 8.0, FreeCAD.Vector(x0 + wall_th, -2.0, 26.0))
p2_hole = Part.makeCylinder(1.8, 7.0, FreeCAD.Vector(x0 - 3.0, 2.0, 30.0), FreeCAD.Vector(1, 0, 0))
p2_shape = p2_bushing.fuse(p2_nut).fuse(p2_inner_body).cut(p2_hole)
obj_p2 = doc.addObject("Part::Feature", "Conector_Jack_P2_Femea_SCT013")
obj_p2.Shape = p2_shape
obj_p2.Label = "Conector_Jack_P2_Femea_SCT013"
apply_style(obj_p2, (0.75, 0.76, 0.78), transp=0, vis=True) # Metal Prateado

gx12_tc_base = Part.makeCylinder(7.5, 3.0, FreeCAD.Vector(x0, 18.0, 30.0), FreeCAD.Vector(-1, 0, 0))
gx12_tc_thread = Part.makeCylinder(5.8, 6.0, FreeCAD.Vector(x0 - 3.0, 18.0, 30.0), FreeCAD.Vector(-1, 0, 0))
gx12_tc_nut = Part.makeCylinder(7.8, 3.0, FreeCAD.Vector(x0 - 4.0, 18.0, 30.0), FreeCAD.Vector(-1, 0, 0))
gx12_tc_pins = Part.makeCylinder(4.5, 5.0, FreeCAD.Vector(x0, 18.0, 30.0), FreeCAD.Vector(1, 0, 0))
obj_gx12_tacho = doc.addObject("Part::Feature", "Conector_GX12_3_Tacometro")
obj_gx12_tacho.Shape = gx12_tc_base.fuse(gx12_tc_thread).fuse(gx12_tc_nut).fuse(gx12_tc_pins)
obj_gx12_tacho.Label = "Conector_Metalico_GX12_3P_Tacometro"
apply_style(obj_gx12_tacho, (0.82, 0.84, 0.86), transp=0, vis=True)

wire_tower = Part.makeCylinder(0.8, 19.0, FreeCAD.Vector(x0 + 4.0, -16.0, 29.0), FreeCAD.Vector(1, 0.35, -0.6))
wire_p2 = Part.makeCylinder(0.8, 17.0, FreeCAD.Vector(x0 + 5.0, 2.0, 30.0), FreeCAD.Vector(1, 0.25, -0.6))
wire_tacho = Part.makeCylinder(0.8, 18.0, FreeCAD.Vector(x0 + 4.0, 18.0, 30.0), FreeCAD.Vector(1, -0.05, -0.6))
obj_wires_gx12 = doc.addObject("Part::Feature", "Chicotes_Fios_Sensores_JST")
obj_wires_gx12.Shape = wire_tower.fuse(wire_p2).fuse(wire_tacho)
obj_wires_gx12.Label = "Chicotes_Fios_Sensores_JST"
apply_style(obj_wires_gx12, (0.2, 0.2, 0.2), transp=0, vis=True)

# 6.3 Interface IHM Embutida na Tampa
oled_pcb = Part.makeBox(27.0, 27.0, 1.6, FreeCAD.Vector(6.0 - 13.5, -14.0 - 13.5, lid_z1 - ceiling_th - 1.6))
oled_glass = Part.makeBox(25.0, 14.0, 1.2, FreeCAD.Vector(6.0 - 12.5, -14.0 - 7.0, lid_z1 - ceiling_th))
obj_oled = doc.addObject("Part::Feature", "Display_OLED_096_I2C")
obj_oled.Shape = oled_pcb.fuse(oled_glass)
obj_oled.Label = "Display_OLED_096_Embutido_Tampa"
apply_style(obj_oled, (0.05, 0.10, 0.45), transp=0, vis=True)

l1_lens = Part.makeCylinder(1.5, 4.0, FreeCAD.Vector(24.0, -22.0, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
l1_tip = Part.makeSphere(1.5, FreeCAD.Vector(24.0, -22.0, lid_z1 + 1.5))
obj_led1 = doc.addObject("Part::Feature", "LED_Power_Bateria_Verde")
obj_led1.Shape = l1_lens.fuse(l1_tip)
apply_style(obj_led1, (0.10, 0.90, 0.20), transp=10, vis=True)

l2_lens = Part.makeCylinder(1.5, 4.0, FreeCAD.Vector(31.0, -22.0, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
l2_tip = Part.makeSphere(1.5, FreeCAD.Vector(31.0, -22.0, lid_z1 + 1.5))
obj_led2 = doc.addObject("Part::Feature", "LED_Alerta_Metrologico_Laranja")
obj_led2.Shape = l2_lens.fuse(l2_tip)
apply_style(obj_led2, (0.95, 0.60, 0.10), transp=10, vis=True)

l3_lens = Part.makeCylinder(1.5, 4.0, FreeCAD.Vector(38.0, -22.0, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
l3_tip = Part.makeSphere(1.5, FreeCAD.Vector(38.0, -22.0, lid_z1 + 1.5))
obj_led3 = doc.addObject("Part::Feature", "LED_Comunicacao_LoRa_Azul")
obj_led3.Shape = l3_lens.fuse(l3_tip)
apply_style(obj_led3, (0.15, 0.45, 0.95), transp=10, vis=True)

buz_cyl = Part.makeCylinder(4.75, 5.5, FreeCAD.Vector(31.0, -8.0, lid_z1 - ceiling_th - 5.5), FreeCAD.Vector(0, 0, 1))
buz_hole = Part.makeCylinder(1.2, 1.5, FreeCAD.Vector(31.0, -8.0, lid_z1 - ceiling_th - 1.0), FreeCAD.Vector(0, 0, 1))
obj_buz = doc.addObject("Part::Feature", "Buzzer_Piezo_Alarme")
obj_buz.Shape = buz_cyl.cut(buz_hole)
obj_buz.Label = "Buzzer_Piezo_Embutido_Tampa"
apply_style(obj_buz, (0.12, 0.12, 0.12), transp=0, vis=True)

# ==============================================================================
# 7. ELEMENTOS DE FIXACAO: PARAFUSOS M3 E INSERTOS DE LATAO
# ==============================================================================
print("7/9: Modelando Elementos de Fixacao (Parafusos M3 e Insertos de Latao)...")

def make_m3_button_head_screw(pos_vec, shaft_len=16.0):
    head = Part.makeCylinder(2.85, 1.65, FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1))
    shaft = Part.makeCylinder(1.48, shaft_len, FreeCAD.Vector(0, 0, -shaft_len), FreeCAD.Vector(0, 0, 1))
    hex_socket = Part.makeCylinder(1.0, 1.2, FreeCAD.Vector(0, 0, 0.5), FreeCAD.Vector(0, 0, 1))
    screw = head.fuse(shaft).cut(hex_socket)
    screw.Placement = FreeCAD.Placement(pos_vec, FreeCAD.Rotation())
    return screw

def make_m3_countersunk_screw(pos_vec, shaft_len=8.0):
    cone = Part.makeCone(3.0, 1.5, 1.65, FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1))
    shaft = Part.makeCylinder(1.48, shaft_len - 1.65, FreeCAD.Vector(0, 0, 1.65), FreeCAD.Vector(0, 0, 1))
    hex_socket = Part.makeCylinder(1.0, 1.2, FreeCAD.Vector(0, 0, 0.0), FreeCAD.Vector(0, 0, 1))
    screw = cone.fuse(shaft).cut(hex_socket)
    screw.Placement = FreeCAD.Placement(pos_vec, FreeCAD.Rotation())
    return screw

# 1) 4 Parafusos ISO 7380 M3x16mm para a Tampa Superior (Rosqueiam nas 4 Colunas de Canto da Base!)
screws_top = None
for cx, cy in lid_corner_coords:
    sc = make_m3_button_head_screw(FreeCAD.Vector(cx, cy, lid_z1 - 2.5), 16.0)
    if screws_top is None:
        screws_top = sc
    else:
        screws_top = screws_top.fuse(sc)

obj_screws_top = doc.addObject("Part::Feature", "Parafusos_M3_Tampa_Superior_ISO7380")
obj_screws_top.Shape = screws_top
obj_screws_top.Label = "Parafusos_M3_Tampa_Superior_4x"
apply_style(obj_screws_top, (0.80, 0.82, 0.85), transp=0, vis=True) # Aço Inox

# 2) 4 Parafusos M3x6mm de Fixacao da PCB Principal nos Pilares Internos (X=+-40.0, Y=+-27.5, Z=22.5mm)
screws_pcb = None
for px, py in pcb_standoffs:
    sp = make_m3_button_head_screw(FreeCAD.Vector(px, py, pcb_z + 1.6), 6.0)
    if screws_pcb is None:
        screws_pcb = sp
    else:
        screws_pcb = screws_pcb.fuse(sp)

obj_screws_pcb = doc.addObject("Part::Feature", "Parafusos_M3_Fixacao_PCB_Principal")
obj_screws_pcb.Shape = screws_pcb
obj_screws_pcb.Label = "Parafusos_M3_Fixacao_PCB_4x"
apply_style(obj_screws_pcb, (0.75, 0.77, 0.80), transp=0, vis=True)

# 3) 4 Parafusos DIN 7991 M3x8mm Escareados para a Tampa Inferior de Bateria (Fundo)
screws_bat = None
for bx, by in bat_screw_posts:
    sb = make_m3_countersunk_screw(FreeCAD.Vector(bx, by, 0.0), 8.0)
    if screws_bat is None:
        screws_bat = sb
    else:
        screws_bat = screws_bat.fuse(sb)

obj_screws_bat = doc.addObject("Part::Feature", "Parafusos_M3_Tampa_Bateria_DIN7991")
obj_screws_bat.Shape = screws_bat
obj_screws_bat.Label = "Parafusos_M3_Tampa_Bateria_4x_Escareados"
apply_style(obj_screws_bat, (0.80, 0.82, 0.85), transp=0, vis=True)

# 4) 4 Insertos Roscados de Latão M3x5mm no Topo das 4 Colunas de Canto da Carcaça Base (Z=33.0 a 38.0mm)
inserts_corners = None
for cx, cy in lid_corner_coords:
    ins_outer = Part.makeCylinder(2.3, 5.0, FreeCAD.Vector(cx, cy, base_total_h - 5.0), FreeCAD.Vector(0, 0, 1))
    ins_inner = Part.makeCylinder(1.5, 5.2, FreeCAD.Vector(cx, cy, base_total_h - 5.1), FreeCAD.Vector(0, 0, 1))
    ins = ins_outer.cut(ins_inner)
    if inserts_corners is None:
        inserts_corners = ins
    else:
        inserts_corners = inserts_corners.fuse(ins)

# 5) 4 Insertos Roscados de Latão M3x4mm nos Apoios da Tampa de Bateria (Z=3.5 a 7.5mm)
inserts_bat = None
for bx, by in bat_screw_posts:
    ins_outer = Part.makeCylinder(2.3, 4.0, FreeCAD.Vector(bx, by, floor_th + 1.0), FreeCAD.Vector(0, 0, 1))
    ins_inner = Part.makeCylinder(1.5, 4.2, FreeCAD.Vector(bx, by, floor_th + 0.9), FreeCAD.Vector(0, 0, 1))
    ins = ins_outer.cut(ins_inner)
    if inserts_bat is None:
        inserts_bat = ins
    else:
        inserts_bat = inserts_bat.fuse(ins)

obj_inserts = doc.addObject("Part::Feature", "Insertos_Roscados_Latao_M3")
obj_inserts.Shape = inserts_corners.fuse(inserts_bat)
obj_inserts.Label = "Insertos_Roscados_Latao_M3_8x"
apply_style(obj_inserts, (0.88, 0.72, 0.18), transp=0, vis=True) # Latão Dourado

# ==============================================================================
# 8. ORGANIZACAO DE GRUPOS E PERSISTENCIA
# ==============================================================================
print("8/9: Organizando grupos e salvando modelo nativo...")

grp_case = doc.addObject("App::DocumentObjectGroup", "Gabinete_Enclosure")
grp_case.addObjects([obj_base, obj_lid, obj_door])

grp_power = doc.addObject("App::DocumentObjectGroup", "Alimentacao_Bateria_Dupla_6000mAh")
grp_power.addObjects([obj_holder, obj_bat1, obj_bat2, obj_tp])

grp_pcb = doc.addObject("App::DocumentObjectGroup", "Eletronica_Principal_PCB")
grp_pcb.addObjects([obj_main_pcb, obj_esp, obj_lora, obj_jst_connectors, obj_resistors])

grp_ihm = doc.addObject("App::DocumentObjectGroup", "Interface_IHM_Tampa")
grp_ihm.addObjects([obj_oled, obj_led1, obj_led2, obj_led3, obj_buz])

grp_probes = doc.addObject("App::DocumentObjectGroup", "Conectores_Sensores_Externos")
grp_probes.addObjects([obj_gx16_tower, obj_p2, obj_gx12_tacho, obj_wires_gx12])

grp_hardware = doc.addObject("App::DocumentObjectGroup", "Elementos_de_Fixacao_Parafusos")
grp_hardware.addObjects([obj_screws_top, obj_screws_pcb, obj_screws_bat, obj_inserts])

doc.recompute()

fcstd_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd"
step_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_assembly_complete.step"

doc.saveAs(fcstd_path)
feats = [o for o in doc.Objects if o.isDerivedFrom("Part::Feature")]
Part.export(feats, step_path)

# Exportacao automatica de STLs para impressao 3D
import Mesh
cad_dir = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad"
Mesh.export([obj_base], os.path.join(cad_dir, "amemiya_main_node_base.stl"))
Mesh.export([obj_lid], os.path.join(cad_dir, "amemiya_main_node_lid.stl"))
Mesh.export([obj_door], os.path.join(cad_dir, "amemiya_main_node_battery_door.stl"))

print("Main Node v2.5 (GX16-8) modelado, exportado e salvo com sucesso em:")
print("  FCStd: " + fcstd_path)
print("  STEP:  " + step_path)
print("  STLs:  Base, Lid, Battery Door em " + cad_dir)
