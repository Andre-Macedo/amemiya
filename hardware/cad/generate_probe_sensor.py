# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Mesh
import time

t0 = time.time()
doc_name = 'Enclosure_Amemiya_Probe'

if doc_name in [d.Name for d in FreeCAD.listDocuments().values()]:
    FreeCAD.closeDocument(doc_name)
doc = FreeCAD.newDocument(doc_name)

# Probe PCB Dimensions (in mm)
pcb_w = 46.0
pcb_d = 40.0
clearance_xy = 1.0
wall_th = 2.0
floor_th = 2.0
standoff_h = 4.0
standoff_r = 3.2
standoff_hole_r = 1.3
standoff_hole_d = 3.8

inner_x0 = -clearance_xy         # -1.0
inner_y0 = -pcb_d - clearance_xy # -41.0
inner_w = pcb_w + 2 * clearance_xy # 48.0
inner_d = pcb_d + 2 * clearance_xy # 42.0

outer_x0 = inner_x0 - wall_th    # -3.0
outer_y0 = inner_y0 - wall_th    # -43.0
outer_w = inner_w + 2 * wall_th  # 52.0
outer_d = inner_d + 2 * wall_th  # 46.0

base_z0 = -(standoff_h + floor_th) # -6.0
base_floor_top = -standoff_h       # -4.0
base_z1 = 8.0                      # +8.0
base_h = base_z1 - base_z0         # 14.0

# 1. Base Tray
outer_box = Part.makeBox(outer_w, outer_d, base_h, FreeCAD.Vector(outer_x0, outer_y0, base_z0))
inner_box = Part.makeBox(inner_w, inner_d, base_h - floor_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, base_floor_top))
base_tray = outer_box.cut(inner_box)

# 2. PCB & Assembly Standoffs (4 unified M3 posts)
pcb_holes = [
    (4.5, -4.5),
    (41.5, -4.5),
    (4.5, -35.5),
    (41.5, -35.5)
]

posts = []
holes = []
for hx, hy in pcb_holes:
    posts.append(Part.makeCylinder(standoff_r, standoff_h, FreeCAD.Vector(hx, hy, base_floor_top), FreeCAD.Vector(0, 0, 1)))
    holes.append(Part.makeCylinder(standoff_hole_r, standoff_hole_d + 0.5, FreeCAD.Vector(hx, hy, -standoff_hole_d), FreeCAD.Vector(0, 0, 1)))

for p in posts:
    base_tray = base_tray.fuse(p)
for h in holes:
    base_tray = base_tray.cut(h)

# 3. Acoustic Port & Sensor Windows on Floor
# Acoustic port under J_MIC at (19.0, -20.0)
mic_port = Part.makeCylinder(2.0, floor_th + 2.0, FreeCAD.Vector(19.0, -20.0, base_z0 - 1.0), FreeCAD.Vector(0, 0, 1))
# Temperature sensor pocket at (30.0, -13.0)
temp_pocket = Part.makeBox(8.0, 8.0, floor_th + 2.0, FreeCAD.Vector(26.0, -17.0, base_z0 - 1.0))
base_tray = base_tray.cut(mic_port).cut(temp_pocket)

# 4. Cable Gland / Wire Slot on Right Wall (X = outer_x0 + outer_w)
cable_cut = Part.makeBox(wall_th + 2.0, 10.0, 7.0, FreeCAD.Vector(outer_x0 + outer_w - wall_th - 1.0, -24.0, 1.0))
base_tray = base_tray.cut(cable_cut)

# 5. External Mounting Flanges (Ears) for Probe Attachment
flange_w = 16.0
flange_d = 10.0
flange_th = 3.0
# Top flange
flange_top = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 + (outer_w - flange_w)/2, outer_y0 + outer_d, base_z0))
flange_top = flange_top.cut(Part.makeCylinder(2.2, flange_th + 1.0, FreeCAD.Vector(outer_x0 + outer_w/2, outer_y0 + outer_d + flange_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1)))
# Bottom flange
flange_bot = Part.makeBox(flange_w, flange_d, flange_th, FreeCAD.Vector(outer_x0 + (outer_w - flange_w)/2, outer_y0 - flange_d, base_z0))
flange_bot = flange_bot.cut(Part.makeCylinder(2.2, flange_th + 1.0, FreeCAD.Vector(outer_x0 + outer_w/2, outer_y0 - flange_d/2, base_z0 - 0.5), FreeCAD.Vector(0, 0, 1)))

base_tray = base_tray.fuse(flange_top).fuse(flange_bot)

base_obj = doc.addObject('Part::Feature', 'ProbeSensor_Base')
base_obj.Shape = base_tray
base_obj.ViewObject.ShapeColor = (0.75, 0.45, 0.15) # Industrial Safety Amber / Orange

# 6. LID (Top Cover)
lid_h = 10.0
lid_ceiling_th = 2.0
lid_z0 = base_z1 # 8.0
lid_z1 = lid_z0 + lid_h # 18.0

lid_outer = Part.makeBox(outer_w, outer_d, lid_h, FreeCAD.Vector(outer_x0, outer_y0, lid_z0))
lid_inner = Part.makeBox(inner_w, inner_d, lid_h - lid_ceiling_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, lid_z0 - 0.5))
lid_tray = lid_outer.cut(lid_inner)

# Interlocking Lip
lip_clr = 0.25
lip_w = inner_w - 2 * lip_clr
lip_d = inner_d - 2 * lip_clr
lip_h = 2.0
lip_out = Part.makeBox(lip_w, lip_d, lip_h, FreeCAD.Vector(inner_x0 + lip_clr, inner_y0 + lip_clr, lid_z0 - lip_h))
lip_in = Part.makeBox(lip_w - 2.0, lip_d - 2.0, lip_h + 1.0, FreeCAD.Vector(inner_x0 + lip_clr + 1.0, inner_y0 + lip_clr + 1.0, lid_z0 - lip_h - 0.5))
lid_tray = lid_tray.fuse(lip_out.cut(lip_in))

# 4 Countersunk Holes in Lid
for hx, hy in pcb_holes:
    hole_m3 = Part.makeCylinder(1.65, lid_h + lip_h + 2.0, FreeCAD.Vector(hx, hy, lid_z0 - lip_h - 0.5), FreeCAD.Vector(0, 0, 1))
    sink_m3 = Part.makeCylinder(3.1, 2.2, FreeCAD.Vector(hx, hy, lid_z1 - 2.2), FreeCAD.Vector(0, 0, 1))
    lid_tray = lid_tray.cut(hole_m3).cut(sink_m3)

# Status LED Peephole
led_hole = Part.makeCylinder(1.5, lid_ceiling_th + 2.0, FreeCAD.Vector(inner_x0 + inner_w/2, inner_y0 + inner_d/2, lid_z1 - lid_ceiling_th - 1.0), FreeCAD.Vector(0, 0, 1))
lid_tray = lid_tray.cut(led_hole)

lid_obj = doc.addObject('Part::Feature', 'ProbeSensor_Lid')
lid_obj.Shape = lid_tray
lid_obj.ViewObject.ShapeColor = (0.2, 0.2, 0.22) # Dark Anthracite

doc.recompute()

# Save & Export
Mesh.export([base_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_sensor_base.stl')
Mesh.export([lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_sensor_lid.stl')
Part.export([base_obj, lid_obj], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_sensor_enclosure.step')
doc.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_sensor_enclosure.FCStd')

print(f'Probe Sensor Enclosure complete in {time.time() - t0:.2f}s')
