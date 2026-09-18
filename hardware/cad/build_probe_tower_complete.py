# -*- coding: utf-8 -*-
"""
Construcao Parametrica da Sonda Torre (Tractian Style) no FreeCAD
- Corpo principal (Gabinete_Torre_Base) via PartDesign::Body com ranhuras/canaletas-guia em U
- Tampa superior removivel (Tampa_Superior_GX16) via PartDesign::Body com nervuras de travamento
- Placa KiCad real importada via STEP com 58 furos fisicos
- Modulo Microfone Circular INMP441 (dia 14mm, 2x3 pinos amarelos) encaixado nos furos da PCB
- Modulo Acelerometro ADXL345 com 8 pinos atravessando os furos da PCB
- Sensor de Temperatura DS18B20 com terminais atravessando os furos da PCB e bulbo no berco termico
- Conector de Topo J_CABLE (8 vias) com pinos atravessando os furos da PCB
- Chicote flexivel e Conector de Aviacao Industrial GX16-8
- Furos de fixacao para mancal UCP 204 e alojamentos para imas de neodimio
"""

import FreeCAD
import Part
import Sketcher
import Import
import os

doc_name = "amemiya_probe_tower_enclosure"
doc = FreeCAD.getDocument(doc_name)
if not doc:
    doc = FreeCAD.newDocument(doc_name)
else:
    for obj in list(doc.Objects):
        try:
            doc.removeObject(obj.Name)
        except Exception:
            pass

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

print("1/6: Construindo Gabinete_Torre_Base (PartDesign)...")
body_tower = doc.addObject("PartDesign::Body", "Gabinete_Torre_Base")
body_tower.Label = "Gabinete_Torre_Base"

# 1.1 Perfil Externo da Torre (30.0 x 32.0 mm, Altura 56.0 mm)
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
for i in range(4):
    sk_perfil.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_perfil.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 30.0))
sk_perfil.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 32.0))
doc.recompute()

pad_torre = doc.addObject("PartDesign::Pad", "Pad_Torre_Solida")
pad_torre.Profile = sk_perfil
pad_torre.Length = 56.0
body_tower.addObject(pad_torre)
doc.recompute()

# 1.2 Cavidade Interna da Torre (21.6 x 26.0 mm, deixando paredes laterais de 4.2mm e piso de 3.0mm)
# O vão central de 21.6mm permite que a PCB (24.0mm) penetre 1.2mm em cada canaleta lateral!
sk_cavidade = doc.addObject("Sketcher::SketchObject", "Sketch_Cavidade_Interna")
body_tower.addObject(sk_cavidade)
sk_cavidade.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_cavidade.MapMode = "Deactivated"

cx0, cy0 = -10.8, -13.0
cx1, cy1 = 10.8, 13.0
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy0, 0), FreeCAD.Vector(cx1, cy0, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy0, 0), FreeCAD.Vector(cx1, cy1, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy1, 0), FreeCAD.Vector(cx0, cy1, 0)), False)
sk_cavidade.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy1, 0), FreeCAD.Vector(cx0, cy0, 0)), False)
for i in range(4):
    sk_cavidade.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_cavidade.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 21.6))
sk_cavidade.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 26.0))
doc.recompute()

pocket_cavidade = doc.addObject("PartDesign::Pocket", "Pocket_Cavidade_Interna")
pocket_cavidade.Profile = sk_cavidade
pocket_cavidade.Length = 53.0
pocket_cavidade.Reversed = False  # Corta para dentro do solido (de Z=56 ate Z=3)
body_tower.addObject(pocket_cavidade)
doc.recompute()

# 1.3 Canaletas-Guia da Regua PCB (Ranhuras em U de 1.8mm x 1.5mm de profundidade em cada lateral)
# Vão total das canaletas: X de -12.3 ate +12.3 mm (largura 24.6 mm para PCB de 24.0 mm)
sk_guias = doc.addObject("Sketcher::SketchObject", "Sketch_Canaletas_Guia_Regua")
body_tower.addObject(sk_guias)
sk_guias.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_guias.MapMode = "Deactivated"
# Canaleta esquerda (X: -12.3 a -10.5, Y: -1.0 a +1.0 mm)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, -1.0, 0), FreeCAD.Vector(-10.5, -1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-10.5, -1.0, 0), FreeCAD.Vector(-10.5, 1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-10.5, 1.0, 0), FreeCAD.Vector(-12.3, 1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, 1.0, 0), FreeCAD.Vector(-12.3, -1.0, 0)), False)
for i in range(4):
    sk_guias.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))

# Canaleta direita (X: 10.5 a 12.3, Y: -1.0 a +1.0 mm)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(10.5, -1.0, 0), FreeCAD.Vector(12.3, -1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, -1.0, 0), FreeCAD.Vector(12.3, 1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, 1.0, 0), FreeCAD.Vector(10.5, 1.0, 0)), False)
sk_guias.addGeometry(Part.LineSegment(FreeCAD.Vector(10.5, 1.0, 0), FreeCAD.Vector(10.5, -1.0, 0)), False)
for i in range(4, 8):
    sk_guias.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc.recompute()

pocket_guias = doc.addObject("PartDesign::Pocket", "Pocket_Canaletas_Guia")
pocket_guias.Profile = sk_guias
pocket_guias.Length = 53.0
pocket_guias.Reversed = False  # Corta para dentro do solido (de Z=56 ate Z=3)
body_tower.addObject(pocket_guias)
doc.recompute()

# 1.4 Berco Termico Inferior para DS18B20 (8.0 x 6.0 x 2.2 mm no piso)
sk_termico = doc.addObject("Sketcher::SketchObject", "Sketch_Rebaixo_Termico_DS18B20")
body_tower.addObject(sk_termico)
sk_termico.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_termico.MapMode = "Deactivated"
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, -3.0, 0), FreeCAD.Vector(4.0, -3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, -3.0, 0), FreeCAD.Vector(4.0, 3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, 3.0, 0), FreeCAD.Vector(-4.0, 3.0, 0)), False)
sk_termico.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, 3.0, 0), FreeCAD.Vector(-4.0, -3.0, 0)), False)
for i in range(4):
    sk_termico.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc.recompute()

pocket_termico = doc.addObject("PartDesign::Pocket", "Pocket_Rebaixo_Termico")
pocket_termico.Profile = sk_termico
pocket_termico.Length = 2.2
pocket_termico.Reversed = True  # Corta para cima a partir de Z=0
body_tower.addObject(pocket_termico)
doc.recompute()

# 1.5 Abas de Fixacao Mecanica Mancal UCP 204
sk_abas = doc.addObject("Sketcher::SketchObject", "Sketch_Abas_Fixacao_Mancal")
body_tower.addObject(sk_abas)
sk_abas.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_abas.MapMode = "Deactivated"
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, 16.0, 0), FreeCAD.Vector(9.0, 16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, 16.0, 0), FreeCAD.Vector(9.0, 25.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, 25.0, 0), FreeCAD.Vector(-9.0, 25.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, 25.0, 0), FreeCAD.Vector(-9.0, 16.0, 0)), False)
for i in range(4):
    sk_abas.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))

sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, -25.0, 0), FreeCAD.Vector(9.0, -25.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, -25.0, 0), FreeCAD.Vector(9.0, -16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(9.0, -16.0, 0), FreeCAD.Vector(-9.0, -16.0, 0)), False)
sk_abas.addGeometry(Part.LineSegment(FreeCAD.Vector(-9.0, -16.0, 0), FreeCAD.Vector(-9.0, -25.0, 0)), False)
for i in range(4, 8):
    sk_abas.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc.recompute()

pad_abas = doc.addObject("PartDesign::Pad", "Pad_Abas_Fixacao")
pad_abas.Profile = sk_abas
pad_abas.Length = 4.0
body_tower.addObject(pad_abas)
doc.recompute()

# 1.6 Furos das Abas e Alojamentos dos Imas de Neodimio
sk_furos_fix = doc.addObject("Sketcher::SketchObject", "Sketch_Furos_Abas_e_Imas")
body_tower.addObject(sk_furos_fix)
sk_furos_fix.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_furos_fix.MapMode = "Deactivated"
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(0, 20.5, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(0, -20.5, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(-7.5, 0, 0), FreeCAD.Vector(0, 0, 1), 5.1), False)
sk_furos_fix.addGeometry(Part.Circle(FreeCAD.Vector(7.5, 0, 0), FreeCAD.Vector(0, 0, 1), 5.1), False)
doc.recompute()

pocket_furos_fix = doc.addObject("PartDesign::Pocket", "Pocket_Furos_Abas_e_Imas")
pocket_furos_fix.Profile = sk_furos_fix
pocket_furos_fix.Length = 2.2
pocket_furos_fix.Reversed = True  # Corta para cima a partir de Z=0
body_tower.addObject(pocket_furos_fix)
doc.recompute()

# 1.7 Porta Acustica Frontal do Microfone INMP441 (Face Frontal Y=-16.0 mm, cota Z=36.0 mm, dia 3.5 mm)
sk_acustica = doc.addObject("Sketcher::SketchObject", "Sketch_Porta_Acustica_Mic")
body_tower.addObject(sk_acustica)
sk_acustica.Placement = FreeCAD.Placement(FreeCAD.Vector(0, -16.0, 36.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
sk_acustica.MapMode = "Deactivated"
sk_acustica.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 1.75), False)
doc.recompute()

pocket_acustica = doc.addObject("PartDesign::Pocket", "Pocket_Porta_Acustica")
pocket_acustica.Profile = sk_acustica
pocket_acustica.Length = 5.0
pocket_acustica.Reversed = False  # Corta para dentro do solido em direcao ao microfone
body_tower.addObject(pocket_acustica)
doc.recompute()

apply_style(body_tower, (0.92, 0.48, 0.12), transp=65, vis=True)
print(f"Gabinete base construido! Volume util: {body_tower.Shape.Volume:.1f} mm3, faces: {len(body_tower.Shape.Faces)}")

# ==============================================================================
# 2. TAMPA SUPERIOR COM NERVURAS DE TRAVAMENTO (PartDesign::Body)
# ==============================================================================
print("2/6: Construindo Tampa_Superior_GX16...")
body_cap = doc.addObject("PartDesign::Body", "Tampa_Superior_GX16")
body_cap.Label = "Tampa_Superior_GX16"

sk_cap_perfil = doc.addObject("Sketcher::SketchObject", "Sketch_Tampa_Perfil_Externo")
body_cap.addObject(sk_cap_perfil)
sk_cap_perfil.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_cap_perfil.MapMode = "Deactivated"
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4):
    sk_cap_perfil.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc.recompute()

pad_cap = doc.addObject("PartDesign::Pad", "Pad_Tampa_Solida")
pad_cap.Profile = sk_cap_perfil
pad_cap.Length = 8.0
body_cap.addObject(pad_cap)
doc.recompute()

sk_gx16 = doc.addObject("Sketcher::SketchObject", "Sketch_Furo_Conector_GX16")
body_cap.addObject(sk_gx16)
sk_gx16.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 64.0), FreeCAD.Rotation())
sk_gx16.MapMode = "Deactivated"
# Furo central para GX16 (dia 16.2 mm, R=8.1 mm)
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 8.1), False)
# 2 Furos de fixacao da tampa (M2.5 em X = +-12.3 mm, livrando perfeitamente a porca GX16 de 19.5mm)
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(-12.3, 0, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
sk_gx16.addGeometry(Part.Circle(FreeCAD.Vector(12.3, 0, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
doc.recompute()

pocket_gx16 = doc.addObject("PartDesign::Pocket", "Pocket_Furo_Conector_GX16")
pocket_gx16.Profile = sk_gx16
pocket_gx16.Length = 8.0
pocket_gx16.Reversed = True
body_cap.addObject(pocket_gx16)
doc.recompute()

# Nervuras de travamento da PCB (descem 5mm pressionando o topo da placa em Z=51)
sk_ribs = doc.addObject("Sketcher::SketchObject", "Sketch_Nervuras_Travamento_PCB")
body_cap.addObject(sk_ribs)
sk_ribs.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_ribs.MapMode = "Deactivated"
# Nervura esquerda
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, -1.0, 0), FreeCAD.Vector(-10.5, -1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(-10.5, -1.0, 0), FreeCAD.Vector(-10.5, 1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(-10.5, 1.0, 0), FreeCAD.Vector(-12.3, 1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.3, 1.0, 0), FreeCAD.Vector(-12.3, -1.0, 0)), False)
for i in range(4):
    sk_ribs.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))

# Nervura direita
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(10.5, -1.0, 0), FreeCAD.Vector(12.3, -1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, -1.0, 0), FreeCAD.Vector(12.3, 1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(12.3, 1.0, 0), FreeCAD.Vector(10.5, 1.0, 0)), False)
sk_ribs.addGeometry(Part.LineSegment(FreeCAD.Vector(10.5, 1.0, 0), FreeCAD.Vector(10.5, -1.0, 0)), False)
for i in range(4, 8):
    sk_ribs.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc.recompute()

pad_ribs = doc.addObject("PartDesign::Pad", "Pad_Nervuras_Travamento")
pad_ribs.Profile = sk_ribs
pad_ribs.Length = 5.0
pad_ribs.Reversed = True  # Desce em direcao ao topo da PCB
body_cap.addObject(pad_ribs)
doc.recompute()

apply_style(body_cap, (0.32, 0.36, 0.42), transp=50, vis=True)

# ==============================================================================
# 3. PLACA PCB KICAD (24x48mm com 58 furos reais subtraidos no solido)
# ==============================================================================
print("3/6: Importando Placa PCB KiCad atualizada...")
pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.step'

Import.insert(pcb_step, doc.Name)
doc.recompute()

rot = FreeCAD.Rotation(FreeCAD.Vector(0, 0, 1), 180) * FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), -90)
pos = FreeCAD.Vector(12.0, 0.8, 3.0)
pcb_placement = FreeCAD.Placement(pos, rot)

# Mover TODOS os objetos pertencentes a PCB importada
for obj in doc.Objects:
    if 'amemiya_probe_tower' in obj.Name:
        obj.Placement = pcb_placement
        if obj.TypeId == 'App::Part':
            obj.Label = "Placa_PCB_Torre_KiCad_24x48"
    if 'Part__Feature' in obj.Name or 'PCB' in obj.Label:
        apply_style(obj, (0.08, 0.55, 0.22), transp=0, vis=True)

doc.recompute()

# ==============================================================================
# 4. COMPONENTES ENCAIXADOS NOS FUROS EXATOS DA PCB
# ==============================================================================
print("4/6: Montando componentes com pinos atravessando os furos da PCB...")

# 4.1 Sensor DS18B20 (TO-92)
# Bulbo termico assentado diretamente no Berco Termico do piso (Z de 0.5 a 3.0 mm)
# A face plana do TO-92 fica em contato direto com o fundo da sonda
ds_cyl = Part.makeCylinder(2.2, 2.5, FreeCAD.Vector(0.0, 0.0, 0.5), FreeCAD.Vector(0, 0, 1))
ds_flat = Part.makeBox(5.0, 2.5, 3.0, FreeCAD.Vector(-2.5, -2.5, 0.3))
ds_bulb = ds_cyl.cut(ds_flat)

# 3 Terminais metalicos longos que sobem do bulbo (Z=3.0) ate os furos da PCB (Z=7.50 mm)
# e dobram 90 graus atravessando os 3 furos da placa KiCad (X = -2.54, 0.0, +2.54 mm)
leads = None
for px in [-2.54, 0.0, 2.54]:
    l_vert = Part.makeCylinder(0.28, 4.5, FreeCAD.Vector(px, -0.8, 3.0), FreeCAD.Vector(0, 0, 1))
    l_horiz = Part.makeCylinder(0.28, 2.5, FreeCAD.Vector(px, -1.0, 7.5), FreeCAD.Vector(0, 1, 0))
    l_total = l_vert.fuse(l_horiz)
    if leads is None:
        leads = l_total
    else:
        leads = leads.fuse(l_total)

obj_ds_bulb = doc.addObject("Part::Feature", "Bulbo_Termico_DS18B20")
obj_ds_bulb.Shape = ds_bulb
obj_ds_bulb.Label = "Bulbo_Termico_DS18B20_No_Piso"
apply_style(obj_ds_bulb, (0.12, 0.12, 0.14), transp=0, vis=True)

obj_ds_leads = doc.addObject("Part::Feature", "Terminais_DS18B20")
obj_ds_leads.Shape = leads
obj_ds_leads.Label = "Terminais_DS18B20_Soldados_PCB"
apply_style(obj_ds_leads, (0.85, 0.85, 0.70), transp=0, vis=True)

# 4.2 Capacitor C1 (100nF Cerâmico de Desacoplamento para DS18B20)
# Furos KiCad: (9.46, 6.5) e (12.00, 6.5) -> FreeCAD X=+2.54 e X=0.00 em Z=9.50 mm (pitch 2.54mm / 0.1")
# Corpo cerâmico arredondado (dia 3.0mm, espessura 1.4mm) posicionado à frente em Y=-2.8mm, Z=8.8mm
c1_body = Part.makeCylinder(1.5, 1.4, FreeCAD.Vector(1.27, -3.5, 8.8), FreeCAD.Vector(0, 1, 0))

# Terminais que emergem do corpo e atravessam os furos da PCB até Y=+1.8mm
p1_start = FreeCAD.Vector(1.8, -2.8, 8.8)
p1_bend = FreeCAD.Vector(2.54, -2.8, 9.5)
l1_diag = Part.makeCylinder(0.25, p1_start.distanceToPoint(p1_bend), p1_start, p1_bend.sub(p1_start))
l1_thru = Part.makeCylinder(0.25, 4.6, FreeCAD.Vector(2.54, -2.8, 9.5), FreeCAD.Vector(0, 1, 0))

p2_start = FreeCAD.Vector(0.74, -2.8, 8.8)
p2_bend = FreeCAD.Vector(0.0, -2.8, 9.5)
l2_diag = Part.makeCylinder(0.25, p2_start.distanceToPoint(p2_bend), p2_start, p2_bend.sub(p2_start))
l2_thru = Part.makeCylinder(0.25, 4.6, FreeCAD.Vector(0.0, -2.8, 9.5), FreeCAD.Vector(0, 1, 0))

c1_leads = l1_diag.fuse(l1_thru).fuse(l2_diag).fuse(l2_thru)
c1_shape = c1_body.fuse(c1_leads)

obj_c1 = doc.addObject("Part::Feature", "Capacitor_C1_100nF_DS18B20")
obj_c1.Shape = c1_shape
obj_c1.Label = "Capacitor_C1_100nF_DS18B20"
apply_style(obj_c1, (0.95, 0.65, 0.15), transp=0, vis=True)

# 4.3 Resistor R1 (4.7kΩ Axial DIN 0204 - Pull-Up da linha 1-Wire DQ)
# Furos KiCad: (9.46, 8.5) e (14.54, 8.5) -> FreeCAD X=+2.54 e X=-2.54 em Z=11.50 mm (pitch 5.08mm / 0.2")
# Corpo cilíndrico axial bege dia 1.6mm, comprimento 3.4mm, rente à PCB em Y=-1.5mm, Z=11.50 mm
r1_cyl = Part.makeCylinder(0.8, 3.4, FreeCAD.Vector(-1.7, -1.5, 11.5), FreeCAD.Vector(1, 0, 0))
r1_s1 = Part.makeSphere(0.8, FreeCAD.Vector(-1.7, -1.5, 11.5))
r1_s2 = Part.makeSphere(0.8, FreeCAD.Vector(1.7, -1.5, 11.5))
r1_core = r1_cyl.fuse(r1_s1).fuse(r1_s2)

# Terminais axiais que dobram 90 graus e penetram os furos da PCB até Y=+1.8mm
rl_x1 = Part.makeCylinder(0.26, 0.84, FreeCAD.Vector(1.7, -1.5, 11.5), FreeCAD.Vector(1, 0, 0))
rl_y1 = Part.makeCylinder(0.26, 3.3, FreeCAD.Vector(2.54, -1.5, 11.5), FreeCAD.Vector(0, 1, 0))
rl_x2 = Part.makeCylinder(0.26, 0.84, FreeCAD.Vector(-2.54, -1.5, 11.5), FreeCAD.Vector(1, 0, 0))
rl_y2 = Part.makeCylinder(0.26, 3.3, FreeCAD.Vector(-2.54, -1.5, 11.5), FreeCAD.Vector(0, 1, 0))

r1_leads = rl_x1.fuse(rl_y1).fuse(rl_x2).fuse(rl_y2)
r1_shape = r1_core.fuse(r1_leads)

obj_r1 = doc.addObject("Part::Feature", "Resistor_R1_4k7_Pullup_DS18B20")
obj_r1.Shape = r1_shape
obj_r1.Label = "Resistor_R1_4k7_Pullup_DS18B20"
apply_style(obj_r1, (0.84, 0.74, 0.55), transp=0, vis=True)

# 4.4 Modulo Acelerometro ADXL345 (GY-291)
adxl_pcb = Part.makeBox(13.0, 1.2, 19.0, FreeCAD.Vector(-5.5, -3.8, 11.5))
adxl_ic = Part.makeBox(3.8, 0.8, 3.8, FreeCAD.Vector(-1.0, -4.6, 18.5))
adxl_hdr = Part.makeBox(2.5, 2.2, 19.0, FreeCAD.Vector(6.25, -2.6, 11.5))
adxl_pins_shape = None
for p in range(8):
    pz = 12.11 + p * 2.54
    p_box = Part.makeBox(0.64, 5.0, 0.64, FreeCAD.Vector(7.5 - 0.32, -3.0, pz - 0.32))
    if adxl_pins_shape is None:
        adxl_pins_shape = p_box
    else:
        adxl_pins_shape = adxl_pins_shape.fuse(p_box)

adxl_total = adxl_pcb.fuse(adxl_ic).fuse(adxl_hdr).fuse(adxl_pins_shape)
obj_adxl = doc.addObject("Part::Feature", "Modulo_Acelerometro_ADXL345")
obj_adxl.Shape = adxl_total
obj_adxl.Label = "Modulo_Acelerometro_ADXL345_GY291"
apply_style(obj_adxl, (0.08, 0.35, 0.88), transp=0, vis=True)

# 4.5 MODULO MICROFONE CIRCULAR INMP441 (dia 14mm)
X_mic, Y_mic, Z_mic = 0.0, -2.5, 36.0
circ_pcb = Part.makeCylinder(7.0, 1.2, FreeCAD.Vector(X_mic, Y_mic, Z_mic), FreeCAD.Vector(0, -1, 0))
cut1 = Part.makeCylinder(0.8, 1.4, FreeCAD.Vector(X_mic - 7.0, Y_mic + 0.1, Z_mic), FreeCAD.Vector(0, -1, 0))
cut2 = Part.makeCylinder(0.8, 1.4, FreeCAD.Vector(X_mic + 7.0, Y_mic + 0.1, Z_mic), FreeCAD.Vector(0, -1, 0))
mic_pcb_notched = circ_pcb.cut(cut1).cut(cut2)

mems_can = Part.makeBox(3.8, 1.1, 3.0, FreeCAD.Vector(X_mic - 1.9, Y_mic - 1.2 - 1.1, Z_mic - 1.5))
smd1 = Part.makeBox(1.6, 0.7, 0.9, FreeCAD.Vector(X_mic - 1.8, Y_mic - 1.2 - 0.7, Z_mic + 2.2))
smd2 = Part.makeBox(1.6, 0.7, 0.9, FreeCAD.Vector(X_mic + 0.2, Y_mic - 1.2 - 0.7, Z_mic + 2.2))

y_hdr1 = Part.makeBox(2.2, 2.5, 7.62, FreeCAD.Vector(5.08 - 1.1, Y_mic - 1.2 - 2.5, Z_mic - 3.81))
y_hdr2 = Part.makeBox(2.2, 2.5, 7.62, FreeCAD.Vector(-5.08 - 1.1, Y_mic - 1.2 - 2.5, Z_mic - 3.81))
yellow_headers = y_hdr1.fuse(y_hdr2)

mic_pins_shape = None
for pz in [33.46, 36.0, 38.54]:
    p1 = Part.makeBox(0.64, 11.0, 0.64, FreeCAD.Vector(5.08 - 0.32, Y_mic - 1.2 - 2.5 + (2.5 - 11.0) + 7.5, pz - 0.32))
    p2 = Part.makeBox(0.64, 11.0, 0.64, FreeCAD.Vector(-5.08 - 0.32, Y_mic - 1.2 - 2.5 + (2.5 - 11.0) + 7.5, pz - 0.32))
    if mic_pins_shape is None:
        mic_pins_shape = p1.fuse(p2)
    else:
        mic_pins_shape = mic_pins_shape.fuse(p1).fuse(p2)

mic_base_shape = mic_pcb_notched.fuse(mems_can).fuse(smd1).fuse(smd2)

obj_mic_pcb = doc.addObject("Part::Feature", "Modulo_Microfone_INMP441_Circular")
obj_mic_pcb.Shape = mic_base_shape
obj_mic_pcb.Label = "Modulo_Microfone_INMP441_Circular_14mm"
apply_style(obj_mic_pcb, (0.12, 0.12, 0.12), transp=0, vis=True)

obj_mic_headers = doc.addObject("Part::Feature", "Conectores_Amarelos_INMP441")
obj_mic_headers.Shape = yellow_headers
obj_mic_headers.Label = "Conectores_Amarelos_INMP441_2x3"
apply_style(obj_mic_headers, (0.95, 0.85, 0.12), transp=0, vis=True)

obj_mic_pins = doc.addObject("Part::Feature", "Pinos_Metalicos_INMP441")
obj_mic_pins.Shape = mic_pins_shape
obj_mic_pins.Label = "Pinos_Metalicos_INMP441_Encaixados_PCB"
apply_style(obj_mic_pins, (0.88, 0.85, 0.5), transp=0, vis=True)

# 4.6 Conector JST-XH 8 Vias (Macho na PCB com Trava Polarizada)
base_box = Part.makeBox(21.5, 4.5, 5.5, FreeCAD.Vector(-10.75, -4.5, 46.0))
cav_box = Part.makeBox(19.9, 3.3, 4.5, FreeCAD.Vector(-9.95, -4.0, 47.0))
shroud = base_box.cut(cav_box)

jst_pins_shape = None
for p in range(8):
    px = -8.89 + p * 2.54
    pin_h = Part.makeBox(0.64, 4.0, 0.64, FreeCAD.Vector(px - 0.32, -2.5, 47.5 - 0.32))
    pin_v = Part.makeBox(0.64, 0.64, 4.5, FreeCAD.Vector(px - 0.32, -2.5, 47.5))
    pin_l = pin_h.fuse(pin_v)
    if jst_pins_shape is None:
        jst_pins_shape = pin_l
    else:
        jst_pins_shape = jst_pins_shape.fuse(pin_l)

jst_male = shroud.fuse(jst_pins_shape)
obj_jst_male = doc.addObject("Part::Feature", "Conector_JST_XH_Macho_PCB")
obj_jst_male.Shape = jst_male
obj_jst_male.Label = "Conector_JST_XH_8P_Macho_PCB"
apply_style(obj_jst_male, (0.94, 0.94, 0.90), transp=0, vis=True)

# 4.7 Plugue Femea JST-XH 8 Vias (Desconectavel no Chicote da Tampa)
plug_box = Part.makeBox(19.5, 3.0, 4.0, FreeCAD.Vector(-9.75, -3.85, 47.5))
latch = Part.makeBox(4.0, 1.2, 3.5, FreeCAD.Vector(-2.0, -5.0, 48.0))
jst_female = plug_box.fuse(latch)
obj_jst_female = doc.addObject("Part::Feature", "Conector_JST_XH_Femea_Plugue")
obj_jst_female.Shape = jst_female
obj_jst_female.Label = "Plugue_JST_XH_8P_Femea_Chicote"
apply_style(obj_jst_female, (0.88, 0.88, 0.85), transp=0, vis=True)

# 4.8 Chicote de Fios Flexiveis de Silicone (8 Vias Coloridas GX12 <-> JST-XH)
colors = [
    (0.85, 0.15, 0.15),  # Vermelho (+3V3)
    (0.15, 0.15, 0.15),  # Preto (GND)
    (0.95, 0.85, 0.15),  # Amarelo (1W_DQ)
    (0.20, 0.75, 0.25),  # Verde (I2C_SCL)
    (0.15, 0.45, 0.90),  # Azul (I2C_SDA)
    (0.95, 0.95, 0.95),  # Branco (I2S_SCK)
    (0.95, 0.50, 0.15),  # Laranja (I2S_SD)
    (0.60, 0.35, 0.20)   # Marrom (I2S_WS)
]
wires_compound = []
for w in range(8):
    wx = -8.89 + w * 2.54
    w_cyl = Part.makeCylinder(0.38, 7.5, FreeCAD.Vector(wx, -2.5, 51.5), FreeCAD.Vector(-wx/12.0, 0.3, 1))
    wires_compound.append(w_cyl)

wires_fused = wires_compound[0]
for wc in wires_compound[1:]:
    wires_fused = wires_fused.fuse(wc)

obj_wires = doc.addObject("Part::Feature", "Chicote_Fios_Internos_8Vias")
obj_wires.Shape = wires_fused
obj_wires.Label = "Chicote_Fios_Flexiveis_JST_GX16"
apply_style(obj_wires, (0.25, 0.60, 0.85), transp=0, vis=True)

# ==============================================================================
# 5. HARDWARE MECANICO (GX16, Imas, Parafusos)
# ==============================================================================
print("5/6: Adicionando Hardware de Fixacao e Conectores...")

# 5.1 Conector Metalico Industrial GX16-8 (M16x1.0mm)
c_base = Part.makeCylinder(9.75, 2.5, FreeCAD.Vector(0, 0, 64.0), FreeCAD.Vector(0, 0, 1))
c_thread = Part.makeCylinder(7.9, 8.0, FreeCAD.Vector(0, 0, 66.5), FreeCAD.Vector(0, 0, 1))
c_nut = Part.makeCylinder(9.75, 3.5, FreeCAD.Vector(0, 0, 68.0), FreeCAD.Vector(0, 0, 1))
c_pins = Part.makeCylinder(4.5, 7.0, FreeCAD.Vector(0, 0, 57.0), FreeCAD.Vector(0, 0, 1))
gx16_shape = c_base.fuse(c_thread).fuse(c_nut).fuse(c_pins)

obj_gx16 = doc.addObject("Part::Feature", "Conector_Aviacao_GX16_8_Vias")
obj_gx16.Shape = gx16_shape
obj_gx16.Label = "Conector_Aviacao_Metalico_GX16_8"
apply_style(obj_gx16, (0.82, 0.84, 0.86), transp=0, vis=True)

# 5.2 Imas de Neodimio N52 (dia 10 x 2 mm) na base
mag1 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(-7.5, 0, 0.1), FreeCAD.Vector(0, 0, 1))
mag2 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(7.5, 0, 0.1), FreeCAD.Vector(0, 0, 1))
obj_mags = doc.addObject("Part::Feature", "Imas_Neodimio_Base_10mm")
obj_mags.Shape = mag1.fuse(mag2)
obj_mags.Label = "Imas_Neodimio_Base_Fixacao_10mm"
apply_style(obj_mags, (0.75, 0.75, 0.78), transp=0, vis=True)

# 5.3 Parafusos de Fixacao M3 para Mancal UCP 204
screw_m3_1 = Part.makeCylinder(1.5, 8.0, FreeCAD.Vector(0, 20.5, -1.0), FreeCAD.Vector(0, 0, 1))
head_m3_1 = Part.makeCylinder(2.7, 2.5, FreeCAD.Vector(0, 20.5, 4.0), FreeCAD.Vector(0, 0, 1))
screw_m3_2 = Part.makeCylinder(1.5, 8.0, FreeCAD.Vector(0, -20.5, -1.0), FreeCAD.Vector(0, 0, 1))
head_m3_2 = Part.makeCylinder(2.7, 2.5, FreeCAD.Vector(0, -20.5, 4.0), FreeCAD.Vector(0, 0, 1))
obj_screws = doc.addObject("Part::Feature", "Parafusos_Fixacao_M3_Mancal")
obj_screws.Shape = screw_m3_1.fuse(head_m3_1).fuse(screw_m3_2).fuse(head_m3_2)
obj_screws.Label = "Parafusos_Fixacao_M3_Mancal"
apply_style(obj_screws, (0.2, 0.2, 0.22), transp=0, vis=True)

# 5.4 Parafusos M2.5 de Fechamento da Tampa (Posicionados em X = +-12.3 mm para folga do GX16)
sc_t1 = Part.makeCylinder(1.25, 9.0, FreeCAD.Vector(-12.3, 0, 56.5), FreeCAD.Vector(0, 0, 1))
hd_t1 = Part.makeCylinder(2.25, 2.0, FreeCAD.Vector(-12.3, 0, 64.0), FreeCAD.Vector(0, 0, 1))
sc_t2 = Part.makeCylinder(1.25, 9.0, FreeCAD.Vector(12.3, 0, 56.5), FreeCAD.Vector(0, 0, 1))
hd_t2 = Part.makeCylinder(2.25, 2.0, FreeCAD.Vector(12.3, 0, 64.0), FreeCAD.Vector(0, 0, 1))
obj_t_screws = doc.addObject("Part::Feature", "Parafusos_Tampa_M2_5")
obj_t_screws.Shape = sc_t1.fuse(hd_t1).fuse(sc_t2).fuse(hd_t2)
obj_t_screws.Label = "Parafusos_Tampa_M2_5"
apply_style(obj_t_screws, (0.2, 0.2, 0.22), transp=0, vis=True)

# 5.5 Componentes Passivos do Termometro (R1 4.7k Pull-up e C1 100nF Desacoplamento)
# Capacitor C1: disco ceramico 100nF entre +3V3 (X=2.54) e GND (X=0.00) em Z=9.50 mm
c1_disc = Part.makeCylinder(1.8, 1.4, FreeCAD.Vector(1.27, -2.2, 9.5), FreeCAD.Vector(0, 1, 0))
# 2 Terminais do C1 atravessando os furos da PCB de Y=-2.2 ate Y=+1.5 em Z=9.50 mm
c1_pin1 = Part.makeCylinder(0.3, 3.7, FreeCAD.Vector(2.54, -2.2, 9.5), FreeCAD.Vector(0, 1, 0))
c1_pin2 = Part.makeCylinder(0.3, 3.7, FreeCAD.Vector(0.00, -2.2, 9.5), FreeCAD.Vector(0, 1, 0))
c1_shape = c1_disc.fuse(c1_pin1).fuse(c1_pin2)

obj_c1 = doc.addObject("Part::Feature", "Capacitor_C1_100nF_DS18B20")
obj_c1.Shape = c1_shape
obj_c1.Label = "Capacitor_C1_100nF_Desacoplamento"
apply_style(obj_c1, (0.85, 0.60, 0.25), transp=0, vis=True)

# Resistor R1: axial 4.7k entre +3V3 (X=2.54) e 1W_DQ (X=-2.54) em Z=11.50 mm (pitch 5.08 mm)
r1_body = Part.makeCylinder(0.9, 3.4, FreeCAD.Vector(-1.7, -2.0, 11.5), FreeCAD.Vector(1, 0, 0))
r1_lead1_v = Part.makeCylinder(0.3, 0.84, FreeCAD.Vector(1.7, -2.0, 11.5), FreeCAD.Vector(1, 0, 0))
r1_lead1_h = Part.makeCylinder(0.3, 3.5, FreeCAD.Vector(2.54, -2.0, 11.5), FreeCAD.Vector(0, 1, 0))
r1_lead2_v = Part.makeCylinder(0.3, 0.84, FreeCAD.Vector(-2.54, -2.0, 11.5), FreeCAD.Vector(1, 0, 0))
r1_lead2_h = Part.makeCylinder(0.3, 3.5, FreeCAD.Vector(-2.54, -2.0, 11.5), FreeCAD.Vector(0, 1, 0))
r1_shape = r1_body.fuse(r1_lead1_v).fuse(r1_lead1_h).fuse(r1_lead2_v).fuse(r1_lead2_h)

obj_r1 = doc.addObject("Part::Feature", "Resistor_R1_4k7_Pullup_DS18B20")
obj_r1.Shape = r1_shape
obj_r1.Label = "Resistor_R1_4k7_Pullup_1Wire"
apply_style(obj_r1, (0.25, 0.65, 0.85), transp=0, vis=True)

# ==============================================================================
# 6. AGRUPAMENTO HIERARQUICO NA ARVORE
# ==============================================================================
print("6/6: Organizando estrutura de grupos...")
grp_mech = doc.addObject("App::DocumentObjectGroup", "Gabinete_e_Estrutura_Mecanica")
grp_mech.Label = "1. Gabinete e Estrutura Mecanica"
grp_mech.addObject(body_tower)
grp_mech.addObject(body_cap)
grp_mech.addObject(obj_gx16)
grp_mech.addObject(obj_mags)
grp_mech.addObject(obj_screws)
grp_mech.addObject(obj_t_screws)

grp_elec = doc.addObject("App::DocumentObjectGroup", "Eletronica_e_Sensores")
grp_elec.Label = "2. Eletronica e Sensores"
for obj in doc.Objects:
    if 'amemiya_probe_tower' in obj.Name or 'Placa' in obj.Label or 'Geometria' in obj.Label:
        grp_elec.addObject(obj)
grp_elec.addObject(obj_ds_bulb)
grp_elec.addObject(obj_ds_leads)
grp_elec.addObject(obj_c1)
grp_elec.addObject(obj_r1)
grp_elec.addObject(obj_adxl)
grp_elec.addObject(obj_mic_pcb)
grp_elec.addObject(obj_mic_headers)
grp_elec.addObject(obj_mic_pins)
grp_elec.addObject(obj_jst_male)
grp_elec.addObject(obj_jst_female)
grp_elec.addObject(obj_wires)

doc.recompute()
fcstd_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.FCStd"
doc.saveAs(fcstd_path)
print(f"Modelo completo salvo com sucesso em: {fcstd_path}")

step_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.step"
Part.export([body_tower, body_cap, obj_gx16], step_path)
print(f"STEP exportado com sucesso em: {step_path}")

step_complete_path = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_assembly_complete.step"
all_parts = [body_tower, body_cap, obj_gx16, obj_mags, obj_screws, obj_t_screws,
             obj_ds_bulb, obj_ds_leads, obj_c1, obj_r1, obj_adxl,
             obj_mic_pcb, obj_mic_headers, obj_mic_pins,
             obj_jst_male, obj_jst_female, obj_wires]
Part.export(all_parts, step_complete_path)
print(f"STEP completo exportado com sucesso em: {step_complete_path}")

import Mesh
stl_body = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_body.stl"
stl_cap = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_cap.stl"
Mesh.export([body_tower], stl_body)
Mesh.export([body_cap], stl_cap)
print(f"STLs exportados com sucesso em:\n - {stl_body}\n - {stl_cap}")
