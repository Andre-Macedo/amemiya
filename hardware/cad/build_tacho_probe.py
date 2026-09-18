# -*- coding: utf-8 -*-
"""
Construcao Parametrica Completa da Sonda Industrial para Tacometro (Sensor Hall A3144 / KY-003)
Projeto: Sistema de Metrologia Lean Tech - Amemiya
Arquitetura:
1. Modulo Sensor de Efeito Hall KY-003 (PCB, Chip A3144 TO-92UA, Pinos Header, LED e Resistor SMD)
2. Sonda Industrial Retangular Bipartida (Base com trilhos, batente fino frontal, trava de cabo e orelha M3)
3. Tampa Superior com visor conico para o LED, nervura de pressao da PCB e furos escareados
4. Colar bipartido para fixacao de ima de neodimio (dia 5x2mm) em eixo rotativo (dia 10mm)
5. Exportacao em FCStd, STEP e STL para impressao 3D
"""

import FreeCAD
import Part
import os
import math

doc_name = "amemiya_tacho_probe"

# Limpa ou cria o documento
try:
    doc = FreeCAD.getDocument(doc_name)
    if doc:
        FreeCAD.closeDocument(doc_name)
except Exception:
    pass

try:
    test_d = FreeCAD.getDocument("test_doc")
    if test_d:
        FreeCAD.closeDocument("test_doc")
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

print("=== 1. Construindo o Modulo KY-003 (Sensor Hall A3144) ===")

# 1.1 Placa de Circuito Impresso (PCB 18.5 x 15.0 x 1.6 mm)
pcb_len = 18.5
pcb_wid = 15.0
pcb_th  = 1.6
pcb_z   = 3.5  # altura de repouso no berco da base

pcb_box = Part.makeBox(pcb_len, pcb_wid, pcb_th, FreeCAD.Vector(-4.0, -pcb_wid/2.0, pcb_z))

# Furo de fixacao original M3 na PCB em X=3.5, Y=0
hole_pcb = Part.makeCylinder(1.6, pcb_th + 1.0, FreeCAD.Vector(3.5, 0.0, pcb_z - 0.5))
pcb_shape = pcb_box.cut(hole_pcb)

obj_pcb = doc.addObject("Part::Feature", "Modulo_KY003_PCB")
obj_pcb.Shape = pcb_shape
obj_pcb.Label = "PCB_KY003_18.5x15mm"
apply_style(obj_pcb, (0.08, 0.12, 0.22)) # Azul escuro/preto industrial

# 1.2 Chip Sensor Hall A3144 (Encapsulamento TO-92UA)
# Fica projetado na frente da PCB (X = 14.5 a 19.5 mm)
# Terminais metalicos (3 perninhas)
t1 = Part.makeCylinder(0.25, 4.2, FreeCAD.Vector(14.5, -1.27, pcb_z + pcb_th/2.0), FreeCAD.Vector(1, 0, 0))
t2 = Part.makeCylinder(0.25, 4.2, FreeCAD.Vector(14.5,  0.00, pcb_z + pcb_th/2.0), FreeCAD.Vector(1, 0, 0))
t3 = Part.makeCylinder(0.25, 4.2, FreeCAD.Vector(14.5,  1.27, pcb_z + pcb_th/2.0), FreeCAD.Vector(1, 0, 0))
terminals_shape = t1.fuse(t2).fuse(t3)

obj_leads = doc.addObject("Part::Feature", "Sensor_A3144_Terminais")
obj_leads.Shape = terminals_shape
obj_leads.Label = "Sensor_A3144_Terminais"
apply_style(obj_leads, (0.85, 0.85, 0.85)) # Prata estanhado

# Corpo plastico do sensor A3144
# 4.1 mm de largura (Y), 3.0 mm de altura (Z), 1.5 mm de espessura (X)
a3144_box = Part.makeBox(1.5, 4.2, 3.2, FreeCAD.Vector(18.7, -2.1, pcb_z + pcb_th/2.0 - 1.6))
# Chanfro traseiro caracteristico do TO-92
chamfer_cut = Part.makeBox(1.0, 5.0, 3.5, FreeCAD.Vector(18.5, -2.5, pcb_z + pcb_th/2.0 - 1.75))
chamfer_cut.rotate(FreeCAD.Vector(18.7, 0, pcb_z + pcb_th/2.0), FreeCAD.Vector(0, 1, 0), 25)
a3144_body = a3144_box.cut(chamfer_cut)

obj_a3144 = doc.addObject("Part::Feature", "Sensor_A3144_Corpo")
obj_a3144.Shape = a3144_body
obj_a3144.Label = "Sensor_Hall_A3144"
apply_style(obj_a3144, (0.05, 0.05, 0.05)) # Preto fosco epoxi

# 1.3 LED Indicador de Pulso e Detecao (SMD 0805 Verde)
led_box = Part.makeBox(2.0, 1.25, 0.8, FreeCAD.Vector(5.0, 2.5, pcb_z + pcb_th))
obj_led = doc.addObject("Part::Feature", "LED_Indicador_Sinal")
obj_led.Shape = led_box
obj_led.Label = "LED_Indicador_Deteccao"
apply_style(obj_led, (0.1, 0.95, 0.2), transp=20) # Verde translucidor

# 1.4 Resistor SMD Pull-up
res_box = Part.makeBox(2.0, 1.25, 0.6, FreeCAD.Vector(5.0, -3.75, pcb_z + pcb_th))
obj_res = doc.addObject("Part::Feature", "Resistor_SMD_10K")
obj_res.Shape = res_box
obj_res.Label = "Resistor_PullUp_10K"
apply_style(obj_res, (0.15, 0.15, 0.15))

# 1.5 Conector Header 1x3 Pinos (X = -4.0 mm)
hdr_base = Part.makeBox(2.5, 7.62, 2.5, FreeCAD.Vector(-4.0, -3.81, pcb_z + pcb_th))
p1 = Part.makeBox(7.0, 0.64, 0.64, FreeCAD.Vector(-7.5, -2.54 - 0.32, pcb_z + pcb_th + 0.93))
p2 = Part.makeBox(7.0, 0.64, 0.64, FreeCAD.Vector(-7.5,  0.00 - 0.32, pcb_z + pcb_th + 0.93))
p3 = Part.makeBox(7.0, 0.64, 0.64, FreeCAD.Vector(-7.5,  2.54 - 0.32, pcb_z + pcb_th + 0.93))
pins_shape = p1.fuse(p2).fuse(p3)

obj_hdr_base = doc.addObject("Part::Feature", "Header_Base_Plastica")
obj_hdr_base.Shape = hdr_base
obj_hdr_base.Label = "Header_Isolador_1x3"
apply_style(obj_hdr_base, (0.1, 0.1, 0.1))

obj_hdr_pins = doc.addObject("Part::Feature", "Header_Pinos_Metalicos")
obj_hdr_pins.Shape = pins_shape
obj_hdr_pins.Label = "Header_Pinos_Dourados"
apply_style(obj_hdr_pins, (0.9, 0.75, 0.2)) # Dourado

print("=== 2. Construindo a Base da Sonda Industrial ===")

# Parametros da Base
base_x_min = -18.0
base_x_max =  21.2  # Deixa apenas 1.0mm de parede na frente do A3144 (21.2 - 20.2 = 1.0mm)
base_len   = base_x_max - base_x_min # 39.2 mm
base_wid   = 21.0
base_h     = 12.0

# 2.1 Bloco Externo Principal
main_body_box = Part.makeBox(base_len, base_wid, base_h, FreeCAD.Vector(base_x_min, -base_wid/2.0, 0.0))

# 2.2 Orelha/Flange Lateral de Montagem (com Furo Oblongo M3)
flange_wid = 9.5
flange_len = 22.0
flange_th  = 4.5
flange_x0  = -8.0
flange_box = Part.makeBox(flange_len, flange_wid, flange_th, FreeCAD.Vector(flange_x0, base_wid/2.0, 0.0))

# Furo Oblongo de 3.5mm de largura x 8.0mm de curso para parafuso M3
slot_c1 = Part.makeCylinder(1.75, flange_th + 2.0, FreeCAD.Vector(flange_x0 + 7.0,  base_wid/2.0 + flange_wid/2.0, -1.0))
slot_c2 = Part.makeCylinder(1.75, flange_th + 2.0, FreeCAD.Vector(flange_x0 + 15.0, base_wid/2.0 + flange_wid/2.0, -1.0))
slot_mid = Part.makeBox(8.0, 3.5, flange_th + 2.0, FreeCAD.Vector(flange_x0 + 7.0, base_wid/2.0 + flange_wid/2.0 - 1.75, -1.0))
slot_cut = slot_c1.fuse(slot_c2).fuse(slot_mid)

flange_solid = flange_box.cut(slot_cut)
base_solid = main_body_box.fuse(flange_solid)

# 2.3 Cavidade Interna da PCB (15.6mm de largura x 20.0mm de comp.)
cav_wid = 15.6
cav_len = 20.0
cav_z0  = pcb_z
cav_h   = base_h - cav_z0 + 1.0
cav_pcb = Part.makeBox(cav_len, cav_wid, cav_h, FreeCAD.Vector(-4.5, -cav_wid/2.0, cav_z0))

# Cavidade Inferior (espaco livre sob a PCB entre os apoios laterais)
sub_cav = Part.makeBox(17.5, 11.0, cav_z0 - 1.5, FreeCAD.Vector(-3.5, -5.5, 1.5))

# 2.4 Bolsao Frontal de Encaixe Justo do Sensor A3144
# Parede frontal fina (exatamente 0.8mm) para minima atenuacao magnetica
pocket_a3144 = Part.makeBox(5.0, 5.2, 5.0, FreeCAD.Vector(15.5, -2.6, pcb_z - 0.5))

# 2.5 Tunel Traseiro do Cabo e Prensa-Cabo
cable_tunnel = Part.makeCylinder(2.25, 15.0, FreeCAD.Vector(-19.0, 0.0, pcb_z + 2.5), FreeCAD.Vector(1, 0, 0))
# Dentes de aperto do cabo (anti-arrancamento)
strain_groove1 = Part.makeCylinder(2.6, 1.0, FreeCAD.Vector(-14.0, 0.0, pcb_z + 2.5), FreeCAD.Vector(1, 0, 0))
strain_groove2 = Part.makeCylinder(2.6, 1.0, FreeCAD.Vector(-10.0, 0.0, pcb_z + 2.5), FreeCAD.Vector(1, 0, 0))

# 2.6 Furos de Fixacao da Tampa (M2.5 / M3)
hole_lid1 = Part.makeCylinder(1.15, 8.0, FreeCAD.Vector(-15.0, -7.5, base_h - 7.0))
hole_lid2 = Part.makeCylinder(1.15, 8.0, FreeCAD.Vector(-15.0,  7.5, base_h - 7.0))
hole_lid3 = Part.makeCylinder(1.15, 8.0, FreeCAD.Vector( 18.0, -7.5, base_h - 7.0))
hole_lid4 = Part.makeCylinder(1.15, 8.0, FreeCAD.Vector( 18.0,  7.5, base_h - 7.0))

# 2.7 Rebaixo perimetral superior para encaixe estanque da tampa (Labio Femea)
lip_pocket = Part.makeBox(base_len - 3.0, base_wid - 3.0, 1.2, FreeCAD.Vector(base_x_min + 1.5, -(base_wid - 3.0)/2.0, base_h - 1.2))

# Subtracao de todas as cavidades
base_finished = base_solid.cut(cav_pcb).cut(sub_cav).cut(pocket_a3144)\
                          .cut(cable_tunnel).cut(strain_groove1).cut(strain_groove2)\
                          .cut(hole_lid1).cut(hole_lid2).cut(hole_lid3).cut(hole_lid4)\
                          .cut(lip_pocket)

# Pino guia de centralizacao da PCB (entra no furo original de 3.2mm)
pin_guide = Part.makeCylinder(1.4, 2.5, FreeCAD.Vector(3.5, 0.0, pcb_z))
base_with_pin = base_finished.fuse(pin_guide)

obj_base = doc.addObject("Part::Feature", "Sonda_Tacometro_Base")
obj_base.Shape = base_with_pin
obj_base.Label = "Sonda_Tacometro_Base_M3"
apply_style(obj_base, (0.15, 0.45, 0.35)) # Verde industrial/petroleo escuro

print("=== 3. Construindo a Tampa Superior com Visor de LED ===")

lid_th = 2.6
lid_z0 = base_h

# Placa principal da tampa
lid_plate = Part.makeBox(base_len, base_wid, lid_th, FreeCAD.Vector(base_x_min, -base_wid/2.0, lid_z0))

# Labio macho perimetral de encaixe
lid_lip = Part.makeBox(base_len - 3.4, base_wid - 3.4, 1.0, FreeCAD.Vector(base_x_min + 1.7, -(base_wid - 3.4)/2.0, lid_z0 - 1.0))
lid_solid = lid_plate.fuse(lid_lip)

# Furos escareados para 4 parafusos M2.5
def make_countersunk_hole(x, y):
    h_cyl = Part.makeCylinder(1.4, lid_th + 3.0, FreeCAD.Vector(x, y, lid_z0 - 1.5))
    h_cone = Part.makeCone(2.8, 1.4, 1.5, FreeCAD.Vector(x, y, lid_z0 + lid_th - 1.5))
    return h_cyl.fuse(h_cone)

sc_lid1 = make_countersunk_hole(-15.0, -7.5)
sc_lid2 = make_countersunk_hole(-15.0,  7.5)
sc_lid3 = make_countersunk_hole( 18.0, -7.5)
sc_lid4 = make_countersunk_hole( 18.0,  7.5)

# Visor / Janela conica para visualizacao do LED de deteccao
led_window = Part.makeCone(1.2, 2.0, lid_th + 2.0, FreeCAD.Vector(6.0, 2.5, lid_z0 - 1.0))

# Nervura inferior de travamento da PCB (hold-down rib)
hold_rib = Part.makeBox(1.5, 12.0, 2.0, FreeCAD.Vector(0.0, -6.0, lid_z0 - 2.0))
lid_with_rib = lid_solid.fuse(hold_rib)

lid_finished = lid_with_rib.cut(sc_lid1).cut(sc_lid2).cut(sc_lid3).cut(sc_lid4).cut(led_window)

obj_lid = doc.addObject("Part::Feature", "Sonda_Tacometro_Tampa")
obj_lid.Shape = lid_finished
obj_lid.Label = "Sonda_Tacometro_Tampa"
apply_style(obj_lid, (0.20, 0.55, 0.45))

print("=== 4. Construindo Cabos e Parafusos de Montagem ===")

# Cabo de 3 vias saindo pela traseira
cable_ext = Part.makeCylinder(2.1, 40.0, FreeCAD.Vector(-18.0, 0.0, pcb_z + 2.5), FreeCAD.Vector(-1, 0, 0))
obj_cable = doc.addObject("Part::Feature", "Cabo_Blindado_3Vias")
obj_cable.Shape = cable_ext
obj_cable.Label = "Cabo_Blindado_3Vias_GX12"
apply_style(obj_cable, (0.1, 0.1, 0.1))

# Parafusos M2.5 de fechamento da tampa
screws_shape = None
for px, py in [(-15.0, -7.5), (-15.0, 7.5), (18.0, -7.5), (18.0, 7.5)]:
    sc_head = Part.makeCone(2.6, 1.3, 1.4, FreeCAD.Vector(px, py, lid_z0 + lid_th - 1.4))
    sc_thread = Part.makeCylinder(1.2, 7.0, FreeCAD.Vector(px, py, lid_z0 - 7.0))
    sc_full = sc_head.fuse(sc_thread)
    if screws_shape is None:
        screws_shape = sc_full
    else:
        screws_shape = screws_shape.fuse(sc_full)

obj_screws = doc.addObject("Part::Feature", "Parafusos_M25_Tampa")
obj_screws.Shape = screws_shape
obj_screws.Label = "Parafusos_Inox_M2.5x8mm"
apply_style(obj_screws, (0.85, 0.85, 0.88))

print("=== 5. Construindo o Colar para Ima de Eixo Rotativo ===")

# Colar cilindrico bipartido para eixo de 10mm com alojamento para ima de neodimio 5x2mm
# Posicionado em frente a sonda (X = 26.0 mm, eixo Z alinhado com o centro do sensor)
collar_outer_r = 10.0
collar_inner_r =  5.0 # Para eixo de 10mm (raio 5mm)
collar_height  = 12.0
collar_pos = FreeCAD.Vector(33.0, 0.0, pcb_z + pcb_th/2.0)

# Cilindro externo e furo do eixo
cyl_out = Part.makeCylinder(collar_outer_r, collar_height, FreeCAD.Vector(collar_pos.x, collar_pos.y, collar_pos.z - collar_height/2.0), FreeCAD.Vector(0, 0, 1))
cyl_in  = Part.makeCylinder(collar_inner_r, collar_height + 2.0, FreeCAD.Vector(collar_pos.x, collar_pos.y, collar_pos.z - collar_height/2.0 - 1.0), FreeCAD.Vector(0, 0, 1))
collar_ring = cyl_out.cut(cyl_in)

# Alojamento cilíndrico para o ímã de neodímio de 5.0 x 2.0 mm voltado para a sonda (-X)
pocket_mag = Part.makeCylinder(2.6, 2.2, FreeCAD.Vector(collar_pos.x - collar_outer_r + 2.1, collar_pos.y, collar_pos.z), FreeCAD.Vector(-1, 0, 0))
collar_finished = collar_ring.cut(pocket_mag)

# Ima de neodimio niquelado (dia 5.0 x 2.0 mm)
magnet_shape = Part.makeCylinder(2.5, 2.0, FreeCAD.Vector(collar_pos.x - collar_outer_r + 2.0, collar_pos.y, collar_pos.z), FreeCAD.Vector(-1, 0, 0))

obj_collar = doc.addObject("Part::Feature", "Colar_Eixo_Tacometro")
obj_collar.Shape = collar_finished
obj_collar.Label = "Colar_Eixo_Rotativo_D10mm"
apply_style(obj_collar, (0.8, 0.4, 0.15)) # Laranja industrial / PETG

obj_magnet = doc.addObject("Part::Feature", "Ima_Neodimio_N35")
obj_magnet.Shape = magnet_shape
obj_magnet.Label = "Ima_Neodimio_5x2mm"
apply_style(obj_magnet, (0.92, 0.93, 0.95)) # Prata espelhado brilhante

# Eixo rotativo de referencia (dia 10mm)
shaft = Part.makeCylinder(collar_inner_r, 40.0, FreeCAD.Vector(collar_pos.x, collar_pos.y, collar_pos.z - 20.0), FreeCAD.Vector(0, 0, 1))
obj_shaft = doc.addObject("Part::Feature", "Eixo_Rotativo_Referencia")
obj_shaft.Shape = shaft
obj_shaft.Label = "Eixo_Motor_10mm"
apply_style(obj_shaft, (0.6, 0.65, 0.70), transp=40)

print("=== 6. Agrupando e Salvando Arquivos ===")

grp_probe = doc.addObject("App::DocumentObjectGroup", "Sonda_Industrial")
grp_probe.Label = "1. Sonda Industrial (Case)"
grp_probe.addObject(obj_base)
grp_probe.addObject(obj_lid)
grp_probe.addObject(obj_screws)
grp_probe.addObject(obj_cable)

grp_sensor = doc.addObject("App::DocumentObjectGroup", "Sensor_KY003")
grp_sensor.Label = "2. Modulo Hall KY-003"
grp_sensor.addObject(obj_pcb)
grp_sensor.addObject(obj_leads)
grp_sensor.addObject(obj_a3144)
grp_sensor.addObject(obj_led)
grp_sensor.addObject(obj_res)
grp_sensor.addObject(obj_hdr_base)
grp_sensor.addObject(obj_hdr_pins)

grp_rotor = doc.addObject("App::DocumentObjectGroup", "Conjunto_Rotor")
grp_rotor.Label = "3. Conjunto Rotor e Ima"
grp_rotor.addObject(obj_collar)
grp_rotor.addObject(obj_magnet)
grp_rotor.addObject(obj_shaft)

doc.recompute()

# Salvar FCStd nativo
fcstd_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_tacho_probe.FCStd"
doc.saveAs(fcstd_path)
print(f"Documento FreeCAD salvo em: {fcstd_path}")

# Exportar STEP completo da montagem
step_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_tacho_probe_assembly.step"
all_parts = [obj_base, obj_lid, obj_screws, obj_cable, obj_pcb, obj_leads, obj_a3144, obj_led, obj_res, obj_hdr_base, obj_hdr_pins, obj_collar, obj_magnet, obj_shaft]
Part.export(all_parts, step_path)
print(f"STEP completo exportado em: {step_path}")

# Exportar STLs para impressao 3D
import Mesh
stl_base = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_tacho_probe_base.stl"
stl_lid  = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_tacho_probe_lid.stl"
stl_col  = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_tacho_magnet_collar.stl"

Mesh.export([obj_base], stl_base)
Mesh.export([obj_lid], stl_lid)
Mesh.export([obj_collar], stl_col)
print(f"STLs exportados com sucesso:\n - {stl_base}\n - {stl_lid}\n - {stl_col}")

print("CONCLUIDO COM SUCESSO!")
