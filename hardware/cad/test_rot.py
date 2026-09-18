# -*- coding: utf-8 -*-
import FreeCAD

doc = FreeCAD.getDocument('Enclosure_Amemiya_ProbeTower')
# Let's inspect the bounding box of the imported PCB object
for obj in doc.Objects:
    if 'amemiya_probe_tower' in obj.Name or 'Part__Feature' in obj.Name:
        if 'Tower' not in obj.Name and 'GX12' not in obj.Name and 'DS18' not in obj.Name and 'ADXL' not in obj.Name and 'INMP' not in obj.Name and 'J_CABLE' not in obj.Name:
            print(obj.Name, obj.Shape.BoundBox)
            # Make it vertical: width 24 along X, height 48 along Z
            obj.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0.0, 65.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))
            print('New BB:', obj.Shape.BoundBox)
            break
doc.recompute()
