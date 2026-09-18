# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import
import time

print("Iniciando construcao detalhada da Sonda Cartucho Torre...")
t0 = time.time()

doc = FreeCAD.newDocument("Enclosure_Amemiya_ProbeTower")

# ==============================================================================
# 1. CARCACA TORRE VERTICAL (PartDesign::Body com Sketches Parametricos)
# ==============================================================================
body_tower = doc.addObject("PartDesign::Body", "Gabinete_Torre_Base")

# Sketch 1: Perfil Externo da Torre (30.0 x 32.0 mm)
sk_perfil = doc.addObject("Sketcher::SketchObject", "Sketch_Torre_Perfil_Externo")
body_tower.addObject(sk_perfil)
sk_perfil.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_perfil.MapMode = "Deactivated"

x0, y0 = -15.0, -16.0
x1, y1 = 15.0, 16.0
sk_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4): sk_perfil.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_perfil.addConstraint(Sketcher.Constraint("Horizontal", 0))
sk_perfil.addConstraint(Sketcher.Constraint("Vertical", 1))
sk_perfil.addConstraint(Sketcher.Constraint("Horizontal", 2))
sk_perfil.addConstraint(Sketcher.Constraint("Vertical", 3))
sk_perfil.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 30.0))
sk_perfil.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 32.0))
doc.recompute()

pad_torre = doc.addObject("PartDesign::Pad", "Pad_Torre_Solida")
pad_torre.Profile = sk_perfil
pad_torre.Length = 56.0 # Altura de 56mm
body_tower.addObject(pad_torre)
doc.recompute()

# Sketch 2: Cavidade Interna da Torre (24.6 x 26.0 mm)
sk_cavidade = doc.addObject("Sketcher::SketchObject", "Sketch_Cavidade_Interna")
body_tower.addObject(sk_cavidade)
sk_cavidade.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_cavidade.MapMode = "Deactivated"

cx0, cy0 = -12.3, -13.0
cx1, cy1 = 12.3, 13.0
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy0, 0), FreeCAD.Vector(cx1, cy0, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy0, 0), FreeCAD.Vector(cx1, cy1, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy1, 0), FreeCAD.Vector(cx0, cy1, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy1, 0), FreeCAD.Vector(cx0, cy0, 0)), False)
for i in range(4): sk_cavidade.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_cavidade.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 24.6))
sk_cavidade.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 26.0))
doc.recompute()

pocket_cavidade = doc.addObject("PartDesign::Pocket", "Pocket_Cavidade_Interna")
pocket_cavidade.Profile = sk_cavidade
pocket_cavidade.Length = 53.0 # Deixa 3.0mm de piso solido
pocket_cavidade.Reversed = True
body_tower.addObject(pocket_cavidade)
doc.recompute()

# Sketch 3: Canaletas-Guia da Regua (Duas ranhuras de 1.8mm x 1.5mm para deslizar a PCB de 24mm)
sk_guias = doc.addObject("Sketcher::SketchObject", "Sketch_Canaletas_Guia_Regua")
body_tower.addObject(sk_guias)
sk_guias.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_guias.MapMode = "Deactivated"

# Canaleta esquerda (X de -13.8 a -12.3, Y de -0.9 a +0.9 mm)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.8, -0.9, 0), FreeCAD.Vector(-12.3, -0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, -0.9, 0), FreeCAD.Vector(-12.3, 0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, 0.9, 0), FreeCAD.Vector(-13.8, 0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.8, 0.9, 0), FreeCAD.Vector(-13.8, -0.9, 0)), False)
for i in range(4): sk_guias.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))

# Canaleta direita (X de 12.3 a 13.8, Y de -0.9 a +0.9 mm)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, -0.9, 0), FreeCAD.Vector(13.8, -0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(13.8, -0.9, 0), FreeCAD.Vector(13.8, 0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(13.8, 0.9, 0), FreeCAD.Vector(12.3, 0.9, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, 0.9, 0), FreeCAD.Vector(12.3, -0.9, 0)), False)
for i in range(4, 8): sk_guias.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc.recompute()

pocket_guias = doc.addObject("PartDesign::Pocket", "Pocket_Canaletas_Guia")
pocket_guias.Profile = sk_guias
pocket_guias.Length = 53.0
pocket_guias.Reversed = True
body_tower.addObject(pocket_guias)
doc.recompute()

# Sketch 4: Rebaixo Termico Inferior para o Sensor DS18B20 (8.0 x 6.0 x 2.2 mm)
sk_termico = doc.addObject("Sketcher::SketchObject", "Sketch_Rebaixo_Termico_DS18B20")
body_tower.addObject(sk_termico)
sk_termico.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_termico.MapMode = "Deactivated"
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, -3.0, 0), FreeCAD.Vector(4.0, -3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, -3.0, 0), FreeCAD.Vector(4.0, 3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, 3.0, 0), FreeCAD.Vector(-4.0, 3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, 3.0, 0), FreeCAD.Vector(-4.0, -3.0, 0)), False)
for i in range(4): sk_termico.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc.recompute()

pocket_termico = doc.addObject("PartDesign::Pocket", "Pocket_Rebaixo_Termico")
pocket_termico.Profile = sk_termico
pocket_termico.Length = 2.2
body_tower.addObject(pocket_termico)
doc.recompute()

# Sketch 5: Porta Acustica do Microfone INMP441 (dia 3.5mm na lateral direita)
sk_acustica = doc.addObject("Sketcher::SketchObject", "Sketch_Porta_Acustica_Mic")
body_tower.addObject(sk_acustica)
sk_acustica.Placement = FreeCAD.Placement(FreeCAD.Vector(16.0, 0, 16.0), FreeCAD.Rotation(FreeCAD.Vector(0, 1, 0), 90))
sk_acustica.MapMode = "Deactivated"
sk_acustica.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 1.75), False)
sk_acustica.addConstraint(Sketcher.Constraint("Radius", 0, 1.75))
doc.recompute()

pocket_acustica = doc.addObject("PartDesign::Pocket", "Pocket_Porta_Acustica")
pocket_acustica.Profile = sk_acustica
pocket_acustica.Length = 6.0
pocket_acustica.Reversed = True
body_tower.addObject(pocket_acustica)
doc.recompute()

# Sketch 6: Abas de Fixacao Mecanica para Mancal UCP 204
sk_abas = doc.addObject("Sketcher::SketchObject", "Sketch_Abas_Fixacao_Mancal")
body_tower.addObject(sk_abas)
sk_abas.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_abas.MapMode = "Deactivated"
# Aba frontal
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, 16.0, 0), FreeCAD.Vector(9.0, 16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, 16.0, 0), FreeCAD.Vector(9.0, 24.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, 24.0, 0), FreeCAD.Vector(-9.0, 24.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, 24.0, 0), FreeCAD.Vector(-9.0, 16.0, 0)), False)
for i in range(4): sk_abas.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
# Aba traseira
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, -24.0, 0), FreeCAD.Vector(9.0, -24.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, -24.0, 0), FreeCAD.Vector(9.0, -16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, -16.0, 0), FreeCAD.Vector(-9.0, -16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, -16.0, 0), FreeCAD.Vector(-9.0, -24.0, 0)), False)
for i in range(4, 8): sk_abas.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc.recompute()

pad_abas = doc.addObject("PartDesign::Pad", "Pad_Abas_Fixacao")
pad_abas.Profile = sk_abas
pad_abas.Length = 3.5
body_tower.addObject(pad_abas)
doc.recompute()

# Sketch 7: Furos das Abas (M3) e Alojamento dos Imas de Neodimio
sk_furos_fix = doc.addObject("Sketcher::SketchObject", "Sketch_Furos_Abas_e_Imas")
body_tower.addObject(sk_furos_fix)
sk_furos_fix.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_furos_fix.MapMode = "Deactivated"
# Furos M3 nas abas
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(0, 20.0, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(0, -20.0, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
# Alojamento de 2 imas de neodimio de 10mm no fundo
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(-8.0, 0, 0), FreeCAD.Vector(0, 0, 1), 5.1), False)
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(8.0, 0, 0), FreeCAD.Vector(0, 0, 1), 5.1), False)
doc.recompute()

pocket_furos_fix = doc.addObject("PartDesign::Pocket", "Pocket_Furos_Abas_e_Imas")
pocket_furos_fix.Profile = sk_furos_fix
pocket_furos_fix.Length = 2.2
body_tower.addObject(pocket_furos_fix)
doc.recompute()

print("Gabinete_Torre_Base construido com 7 Sketches PartDesign!")

# ==============================================================================
# 2. TAMPA SUPERIOR COM CONECTOR GX12-8 (PartDesign::Body)
# ==============================================================================
body_cap = doc.addObject("PartDesign::Body", "Tampa_Superior_GX16")

sk_cap_perfil = doc.addObject("Sketcher::SketchObject", "Sketch_Tampa_Perfil_Externo")
body_cap.addObject(sk_cap_perfil)
sk_cap_perfil.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_cap_perfil.MapMode = "Deactivated"
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4): sk_cap_perfil.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc.recompute()

pad_cap = doc.addObject("PartDesign::Pad", "Pad_Tampa_Solida")
pad_cap.Profile = sk_cap_perfil
pad_cap.Length = 10.0
body_cap.addObject(pad_cap)
doc.recompute()

# Furo Central para Conector de Aviacao Industrial GX16 (dia 16.2 mm)
sk_gx16 = doc.addObject("Sketcher::SketchObject", "Sketch_Furo_Conector_GX16")
body_cap.addObject(sk_gx16)
sk_gx16.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 66.0), FreeCAD.Rotation())
sk_gx16.MapMode = "Deactivated"
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 8.1), False)
sk_gx16.addConstraint(Sketcher.Constraint("Radius", 0, 8.1))
# 2 Furos de fixacao da tampa (M2.5 em X = +-12.3 mm)
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(-12.3, 0, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(12.3, 0, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
doc.recompute()

pocket_gx16 = doc.addObject("PartDesign::Pocket", "Pocket_Furo_Conector_GX16")
pocket_gx16.Profile = sk_gx16
pocket_gx16.Length = 10.0
pocket_gx16.Reversed = True
body_cap.addObject(pocket_gx16)
doc.recompute()

print("Tampa_Superior_GX16 construida com 2 Sketches PartDesign!")

# ==============================================================================
# 3. PLACA PCB REGUA (amemiya_probe_tower.step, 24 x 48 mm)
# ==============================================================================
pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.step'
Import.insert(pcb_step, doc.Name)
doc.recompute()

# Achar e nomear claramente a placa
pcb_obj = None
for obj in doc.Objects:
    if 'amemiya_probe_tower_1' in obj.Name:
        pcb_obj = obj
        obj.Label = "Placa_PCB_Torre_24x48mm"
        # Posicionar verticalmente: X=[-12, 12], Y=[-0.8, 0.8], Z=[3.0, 51.0] dentro das canaletas
        obj.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0.8, 3.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
        break

# Resetar o Part__Feature filho para transformacao limpa
pf = doc.getObject("Part__Feature")
if pf:
    pf.Placement = FreeCAD.Placement()
    pf.Label = "PCB_Geometria_KiCad"

# ==============================================================================
# 4. COMPONENTES INDIVIDUALIZADOS E DETALHADOS NA PCB
# ==============================================================================

# A. Sensor DS18B20 (TO-92) na ponta inferior (Z=3mm)
# Corpo TO-92 cilindro cortado
ds_cyl = Part.makeCylinder(2.2, 4.8, FreeCAD.Vector(0, 0, 1.2), FreeCAD.Vector(0, 0, 1))
ds_corte = Part.makeBox(4.5, 2.0, 5.0, FreeCAD.Vector(-2.25, -2.0, 1.0))
ds_corpo = ds_cyl.cut(ds_corte)
# 3 Terminais de cobre (GND, DQ, VDD)
p1 = Part.makeCylinder(0.3, 3.0, FreeCAD.Vector(-1.27, -0.5, 4.8), FreeCAD.Vector(0, 0, 1))
p2 = Part.makeCylinder(0.3, 3.0, FreeCAD.Vector(0.0, -0.5, 4.8), FreeCAD.Vector(0, 0, 1))
p3 = Part.makeCylinder(0.3, 3.0, FreeCAD.Vector(1.27, -0.5, 4.8), FreeCAD.Vector(0, 0, 1))
ds_total = ds_corpo.fuse(p1).fuse(p2).fuse(p3)

obj_ds = doc.addObject("Part::Feature", "Sensor_Temperatura_DS18B20_TO92")
obj_ds.Shape = ds_total
obj_ds.Label = "Sensor_Temperatura_DS18B20_TO92"

# B. Modulo Acelerometro ADXL345 (GY-291, Coluna Esquerda: X=-10.5 a -1.5, Z=14 a 34)
# PCB Azul do modulo
adxl_placa = Part.makeBox(18.0, 1.2, 12.0, FreeCAD.Vector(-10.5, 1.2, 16.0))
# Chip sensor ADXL345 central
adxl_chip = Part.makeBox(4.5, 1.0, 4.5, FreeCAD.Vector(-5.5, 2.4, 19.5))
# Barra de 8 pinos
adxl_pinos = Part.makeBox(18.0, 2.5, 2.5, FreeCAD.Vector(-10.5, 0.0, 14.0))
adxl_total = adxl_placa.fuse(adxl_chip).fuse(adxl_pinos)

obj_adxl = doc.addObject("Part::Feature", "Modulo_Acelerometro_ADXL345")
obj_adxl.Shape = adxl_total
obj_adxl.Label = "Modulo_Acelerometro_ADXL345_Triaxial"

# C. Modulo Microfone Digital I2S INMP441 (Coluna Direita: X=1.5 a 10.5, Z=18 a 30)
mic_placa = Part.makeBox(12.0, 1.2, 10.0, FreeCAD.Vector(0.5, 1.2, 18.0))
mic_can = Part.makeCylinder(1.5, 1.2, FreeCAD.Vector(6.0, 1.2, 23.0), FreeCAD.Vector(0, 1, 0))
mic_pinos = Part.makeBox(12.0, 2.5, 2.5, FreeCAD.Vector(0.5, 0.0, 16.0))
mic_total = mic_placa.fuse(mic_can).fuse(mic_pinos)

obj_mic = doc.addObject("Part::Feature", "Modulo_Microfone_INMP441")
obj_mic.Shape = mic_total
obj_mic.Label = "Modulo_Microfone_Digital_INMP441_I2S"

# D. Conector de Topo J_CABLE (Barra de Pinos 1x8 ligando ao GX12-8)
hdr_base = Part.makeBox(20.32, 2.5, 8.5, FreeCAD.Vector(-10.16, -1.25, 45.0))
obj_hdr = doc.addObject("Part::Feature", "Conector_J_CABLE_Topo_1x8")
obj_hdr.Shape = hdr_base
obj_hdr.Label = "Conector_J_CABLE_Topo_1x8_Pinos"

# E. Conector de Aviacao Industrial GX16-8 (Montado na Tampa em Z=66mm)
gx_corpo = Part.makeCylinder(7.9, 14.0, FreeCAD.Vector(0, 0, 64.0), FreeCAD.Vector(0, 0, 1))
gx_porca = Part.makeCylinder(9.75, 3.5, FreeCAD.Vector(0, 0, 68.0), FreeCAD.Vector(0, 0, 1))
gx_flange = Part.makeCylinder(9.75, 2.5, FreeCAD.Vector(0, 0, 65.0), FreeCAD.Vector(0, 0, 1))
gx_total = gx_corpo.fuse(gx_porca).fuse(gx_flange)

obj_gx = doc.addObject("Part::Feature", "Conector_Aviacao_GX16_8_Vias")
obj_gx.Shape = gx_total
obj_gx.Label = "Conector_Aviacao_Metalico_GX16_8"

# F. Imas de Neodimio na Base (2x 10x2mm)
ima1 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(-8.0, 0, 0.1), FreeCAD.Vector(0, 0, 1))
ima2 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(8.0, 0, 0.1), FreeCAD.Vector(0, 0, 1))
obj_imas = doc.addObject("Part::Feature", "Imas_Neodimio_Base_10mm")
obj_imas.Shape = ima1.fuse(ima2)
obj_imas.Label = "Imas_Neodimio_Base_Fixacao_10mm"

doc.recompute()

# Salvar arquivo nativo
doc.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.FCStd')
Part.export([body_tower, body_cap, obj_gx], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.step')
Mesh.export([body_tower], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_body.stl')
Mesh.export([body_cap], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_cap.stl')

print(f"Projeto completo da Sonda Cartucho Torre finalizado em {time.time() - t0:.2f}s!")
