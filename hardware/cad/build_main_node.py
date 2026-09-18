import FreeCAD
import Part
import Import
import math

doc_name = "Enclosure_Amemiya"
doc = FreeCAD.getDocument(doc_name)
if not doc:
    doc = FreeCAD.newDocument(doc_name)

# Parameters (in mm)
pcb_w = 90.0
pcb_d = 65.0
pcb_th = 1.6

clearance_xy = 1.0
wall_th = 2.0
floor_th = 2.0
standoff_h = 5.0
standoff_r = 3.0
standoff_hole_r = 1.3
standoff_hole_d = 4.5

# Dimensions
inner_x0 = -clearance_xy       # -1.0
inner_y0 = -pcb_d - clearance_xy # -66.0
inner_w = pcb_w + 2 * clearance_xy # 92.0
inner_d = pcb_d + 2 * clearance_xy # 67.0

outer_x0 = inner_x0 - wall_th    # -3.0
outer_y0 = inner_y0 - wall_th    # -68.0
outer_w = inner_w + 2 * wall_th  # 96.0
outer_d = inner_d + 2 * wall_th  # 71.0

base_z0 = -(standoff_h + floor_th) # -7.0
base_floor_top = base_z0 + floor_th # -5.0
base_z1 = 10.0                     # top rim of base
base_h = base_z1 - base_z0         # 17.0

# 1. Base Outer Shell
outer_box = Part.makeBox(outer_w, outer_d, base_h, FreeCAD.Vector(outer_x0, outer_y0, base_z0))

# Inner cavity
inner_box = Part.makeBox(inner_w, inner_d, base_h - floor_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, base_floor_top))

base_tray = outer_box.cut(inner_box)

# 2. PCB Mounting Standoffs (4 posts)
standoffs = []
pcb_holes = [
    (4.5, -4.5),
    (85.5, -4.5),
    (4.5, -60.5),
    (85.5, -60.5)
]

for hx, hy in pcb_holes:
    # solid post
    post = Part.makeCylinder(standoff_r, standoff_h, FreeCAD.Vector(hx, hy, base_floor_top), FreeCAD.Vector(0, 0, 1))
    # hole
    hole = Part.makeCylinder(standoff_hole_r, standoff_hole_d + 0.5, FreeCAD.Vector(hx, hy, -standoff_hole_d), FreeCAD.Vector(0, 0, 1))
    post = post.cut(hole)
    base_tray = base_tray.fuse(post)

# 3. Corner Assembly Bosses (in 4 inside corners for Lid fastening M3 screws)
corner_screw_r = 1.4 # for M3 thread
boss_r = 4.0
boss_h = base_h - floor_th
corners = [
    (inner_x0 + 2.5, inner_y0 + 2.5),
    (inner_x0 + inner_w - 2.5, inner_y0 + 2.5),
    (inner_x0 + 2.5, inner_y0 + inner_d - 2.5),
    (inner_x0 + inner_w - 2.5, inner_y0 + inner_d - 2.5)
]

for cx, cy in corners:
    boss = Part.makeCylinder(boss_r, boss_h, FreeCAD.Vector(cx, cy, base_floor_top), FreeCAD.Vector(0, 0, 1))
    b_hole = Part.makeCylinder(corner_screw_r, boss_h + 1.0, FreeCAD.Vector(cx, cy, base_floor_top), FreeCAD.Vector(0, 0, 1))
    boss = boss.cut(b_hole)
    base_tray = base_tray.fuse(boss)

# 4. Cutouts in Base
# Cable cutout on left wall (X = outer_x0)
cable_cutout = Part.makeBox(wall_th + 2.0, 38.0, 9.0, FreeCAD.Vector(outer_x0 - 1.0, -48.0, 1.0))
base_tray = base_tray.cut(cable_cutout)

# USB-C cutout on top wall (Y = outer_y0 + outer_d - wall_th)
usbc_cutout = Part.makeBox(12.0, wall_th + 2.0, 7.0, FreeCAD.Vector(42.7, outer_y0 + outer_d - wall_th - 1.0, 1.5))
base_tray = base_tray.cut(usbc_cutout)

# Add Base object to document
base_obj = doc.getObject("MainNode_Base")
if not base_obj:
    base_obj = doc.addObject("Part::Feature", "MainNode_Base")
base_obj.Shape = base_tray
base_obj.ViewObject.ShapeColor = (0.2, 0.4, 0.7) # Industrial Blue

# 5. LID (Top Cover)
lid_h = 14.0
lid_ceiling_th = 2.0
lid_z0 = base_z1 # 10.0
lid_z1 = lid_z0 + lid_h # 24.0

# Outer Lid Box
lid_outer = Part.makeBox(outer_w, outer_d, lid_h, FreeCAD.Vector(outer_x0, outer_y0, lid_z0))

# Inner cavity of Lid
lid_inner = Part.makeBox(inner_w, inner_d, lid_h - lid_ceiling_th + 1.0, FreeCAD.Vector(inner_x0, inner_y0, lid_z0 - 0.5))
lid_tray = lid_outer.cut(lid_inner)

# Interlocking Rim / Lip (extends down 2.5mm into base)
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

# Cutouts in Lid:
# A. 4 Corner Screw Holes through Lid with countersink
for cx, cy in corners:
    c_hole = Part.makeCylinder(1.65, lid_h + 2.0, FreeCAD.Vector(cx, cy, lid_z0 - 1.0), FreeCAD.Vector(0, 0, 1))
    c_sink = Part.makeCylinder(3.2, 3.5, FreeCAD.Vector(cx, cy, lid_z1 - 3.0), FreeCAD.Vector(0, 0, 1))
    lid_tray = lid_tray.cut(c_hole).cut(c_sink)

# B. OLED Display Cutout (26mm x 15mm) centered around X=75, Y=-25
oled_cutout = Part.makeBox(26.0, 15.0, lid_ceiling_th + 4.0, FreeCAD.Vector(62.0, -32.5, lid_z1 - lid_ceiling_th - 1.0))
lid_tray = lid_tray.cut(oled_cutout)

# C. Ventilation Grille over ESP32 CPU (6 aesthetic cooling slots)
for i in range(6):
    slot_y = -48.0 + i * 5.5
    vent_slot = Part.makeBox(20.0, 2.5, lid_ceiling_th + 4.0, FreeCAD.Vector(38.7, slot_y, lid_z1 - lid_ceiling_th - 1.0))
    lid_tray = lid_tray.cut(vent_slot)

# Add Lid object to document
lid_obj = doc.getObject("MainNode_Lid")
if not lid_obj:
    lid_obj = doc.addObject("Part::Feature", "MainNode_Lid")
lid_obj.Shape = lid_tray
lid_obj.ViewObject.ShapeColor = (0.25, 0.25, 0.28) # Charcoal / Matte Dark Grey

doc.recompute()
print("Main Node Base and Lid modeled successfully!")
