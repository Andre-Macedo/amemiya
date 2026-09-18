# -*- coding: utf-8 -*-
import FreeCAD
import Part
import Sketcher
import Mesh
import Import

def make_sketch_rect(body, name, x0, y0, x1, y1):
    sk = body.newObject('Sketcher::SketchObject', name)
    lines = [
        Part.LineSegment(FreeCAD.Vector(x0, y0, 0), FreeCAD.Vector(x1, y0, 0)),
        Part.LineSegment(FreeCAD.Vector(x1, y0, 0), FreeCAD.Vector(x1, y1, 0)),
        Part.LineSegment(FreeCAD.Vector(x1, y1, 0), FreeCAD.Vector(x0, y1, 0)),
        Part.LineSegment(FreeCAD.Vector(x0, y1, 0), FreeCAD.Vector(x0, y0, 0))
    ]
    for l in lines:
        sk.addGeometry(l, False)
    sk.addConstraint(Sketcher.Constraint('Coincident', 0, 2, 1, 1))
    sk.addConstraint(Sketcher.Constraint('Coincident', 1, 2, 2, 1))
    sk.addConstraint(Sketcher.Constraint('Coincident', 2, 2, 3, 1))
    sk.addConstraint(Sketcher.Constraint('Coincident', 3, 2, 0, 1))
    sk.addConstraint(Sketcher.Constraint('Horizontal', 0))
    sk.addConstraint(Sketcher.Constraint('Vertical', 1))
    sk.addConstraint(Sketcher.Constraint('Horizontal', 2))
    sk.addConstraint(Sketcher.Constraint('Vertical', 3))
    return sk

print('Helper functions defined')
