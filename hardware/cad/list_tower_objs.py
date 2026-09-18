# -*- coding: utf-8 -*-
import FreeCAD

doc = FreeCAD.getDocument('Enclosure_Amemiya_ProbeTower')
for obj in doc.Objects:
    print(obj.Name, obj.TypeId)
    if hasattr(obj, 'Shape') and hasattr(obj, 'Placement'):
        print('  Placement:', obj.Placement)
