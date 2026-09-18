# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Mesh
import Import

doc_name = 'Enclosure_Amemiya_MainNode'
try:
    doc = FreeCAD.getDocument(doc_name)
    if doc:
        FreeCAD.closeDocument(doc_name)
except Exception:
    pass
doc = FreeCAD.newDocument(doc_name)

# Import PCB STEP
pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_main_node\amemiya_main_node.step'
Import.insert(pcb_step, doc_name)

# Dimensions
pcb_w = 90.0
pcb_d = 65.0
pcb_th = 1.6

clearance_xy = 1.0
wall_th = 2.0
floor_th = 2.0
standoff_h = 5.0
standoff_r = 3.5
standoff_hole_r = 1.35 # for M3 thread or heat-set insert
standoff_hole_d = 4.8

inner_x0 = -clearance_xy
inner_y0 = -pcb_d - clearance_xy
inner_w = pcb_w + 2 * clearance_xy # 92.0
inner_d = pcb_d + 2 * clearance_xy # 67.0

outer_x0 = inner_x0 - wall_th # -3.0
outer_y0 = inner_y0 - wall_th # -68.0
outer_w = inner_w + 2 * wall_th # 96.0
outer_d = inner_d + 2 * wall_th # 71.0

base_z0 = -(standoff_h + floor_th) # -7.0
base_floor_top = -standoff_h       # -5.0
base_z1 = 10.0                     # +10.0
base_h = base_z1 - base_z0         # 17.0

# 1. Base Outer Shell
outer_box = Part.makeBox(outer_w, outer_d, base_h, FreeCAD.Vector(outer_x0, outer_y0, base_z0))
inner_box = Part.makeBox(inner_w, inner_d, base_h - floor_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, base_floor_top))
base_tray = outer_box.cut(inner_box)

# 2. PCB & Assembly Standoffs (4 unified M3 posts)
pcb_holes = [
    (4.5, -4.5),
    (85.5, -4.5),
    (4.5, -60.5),
    (85.5, -60.5)
]

for hx, hy in pcb_holes:
    post = Part.makeCylinder(standoff_r, standoff_h, FreeCAD.Vector(hx, hy, base_floor_top), FreeCAD.Vector(0, 0, 1))
    hole = Part.makeCylinder(standoff_hole_r, standoff_hole_d + 0.5, FreeCAD.Vector(hx, hy, -standoff_hole_d), FreeCAD.Vector(0, 0, 1))
    post = post.cut(hole)
    base_tray = base_tray.fuse(post)

# 3. External Mounting Flanges (Ears) for DIN/Panel Mounting
flange_w = 12.0
flange_d = 20.0
flange_th = 3.0
# Left flange
flange_left = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 - flange_w, outer_y0 + (outer_d - flange_d)/2, base_z0))
slot_left = Part.makeCylinder(2.25, flange_th + 1.0, FreeCAD.Vector(outer_x0 - flange_w/2, outer_y0 + outer_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1))
flange_left = flange_left.cut(slot_left)
# Right flange
flange_right = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 + outer_w, outer_y0 + (outer_d - flange_d)/2, base_z0))
slot_right = Part.makeCylinder(2.25, flange_th + 1.0, FreeCAD.Vector(outer_x0 + outer_w + flange_w/2, outer_y0 + outer_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1))
flange_right = flange_right.cut(slot_right)

base_tray = base_tray.fuse(flange_left).fuse(flange_right)

# 4. Cutouts in Base
# Wiring terminal block slot on left wall
cable_cutout = Part.makeBox(wall_th + 2.0, 38.0, 9.0, FreeCAD.Vector(outer_x0 - 1.0, -48.0, 1.0))
base_tray = base_tray.cut(cable_cutout)

# USB-C port cutout on top wall
usbc_cutout = Part.makeBox(12.0, wall_th + 2.0, 7.0, FreeCAD.Vector(42.7, outer_y0 + outer_d - wall_th - 1.0, 1.5))
base_tray = base_tray.cut(usbc_cutout)

# Base Object
base_obj = doc.addObject('Part::Feature', 'MainNode_Base')
base_obj.Shape = base_tray
base_obj.ViewObject.ShapeColor = (0.15, 0.35, 0.65) # Industrial Deep Blue

# 5. LID (Top Cover)
lid_h = 15.0
lid_ceiling_th = 2.2
lid_z0 = base_z1 # 10.0
lid_z1 = lid_z0 + lid_h # 25.0

lid_outer = Part.makeBox(outer_w, outer_d, lid_h, FreeCAD.Vector(outer_x0, outer_y0, lid_z0))
lid_inner = Part.makeBox(inner_w, inner_d, lid_h - lid_ceiling_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, lid_z0 - 0.5))
lid_tray = lid_outer.cut(lid_inner)

# Interlocking Rim (extends 2.5mm into base)
lip_clearance = 0.25
lip_w = inner_w - 2 * lip_clearance
lip_d = inner_d - 2 * lip_clearance
lip_x0 = inner_x0 + lip_clearance
lip_y0 = inner_y0 + lip_clearance
lip_h = 2.5

lip_outer = Part.makeBox(lip_w, lip_d, lip_h, FreeCAD.Vector(lip_x0, lip_y0, lid_z0 - lip_h))
lip_inner = Part.makeBox(lip_w - 2.0, lip_d - 2.0, lip_h + 1.0, FreeCAD.Vector(lip_x0 + 1.0, lip_y0 + 1.0, lid_z0 - lip_h - 0.5))
lip_rim = lip_outer.cut(lip_inner)
lid_tray = lid_tray.fuse(lip_rim)

# 4 Clamp Standoffs in Lid that extend from ceiling down to PCB top (Z = 1.6)
for hx, hy in pcb_holes:
    post_lid = Part.makeCylinder(standoff_r, lid_z1 - lid_ceiling_th - 1.6, FreeCAD.Vector(hx, hy, 1.6), FreeCAD.Vector(0, 0, 1))
    lid_tray = lid_tray.fuse(post_lid)
    # Screw clearance hole (M3 through-hole dia 3.3mm)
    s_hole = Part.makeCylinder(1.65, lid_h + lip_h + 2.0, FreeCAD.Vector(hx, hy, 1.0), FreeCAD.Vector(0, 0, 1))
    # Countersink for M3 flat head screw (dia 6.2mm, depth 2.5mm)
    s_sink = Part.makeCylinder(3.1, 2.5, FreeCAD.Vector(hx, hy, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
    lid_tray = lid_tray.cut(s_hole).cut(s_sink)

# OLED Window (28mm x 16mm)
oled_cutout = Part.makeBox(28.0, 16.0, lid_ceiling_th + 4.0, FreeCAD.Vector(61.0, -33.0, lid_z1 - lid_ceiling_th - 1.0))
lid_tray = lid_tray.cut(oled_cutout)

# Ventilation Grille (6 slots)
for i in range(6):
    slot_y = -48.0 + i * 5.5
    vent = Part.makeBox(20.0, 2.5, lid_ceiling_th + 4.0, FreeCAD.Vector(38.7, slot_y, lid_z1 - lid_ceiling_th - 1.0))
    lid_tray = lid_tray.cut(vent)

lid_obj = doc.addObject('Part::Feature', 'MainNode_Lid')
lid_obj.Shape = lid_tray
lid_obj.ViewObject.ShapeColor = (0.22, 0.22, 0.25) # Matte Anthracite

doc.recompute()

# Save & Export
Mesh.export([base_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\main_node_base.stl')
Mesh.export([lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\main_node_lid.stl')
Part.export([base_obj, lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.step')
doc.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd')

print('Main Node Enclosure complete and exported!')
