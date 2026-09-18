# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Mesh
import time

t0 = time.time()
doc_name = 'Enclosure_Amemiya_MainNode'
if doc_name in [d.Name for d in FreeCAD.listDocuments().values()]:
    FreeCAD.closeDocument(doc_name)
doc = FreeCAD.newDocument(doc_name)

pcb_w = 90.0
pcb_d = 65.0
clearance_xy = 1.0
wall_th = 2.0
floor_th = 2.0
standoff_h = 5.0
standoff_r = 3.2
standoff_hole_r = 1.3
standoff_hole_d = 4.5

inner_x0 = -clearance_xy # -1.0
inner_y0 = -pcb_d - clearance_xy # -66.0
inner_w = pcb_w + 2 * clearance_xy # 92.0
inner_d = pcb_d + 2 * clearance_xy # 67.0

outer_x0 = inner_x0 - wall_th # -3.0
outer_y0 = inner_y0 - wall_th # -68.0
outer_w = inner_w + 2 * wall_th # 96.0
outer_d = inner_d + 2 * wall_th # 71.0

base_z0 = -(standoff_h + floor_th) # -7.0
base_floor_top = -standoff_h       # -5.0
base_z1 = 10.0                     # 10.0
base_h = base_z1 - base_z0         # 17.0

# 1. Base Tray
outer_box = Part.makeBox(outer_w, outer_d, base_h, FreeCAD.Vector(outer_x0, outer_y0, base_z0))
inner_box = Part.makeBox(inner_w, inner_d, base_h - floor_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, base_floor_top))
base_tray = outer_box.cut(inner_box)

# 2. Standoffs
pcb_holes = [(4.5, -4.5), (85.5, -4.5), (4.5, -60.5), (85.5, -60.5)]
posts = []
holes = []
for hx, hy in pcb_holes:
    posts.append(Part.makeCylinder(standoff_r, standoff_h, FreeCAD.Vector(hx, hy, base_floor_top), FreeCAD.Vector(0, 0, 1)))
    holes.append(Part.makeCylinder(standoff_hole_r, standoff_hole_d + 0.5, FreeCAD.Vector(hx, hy, -standoff_hole_d), FreeCAD.Vector(0, 0, 1)))

for p in posts:
    base_tray = base_tray.fuse(p)
for h in holes:
    base_tray = base_tray.cut(h)

# 3. Flanges
flange_w = 12.0
flange_d = 22.0
flange_th = 3.0
flange_l = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 - flange_w, outer_y0 + (outer_d - flange_d)/2, base_z0))
flange_l = flange_l.cut(Part.makeCylinder(2.5, flange_th + 1.0, FreeCAD.Vector(outer_x0 - flange_w/2, outer_y0 + outer_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1)))

flange_r = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 + outer_w, outer_y0 + (outer_d - flange_d)/2, base_z0))
flange_r = flange_r.cut(Part.makeCylinder(2.5, flange_th + 1.0, FreeCAD.Vector(outer_x0 + outer_w + flange_w/2, outer_y0 + outer_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1)))

base_tray = base_tray.fuse(flange_l).fuse(flange_r)

# 4. Cutouts
cable_cut = Part.makeBox(wall_th + 2.0, 36.0, 9.0, FreeCAD.Vector(outer_x0 - 1.0, -48.0, 1.0))
usbc_cut = Part.makeBox(12.0, wall_th + 2.0, 7.0, FreeCAD.Vector(42.7, outer_y0 + outer_d - wall_th - 1.0, 1.5))
base_tray = base_tray.cut(cable_cut).cut(usbc_cut)

base_obj = doc.addObject('Part::Feature', 'MainNode_Base')
base_obj.Shape = base_tray
base_obj.ViewObject.ShapeColor = (0.15, 0.35, 0.65)

# 5. LID
lid_h = 15.0
lid_ceiling_th = 2.2
lid_z0 = base_z1
lid_z1 = lid_z0 + lid_h

lid_outer = Part.makeBox(outer_w, outer_d, lid_h, FreeCAD.Vector(outer_x0, outer_y0, lid_z0))
lid_inner = Part.makeBox(inner_w, inner_d, lid_h - lid_ceiling_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, lid_z0 - 0.5))
lid_tray = lid_outer.cut(lid_inner)

# Lip
lip_clr = 0.25
lip_w = inner_w - 2 * lip_clr
lip_d = inner_d - 2 * lip_clr
lip_h = 2.5
lip_out = Part.makeBox(lip_w, lip_d, lip_h, FreeCAD.Vector(inner_x0 + lip_clr, inner_y0 + lip_clr, lid_z0 - lip_h))
lip_in = Part.makeBox(lip_w - 2.0, lip_d - 2.0, lip_h + 1.0, FreeCAD.Vector(inner_x0 + lip_clr + 1.0, inner_y0 + lip_clr + 1.0, lid_z0 - lip_h - 0.5))
lid_tray = lid_tray.fuse(lip_out.cut(lip_in))

# Holes in Lid
for hx, hy in pcb_holes:
    hole_m3 = Part.makeCylinder(1.65, lid_h + lip_h + 2.0, FreeCAD.Vector(hx, hy, lid_z0 - lip_h - 0.5), FreeCAD.Vector(0, 0, 1))
    sink_m3 = Part.makeCylinder(3.2, 2.5, FreeCAD.Vector(hx, hy, lid_z1 - 2.5), FreeCAD.Vector(0, 0, 1))
    lid_tray = lid_tray.cut(hole_m3).cut(sink_m3)

# OLED Cutout
oled = Part.makeBox(28.0, 16.0, lid_ceiling_th + 4.0, FreeCAD.Vector(61.0, -33.0, lid_z1 - lid_ceiling_th - 1.0))
lid_tray = lid_tray.cut(oled)

# Vents
for i in range(6):
    v = Part.makeBox(20.0, 2.2, lid_ceiling_th + 4.0, FreeCAD.Vector(38.7, -48.0 + i * 5.5, lid_z1 - lid_ceiling_th - 1.0))
    lid_tray = lid_tray.cut(v)

lid_obj = doc.addObject('Part::Feature', 'MainNode_Lid')
lid_obj.Shape = lid_tray
lid_obj.ViewObject.ShapeColor = (0.22, 0.22, 0.25)

doc.recompute()

Mesh.export([base_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_base.stl')
Mesh.export([lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_lid.stl')
Part.export([base_obj, lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.step')
doc.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd')

print(f'Done in {time.time() - t0:.2f}s')
