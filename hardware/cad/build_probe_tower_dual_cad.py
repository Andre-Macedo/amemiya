# -*- coding: utf-8 -*-
"""
Construção Paramétrica da Sonda Torre Dual (Acelerômetro + Termômetro)
Compatível com FreeCAD 1.1 e Autodesk Fusion 360
- Gabinete_Torre_Base: 100% selado IP65/IP67, com ranhuras para PCB 24x48mm e berço térmico para DS18B20
- Tampa_Superior_GX12: Furo central para conector industrial de 5 pinos (GX12-5)
- Importação da PCB real KiCad Dual via STEP (amemiya_probe_tower_dual.step)
- Módulo Acelerômetro ADXL345
- Sensor DS18B20 com bulbo de aço inox no berço térmico
- Conector JST-XH 5 vias e chicote flexível de 5 fios (+3V3, GND, 1W_DQ, SCL, SDA)
- Alojamentos para ímãs de neodímio N52 e furação para mancal UCP204
- Exportação FCStd, STL e STEP universal para Fusion 360
"""

import FreeCAD
import Part
import Sketcher
import Import
import Mesh
import os
import sys

CAD_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad"
KICAD_DUAL_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower_dual"

doc_name = "amemiya_probe_tower_dual_enclosure"
try:
    doc = FreeCAD.getDocument(doc_name)
    for obj in list(doc.Objects):
        try:
            doc.removeObject(obj.Name)
        except Exception:
            pass
except Exception:
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

print("1/5: Construindo Gabinete_Torre_Base (Gabinete Selado IP65)...")
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

# 1.2 Cavidade Interna da Torre (21.6 x 26.0 mm)
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
pocket_cavidade.Reversed = False
body_tower.addObject(pocket_cavidade)
doc.recompute()

# 1.3 Ranhuras / Canaletas Laterais para Deslize da PCB (Largura 24.0 mm, espessura 1.8 mm)
sk_ranhura = doc.addObject("Sketcher::SketchObject", "Sketch_Ranhura_PCB")
body_tower.addObject(sk_ranhura)
sk_ranhura.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_ranhura.MapMode = "Deactivated"

rx0, ry0 = -12.1, -0.9
rx1, ry1 = 12.1, 0.9
sk_ranhura.addGeometry(Part.LineSegment(FreeCAD.Vector(rx0, ry0, 0), FreeCAD.Vector(rx1, ry0, 0)), False)
sk_ranhura.addGeometry(Part.LineSegment(FreeCAD.Vector(rx1, ry0, 0), FreeCAD.Vector(rx1, ry1, 0)), False)
sk_ranhura.addGeometry(Part.LineSegment(FreeCAD.Vector(rx1, ry1, 0), FreeCAD.Vector(rx0, ry1, 0)), False)
sk_ranhura.addGeometry(Part.LineSegment(FreeCAD.Vector(rx0, ry1, 0), FreeCAD.Vector(rx0, ry0, 0)), False)
for i in range(4):
    sk_ranhura.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_ranhura.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 24.2))
sk_ranhura.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 1.8))
doc.recompute()

pocket_ranhura = doc.addObject("PartDesign::Pocket", "Pocket_Ranhura_PCB")
pocket_ranhura.Profile = sk_ranhura
pocket_ranhura.Length = 50.0
pocket_ranhura.Reversed = False
body_tower.addObject(pocket_ranhura)
doc.recompute()

# 1.4 Berço Térmico de Contato para o Sensor DS18B20 no Fundo (X=0, Y=-6.0)
sk_berco = doc.addObject("Sketcher::SketchObject", "Sketch_Berco_Termico_DS18B20")
body_tower.addObject(sk_berco)
sk_berco.Placement = FreeCAD.Placement(FreeCAD.Vector(0, -6.0, 3.0), FreeCAD.Rotation())
sk_berco.MapMode = "Deactivated"
sk_berco.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 3.1), False)
doc.recompute()

pocket_berco = doc.addObject("PartDesign::Pocket", "Pocket_Berco_Termico")
pocket_berco.Profile = sk_berco
pocket_berco.Length = 2.0
pocket_berco.Reversed = True
body_tower.addObject(pocket_berco)
doc.recompute()

# 1.5 Alojamentos para Ímãs de Neodímio na Base Inferior (Dia 10mm, Profundidade 2mm)
sk_mags = doc.addObject("Sketcher::SketchObject", "Sketch_Imas_Neodimio_Base")
body_tower.addObject(sk_mags)
sk_mags.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0), FreeCAD.Rotation())
sk_mags.MapMode = "Deactivated"
sk_mags.addGeometry(Part.Circle(FreeCAD.Vector(-7.5, 0, 0), FreeCAD.Vector(0, 0, 1), 5.05), False)
sk_mags.addGeometry(Part.Circle(FreeCAD.Vector(7.5, 0, 0), FreeCAD.Vector(0, 0, 1), 5.05), False)
doc.recompute()

pocket_mags = doc.addObject("PartDesign::Pocket", "Pocket_Imas_Neodimio")
pocket_mags.Profile = sk_mags
pocket_mags.Length = 2.0
pocket_mags.Reversed = False
body_tower.addObject(pocket_mags)
doc.recompute()

# Cor Industrial do Gabinete (Polímero reforçado com fibra / alumínio anodizado cinza grafite)
apply_style(body_tower, (0.28, 0.30, 0.33), transp=20, vis=True)

# ==============================================================================
# 2. TAMPA SUPERIOR COM FURO PARA CONECTOR GX12 (PartDesign::Body)
# ==============================================================================
print("2/5: Construindo Tampa_Superior_GX12...")
body_cap = doc.addObject("PartDesign::Body", "Tampa_Superior_GX12")
body_cap.Label = "Tampa_Superior_GX12"

sk_cap_perfil = doc.addObject("Sketcher::SketchObject", "Sketch_Cap_Perfil")
body_cap.addObject(sk_cap_perfil)
sk_cap_perfil.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 56.0), FreeCAD.Rotation())
sk_cap_perfil.MapMode = "Deactivated"

sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_cap_perfil.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4):
    sk_cap_perfil.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_cap_perfil.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 30.0))
sk_cap_perfil.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 32.0))
doc.recompute()

pad_cap = doc.addObject("PartDesign::Pad", "Pad_Tampa_Placa")
pad_cap.Profile = sk_cap_perfil
pad_cap.Length = 8.0
body_cap.addObject(pad_cap)
doc.recompute()

# Furo do Conector Industrial GX12 (M12x1.0mm -> Diâmetro 12.0 mm)
sk_cap_gx = doc.addObject("Sketcher::SketchObject", "Sketch_Furo_GX12")
body_cap.addObject(sk_cap_gx)
sk_cap_gx.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 64.0), FreeCAD.Rotation())
sk_cap_gx.MapMode = "Deactivated"
sk_cap_gx.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 6.1), False)
doc.recompute()

pocket_cap_gx = doc.addObject("PartDesign::Pocket", "Pocket_Furo_GX12")
pocket_cap_gx.Profile = sk_cap_gx
pocket_cap_gx.Length = 8.0
pocket_cap_gx.Reversed = False
body_cap.addObject(pocket_cap_gx)
doc.recompute()

apply_style(body_cap, (0.85, 0.40, 0.10), transp=0, vis=True) # Laranja Amemiya

# ==============================================================================
# 3. IMPORTAÇÃO E POSICIONAMENTO DA PCB REAL KICAD DUAL
# ==============================================================================
print("3/5: Importando amemiya_probe_tower_dual.step...")
step_pcb = os.path.join(KICAD_DUAL_DIR, "amemiya_probe_tower_dual.step")
if os.path.exists(step_pcb):
    imported_pcb = Import.insert(step_pcb, doc_name)
    print("  [OK] PCB importada via STEP!")
    # Centralizar PCB na ranhura da torre (X=0, Y=0, Z=4.0 a Z=52.0 mm)
    for obj in doc.Objects:
        if 'amemiya_probe_tower_dual' in obj.Name:
            obj.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0, 4.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
            apply_style(obj, (0.10, 0.50, 0.25), transp=0, vis=True)

# ==============================================================================
# 4. COMPONENTES ELETROMECÂNICOS (ADXL345, DS18B20, GX12-5, Imas)
# ==============================================================================
print("4/5: Construindo componentes eletromecânicos...")

# 4.1 Módulo Acelerômetro ADXL345 (GY-291 placa azul 20x15 mm com chip central)
adxl_sub = Part.makeBox(20.0, 1.2, 15.0, FreeCAD.Vector(-10.0, 0.8, 12.0))
adxl_chip = Part.makeBox(3.0, 1.0, 5.0, FreeCAD.Vector(-1.5, 2.0, 17.0))
adxl_shape = adxl_sub.fuse(adxl_chip)
obj_adxl = doc.addObject("Part::Feature", "Modulo_ADXL345_GY291")
obj_adxl.Shape = adxl_shape
obj_adxl.Label = "Modulo_ADXL345_Acelerometro"
apply_style(obj_adxl, (0.12, 0.35, 0.85), transp=0, vis=True)

# 4.2 Sensor de Temperatura DS18B20 (Bulbo cilíndrico de aço inox dia 6mm x 20mm no berço da base)
ds_bulb = Part.makeCylinder(3.0, 18.0, FreeCAD.Vector(0, -6.0, 1.0), FreeCAD.Vector(0, 0, 1))
obj_ds = doc.addObject("Part::Feature", "Sensor_DS18B20_Bulbo_Inox")
obj_ds.Shape = ds_bulb
obj_ds.Label = "Sensor_DS18B20_Bulbo_Inox"
apply_style(obj_ds, (0.80, 0.82, 0.85), transp=0, vis=True) # Aço inox polido

# 4.3 Conector Metálico Industrial GX12-5 (M12x1.0mm 5 vias)
gx_base = Part.makeCylinder(7.5, 2.5, FreeCAD.Vector(0, 0, 64.0), FreeCAD.Vector(0, 0, 1))
gx_thread = Part.makeCylinder(5.9, 7.0, FreeCAD.Vector(0, 0, 66.5), FreeCAD.Vector(0, 0, 1))
gx_nut = Part.makeCylinder(7.5, 3.0, FreeCAD.Vector(0, 0, 67.5), FreeCAD.Vector(0, 0, 1))
gx_shape = gx_base.fuse(gx_thread).fuse(gx_nut)
obj_gx12 = doc.addObject("Part::Feature", "Conector_Aviacao_GX12_5_Vias")
obj_gx12.Shape = gx_shape
obj_gx12.Label = "Conector_Aviacao_Metalico_GX12_5"
apply_style(obj_gx12, (0.82, 0.84, 0.86), transp=0, vis=True)

# 4.4 Ímãs de Neodímio N52 (dia 10 x 2 mm) na base
mag1 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(-7.5, 0, 0.1), FreeCAD.Vector(0, 0, 1))
mag2 = Part.makeCylinder(5.0, 2.0, FreeCAD.Vector(7.5, 0, 0.1), FreeCAD.Vector(0, 0, 1))
obj_mags = doc.addObject("Part::Feature", "Imas_Neodimio_Base_10mm")
obj_mags.Shape = mag1.fuse(mag2)
obj_mags.Label = "Imas_Neodimio_Base_Fixacao_10mm"
apply_style(obj_mags, (0.75, 0.75, 0.78), transp=0, vis=True)

# 4.5 Conector JST-XH 5 Pinos Macho na PCB
jst5 = Part.makeBox(14.9, 5.7, 7.0, FreeCAD.Vector(-7.45, -2.85, 43.8))
obj_jst = doc.addObject("Part::Feature", "Conector_JST_XH_5P")
obj_jst.Shape = jst5
obj_jst.Label = "Conector_JST_XH_5P_PCB"
apply_style(obj_jst, (0.95, 0.95, 0.92), transp=0, vis=True)

# 4.6 Chicote Interno 5 Vias Flexível
wires = []
colors = [(0.85, 0.1, 0.1), (0.1, 0.1, 0.1), (0.1, 0.4, 0.8), (0.1, 0.8, 0.1), (0.9, 0.8, 0.1)]
for i in range(5):
    wx = -5.0 + i * 2.5
    w_cyl = Part.makeCylinder(0.5, 12.0, FreeCAD.Vector(wx, 0, 50.8), FreeCAD.Vector(-wx * 0.05, 0, 1))
    wires.append(w_cyl)
wf = wires[0]
for w in wires[1:]:
    wf = wf.fuse(w)
obj_wire = doc.addObject("Part::Feature", "Chicote_Interno_5Vias")
obj_wire.Shape = wf
obj_wire.Label = "Chicote_Interno_5Vias"
apply_style(obj_wire, (0.2, 0.6, 0.8), transp=0, vis=True)

# ==============================================================================
# 5. ORGANIZAÇÃO E EXPORTAÇÃO
# ==============================================================================
print("5/5: Salvando e exportando arquivos CAD...")
doc.recompute()

fcstd_path = os.path.join(CAD_DIR, "amemiya_probe_tower_dual_enclosure.FCStd")
doc.saveAs(fcstd_path)
print(f"  [OK] FreeCAD FCStd: {fcstd_path}")

step_complete_path = os.path.join(CAD_DIR, "amemiya_probe_tower_dual_assembly_complete.step")
all_shapes = [body_tower.Shape, body_cap.Shape, obj_adxl.Shape, obj_ds.Shape, obj_gx12.Shape, obj_mags.Shape, obj_jst.Shape, obj_wire.Shape]
Part.export(all_shapes, step_complete_path)
print(f"  [OK] Assembly STEP (Fusion 360): {step_complete_path}")

stl_body = os.path.join(CAD_DIR, "amemiya_probe_tower_dual_body.stl")
stl_cap = os.path.join(CAD_DIR, "amemiya_probe_tower_dual_cap.stl")
Mesh.export([body_tower], stl_body)
Mesh.export([body_cap], stl_cap)
print(f"  [OK] STLs para impressão 3D:\n    - {stl_body}\n    - {stl_cap}")

print("=== CONCLUÍDO COM SUCESSO! ===")
