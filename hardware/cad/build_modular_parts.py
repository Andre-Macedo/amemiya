# -*- coding: utf-8 -*-
"""
Gerador de Arquitetura Modular CAD (SolidWorks / Fusion 360 Style) para o Amemiya:
- Cada peca fisica possui seu proprio arquivo .FCStd dedicado, 100% em PartDesign com Sketches parametrizados.
- Todos os furos, bolsões e alojamentos estão devidamente cortados (Pockets operacionais com direções validadas).
- Os arquivos de montagem (Assemblies) utilizam App::Link apontando para os arquivos de pecas individuais.
"""

import os
import FreeCAD
import Part
import Sketcher
import Mesh

cad_dir = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad"

def apply_style(obj, rgb, transp=0):
    if hasattr(obj, 'ViewObject') and obj.ViewObject is not None:
        if hasattr(obj.ViewObject, 'ShapeColor'):
            try: obj.ViewObject.ShapeColor = rgb
            except Exception: pass
        if hasattr(obj.ViewObject, 'Transparency'):
            try: obj.ViewObject.Transparency = transp
            except Exception: pass

# ==============================================================================
# 1. MAIN NODE - BASE (amemiya_main_node_base.FCStd)
# ==============================================================================
print("--- 1. Gerando amemiya_main_node_base.FCStd com todos os furos operacionais ---")
doc_base = FreeCAD.newDocument("amemiya_main_node_base")
body_base = doc_base.addObject("PartDesign::Body", "Body_Base")
body_base.Label = "MainNode_Gabinete_Base"

# 1.1 Perfil Externo (111.0 x 86.0 mm, H = 38.0 mm)
sk_p_ext = doc_base.addObject("Sketcher::SketchObject", "Sketch_Perfil_Externo")
body_base.addObject(sk_p_ext)
sk_p_ext.MapMode = "Deactivated"
sk_p_ext.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_p_ext.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, -43.0, 0), FreeCAD.Vector(55.5, -43.0, 0)), False)
sk_p_ext.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, -43.0, 0), FreeCAD.Vector(55.5, 43.0, 0)), False)
sk_p_ext.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, 43.0, 0), FreeCAD.Vector(-55.5, 43.0, 0)), False)
sk_p_ext.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, 43.0, 0), FreeCAD.Vector(-55.5, -43.0, 0)), False)
for i in range(4): sk_p_ext.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_p_ext.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 111.0))
sk_p_ext.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 86.0))
pad_base = doc_base.addObject("PartDesign::Pad", "Pad_Base_Solida")
pad_base.Profile = sk_p_ext
pad_base.Length = 38.0
body_base.addObject(pad_base)
doc_base.recompute()

# 1.2 Cavidade Interna (106.0 x 81.0 mm, descendo 35.5 mm do topo Z=38.0)
sk_p_cav = doc_base.addObject("Sketcher::SketchObject", "Sketch_Cavidade_Interna")
body_base.addObject(sk_p_cav)
sk_p_cav.MapMode = "Deactivated"
sk_p_cav.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 38.0), FreeCAD.Rotation())
sk_p_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-53.0, -40.5, 0), FreeCAD.Vector(53.0, -40.5, 0)), False)
sk_p_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(53.0, -40.5, 0), FreeCAD.Vector(53.0, 40.5, 0)), False)
sk_p_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(53.0, 40.5, 0), FreeCAD.Vector(-53.0, 40.5, 0)), False)
sk_p_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-53.0, 40.5, 0), FreeCAD.Vector(-53.0, -40.5, 0)), False)
for i in range(4): sk_p_cav.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_p_cav.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 106.0))
sk_p_cav.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 81.0))
pocket_cav = doc_base.addObject("PartDesign::Pocket", "Pocket_Cavidade_Interna")
pocket_cav.Profile = sk_p_cav
pocket_cav.Length = 35.5
pocket_cav.Reversed = False
body_base.addObject(pocket_cav)
doc_base.recompute()

# 1.3 Colunas Maciças de Canto para Fechamento da Tampa (Z=2.5 ate Z=38.0)
sk_cols = doc_base.addObject("Sketcher::SketchObject", "Sketch_Colunas_Canto_M3")
body_base.addObject(sk_cols)
sk_cols.MapMode = "Deactivated"
sk_cols.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 2.5), FreeCAD.Rotation())
lid_corners = [(-49.5, -37.0), (49.5, -37.0), (-49.5, 37.0), (49.5, 37.0)]
for cx, cy in lid_corners:
    sk_cols.addGeometry(Part.Circle(FreeCAD.Vector(cx, cy, 0), FreeCAD.Vector(0, 0, 1), 4.0), False)
pad_cols = doc_base.addObject("PartDesign::Pad", "Pad_Colunas_Canto")
pad_cols.Profile = sk_cols
pad_cols.Length = 35.5
pad_cols.Reversed = False
body_base.addObject(pad_cols)
doc_base.recompute()

# 1.4 Furos para insertos de latão M3 (dia 4.2mm, prof 6mm)
sk_col_holes = doc_base.addObject("Sketcher::SketchObject", "Sketch_Furos_Insertos_Canto")
body_base.addObject(sk_col_holes)
sk_col_holes.MapMode = "Deactivated"
sk_col_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 38.0), FreeCAD.Rotation())
for cx, cy in lid_corners:
    sk_col_holes.addGeometry(Part.Circle(FreeCAD.Vector(cx, cy, 0), FreeCAD.Vector(0, 0, 1), 2.1), False)
pocket_col_holes = doc_base.addObject("PartDesign::Pocket", "Pocket_Furos_Insertos_Canto")
pocket_col_holes.Profile = sk_col_holes
pocket_col_holes.Length = 6.0
pocket_col_holes.Reversed = False
body_base.addObject(pocket_col_holes)
doc_base.recompute()

# 1.5 Pilares de Apoio da Placa PCB (Z=2.5 com altura 20.0mm, topo em Z=22.5mm)
sk_posts = doc_base.addObject("Sketcher::SketchObject", "Sketch_Pilares_PCB")
body_base.addObject(sk_posts)
sk_posts.MapMode = "Deactivated"
sk_posts.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 2.5), FreeCAD.Rotation())
pcb_posts = [(-40.0, -27.5), (40.0, -27.5), (-40.0, 27.5), (40.0, 27.5)]
for px, py in pcb_posts:
    sk_posts.addGeometry(Part.Circle(FreeCAD.Vector(px, py, 0), FreeCAD.Vector(0, 0, 1), 3.0), False)
pad_posts = doc_base.addObject("PartDesign::Pad", "Pad_Pilares_PCB")
pad_posts.Profile = sk_posts
pad_posts.Length = 20.0
pad_posts.Reversed = False
body_base.addObject(pad_posts)
doc_base.recompute()

# 1.6 Furos de Parafuso nos Pilares da PCB (prof 5.0mm)
sk_post_holes = doc_base.addObject("Sketcher::SketchObject", "Sketch_Furos_Pilares_PCB")
body_base.addObject(sk_post_holes)
sk_post_holes.MapMode = "Deactivated"
sk_post_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 22.5), FreeCAD.Rotation())
for px, py in pcb_posts:
    sk_post_holes.addGeometry(Part.Circle(FreeCAD.Vector(px, py, 0), FreeCAD.Vector(0, 0, 1), 1.3), False)
pocket_post_holes = doc_base.addObject("PartDesign::Pocket", "Pocket_Furos_Pilares_PCB")
pocket_post_holes.Profile = sk_post_holes
pocket_post_holes.Length = 5.0
pocket_post_holes.Reversed = False
body_base.addObject(pocket_post_holes)
doc_base.recompute()

# 1.7 Compartimento de Bateria no Fundo (Abertura Passante 74x38mm e Rebaixo 80.4x44.4mm)
sk_bat_thru = doc_base.addObject("Sketcher::SketchObject", "Sketch_Abertura_Bateria_Passante")
body_base.addObject(sk_bat_thru)
sk_bat_thru.MapMode = "Deactivated"
sk_bat_thru.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_bat_thru.addGeometry(Part.LineSegment(FreeCAD.Vector(-37.0, -7.0, 0), FreeCAD.Vector(37.0, -7.0, 0)), False)
sk_bat_thru.addGeometry(Part.LineSegment(FreeCAD.Vector(37.0, -7.0, 0), FreeCAD.Vector(37.0, 31.0, 0)), False)
sk_bat_thru.addGeometry(Part.LineSegment(FreeCAD.Vector(37.0, 31.0, 0), FreeCAD.Vector(-37.0, 31.0, 0)), False)
sk_bat_thru.addGeometry(Part.LineSegment(FreeCAD.Vector(-37.0, 31.0, 0), FreeCAD.Vector(-37.0, -7.0, 0)), False)
for i in range(4): sk_bat_thru.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
pocket_bat_thru = doc_base.addObject("PartDesign::Pocket", "Pocket_Abertura_Bateria")
pocket_bat_thru.Profile = sk_bat_thru
pocket_bat_thru.Length = 2.5
pocket_bat_thru.Reversed = True
body_base.addObject(pocket_bat_thru)
doc_base.recompute()

sk_bat_reb = doc_base.addObject("Sketcher::SketchObject", "Sketch_Rebaixo_Tampa_Bateria")
body_base.addObject(sk_bat_reb)
sk_bat_reb.MapMode = "Deactivated"
sk_bat_reb.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_bat_reb.addGeometry(Part.LineSegment(FreeCAD.Vector(-40.2, -10.2, 0), FreeCAD.Vector(40.2, -10.2, 0)), False)
sk_bat_reb.addGeometry(Part.LineSegment(FreeCAD.Vector(40.2, -10.2, 0), FreeCAD.Vector(40.2, 34.2, 0)), False)
sk_bat_reb.addGeometry(Part.LineSegment(FreeCAD.Vector(40.2, 34.2, 0), FreeCAD.Vector(-40.2, 34.2, 0)), False)
sk_bat_reb.addGeometry(Part.LineSegment(FreeCAD.Vector(-40.2, 34.2, 0), FreeCAD.Vector(-40.2, -10.2, 0)), False)
for i in range(4): sk_bat_reb.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
pocket_bat_reb = doc_base.addObject("PartDesign::Pocket", "Pocket_Rebaixo_Bateria")
pocket_bat_reb.Profile = sk_bat_reb
pocket_bat_reb.Length = 2.0
pocket_bat_reb.Reversed = True
body_base.addObject(pocket_bat_reb)
doc_base.recompute()

# 1.8 Abas Externas de Fixacao Mecanica (M4)
sk_flanges = doc_base.addObject("Sketcher::SketchObject", "Sketch_Abas_Fixacao_M4")
body_base.addObject(sk_flanges)
sk_flanges.MapMode = "Deactivated"
sk_flanges.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
# Aba esquerda
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, -14.0, 0), FreeCAD.Vector(-69.5, -14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-69.5, -14.0, 0), FreeCAD.Vector(-69.5, 14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-69.5, 14.0, 0), FreeCAD.Vector(-55.5, 14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, 14.0, 0), FreeCAD.Vector(-55.5, -14.0, 0)), False)
for i in range(4): sk_flanges.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_flanges.addGeometry(Part.Circle(FreeCAD.Vector(-62.5, 0, 0), FreeCAD.Vector(0, 0, 1), 2.5), False)
# Aba direita
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, -14.0, 0), FreeCAD.Vector(69.5, -14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(69.5, -14.0, 0), FreeCAD.Vector(69.5, 14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(69.5, 14.0, 0), FreeCAD.Vector(55.5, 14.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, 14.0, 0), FreeCAD.Vector(55.5, -14.0, 0)), False)
for i in range(5, 9): sk_flanges.addConstraint(Sketcher.Constraint("Coincident", i, 2, 5 + (i - 4) % 4, 1))
sk_flanges.addGeometry(Part.Circle(FreeCAD.Vector(62.5, 0, 0), FreeCAD.Vector(0, 0, 1), 2.5), False)
pad_flanges = doc_base.addObject("PartDesign::Pad", "Pad_Abas_Fixacao")
pad_flanges.Profile = sk_flanges
pad_flanges.Length = 3.5
pad_flanges.Reversed = False
body_base.addObject(pad_flanges)
doc_base.recompute()

# 1.9 Painel Lateral Esquerdo de Sensores (X = -55.5 mm)
sk_sensores = doc_base.addObject("Sketcher::SketchObject", "Sketch_Painel_Esquerdo_Sensores")
body_base.addObject(sk_sensores)
sk_sensores.MapMode = "Deactivated"
sk_sensores.Placement = FreeCAD.Placement(FreeCAD.Vector(-55.5, 0, 0), FreeCAD.Rotation(FreeCAD.Vector(1, 1, 1), 120))
# GX16-8 Torre (Y = -16.0, Z = 29.0, raio 8.1 mm)
sk_sensores.addGeometry(Part.Circle(FreeCAD.Vector(-16.0, 29.0, 0), FreeCAD.Vector(0, 0, 1), 8.1), False)
# Jack P2 3.5mm (Y = +2.0, Z = 29.0, raio 3.0 mm)
sk_sensores.addGeometry(Part.Circle(FreeCAD.Vector(2.0, 29.0, 0), FreeCAD.Vector(0, 0, 1), 3.0), False)
# GX12-3 Tacometro (Y = +18.0, Z = 29.0, raio 6.0 mm)
sk_sensores.addGeometry(Part.Circle(FreeCAD.Vector(18.0, 29.0, 0), FreeCAD.Vector(0, 0, 1), 6.0), False)
pocket_sensores = doc_base.addObject("PartDesign::Pocket", "Pocket_Painel_Sensores")
pocket_sensores.Profile = sk_sensores
pocket_sensores.Length = 5.0
pocket_sensores.Reversed = True
body_base.addObject(pocket_sensores)
doc_base.recompute()

# 1.10 Painel Frontal para Conectores USB-C (Y = -43.0 mm)
sk_usbs = doc_base.addObject("Sketcher::SketchObject", "Sketch_Painel_Frontal_USBs")
body_base.addObject(sk_usbs)
sk_usbs.MapMode = "Deactivated"
sk_usbs.Placement = FreeCAD.Placement(FreeCAD.Vector(0, -43.0, 0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
# USB-C TP4056 em X = 22.0, Z = 4.5 (10.0 x 4.5 mm)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(17.0, 2.25, 0), FreeCAD.Vector(27.0, 2.25, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(27.0, 2.25, 0), FreeCAD.Vector(27.0, 6.75, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(27.0, 6.75, 0), FreeCAD.Vector(17.0, 6.75, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(17.0, 6.75, 0), FreeCAD.Vector(17.0, 2.25, 0)), False)
for i in range(4): sk_usbs.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
# USB-C ESP32-S3 em X = -9.0, Z = 26.5 (10.0 x 4.5 mm)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(-14.0, 24.25, 0), FreeCAD.Vector(-4.0, 24.25, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, 24.25, 0), FreeCAD.Vector(-4.0, 28.75, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, 28.75, 0), FreeCAD.Vector(-14.0, 28.75, 0)), False)
sk_usbs.addGeometry(Part.LineSegment(FreeCAD.Vector(-14.0, 28.75, 0), FreeCAD.Vector(-14.0, 24.25, 0)), False)
for i in range(4, 8): sk_usbs.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
pocket_usbs = doc_base.addObject("PartDesign::Pocket", "Pocket_Painel_USBs")
pocket_usbs.Profile = sk_usbs
pocket_usbs.Length = 5.0
pocket_usbs.Reversed = False
body_base.addObject(pocket_usbs)
doc_base.recompute()

# 1.11 Painel Lateral Direito para Antena SMA LoRa (X = +55.5 mm)
sk_sma = doc_base.addObject("Sketcher::SketchObject", "Sketch_Painel_Direito_SMA")
body_base.addObject(sk_sma)
sk_sma.MapMode = "Deactivated"
sk_sma.Placement = FreeCAD.Placement(FreeCAD.Vector(55.5, 0, 0), FreeCAD.Rotation(FreeCAD.Vector(1, 1, 1), 120))
# SMA LoRa em Y = 6.0, Z = 28.5 (raio 3.5 mm)
sk_sma.addGeometry(Part.Circle(FreeCAD.Vector(6.0, 28.5, 0), FreeCAD.Vector(0, 0, 1), 3.5), False)
pocket_sma = doc_base.addObject("PartDesign::Pocket", "Pocket_Painel_SMA")
pocket_sma.Profile = sk_sma
pocket_sma.Length = 5.0
pocket_sma.Reversed = False
body_base.addObject(pocket_sma)
doc_base.recompute()

apply_style(body_base, (0.16, 0.32, 0.62), transp=0)
base_file = os.path.join(cad_dir, "amemiya_main_node_base.FCStd")
doc_base.saveAs(base_file)
Mesh.export([body_base], os.path.join(cad_dir, "amemiya_main_node_base.stl"))
Part.export([body_base], os.path.join(cad_dir, "amemiya_main_node_base.step"))
print(f"Base salva com sucesso! Vol final: {body_base.Tip.Shape.Volume:.1f}, Faces: {len(body_base.Tip.Shape.Faces)}")

# ==============================================================================
# 2. MAIN NODE - TAMPA SUPERIOR IHM (amemiya_main_node_lid.FCStd)
# ==============================================================================
print("--- 2. Gerando amemiya_main_node_lid.FCStd com todos os furos operacionais ---")
doc_lid = FreeCAD.newDocument("amemiya_main_node_lid")
body_lid = doc_lid.addObject("PartDesign::Body", "Body_Tampa")
body_lid.Label = "MainNode_Tampa_Superior"

# 2.1 Perfil Externo da Tampa (111.0 x 86.0 mm, Altura 14.0 mm)
sk_l_prof = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Perfil_Externo_Tampa")
body_lid.addObject(sk_l_prof)
sk_l_prof.MapMode = "Deactivated"
sk_l_prof.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_l_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, -43.0, 0), FreeCAD.Vector(55.5, -43.0, 0)), False)
sk_l_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, -43.0, 0), FreeCAD.Vector(55.5, 43.0, 0)), False)
sk_l_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(55.5, 43.0, 0), FreeCAD.Vector(-55.5, 43.0, 0)), False)
sk_l_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-55.5, 43.0, 0), FreeCAD.Vector(-55.5, -43.0, 0)), False)
for i in range(4): sk_l_prof.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_l_prof.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 111.0))
sk_l_prof.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 86.0))
pad_lid = doc_lid.addObject("PartDesign::Pad", "Pad_Tampa_Solida")
pad_lid.Profile = sk_l_prof
pad_lid.Length = 14.0
body_lid.addObject(pad_lid)
doc_lid.recompute()

# 2.2 Rebaixo Interno da Tampa (106.0 x 81.0 mm, prof 11.4 mm, deixa teto de 2.6 mm)
sk_l_cav = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Cavidade_Tampa")
body_lid.addObject(sk_l_cav)
sk_l_cav.MapMode = "Deactivated"
sk_l_cav.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_l_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-53.0, -40.5, 0), FreeCAD.Vector(53.0, -40.5, 0)), False)
sk_l_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(53.0, -40.5, 0), FreeCAD.Vector(53.0, 40.5, 0)), False)
sk_l_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(53.0, 40.5, 0), FreeCAD.Vector(-53.0, 40.5, 0)), False)
sk_l_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-53.0, 40.5, 0), FreeCAD.Vector(-53.0, -40.5, 0)), False)
for i in range(4): sk_l_cav.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
pocket_l_cav = doc_lid.addObject("PartDesign::Pocket", "Pocket_Cavidade_Tampa")
pocket_l_cav.Profile = sk_l_cav
pocket_l_cav.Length = 11.4
pocket_l_cav.Reversed = True
body_lid.addObject(pocket_l_cav)
doc_lid.recompute()

# 2.3 Labio de Encaixe Macho no Perimetro (Z=0 descendo 2.0 mm)
sk_l_lip = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Labio_Encaixe_Macho")
body_lid.addObject(sk_l_lip)
sk_l_lip.MapMode = "Deactivated"
sk_l_lip.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
# Borda externa do labio (105.4 x 80.4 mm)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(-52.7, -40.2, 0), FreeCAD.Vector(52.7, -40.2, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(52.7, -40.2, 0), FreeCAD.Vector(52.7, 40.2, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(52.7, 40.2, 0), FreeCAD.Vector(-52.7, 40.2, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(-52.7, 40.2, 0), FreeCAD.Vector(-52.7, -40.2, 0)), False)
for i in range(4): sk_l_lip.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
# Borda interna do labio (103.0 x 78.0 mm)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(-51.5, -39.0, 0), FreeCAD.Vector(51.5, -39.0, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(51.5, -39.0, 0), FreeCAD.Vector(51.5, 39.0, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(51.5, 39.0, 0), FreeCAD.Vector(-51.5, 39.0, 0)), False)
sk_l_lip.addGeometry(Part.LineSegment(FreeCAD.Vector(-51.5, 39.0, 0), FreeCAD.Vector(-51.5, -39.0, 0)), False)
for i in range(4, 8): sk_l_lip.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
pad_lip = doc_lid.addObject("PartDesign::Pad", "Pad_Labio_Encaixe")
pad_lip.Profile = sk_l_lip
pad_lip.Length = 2.0
pad_lip.Reversed = True
body_lid.addObject(pad_lip)
doc_lid.recompute()

# 2.4 Janela do Display OLED 0.96 (27.0 x 22.0 mm centralizada) no Topo Z=14.0
sk_oled = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Janela_Display_OLED")
body_lid.addObject(sk_oled)
sk_oled.MapMode = "Deactivated"
sk_oled.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 14.0), FreeCAD.Rotation())
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.5, -11.0, 0), FreeCAD.Vector(13.5, -11.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(13.5, -11.0, 0), FreeCAD.Vector(13.5, 11.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(13.5, 11.0, 0), FreeCAD.Vector(-13.5, 11.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.5, 11.0, 0), FreeCAD.Vector(-13.5, -11.0, 0)), False)
for i in range(4): sk_oled.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
pocket_oled = doc_lid.addObject("PartDesign::Pocket", "Pocket_Janela_OLED")
pocket_oled.Profile = sk_oled
pocket_oled.Length = 4.0
pocket_oled.Reversed = False
body_lid.addObject(pocket_oled)
doc_lid.recompute()

# 2.5 Furos para LEDs de Status 3mm (3 furos de dia 3.2mm a esquerda)
sk_leds = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Furos_LEDs_Status")
body_lid.addObject(sk_leds)
sk_leds.MapMode = "Deactivated"
sk_leds.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 14.0), FreeCAD.Rotation())
led_coords = [(-31.0, 8.0), (-31.0, 0.0), (-31.0, -8.0)]
for lx, ly in led_coords:
    sk_leds.addGeometry(Part.Circle(FreeCAD.Vector(lx, ly, 0), FreeCAD.Vector(0, 0, 1), 1.6), False)
pocket_leds = doc_lid.addObject("PartDesign::Pocket", "Pocket_Furos_LEDs")
pocket_leds.Profile = sk_leds
pocket_leds.Length = 4.0
pocket_leds.Reversed = False
body_lid.addObject(pocket_leds)
doc_lid.recompute()

# 2.6 Grelha Acustica do Buzzer Piezo (5 furos dia 1.8mm em cruz a direita)
sk_buz = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Grelha_Buzzer_Piezo")
body_lid.addObject(sk_buz)
sk_buz.MapMode = "Deactivated"
sk_buz.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 14.0), FreeCAD.Rotation())
for bx, by in [(31.0, -8.0), (33.4, -8.0), (28.6, -8.0), (31.0, -5.6), (31.0, -10.4)]:
    sk_buz.addGeometry(Part.Circle(FreeCAD.Vector(bx, by, 0), FreeCAD.Vector(0, 0, 1), 0.9), False)
pocket_buz = doc_lid.addObject("PartDesign::Pocket", "Pocket_Grelha_Buzzer")
pocket_buz.Profile = sk_buz
pocket_buz.Length = 4.0
pocket_buz.Reversed = False
body_lid.addObject(pocket_buz)
doc_lid.recompute()

# 2.7 Furos Passantes dos 4 Parafusos M3 de Canto (+-49.5, +-37.0)
sk_l_holes = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Furos_Parafusos_Tampa")
body_lid.addObject(sk_l_holes)
sk_l_holes.MapMode = "Deactivated"
sk_l_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 14.0), FreeCAD.Rotation())
for cx, cy in lid_corners:
    sk_l_holes.addGeometry(Part.Circle(FreeCAD.Vector(cx, cy, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
pocket_l_holes = doc_lid.addObject("PartDesign::Pocket", "Pocket_Furos_Parafusos_Tampa")
pocket_l_holes.Profile = sk_l_holes
pocket_l_holes.Length = 14.0
pocket_l_holes.Reversed = False
body_lid.addObject(pocket_l_holes)
doc_lid.recompute()

# 2.8 Rebaixo para Cabeca do Parafuso ISO 7380 (dia 6.5mm, prof 2.2mm)
sk_couterb = doc_lid.addObject("Sketcher::SketchObject", "Sketch_Rebaixo_Cabecas_M3")
body_lid.addObject(sk_couterb)
sk_couterb.MapMode = "Deactivated"
sk_couterb.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 14.0), FreeCAD.Rotation())
for cx, cy in lid_corners:
    sk_couterb.addGeometry(Part.Circle(FreeCAD.Vector(cx, cy, 0), FreeCAD.Vector(0, 0, 1), 3.25), False)
pocket_counterb = doc_lid.addObject("PartDesign::Pocket", "Pocket_Rebaixo_Cabecas_M3")
pocket_counterb.Profile = sk_couterb
pocket_counterb.Length = 2.2
pocket_counterb.Reversed = False
body_lid.addObject(pocket_counterb)
doc_lid.recompute()

apply_style(body_lid, (0.18, 0.35, 0.65), transp=0)
lid_file = os.path.join(cad_dir, "amemiya_main_node_lid.FCStd")
doc_lid.saveAs(lid_file)
Mesh.export([body_lid], os.path.join(cad_dir, "amemiya_main_node_lid.stl"))
Part.export([body_lid], os.path.join(cad_dir, "amemiya_main_node_lid.step"))
print(f"Tampa salva com sucesso! Vol final: {body_lid.Tip.Shape.Volume:.1f}, Faces: {len(body_lid.Tip.Shape.Faces)}")

# ==============================================================================
# 3. MAIN NODE - TAMPA DE BATERIA (amemiya_main_node_battery_door.FCStd)
# ==============================================================================
print("--- 3. Gerando amemiya_main_node_battery_door.FCStd ---")
doc_bat = FreeCAD.newDocument("amemiya_main_node_battery_door")
body_bat = doc_bat.addObject("PartDesign::Body", "Body_Porta_Bateria")
body_bat.Label = "MainNode_Tampa_Bateria"

# 3.1 Perfil da Tampa (80.0 x 44.0 mm, espessura 2.0 mm)
sk_b_prof = doc_bat.addObject("Sketcher::SketchObject", "Sketch_Perfil_Tampa_Bateria")
body_bat.addObject(sk_b_prof)
sk_b_prof.MapMode = "Deactivated"
sk_b_prof.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_b_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-40.0, -10.0, 0), FreeCAD.Vector(40.0, -10.0, 0)), False)
sk_b_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(40.0, -10.0, 0), FreeCAD.Vector(40.0, 34.0, 0)), False)
sk_b_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(40.0, 34.0, 0), FreeCAD.Vector(-40.0, 34.0, 0)), False)
sk_b_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-40.0, 34.0, 0), FreeCAD.Vector(-40.0, -10.0, 0)), False)
for i in range(4): sk_b_prof.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
pad_bat = doc_bat.addObject("PartDesign::Pad", "Pad_Tampa_Bateria")
pad_bat.Profile = sk_b_prof
pad_bat.Length = 2.0
body_bat.addObject(pad_bat)
doc_bat.recompute()

# 3.2 Furos Passantes dos 4 Parafusos M3 (+-37.0, -7.0 e +-37.0, +31.0)
sk_b_holes = doc_bat.addObject("Sketcher::SketchObject", "Sketch_Furos_Parafusos_M3")
body_bat.addObject(sk_b_holes)
sk_b_holes.MapMode = "Deactivated"
sk_b_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 2.0), FreeCAD.Rotation())
bat_screw_coords = [(-37.0, -7.0), (37.0, -7.0), (-37.0, 31.0), (37.0, 31.0)]
for bx, by in bat_screw_coords:
    sk_b_holes.addGeometry(Part.Circle(FreeCAD.Vector(bx, by, 0), FreeCAD.Vector(0, 0, 1), 1.7), False)
pocket_b_holes = doc_bat.addObject("PartDesign::Pocket", "Pocket_Furos_Parafusos")
pocket_b_holes.Profile = sk_b_holes
pocket_b_holes.Length = 2.0
pocket_b_holes.Reversed = False
body_bat.addObject(pocket_b_holes)
doc_bat.recompute()

# 3.3 Escareados para Cabeca DIN 7991 M3 (dia 6.2mm, prof 1.2mm)
sk_b_countersink = doc_bat.addObject("Sketcher::SketchObject", "Sketch_Escareado_DIN7991")
body_bat.addObject(sk_b_countersink)
sk_b_countersink.MapMode = "Deactivated"
sk_b_countersink.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 2.0), FreeCAD.Rotation())
for bx, by in bat_screw_coords:
    sk_b_countersink.addGeometry(Part.Circle(FreeCAD.Vector(bx, by, 0), FreeCAD.Vector(0, 0, 1), 3.1), False)
pocket_b_cs = doc_bat.addObject("PartDesign::Pocket", "Pocket_Escareado_Cabecas")
pocket_b_cs.Profile = sk_b_countersink
pocket_b_cs.Length = 1.2
pocket_b_cs.Reversed = False
body_bat.addObject(pocket_b_cs)
doc_bat.recompute()

apply_style(body_bat, (0.16, 0.32, 0.62), transp=0)
bat_file = os.path.join(cad_dir, "amemiya_main_node_battery_door.FCStd")
doc_bat.saveAs(bat_file)
Mesh.export([body_bat], os.path.join(cad_dir, "amemiya_main_node_battery_door.stl"))
Part.export([body_bat], os.path.join(cad_dir, "amemiya_main_node_battery_door.step"))
print(f"Porta Bateria salva com sucesso! Vol final: {body_bat.Tip.Shape.Volume:.1f}, Faces: {len(body_bat.Tip.Shape.Faces)}")

# ==============================================================================
# 4. SONDA TORRE - TAMPA GX16 (amemiya_probe_tower_cap.FCStd)
# ==============================================================================
print("--- 4. Atualizando amemiya_probe_tower_cap.FCStd com furo passante GX16 ---")
doc_cap = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_cap.FCStd"))
b_cap = doc_cap.getObject("Tampa_Superior_GX16")
pock_gx16 = doc_cap.getObject("Pocket_Furo_Conector_GX16")
if pock_gx16:
    pock_gx16.Reversed = False
doc_cap.recompute()
doc_cap.save()
Mesh.export([b_cap], os.path.join(cad_dir, "amemiya_probe_tower_cap.stl"))
Part.export([b_cap], os.path.join(cad_dir, "amemiya_probe_tower_cap.step"))
print(f"Tampa Torre GX16 concluida! Vol: {b_cap.Tip.Shape.Volume:.1f}, Faces: {len(b_cap.Tip.Shape.Faces)}")

# ==============================================================================
# 5. ATUALIZACAO DAS MONTAGENS MASTER
# ==============================================================================
doc_main_assy = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_main_node_assembly.FCStd"))
doc_main_assy.recompute()
doc_main_assy.save()

doc_tower_assy = FreeCAD.openDocument(os.path.join(cad_dir, "amemiya_probe_tower_assembly.FCStd"))
doc_tower_assy.recompute()
doc_tower_assy.save()
print("Montagens recomputadas e salvas com sucesso!")
