# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import

doc_name = 'Enclosure_Amemiya_MainNode'
if doc_name in [d.Name for d in FreeCAD.listDocuments().values()]:
    FreeCAD.closeDocument(doc_name)
doc = FreeCAD.newDocument(doc_name)

# 1. Base Body (PartDesign)
body_base = doc.addObject('PartDesign::Body', 'MainNode_Base_Body')

# Sketch Base Profile
sk_base = doc.addObject('Sketcher::SketchObject', 'Sketch_Base_Profile')
body_base.addObject(sk_base)

# Outer dimensions: X in [-3, 93], Y in [-68, 3], Outer: 96 x 71 mm
x0, y0 = -3.0, -68.0
x1, y1 = 93.0, 3.0
lines = [
    Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)),
    Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0))
]
for l in lines:
    sk_base.addGeometry(l, False)

sk_base.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
sk_base.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
sk_base.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
sk_base.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))
sk_base.addConstraint(Sketcher.Constraint('Horizontal', 0))
sk_base.addConstraint(Sketcher.Constraint('Vertical', 1))
sk_base.addConstraint(Sketcher.Constraint('Horizontal', 2))
sk_base.addConstraint(Sketcher.Constraint('Vertical', 3))

doc.recompute()

pad_base = doc.addObject('PartDesign::Pad', 'Pad_Base')
pad_base.Profile = sk_base
pad_base.Length = 17.0
body_base.addObject(pad_base)
body_base.Placement.Base = FreeCAD.Vector(0, 0, -7.0)

doc.recompute()
print('Base Body Pad created successfully, Volume:', pad_base.Shape.Volume)
