# -*- coding: utf-8 -*-
import FreeCAD

doc = FreeCAD.getDocument('Enclosure_Amemiya_ProbeTower')

# Reset child Part__Feature
pf = doc.getObject('Part__Feature')
if pf:
    pf.Placement = FreeCAD.Placement()

# Rotate parent App::Part 90 deg around X and translate UP out of the tower slot
pt = doc.getObject('amemiya_probe_tower_1')
if pt:
    pt.Placement = FreeCAD.Placement(FreeCAD.Vector(-12.0, 0.8, 68.0), FreeCAD.Rotation(FreeCAD.Vector(1, 0, 0), 90))

# Move Top Cap and GX12 up above PCB
cap = doc.getObject('ProbeTower_TopCap')
if cap:
    cap.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 75.0), FreeCAD.Rotation())

gx = doc.getObject('GX12_8_Aviation_Connector')
if gx:
    gx.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 90.0), FreeCAD.Rotation())

# Components attached to PCB in exploded position
ds = doc.getObject('DS18B20_Temp_Sensor')
if ds:
    ds.Placement = FreeCAD.Placement(FreeCAD.Vector(0, -1.0, 68.0), FreeCAD.Rotation())

adxl = doc.getObject('ADXL345_Accelerometer_Module')
if adxl:
    adxl.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 68.0), FreeCAD.Rotation())

mic = doc.getObject('INMP441_Microphone_Module')
if mic:
    mic.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 68.0), FreeCAD.Rotation())

hdr = doc.getObject('J_CABLE_Header')
if hdr:
    hdr.Placement = FreeCAD.Placement(FreeCAD.Vector(0, 0, 68.0), FreeCAD.Rotation())

doc.recompute()
print('Probe Tower perfectly assembled in exploded view!')
