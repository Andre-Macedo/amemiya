# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import
import time

t0 = time.time()

# ==============================================================================
# PART 1: MAIN NODE ENCLOSURE & COMPONENTS (PartDesign + Populated Assembly)
# ==============================================================================
doc_main_name = 'Enclosure_Amemiya_MainNode'
if doc_main_name in [d.Name for d in FreeCAD.listDocuments().values()]:
    FreeCAD.closeDocument(doc_main_name)
doc_main = FreeCAD.newDocument(doc_main_name)

# 1. Base Body (PartDesign)
body_base = doc_main.addObject('PartDesign::Body', 'MainNode_Base_Body')

# Sketch Base Outer (96 x 71 mm)
sk_base_outer = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Base_Outer')
body_base.addObject(sk_base_outer)
x0, y0 = -3.0, -68.0
x1, y1 = 93.0, 3.0
lines = [
    Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0))
]
for l in lines: sk_base_outer.addGeometry(l, False)
sk_base_outer.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
sk_base_outer.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
sk_base_outer.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
sk_base_outer.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))
sk_base_outer.addConstraint(Sketcher.Constraint('Horizontal', 0))
sk_base_outer.addConstraint(Sketcher.Constraint('Vertical', 1))
sk_base_outer.addConstraint(Sketcher.Constraint('Horizontal', 2))
sk_base_outer.addConstraint(Sketcher.Constraint('Vertical', 3))

pad_base = doc_main.addObject('PartDesign::Pad', 'Pad_Base_Solid')
pad_base.Profile = sk_base_outer
pad_base.Length = 17.0
body_base.addObject(pad_base)
body_base.Placement.Base = FreeCAD.Vector(0, 0, -7.0)
doc_main.recompute()

# Pocket Base Cavity (92 x 67 mm)
sk_base_cavity = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Base_Cavity')
body_base.addObject(sk_base_cavity)
cx0, cy0 = -1.0, -66.0
cx1, cy1 = 91.0, 1.0
c_lines = [
    Part.LineSegment(FreeCAD.Vector(cx0, cy0, 0), FreeCAD.Vector(cx1, cy0, 0)),
    Part.LineSegment(FreeCAD.Vector(cx1, cy0, 0), FreeCAD.Vector(cx1, cy1, 0)),
    Part.LineSegment(FreeCAD.Vector(cx1, cy1, 0), FreeCAD.Vector(cx0, cy1, 0)),
    Part.LineSegment(FreeCAD.Vector(cx0, cy1, 0), FreeCAD.Vector(cx0, cy0, 0))
]
for l in c_lines: sk_base_cavity.addGeometry(l, False)
sk_base_cavity.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
sk_base_cavity.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
sk_base_cavity.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
sk_base_cavity.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))

pocket_cavity = doc_main.addObject('PartDesign::Pocket', 'Pocket_Base_Cavity')
pocket_cavity.Profile = sk_base_cavity
pocket_cavity.Length = 15.0 # leaves 2mm floor
body_base.addObject(pocket_cavity)
doc_main.recompute()

# Standoffs in Base
pcb_holes = [(4.5, -4.5), (85.5, -4.5), (4.5, -60.5), (85.5, -60.5)]
sk_standoffs = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Base_Standoffs')
body_base.addObject(sk_standoffs)
for hx, hy in pcb_holes:
    sk_standoffs.addGeometry(Part.Circle(FreeCAD.Vector(hx, hy, 0), FreeCAD.Vector(0, 0, 1), 3.2), False)
doc_main.recompute()

pad_standoffs = doc_main.addObject('PartDesign::Pad', 'Pad_Standoffs')
pad_standoffs.Profile = sk_standoffs
pad_standoffs.Length = 5.0 # elevates PCB to Z=0
body_base.addObject(pad_standoffs)
doc_main.recompute()

# Standoff M3 pilot holes
sk_holes = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Standoff_Holes')
body_base.addObject(sk_holes)
for hx, hy in pcb_holes:
    sk_holes.addGeometry(Part.Circle(FreeCAD.Vector(hx, hy, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
doc_main.recompute()

pocket_holes = doc_main.addObject('PartDesign::Pocket', 'Pocket_Standoff_Holes')
pocket_holes.Profile = sk_holes
pocket_holes.Length = 4.8
body_base.addObject(pocket_holes)
doc_main.recompute()

# Side Flanges (Mounting Ears)
flange_l = Part.makeBox(12.0, 22.0, 3.0, FreeCAD.Vector(-15.0, -47.0, -7.0))
flange_l = flange_l.cut(Part.makeCylinder(2.5, 4.0, FreeCAD.Vector(-9.0, -36.0, -7.5), FreeCAD.Vector(0, 0, 1)))
flange_r = Part.makeBox(12.0, 22.0, 3.0, FreeCAD.Vector(93.0, -47.0, -7.0))
flange_r = flange_r.cut(Part.makeCylinder(2.5, 4.0, FreeCAD.Vector(99.0, -36.0, -7.5), FreeCAD.Vector(0, 0, 1)))
# Cable & USB Cutouts
cable_cut = Part.makeBox(4.0, 38.0, 9.0, FreeCAD.Vector(-4.0, -48.0, 1.0))
usbc_cut = Part.makeBox(12.0, 4.0, 7.0, FreeCAD.Vector(42.7, 0.0, 1.5))

base_solid = body_base.Shape.fuse(flange_l).fuse(flange_r).cut(cable_cut).cut(usbc_cut)
base_final = doc_main.addObject('Part::Feature', 'MainNode_Base')
base_final.Shape = base_solid
base_final.ViewObject.ShapeColor = (0.15, 0.35, 0.65) # Industrial Deep Blue
body_base.ViewObject.Visibility = False

# 2. Lid Body (PartDesign)
body_lid = doc_main.addObject('PartDesign::Body', 'MainNode_Lid_Body')
sk_lid_outer = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Lid_Outer')
body_lid.addObject(sk_lid_outer)
for l in lines: sk_lid_outer.addGeometry(l, False)
pad_lid = doc_main.addObject('PartDesign::Pad', 'Pad_Lid_Solid')
pad_lid.Profile = sk_lid_outer
pad_lid.Length = 15.0
body_lid.addObject(pad_lid)
body_lid.Placement.Base = FreeCAD.Vector(0, 0, 10.0)

# Lid Cavity
sk_lid_cavity = doc_main.addObject('Sketcher::SketchObject', 'Sketch_Lid_Cavity')
body_lid.addObject(sk_lid_cavity)
for l in c_lines: sk_lid_cavity.addGeometry(l, False)
pocket_lid = doc_main.addObject('PartDesign::Pocket', 'Pocket_Lid_Cavity')
pocket_lid.Profile = sk_lid_cavity
pocket_lid.Length = 12.8 # leaves 2.2mm ceiling
body_lid.addObject(pocket_lid)
doc_main.recompute()

# Lip, OLED cutout, Vents, Countersunk Holes
lip_out = Part.makeBox(91.5, 66.5, 2.5, FreeCAD.Vector(-0.75, -65.75, 7.5))
lip_in = Part.makeBox(89.5, 64.5, 3.5, FreeCAD.Vector(0.25, -64.75, 7.0))
lip = lip_out.cut(lip_in)

lid_solid = body_lid.Shape.fuse(lip)
# OLED Window
oled_win = Part.makeBox(28.0, 16.0, 6.0, FreeCAD.Vector(61.0, -33.0, 22.0))
lid_solid = lid_solid.cut(oled_win)
# Vents
for i in range(6):
    v = Part.makeBox(20.0, 2.2, 6.0, FreeCAD.Vector(38.7, -48.0 + i * 5.5, 22.0))
    lid_solid = lid_solid.cut(v)
# 4 M3 Countersunk Holes
for hx, hy in pcb_holes:
    h = Part.makeCylinder(1.65, 20.0, FreeCAD.Vector(hx, hy, 6.0), FreeCAD.Vector(0, 0, 1))
    sink = Part.makeCylinder(3.2, 2.5, FreeCAD.Vector(hx, hy, 22.5), FreeCAD.Vector(0, 0, 1))
    lid_solid = lid_solid.cut(h).cut(sink)

lid_final = doc_main.addObject('Part::Feature', 'MainNode_Lid')
lid_final.Shape = lid_solid
lid_final.ViewObject.ShapeColor = (0.22, 0.22, 0.25) # Matte Anthracite
body_lid.ViewObject.Visibility = False

# 3. PCB & Detailed Realistic 3D Components
# A. Import KiCad PCB
pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_main_node\amemiya_main_node.step'
Import.insert(pcb_step, doc_main.Name)
doc_main.recompute()
for obj in doc_main.Objects:
    if 'amemiya_main_node' in obj.Name or ('Part__Feature' in obj.Name and 'MainNode' not in obj.Name and 'M3' not in obj.Name):
        if hasattr(obj, 'ViewObject') and obj.ViewObject and hasattr(obj.ViewObject, 'ShapeColor'):
            obj.ViewObject.ShapeColor = (0.1, 0.55, 0.25) # PCB Mask Green

# B. ESP32-S3 DevKit Module (at X=36 to 61.4, Y=-56 to -8)
esp_pcb = Part.makeBox(25.4, 48.2, 1.2, FreeCAD.Vector(36.0, -56.0, 8.5))
sock_l = Part.makeBox(2.5, 48.2, 8.5, FreeCAD.Vector(34.8, -56.0, 0.0))
sock_r = Part.makeBox(2.5, 48.2, 8.5, FreeCAD.Vector(60.1, -56.0, 0.0))
esp_shield = Part.makeBox(18.0, 25.5, 3.0, FreeCAD.Vector(39.7, -42.0, 9.7))
esp_usbc = Part.makeBox(9.0, 7.5, 3.2, FreeCAD.Vector(44.2, -4.5, 9.7))
esp_ant = Part.makeBox(20.0, 8.0, 0.8, FreeCAD.Vector(38.7, -54.5, 9.7))

esp_comp = esp_pcb.fuse(sock_l).fuse(sock_r).fuse(esp_shield).fuse(esp_usbc).fuse(esp_ant)
esp_obj = doc_main.addObject('Part::Feature', 'ESP32_S3_DevKit_Module')
esp_obj.Shape = esp_comp
esp_obj.ViewObject.ShapeColor = (0.12, 0.12, 0.14) # Black & Metallic

# C. OLED 0.96 Display Module (at X=62 to 88, Y=-33 to -7)
oled_pcb = Part.makeBox(27.0, 27.0, 1.2, FreeCAD.Vector(62.0, -33.0, 11.0))
oled_glass = Part.makeBox(24.7, 14.0, 1.5, FreeCAD.Vector(63.2, -26.5, 12.2))
oled_comp = oled_pcb.fuse(oled_glass)
oled_obj = doc_main.addObject('Part::Feature', 'OLED_096_Display_Module')
oled_obj.Shape = oled_comp
oled_obj.ViewObject.ShapeColor = (0.05, 0.35, 0.65) # Display Blue

# D. LoRa Ra-02 Module (at X=70 to 86, Y=-41 to -25)
lora_pcb = Part.makeBox(16.0, 17.0, 1.2, FreeCAD.Vector(70.0, -41.0, 4.0))
lora_shield = Part.makeBox(12.0, 13.0, 2.5, FreeCAD.Vector(72.0, -39.0, 5.2))
lora_obj = doc_main.addObject('Part::Feature', 'LoRa_Ra02_Module')
lora_obj.Shape = lora_pcb.fuse(lora_shield)
lora_obj.ViewObject.ShapeColor = (0.1, 0.25, 0.5)

# E. Phoenix Industrial Terminal Blocks (Left Edge)
term_probe = Part.makeBox(8.0, 20.0, 10.0, FreeCAD.Vector(8.0, -28.0, 1.6))
term_tacho = Part.makeBox(8.0, 7.5, 10.0, FreeCAD.Vector(8.0, -37.0, 1.6))
term_amp = Part.makeBox(8.0, 5.0, 10.0, FreeCAD.Vector(8.0, -46.0, 1.6))
term_all = term_probe.fuse(term_tacho).fuse(term_amp)
term_obj = doc_main.addObject('Part::Feature', 'Terminal_Blocks_Phoenix')
term_obj.Shape = term_all
term_obj.ViewObject.ShapeColor = (0.15, 0.55, 0.25) # Industrial Green

# F. 4x M3 Screws
for i, (hx, hy) in enumerate(pcb_holes):
    shaft = Part.makeCylinder(1.45, 16.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, -1))
    head = Part.makeCylinder(2.7, 3.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, 1))
    s_obj = doc_main.addObject('Part::Feature', 'M3_Screw_' + str(i+1))
    s_obj.Shape = shaft.fuse(head)
    s_obj.ViewObject.ShapeColor = (0.85, 0.85, 0.88)

doc_main.recompute()

# Exploded View for rendering
lid_final.Placement.Base = FreeCAD.Vector(0, 0, 35.0)
esp_obj.Placement.Base = FreeCAD.Vector(0, 0, 15.0)
oled_obj.Placement.Base = FreeCAD.Vector(0, 0, 18.0)
lora_obj.Placement.Base = FreeCAD.Vector(0, 0, 15.0)
term_obj.Placement.Base = FreeCAD.Vector(0, 0, 10.0)
for obj in doc_main.Objects:
    if 'amemiya_main_node' in obj.Name or ('Part__Feature' in obj.Name and 'MainNode' not in obj.Name and 'M3' not in obj.Name and 'ESP' not in obj.Name and 'OLED' not in obj.Name and 'LoRa' not in obj.Name and 'Terminal' not in obj.Name):
        obj.Placement.Base = FreeCAD.Vector(0, 0, 8.0)
for i in range(1, 5):
    s = doc_main.getObject('M3_Screw_' + str(i))
    if s: s.Placement.Base = FreeCAD.Vector(0, 0, 55.0)

doc_main.recompute()

Mesh.export([base_final], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_base.stl')
Mesh.export([lid_final], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_lid.stl')
Part.export([base_final, lid_final], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.step')
doc_main.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd')

print('Populated Main Node generated in ' + str(round(time.time() - t0, 2)) + 's')
