# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import
import time

t0 = time.time()

doc_tower_name = 'Enclosure_Amemiya_ProbeTower'
if doc_tower_name in [d.Name for d in FreeCAD.listDocuments().values()]:
    FreeCAD.closeDocument(doc_tower_name)
doc_tower = FreeCAD.newDocument(doc_tower_name)

# Dimensions for Cartridge Tower (Probe Ruler / Estilo Tractian)
# PCB: 24.0mm width x 48.0mm height x 1.6mm th
pcb_w = 24.0
pcb_h = 48.0
pcb_th = 1.6

tower_w = 30.0 # outer X
tower_d = 32.0 # outer Y
tower_h = 56.0 # outer Z (vertical tower)
wall_th = 2.4
floor_th = 3.0

# 1. Tower Body (PartDesign)
body_tower = doc_tower.addObject('PartDesign::Body', 'ProbeTower_Body')

# Sketch Profile (30 x 32 mm)
sk_tower_outer = doc_tower.addObject('Sketcher::SketchObject', 'Sketch_Tower_Outer')
body_tower.addObject(sk_tower_outer)
x0, y0 = -tower_w/2, -tower_d/2
x1, y1 = tower_w/2, tower_d/2
lines = [
    Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0))
]
for l in lines: sk_tower_outer.addGeometry(l, False)
sk_tower_outer.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
sk_tower_outer.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
sk_tower_outer.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
sk_tower_outer.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))
sk_tower_outer.addConstraint(Sketcher.Constraint('Horizontal', 0))
sk_tower_outer.addConstraint(Sketcher.Constraint('Vertical', 1))
sk_tower_outer.addConstraint(Sketcher.Constraint('Horizontal', 2))
sk_tower_outer.addConstraint(Sketcher.Constraint('Vertical', 3))

pad_tower = doc_tower.addObject('PartDesign::Pad', 'Pad_Tower_Solid')
pad_tower.Profile = sk_tower_outer
pad_tower.Length = tower_h
body_tower.addObject(pad_tower)
doc_tower.recompute()

# Inner Cavity with Guide Slots for PCB Slide-in
# Main inner chamber: 24.6mm x 26.0mm
sk_tower_cavity = doc_tower.addObject('Sketcher::SketchObject', 'Sketch_Tower_Cavity')
body_tower.addObject(sk_tower_cavity)
cx0, cy0 = -12.3, -13.0
cx1, cy1 = 12.3, 13.0
c_lines = [
    Part.LineSegment(FreeCAD.Vector(cx0, cy0, 0), FreeCAD.Vector(cx1, cy0, 0)),
    Part.LineSegment(FreeCAD.Vector(cx1, cy0, 0), FreeCAD.Vector(cx1, cy1, 0)),
    Part.LineSegment(FreeCAD.Vector(cx1, cy1, 0), FreeCAD.Vector(cx0, cy1, 0)),
    Part.LineSegment(FreeCAD.Vector(cx0, cy1, 0), FreeCAD.Vector(cx0, cy0, 0))
]
for l in c_lines: sk_tower_cavity.addGeometry(l, False)
sk_tower_cavity.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
sk_tower_cavity.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
sk_tower_cavity.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
sk_tower_cavity.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))

pocket_tower = doc_tower.addObject('PartDesign::Pocket', 'Pocket_Tower_Cavity')
pocket_tower.Profile = sk_tower_cavity
pocket_tower.Length = tower_h - floor_th
body_tower.addObject(pocket_tower)
doc_tower.recompute()

# Two Vertical Guide Grooves (1.8mm wide, 1.2mm deep) for the 24mm PCB
# Left groove: X in [-13.5, -12.3], Y in [-1.0, 1.0]
# Right groove: X in [12.3, 13.5], Y in [-1.0, 1.0]
groove_l = Part.makeBox(1.5, 2.0, tower_h - floor_th + 1.0, FreeCAD.Vector(-13.5, -1.0, floor_th))
groove_r = Part.makeBox(1.5, 2.0, tower_h - floor_th + 1.0, FreeCAD.Vector(12.0, -1.0, floor_th))

# Bottom Sensor Pocket for DS18B20 flat contact face: 8 x 6 x 2.2mm
ds18b20_pocket = Part.makeBox(8.0, 6.0, 3.5, FreeCAD.Vector(-4.0, -3.0, -0.5))

# Side Acoustic Port for INMP441 Mic: dia 3.5mm
mic_port = Part.makeCylinder(1.75, 8.0, FreeCAD.Vector(0, 12.0, 12.0), FreeCAD.Vector(0, 1, 0))

# Flanges at Bottom for Bearing / UCP 204 Mounting (2x M3 ears)
flange_front = Part.makeBox(18.0, 8.0, 3.0, FreeCAD.Vector(-9.0, tower_d/2, 0.0))
flange_front = flange_front.cut(Part.makeCylinder(1.7, 4.0, FreeCAD.Vector(0, tower_d/2 + 4.0, -0.5), FreeCAD.Vector(0, 0, 1)))

flange_back = Part.makeBox(18.0, 8.0, 3.0, FreeCAD.Vector(-9.0, -tower_d/2 - 8.0, 0.0))
flange_back = flange_back.cut(Part.makeCylinder(1.7, 4.0, FreeCAD.Vector(0, -tower_d/2 - 4.0, -0.5), FreeCAD.Vector(0, 0, 1)))

# Dual Magnet Cavities on bottom (dia 10.2mm x 2.2mm)
mag1 = Part.makeCylinder(5.1, 2.2, FreeCAD.Vector(-8.0, 0, -0.1), FreeCAD.Vector(0, 0, 1))
mag2 = Part.makeCylinder(5.1, 2.2, FreeCAD.Vector(8.0, 0, -0.1), FreeCAD.Vector(0, 0, 1))

tower_solid = body_tower.Shape.fuse(flange_front).fuse(flange_back).cut(groove_l).cut(groove_r).cut(ds18b20_pocket).cut(mic_port).cut(mag1).cut(mag2)

tower_obj = doc_tower.addObject('Part::Feature', 'ProbeTower_Body_Final')
tower_obj.Shape = tower_solid
tower_obj.ViewObject.ShapeColor = (0.78, 0.42, 0.12) # Industrial Amber / Safety Orange
body_tower.ViewObject.Visibility = False

# 2. TOP CAP (With GX12-8 Aviation Connector Mounting)
cap_h = 10.0
cap_ceiling_th = 2.5
cap_outer = Part.makeBox(tower_w, tower_d, cap_h, FreeCAD.Vector(-tower_w/2, -tower_d/2, tower_h))
cap_inner = Part.makeBox(tower_w - 2*wall_th, tower_d - 2*wall_th, cap_h - cap_ceiling_th + 1.0, FreeCAD.Vector(-tower_w/2 + wall_th, -tower_d/2 + wall_th, tower_h - 0.5))
cap_tray = cap_outer.cut(cap_inner)

# Interlocking Rim into Tower
cap_lip_out = Part.makeBox(24.0, 25.4, 2.5, FreeCAD.Vector(-12.0, -12.7, tower_h - 2.5))
cap_lip_in = Part.makeBox(21.0, 22.4, 3.5, FreeCAD.Vector(-10.5, -11.2, tower_h - 3.0))
cap_lip = cap_lip_out.cut(cap_lip_in)
cap_solid = cap_tray.fuse(cap_lip)

# Central Hole for GX12-8 Aviation Connector (dia 12.0mm)
gx12_hole = Part.makeCylinder(6.0, cap_ceiling_th + 2.0, FreeCAD.Vector(0, 0, tower_h + cap_h - cap_ceiling_th - 1.0), FreeCAD.Vector(0, 0, 1))
# 2 Fastening Screw Holes (M2.5)
cap_screw1 = Part.makeCylinder(1.35, cap_h + 4.0, FreeCAD.Vector(-11.5, 0, tower_h - 2.0), FreeCAD.Vector(0, 0, 1))
cap_screw2 = Part.makeCylinder(1.35, cap_h + 4.0, FreeCAD.Vector(11.5, 0, tower_h - 2.0), FreeCAD.Vector(0, 0, 1))

cap_solid = cap_solid.cut(gx12_hole).cut(cap_screw1).cut(cap_screw2)

cap_obj = doc_tower.addObject('Part::Feature', 'ProbeTower_TopCap')
cap_obj.Shape = cap_solid
cap_obj.ViewObject.ShapeColor = (0.2, 0.2, 0.22) # Dark Anthracite

# 3. GX12-8 Industrial Aviation Metal Connector 3D Model
# Outer threaded metal barrel (dia 12mm, h 14mm)
gx_barrel = Part.makeCylinder(5.8, 12.0, FreeCAD.Vector(0, 0, tower_h + cap_h - 2.0), FreeCAD.Vector(0, 0, 1))
gx_hex_nut = Part.makeCylinder(8.0, 3.0, FreeCAD.Vector(0, 0, tower_h + cap_h + 1.0), FreeCAD.Vector(0, 0, 1))
gx_connector = gx_barrel.fuse(gx_hex_nut)
gx_obj = doc_tower.addObject('Part::Feature', 'GX12_8_Aviation_Connector')
gx_obj.Shape = gx_connector
gx_obj.ViewObject.ShapeColor = (0.85, 0.85, 0.9) # Shiny Chrome Metal

# 4. Import PCB (amemiya_probe_tower.step)
pcb_tower_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_tower\amemiya_probe_tower.step'
Import.insert(pcb_tower_step, doc_tower.Name)
doc_tower.recompute()

# Style PCB & Place Vertically inside Tower
for obj in doc_tower.Objects:
    if 'amemiya_probe_tower' in obj.Name or ('Part__Feature' in obj.Name and 'ProbeTower' not in obj.Name and 'GX12' not in obj.Name):
        if hasattr(obj, 'ViewObject') and obj.ViewObject and hasattr(obj.ViewObject, 'ShapeColor'):
            obj.ViewObject.ShapeColor = (0.1, 0.55, 0.25)
        # Position PCB vertically inside tower guide slots
        # PCB in KiCad is XY plane, rotate 90 deg around X axis so it stands vertically along Z
        obj.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0.8, 4.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))

# 5. Add 3D Components on the Probe Tower Cartridge:
# A. DS18B20 TO-92 Temperature Sensor at bottom tip
ds_body = Part.makeCylinder(2.2, 5.0, FreeCAD.Vector(0, -1.0, 4.0), FreeCAD.Vector(0, 0, 1))
ds_flat = Part.makeBox(4.5, 2.0, 5.0, FreeCAD.Vector(-2.25, -2.0, 4.0))
ds_sensor = ds_body.cut(ds_flat)
ds_obj = doc_tower.addObject('Part::Feature', 'DS18B20_Temp_Sensor')
ds_obj.Shape = ds_sensor
ds_obj.ViewObject.ShapeColor = (0.1, 0.1, 0.1)

# B. ADXL345 Accelerometer Breakout Board (Left column)
adxl_pcb = Part.makeBox(12.0, 1.2, 19.0, FreeCAD.Vector(-10.5, 1.0, 15.0))
adxl_ic = Part.makeBox(5.0, 1.2, 5.0, FreeCAD.Vector(-7.0, 2.2, 22.0))
adxl_obj = doc_tower.addObject('Part::Feature', 'ADXL345_Accelerometer_Module')
adxl_obj.Shape = adxl_pcb.fuse(adxl_ic)
adxl_obj.ViewObject.ShapeColor = (0.1, 0.25, 0.6) # Blue PCB Module

# C. INMP441 Microphone Breakout (Right column)
mic_pcb = Part.makeBox(10.0, 1.2, 14.0, FreeCAD.Vector(1.0, 1.0, 12.0))
mic_can = Part.makeCylinder(1.5, 1.5, FreeCAD.Vector(6.0, 1.0, 18.0), FreeCAD.Vector(0, 1, 0))
mic_obj = doc_tower.addObject('Part::Feature', 'INMP441_Microphone_Module')
mic_obj.Shape = mic_pcb.fuse(mic_can)
mic_obj.ViewObject.ShapeColor = (0.12, 0.12, 0.12) # Black PCB Module

# D. Top J_CABLE Connector (1x8 pin header connecting to GX12-8)
cable_hdr = Part.makeBox(20.0, 2.5, 8.0, FreeCAD.Vector(-10.0, 0.0, 44.0))
hdr_obj = doc_tower.addObject('Part::Feature', 'J_CABLE_Header')
hdr_obj.Shape = cable_hdr
hdr_obj.ViewObject.ShapeColor = (0.15, 0.15, 0.15)

doc_tower.recompute()

# Exploded View Setup
cap_obj.Placement.Base = FreeCAD.Vector(0, 0, 30.0)
gx_obj.Placement.Base = FreeCAD.Vector(0, 0, 40.0)
rot_vertical = FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90)
for obj in doc_tower.Objects:
    if 'amemiya_probe_tower' in obj.Name or ('Part__Feature' in obj.Name and 'ProbeTower' not in obj.Name and 'GX12' not in obj.Name and 'DS18' not in obj.Name and 'ADXL' not in obj.Name and 'INMP' not in obj.Name and 'J_CABLE' not in obj.Name):
        obj.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0.0, 68.0), rot_vertical)
ds_obj.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 22.0), FreeCAD.Rotation())
adxl_obj.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 22.0), FreeCAD.Rotation())
mic_obj.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 22.0), FreeCAD.Rotation())
hdr_obj.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 22.0), FreeCAD.Rotation())

doc_tower.recompute()

# Export STL, STEP, FCStd
Mesh.export([tower_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_body.stl')
Mesh.export([cap_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_cap.stl')
Part.export([tower_obj, cap_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.step')
doc_tower.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_tower_enclosure.FCStd')

print('Probe Tower Cartridge complete in ' + str(round(time.time() - t0, 2)) + 's')
