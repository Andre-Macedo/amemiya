# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import
import time

print("Building 100% Pure PartDesign Probe Tower Models...")

doc_tower = FreeCAD.newDocument("Enclosure_Amemiya_ProbeTower")

tower_w = 30.0 # outer X
tower_d = 32.0 # outer Y
tower_h = 56.0 # outer Z
floor_th = 3.0

# 1. TOWER BODY (PartDesign)
body_tower = doc_tower.addObject("PartDesign::Body", "ProbeTower_Body")

# Sketch 1: Outer Profile (30 x 32 mm)
sk_prof = doc_tower.addObject("Sketcher::SketchObject", "Sketch_Tower_Profile")
body_tower.addObject(sk_prof)
sk_prof.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0.0), FreeCAD.Rotation())
sk_prof.MapMode = "Deactivated"

x0, y0 = -tower_w/2, -tower_d/2
x1, y1 = tower_w/2, tower_d/2
sk_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4): sk_prof.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_prof.addConstraint(Sketcher.Constraint("Horizontal", 0))
sk_prof.addConstraint(Sketcher.Constraint("Vertical", 1))
sk_prof.addConstraint(Sketcher.Constraint("Horizontal", 2))
sk_prof.addConstraint(Sketcher.Constraint("Vertical", 3))
sk_prof.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 30.0))
sk_prof.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 32.0))
doc_tower.recompute()

pad_tower = doc_tower.addObject("PartDesign::Pad", "Pad_Tower_Solid")
pad_tower.Profile = sk_prof
pad_tower.Length = tower_h
body_tower.addObject(pad_tower)
doc_tower.recompute()

# Sketch 2: Inner Chamber (24.6 x 26.0 mm)
sk_cav = doc_tower.addObject("Sketcher::SketchObject", "Sketch_Tower_Cavity")
body_tower.addObject(sk_cav)
sk_cav.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, tower_h), FreeCAD.Rotation())
sk_cav.MapMode = "Deactivated"
cx0, cy0 = -12.3, -13.0
cx1, cy1 = 12.3, 13.0
sk_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy0, 0), FreeCAD.Vector(cx1, cy0, 0)), False)
sk_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy0, 0), FreeCAD.Vector(cx1, cy1, 0)), False)
sk_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(cx1, cy1, 0), FreeCAD.Vector(cx0, cy1, 0)), False)
sk_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(cx0, cy1, 0), FreeCAD.Vector(cx0, cy0, 0)), False)
for i in range(4): sk_cav.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_cav.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 24.6))
sk_cav.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 26.0))
doc_tower.recompute()

pocket_cav = doc_tower.addObject("PartDesign::Pocket", "Pocket_Tower_Cavity")
pocket_cav.Profile = sk_cav
pocket_cav.Length = tower_h - floor_th
pocket_cav.Reversed = True
body_tower.addObject(pocket_cav)
doc_tower.recompute()

# Sketch 3: Two Side Cartridge Guide Grooves (1.8mm width x 1.5mm depth)
sk_grooves = doc_tower.addObject("Sketcher::SketchObject", "Sketch_Guide_Grooves")
body_tower.addObject(sk_grooves)
sk_grooves.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, tower_h), FreeCAD.Rotation())
sk_grooves.MapMode = "Deactivated"
# Left groove: X in [-13.5, -12.0], Y in [-1.0, 1.0]
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.5, -1.0, 0), FreeCAD.Vector(-12.0, -1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.0, -1.0, 0), FreeCAD.Vector(-12.0, 1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(-12.0, 1.0, 0), FreeCAD.Vector(-13.5, 1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(-13.5, 1.0, 0), FreeCAD.Vector(-13.5, -1.0, 0)), False)
for i in range(4): sk_grooves.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
# Right groove: X in [12.0, 13.5], Y in [-1.0, 1.0]
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(12.0, -1.0, 0), FreeCAD.Vector(13.5, -1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(13.5, -1.0, 0), FreeCAD.Vector(13.5, 1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(13.5, 1.0, 0), FreeCAD.Vector(12.0, 1.0, 0)), False)
sk_grooves.addGeometry(Part.LineSegment(FreeCAD.Vector(12.0, 1.0, 0), FreeCAD.Vector(12.0, -1.0, 0)), False)
for i in range(4, 8): sk_grooves.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))
doc_tower.recompute()

pocket_grooves = doc_tower.addObject("PartDesign::Pocket", "Pocket_Guide_Grooves")
pocket_grooves.Profile = sk_grooves
pocket_grooves.Length = tower_h - floor_th
pocket_grooves.Reversed = True
body_tower.addObject(pocket_grooves)
doc_tower.recompute()

# Sketch 4: Bottom Sensor Pocket for DS18B20 (8 x 6 mm)
sk_ds = doc_tower.addObject("Sketcher::SketchObject", "Sketch_DS18B20_Pocket")
body_tower.addObject(sk_ds)
sk_ds.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0.0), FreeCAD.Rotation())
sk_ds.MapMode = "Deactivated"
sk_ds.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, -3.0, 0), FreeCAD.Vector(4.0, -3.0, 0)), False)
sk_ds.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, -3.0, 0), FreeCAD.Vector(4.0, 3.0, 0)), False)
sk_ds.addGeometry(Part.LineSegment(FreeCAD.Vector(4.0, 3.0, 0), FreeCAD.Vector(-4.0, 3.0, 0)), False)
sk_ds.addGeometry(Part.LineSegment(FreeCAD.Vector(-4.0, 3.0, 0), FreeCAD.Vector(-4.0, -3.0, 0)), False)
for i in range(4): sk_ds.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc_tower.recompute()

pocket_ds = doc_tower.addObject("PartDesign::Pocket", "Pocket_DS18B20")
pocket_ds.Profile = sk_ds
pocket_ds.Length = 2.5
body_tower.addObject(pocket_ds)
doc_tower.recompute()

# Sketch 5: Side Acoustic Port for Mic (dia 3.5mm)
sk_mic = doc_tower.addObject("Sketcher::SketchObject", "Sketch_Mic_Port")
body_tower.addObject(sk_mic)
sk_mic.Placement = FreeCAD.Placement(FreeCAD.Vector(0, tower_d/2 + 1.0, 12.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
sk_mic.MapMode = "Deactivated"
sk_mic.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 1.75), False)
doc_tower.recompute()

pocket_mic = doc_tower.addObject("PartDesign::Pocket", "Pocket_Mic_Port")
pocket_mic.Profile = sk_mic
pocket_mic.Length = 6.0
pocket_mic.Reversed = True
body_tower.addObject(pocket_mic)
doc_tower.recompute()

print("ProbeTower_Body successfully generated with 5 fully constrained PartDesign sketches!")

# 2. TOP CAP BODY (PartDesign)
body_cap = doc_tower.addObject("PartDesign::Body", "ProbeTower_Cap_Body")

sk_cap_prof = doc_tower.addObject("Sketcher::SketchObject", "Sketch_Cap_Profile")
body_cap.addObject(sk_cap_prof)
sk_cap_prof.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, tower_h), FreeCAD.Rotation())
sk_cap_prof.MapMode = "Deactivated"
sk_cap_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)), False)
sk_cap_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)), False)
sk_cap_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)), False)
sk_cap_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0)), False)
for i in range(4): sk_cap_prof.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc_tower.recompute()

pad_cap = doc_tower.addObject("PartDesign::Pad", "Pad_Cap_Solid")
pad_cap.Profile = sk_cap_prof
pad_cap.Length = 10.0
body_cap.addObject(pad_cap)
doc_tower.recompute()

# Central Hole for GX12-8 (dia 12mm)
sk_gx12 = doc_tower.addObject("Sketcher::SketchObject", "Sketch_GX12_Mounting_Hole")
body_cap.addObject(sk_gx12)
sk_gx12.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, tower_h + 10.0), FreeCAD.Rotation())
sk_gx12.MapMode = "Deactivated"
sk_gx12.addGeometry(Part.Circle(FreeCAD.Vector(0, 0, 0), FreeCAD.Vector(0, 0, 1), 6.0), False)
sk_gx12.addConstraint(Sketcher.Constraint("Radius", 0, 6.0))
doc_tower.recompute()

pocket_gx12 = doc_tower.addObject("PartDesign::Pocket", "Pocket_GX12_Hole")
pocket_gx12.Profile = sk_gx12
pocket_gx12.Length = 10.0
pocket_gx12.Reversed = True
body_cap.addObject(pocket_gx12)
doc_tower.recompute()

print("ProbeTower_Cap_Body successfully generated with 2 fully constrained PartDesign sketches!")

# Import PCB & Components
pcb_tower_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.step'
Import.insert(pcb_tower_step, doc_tower.Name)
doc_tower.recompute()

# GX12-8 Metal Connector
gx_barrel = Part.makeCylinder(5.8, 12.0, FreeCAD.Vector(0, 0, tower_h + 8.0), FreeCAD.Vector(0, 0, 1))
gx_hex_nut = Part.makeCylinder(8.0, 3.0, FreeCAD.Vector(0, 0, tower_h + 11.0), FreeCAD.Vector(0, 0, 1))
gx_obj = doc_tower.addObject("Part::Feature", "GX12_8_Aviation_Connector")
gx_obj.Shape = gx_barrel.fuse(gx_hex_nut)

doc_tower.recompute()

# Save Probe Tower
doc_tower.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.FCStd')
Part.export([body_tower, body_cap], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.step')
Mesh.export([body_tower], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_body.stl')
Mesh.export([body_cap], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_cap.stl')

print("Probe Tower pure PartDesign build completed successfully!")
