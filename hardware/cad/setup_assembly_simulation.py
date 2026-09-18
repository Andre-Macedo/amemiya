# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Mesh
import Import

doc_name = 'Enclosure_Amemiya_MainNode'
doc = FreeCAD.getDocument(doc_name)
if not doc:
    doc = FreeCAD.openDocument(r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\cad\amemiya_main_node_enclosure.FCStd')

# Check if PCB is already in doc, if not import it
has_pcb = any('amemiya_main_node' in o.Name for o in doc.Objects)
if not has_pcb:
    pcb_step = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_main_node\amemiya_main_node.step'
    Import.insert(pcb_step, doc.Name)
    doc.recompute()

# Find PCB object and style it
for obj in doc.Objects:
    if hasattr(obj, 'ViewObject') and obj.ViewObject and hasattr(obj.ViewObject, 'ShapeColor'):
        if 'amemiya_main_node' in obj.Name or 'Part__Feature' in obj.Name:
            obj.ViewObject.ShapeColor = (0.1, 0.55, 0.25)

# Create 4 M3 Assembly Screws
pcb_holes = [(4.5, -4.5), (85.5, -4.5), (4.5, -60.5), (85.5, -60.5)]
screws = []
for i, (hx, hy) in enumerate(pcb_holes):
    # M3 cap head screw: head dia 5.5mm, head h 3mm, shaft dia 3mm, shaft len 16mm
    shaft = Part.makeCylinder(1.45, 16.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, -1))
    head = Part.makeCylinder(2.7, 3.0, FreeCAD.Vector(hx, hy, 10.0), FreeCAD.Vector(0, 0, 1))
    screw_shape = shaft.fuse(head)
    s_name = f'M3_Screw_{i+1}'
    s_obj = doc.getObject(s_name)
    if not s_obj:
        s_obj = doc.addObject('Part::Feature', s_name)
    s_obj.Shape = screw_shape
    s_obj.ViewObject.ShapeColor = (0.85, 0.85, 0.88) # Shiny Stainless Steel
    screws.append(s_obj)

base_obj = doc.getObject('MainNode_Base')
lid_obj = doc.getObject('MainNode_Lid')

if base_obj:
    base_obj.ViewObject.ShapeColor = (0.15, 0.35, 0.65) # Industrial Deep Blue
    base_obj.ViewObject.Transparency = 0
if lid_obj:
    lid_obj.ViewObject.ShapeColor = (0.22, 0.22, 0.25) # Matte Anthracite
    lid_obj.ViewObject.Transparency = 0

doc.recompute()
print('Assembly setup complete')
