# -*- coding: utf-8 -*-
import FreeCAD

doc = FreeCAD.getDocument('Enclosure_Amemiya_MainNode')

# Reset Lid opacity
lid = doc.getObject('MainNode_Lid')
if lid:
    lid.ViewObject.Transparency = 0
    lid.Placement.Base = FreeCAD.Vector(0, 0, 45.0)

# Move PCB up
for obj in doc.Objects:
    if 'amemiya_main_node' in obj.Name or 'Part__Feature' in obj.Name:
        obj.Placement.Base = FreeCAD.Vector(0, 0, 20.0)

# Move Screws up
for i in range(1, 5):
    s = doc.getObject(f'M3_Screw_{i}')
    if s:
        s.Placement.Base = FreeCAD.Vector(0, 0, 65.0)

doc.recompute()
print('Exploded view configured')
