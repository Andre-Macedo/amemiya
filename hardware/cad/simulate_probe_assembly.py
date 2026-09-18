# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Import

doc_name = 'Enclosure_Amemiya_Probe'
doc = FreeCAD.getDocument(doc_name)
if not doc:
    doc = FreeCAD.openDocument(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_probe_sensor_enclosure.FCStd')

# Import PCB STEP if not present
has_pcb = any('amemiya_probe_sensor' in o.Name for o in doc.Objects)
if not has_pcb:
    pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_probe_sensor\amemiya_probe_sensor.step'
    Import.insert(pcb_step, doc.Name)
    doc.recompute()

for obj in doc.Objects:
    if hasattr(obj, 'ViewObject') and obj.ViewObject and hasattr(obj.ViewObject, 'ShapeColor'):
        if 'amemiya_probe_sensor' in obj.Name or 'Part__Feature' in obj.Name:
            obj.ViewObject.ShapeColor = (0.1, 0.55, 0.25) # Green

# 4 M3 Screws
pcb_holes = [(4.5, -4.5), (41.5, -4.5), (4.5, -35.5), (41.5, -35.5)]
for i, (hx, hy) in enumerate(pcb_holes):
    shaft = Part.makeCylinder(1.45, 14.0, FreeCAD.Vector(hx, hy, 8.0), FreeCAD.Vector(0, 0, -1))
    head = Part.makeCylinder(2.7, 2.5, FreeCAD.Vector(hx, hy, 8.0), FreeCAD.Vector(0, 0, 1))
    s_shape = shaft.fuse(head)
    s_name = f'Probe_M3_Screw_{i+1}'
    s_obj = doc.getObject(s_name)
    if not s_obj:
        s_obj = doc.addObject('Part::Feature', s_name)
    s_obj.Shape = s_shape
    s_obj.ViewObject.ShapeColor = (0.85, 0.85, 0.88)

# Exploded View
lid = doc.getObject('ProbeSensor_Lid')
if lid:
    lid.Placement.Base = FreeCAD.Vector(0, 0, 35.0)

for obj in doc.Objects:
    if 'amemiya_probe_sensor' in obj.Name or ('Part__Feature' in obj.Name and 'Probe' not in obj.Name):
        obj.Placement.Base = FreeCAD.Vector(0, 0, 15.0)

for i in range(1, 5):
    s = doc.getObject(f'Probe_M3_Screw_{i}')
    if s:
        s.Placement.Base = FreeCAD.Vector(0, 0, 50.0)

doc.recompute()
print('Probe exploded view complete')
