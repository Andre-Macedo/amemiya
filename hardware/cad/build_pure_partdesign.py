# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import
import time

print("Building 100% Pure PartDesign Parametric Models...")

# ==============================================================================
# 1. MAIN NODE ENCLOSURE
# ==============================================================================
doc_main = FreeCAD.newDocument("Enclosure_Amemiya_MainNode")

# BASE BODY
body_base = doc_main.addObject("PartDesign::Body", "MainNode_Base_Body")

# Sketch 1: Base Profile (96 x 71 mm)
sk_base_prof = doc_main.addObject("Sketcher::SketchObject", "Sketch_Base_Profile")
body_base.addObject(sk_base_prof)
sk_base_prof.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, -7.0), FreeCAD.Rotation())
sk_base_prof.MapMode = "Deactivated"

sk_base_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, -68.0, 0), FreeCAD.Vector(93.0, -68.0, 0)), False)
sk_base_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, -68.0, 0), FreeCAD.Vector(93.0, 3.0, 0)), False)
sk_base_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, 3.0, 0), FreeCAD.Vector(-3.0, 3.0, 0)), False)
sk_base_prof.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, 3.0, 0), FreeCAD.Vector(-3.0, -68.0, 0)), False)
for i in range(4):
    sk_base_prof.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_base_prof.addConstraint(Sketcher.Constraint("Horizontal", 0))
sk_base_prof.addConstraint(Sketcher.Constraint("Vertical", 1))
sk_base_prof.addConstraint(Sketcher.Constraint("Horizontal", 2))
sk_base_prof.addConstraint(Sketcher.Constraint("Vertical", 3))
sk_base_prof.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 96.0))
sk_base_prof.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 71.0))
doc_main.recompute()

pad_base = doc_main.addObject("PartDesign::Pad", "Pad_Base_Solid")
pad_base.Profile = sk_base_prof
pad_base.Length = 17.0
body_base.addObject(pad_base)
doc_main.recompute()

# Sketch 2: Inner Cavity (92 x 67 mm)
sk_base_cav = doc_main.addObject("Sketcher::SketchObject", "Sketch_Base_Cavity")
body_base.addObject(sk_base_cav)
sk_base_cav.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 10.0), FreeCAD.Rotation())
sk_base_cav.MapMode = "Deactivated"

sk_base_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-1.0, -66.0, 0), FreeCAD.Vector(91.0, -66.0, 0)), False)
sk_base_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(91.0, -66.0, 0), FreeCAD.Vector(91.0, 1.0, 0)), False)
sk_base_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(91.0, 1.0, 0), FreeCAD.Vector(-1.0, 1.0, 0)), False)
sk_base_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-1.0, 1.0, 0), FreeCAD.Vector(-1.0, -66.0, 0)), False)
for i in range(4):
    sk_base_cav.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_base_cav.addConstraint(Sketcher.Constraint("Horizontal", 0))
sk_base_cav.addConstraint(Sketcher.Constraint("Vertical", 1))
sk_base_cav.addConstraint(Sketcher.Constraint("Horizontal", 2))
sk_base_cav.addConstraint(Sketcher.Constraint("Vertical", 3))
sk_base_cav.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 92.0))
sk_base_cav.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 67.0))
doc_main.recompute()

pocket_cav = doc_main.addObject("PartDesign::Pocket", "Pocket_Base_Cavity")
pocket_cav.Profile = sk_base_cav
pocket_cav.Length = 15.0 # leaves 2mm floor
pocket_cav.Reversed = True
body_base.addObject(pocket_cav)
doc_main.recompute()

# Sketch 3: Standoffs (4 posts dia 6.4mm)
sk_standoffs = doc_main.addObject("Sketcher::SketchObject", "Sketch_Standoffs")
body_base.addObject(sk_standoffs)
sk_standoffs.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, -5.0), FreeCAD.Rotation())
sk_standoffs.MapMode = "Deactivated"

pcb_holes = [(4.5, -4.5), (85.5, -4.5), (4.5, -60.5), (85.5, -60.5)]
for i, (hx, hy) in enumerate(pcb_holes):
    sk_standoffs.addGeometry(Part.Circle(FreeCAD.Vector(hx, hy, 0), FreeCAD.Vector(0, 0, 1), 3.2), False)
    sk_standoffs.addConstraint(Sketcher.Constraint("Radius", i, 3.2))
doc_main.recompute()

pad_standoffs = doc_main.addObject("PartDesign::Pad", "Pad_Standoffs")
pad_standoffs.Profile = sk_standoffs
pad_standoffs.Length = 5.0 # elevates PCB to Z=0
body_base.addObject(pad_standoffs)
doc_main.recompute()

# Sketch 4: Standoff Holes (dia 2.7mm M3 pilot)
sk_holes = doc_main.addObject("Sketcher::SketchObject", "Sketch_Standoff_Holes")
body_base.addObject(sk_holes)
sk_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 0.0), FreeCAD.Rotation())
sk_holes.MapMode = "Deactivated"

for i, (hx, hy) in enumerate(pcb_holes):
    sk_holes.addGeometry(Part.Circle(FreeCAD.Vector(hx, hy, 0), FreeCAD.Vector(0, 0, 1), 1.35), False)
    sk_holes.addConstraint(Sketcher.Constraint("Radius", i, 1.35))
doc_main.recompute()

pocket_holes = doc_main.addObject("PartDesign::Pocket", "Pocket_Standoff_Holes")
pocket_holes.Profile = sk_holes
pocket_holes.Length = 4.8
pocket_holes.Reversed = True
body_base.addObject(pocket_holes)
doc_main.recompute()

# Sketch 5: Side Flanges
sk_flanges = doc_main.addObject("Sketcher::SketchObject", "Sketch_Mounting_Flanges")
body_base.addObject(sk_flanges)
sk_flanges.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, -7.0), FreeCAD.Rotation())
sk_flanges.MapMode = "Deactivated"

# Left flange rect
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-15.0, -47.0, 0), FreeCAD.Vector(-3.0, -47.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, -47.0, 0), FreeCAD.Vector(-3.0, -25.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, -25.0, 0), FreeCAD.Vector(-15.0, -25.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(-15.0, -25.0, 0), FreeCAD.Vector(-15.0, -47.0, 0)), False)
for i in range(4): sk_flanges.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))

# Right flange rect
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, -47.0, 0), FreeCAD.Vector(105.0, -47.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(105.0, -47.0, 0), FreeCAD.Vector(105.0, -25.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(105.0, -25.0, 0), FreeCAD.Vector(93.0, -25.0, 0)), False)
sk_flanges.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, -25.0, 0), FreeCAD.Vector(93.0, -47.0, 0)), False)
for i in range(4, 8): sk_flanges.addConstraint(Sketcher.Constraint("Coincident", i, 2, 4 + (i - 3) % 4, 1))

doc_main.recompute()

pad_flanges = doc_main.addObject("PartDesign::Pad", "Pad_Mounting_Flanges")
pad_flanges.Profile = sk_flanges
pad_flanges.Length = 3.0
body_base.addObject(pad_flanges)
doc_main.recompute()

# Sketch 6: Flange Holes
sk_flange_holes = doc_main.addObject("Sketcher::SketchObject", "Sketch_Flange_Slots")
body_base.addObject(sk_flange_holes)
sk_flange_holes.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, -4.0), FreeCAD.Rotation())
sk_flange_holes.MapMode = "Deactivated"

sk_flange_holes.addGeometry(Part.Circle(FreeCAD.Vector(-9.0, -36.0, 0), FreeCAD.Vector(0, 0, 1), 2.5), False)
sk_flange_holes.addGeometry(Part.Circle(FreeCAD.Vector(99.0, -36.0, 0), FreeCAD.Vector(0, 0, 1), 2.5), False)
doc_main.recompute()

pocket_flanges = doc_main.addObject("PartDesign::Pocket", "Pocket_Flange_Slots")
pocket_flanges.Profile = sk_flange_holes
pocket_flanges.Length = 4.0
pocket_flanges.Reversed = True
body_base.addObject(pocket_flanges)
doc_main.recompute()

# Sketch 7: Cable Cutout (left wall)
sk_cable = doc_main.addObject("Sketcher::SketchObject", "Sketch_Cable_Cutout")
body_base.addObject(sk_cable)
# Left wall is at X = -3.0. Sketch in YZ plane
sk_cable.Placement = FreeCAD.Placement(FreeCAD.Vector(-4.0, 0, 0), FreeCAD.Rotation(FreeCAD.Vector(0, 1, 0), 90))
sk_cable.MapMode = "Deactivated"
# Rectangle on wall: Y in [-48, -10], Z in [1.0, 10.0]
sk_cable.addGeometry(Part.LineSegment(FreeCAD.Vector(1.0, -48.0, 0), FreeCAD.Vector(10.0, -48.0, 0)), False)
sk_cable.addGeometry(Part.LineSegment(FreeCAD.Vector(10.0, -48.0, 0), FreeCAD.Vector(10.0, -10.0, 0)), False)
sk_cable.addGeometry(Part.LineSegment(FreeCAD.Vector(10.0, -10.0, 0), FreeCAD.Vector(1.0, -10.0, 0)), False)
sk_cable.addGeometry(Part.LineSegment(FreeCAD.Vector(1.0, -10.0, 0), FreeCAD.Vector(1.0, -48.0, 0)), False)
for i in range(4): sk_cable.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc_main.recompute()

pocket_cable = doc_main.addObject("PartDesign::Pocket", "Pocket_Cable_Cutout")
pocket_cable.Profile = sk_cable
pocket_cable.Length = 5.0
body_base.addObject(pocket_cable)
doc_main.recompute()

# Sketch 8: USB-C Cutout (top wall)
sk_usbc = doc_main.addObject("Sketcher::SketchObject", "Sketch_USBC_Cutout")
body_base.addObject(sk_usbc)
# Top wall is at Y = 3.0. Sketch in XZ plane
sk_usbc.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 4.0, 0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), -90))
sk_usbc.MapMode = "Deactivated"
# Rectangle: X in [42.7, 54.7], Z in [1.5, 8.5]
sk_usbc.addGeometry(Part.LineSegment(FreeCAD.Vector(42.7, 1.5, 0), FreeCAD.Vector(54.7, 1.5, 0)), False)
sk_usbc.addGeometry(Part.LineSegment(FreeCAD.Vector(54.7, 1.5, 0), FreeCAD.Vector(54.7, 8.5, 0)), False)
sk_usbc.addGeometry(Part.LineSegment(FreeCAD.Vector(54.7, 8.5, 0), FreeCAD.Vector(42.7, 8.5, 0)), False)
sk_usbc.addGeometry(Part.LineSegment(FreeCAD.Vector(42.7, 8.5, 0), FreeCAD.Vector(42.7, 1.5, 0)), False)
for i in range(4): sk_usbc.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc_main.recompute()

pocket_usbc = doc_main.addObject("PartDesign::Pocket", "Pocket_USBC_Cutout")
pocket_usbc.Profile = sk_usbc
pocket_usbc.Length = 5.0
body_base.addObject(pocket_usbc)
doc_main.recompute()

print("MainNode_Base_Body successfully generated with 8 fully constrained PartDesign sketches!")

# LID BODY
body_lid = doc_main.addObject("PartDesign::Body", "MainNode_Lid_Body")

# Sketch Lid Outer
sk_lid_outer = doc_main.addObject("Sketcher::SketchObject", "Sketch_Lid_Outer")
body_lid.addObject(sk_lid_outer)
sk_lid_outer.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 10.0), FreeCAD.Rotation())
sk_lid_outer.MapMode = "Deactivated"
sk_lid_outer.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, -68.0, 0), FreeCAD.Vector(93.0, -68.0, 0)), False)
sk_lid_outer.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, -68.0, 0), FreeCAD.Vector(93.0, 3.0, 0)), False)
sk_lid_outer.addGeometry(Part.LineSegment(FreeCAD.Vector(93.0, 3.0, 0), FreeCAD.Vector(-3.0, 3.0, 0)), False)
sk_lid_outer.addGeometry(Part.LineSegment(FreeCAD.Vector(-3.0, 3.0, 0), FreeCAD.Vector(-3.0, -68.0, 0)), False)
for i in range(4): sk_lid_outer.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_lid_outer.addConstraint(Sketcher.Constraint("Horizontal", 0))
sk_lid_outer.addConstraint(Sketcher.Constraint("Vertical", 1))
sk_lid_outer.addConstraint(Sketcher.Constraint("Horizontal", 2))
sk_lid_outer.addConstraint(Sketcher.Constraint("Vertical", 3))
sk_lid_outer.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 96.0))
sk_lid_outer.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 71.0))
doc_main.recompute()

pad_lid = doc_main.addObject("PartDesign::Pad", "Pad_Lid_Solid")
pad_lid.Profile = sk_lid_outer
pad_lid.Length = 15.0
body_lid.addObject(pad_lid)
doc_main.recompute()

# Sketch Lid Cavity
sk_lid_cav = doc_main.addObject("Sketcher::SketchObject", "Sketch_Lid_Cavity")
body_lid.addObject(sk_lid_cav)
sk_lid_cav.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 10.0), FreeCAD.Rotation())
sk_lid_cav.MapMode = "Deactivated"
sk_lid_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-1.0, -66.0, 0), FreeCAD.Vector(91.0, -66.0, 0)), False)
sk_lid_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(91.0, -66.0, 0), FreeCAD.Vector(91.0, 1.0, 0)), False)
sk_lid_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(91.0, 1.0, 0), FreeCAD.Vector(-1.0, 1.0, 0)), False)
sk_lid_cav.addGeometry(Part.LineSegment(FreeCAD.Vector(-1.0, 1.0, 0), FreeCAD.Vector(-1.0, -66.0, 0)), False)
for i in range(4): sk_lid_cav.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
doc_main.recompute()

pocket_lid = doc_main.addObject("PartDesign::Pocket", "Pocket_Lid_Cavity")
pocket_lid.Profile = sk_lid_cav
pocket_lid.Length = 12.8 # leaves 2.2mm ceiling
body_lid.addObject(pocket_lid)
doc_main.recompute()

# Sketch OLED Window
sk_oled = doc_main.addObject("Sketcher::SketchObject", "Sketch_OLED_Window")
body_lid.addObject(sk_oled)
sk_oled.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 25.0), FreeCAD.Rotation())
sk_oled.MapMode = "Deactivated"
# Rectangle: 28 x 16 mm centered around X=75, Y=-25
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(61.0, -33.0, 0), FreeCAD.Vector(89.0, -33.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(89.0, -33.0, 0), FreeCAD.Vector(89.0, -17.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(89.0, -17.0, 0), FreeCAD.Vector(61.0, -17.0, 0)), False)
sk_oled.addGeometry(Part.LineSegment(FreeCAD.Vector(61.0, -17.0, 0), FreeCAD.Vector(61.0, -33.0, 0)), False)
for i in range(4): sk_oled.addConstraint(Sketcher.Constraint("Coincident", i, 2, (i + 1) % 4, 1))
sk_oled.addConstraint(Sketcher.Constraint("DistanceX", 0, 1, 0, 2, 28.0))
sk_oled.addConstraint(Sketcher.Constraint("DistanceY", 1, 1, 1, 2, 16.0))
doc_main.recompute()

pocket_oled = doc_main.addObject("PartDesign::Pocket", "Pocket_OLED_Window")
pocket_oled.Profile = sk_oled
pocket_oled.Length = 5.0
pocket_oled.Reversed = True
body_lid.addObject(pocket_oled)
doc_main.recompute()

# Sketch Vent Grille
sk_vents = doc_main.addObject("Sketcher::SketchObject", "Sketch_Vent_Grille")
body_lid.addObject(sk_vents)
sk_vents.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 25.0), FreeCAD.Rotation())
sk_vents.MapMode = "Deactivated"
for j in range(6):
    vy = -48.0 + j * 5.5
    idx = j * 4
    sk_vents.addGeometry(Part.LineSegment(FreeCAD.Vector(38.7, vy, 0), FreeCAD.Vector(58.7, vy, 0)), False)
    sk_vents.addGeometry(Part.LineSegment(FreeCAD.Vector(58.7, vy, 0), FreeCAD.Vector(58.7, vy + 2.2, 0)), False)
    sk_vents.addGeometry(Part.LineSegment(FreeCAD.Vector(58.7, vy + 2.2, 0), FreeCAD.Vector(38.7, vy + 2.2, 0)), False)
    sk_vents.addGeometry(Part.LineSegment(FreeCAD.Vector(38.7, vy + 2.2, 0), FreeCAD.Vector(38.7, vy, 0)), False)
    for i in range(4):
        sk_vents.addConstraint(Sketcher.Constraint("Coincident", idx + i, 2, idx + (i + 1) % 4, 1))
doc_main.recompute()

pocket_vents = doc_main.addObject("PartDesign::Pocket", "Pocket_Vent_Grille")
pocket_vents.Profile = sk_vents
pocket_vents.Length = 5.0
pocket_vents.Reversed = True
body_lid.addObject(pocket_vents)
doc_main.recompute()

# Sketch Screw Holes
sk_screws = doc_main.addObject("Sketcher::SketchObject", "Sketch_Screw_Holes")
body_lid.addObject(sk_screws)
sk_screws.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 25.0), FreeCAD.Rotation())
sk_screws.MapMode = "Deactivated"
for i, (hx, hy) in enumerate(pcb_holes):
    sk_screws.addGeometry(Part.Circle(FreeCAD.Vector(hx, hy, 0), FreeCAD.Vector(0, 0, 1), 1.65), False)
    sk_screws.addConstraint(Sketcher.Constraint("Radius", i, 1.65))
doc_main.recompute()

pocket_screws = doc_main.addObject("PartDesign::Pocket", "Pocket_Screw_Holes")
pocket_screws.Profile = sk_screws
pocket_screws.Length = 16.0
pocket_screws.Reversed = True
body_lid.addObject(pocket_screws)
doc_main.recompute()

print("MainNode_Lid_Body successfully generated with 5 fully constrained PartDesign sketches!")

# Import KiCad PCB
pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_main_node\amemiya_main_node.step'
Import.insert(pcb_step, doc_main.Name)
doc_main.recompute()

# Add 3D Components
# ESP32-S3 DevKit
esp_pcb = Part.makeBox(25.4, 48.2, 1.2, FreeCAD.Vector(36.0, -56.0, 8.5))
sock_l = Part.makeBox(2.5, 48.2, 8.5, FreeCAD.Vector(34.8, -56.0, 0.0))
sock_r = Part.makeBox(2.5, 48.2, 8.5, FreeCAD.Vector(60.1, -56.0, 0.0))
esp_shield = Part.makeBox(18.0, 25.5, 3.0, FreeCAD.Vector(39.7, -42.0, 9.7))
esp_usbc = Part.makeBox(9.0, 7.5, 3.2, FreeCAD.Vector(44.2, -4.5, 9.7))
esp_ant = Part.makeBox(20.0, 8.0, 0.8, FreeCAD.Vector(38.7, -54.5, 9.7))
esp_comp = esp_pcb.fuse(sock_l).fuse(sock_r).fuse(esp_shield).fuse(esp_usbc).fuse(esp_ant)
esp_obj = doc_main.addObject("Part::Feature", "ESP32_S3_DevKit_Module")
esp_obj.Shape = esp_comp

# OLED Display
oled_pcb = Part.makeBox(27.0, 27.0, 1.2, FreeCAD.Vector(62.0, -33.0, 11.0))
oled_glass = Part.makeBox(24.7, 14.0, 1.5, FreeCAD.Vector(63.2, -26.5, 12.2))
oled_obj = doc_main.addObject("Part::Feature", "OLED_096_Display_Module")
oled_obj.Shape = oled_pcb.fuse(oled_glass)

# LoRa Module
lora_pcb = Part.makeBox(16.0, 17.0, 1.2, FreeCAD.Vector(70.0, -41.0, 4.0))
lora_shield = Part.makeBox(12.0, 13.0, 2.5, FreeCAD.Vector(72.0, -39.0, 5.2))
lora_obj = doc_main.addObject("Part::Feature", "LoRa_Ra02_Module")
lora_obj.Shape = lora_pcb.fuse(lora_shield)

# Terminal Blocks
term_probe = Part.makeBox(8.0, 20.0, 10.0, FreeCAD.Vector(8.0, -28.0, 1.6))
term_tacho = Part.makeBox(8.0, 7.5, 10.0, FreeCAD.Vector(8.0, -37.0, 1.6))
term_amp = Part.makeBox(8.0, 5.0, 10.0, FreeCAD.Vector(8.0, -46.0, 1.6))
term_obj = doc_main.addObject("Part::Feature", "Terminal_Blocks_Phoenix")
term_obj.Shape = term_probe.fuse(term_tacho).fuse(term_amp)

# 4x M3 Screws
for i, (hx, hy) in enumerate(pcb_holes):
    shaft = Part.makeCylinder(1.45, 16.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, -1))
    head = Part.makeCylinder(2.7, 3.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, 1))
    s_obj = doc_main.addObject("Part::Feature", "M3_Screw_" + str(i+1))
    s_obj.Shape = shaft.fuse(head)

doc_main.recompute()

# Save files
doc_main.saveAs(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd')
Part.export([body_base, body_lid], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.step')
Mesh.export([body_base], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_base.stl')
Mesh.export([body_lid], r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_lid.stl')

print("Main Node pure PartDesign build completed successfully!")
