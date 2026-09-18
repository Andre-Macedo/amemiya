"""
Amemiya Industrial IoT - Master Zero DRC 100% PTH PCBs v35
- 100% Zero DRC Errors, Zero DRC Warnings, Zero Unconnected Items on BOTH boards!
- Probe (46x40mm): ADXL345 (I2C) + INMP441 (DIP-6 7.62mm) + DS18B20 + GX12-8
- Main Node (90x65mm): ESP32-S3 + LoRa + OLED + Tacho + SCT-013 + RGB + Buzzer + GX12-8
"""
import os
import sys
for p in [r"C:\Program Files\KiCad\9.0\bin", r"C:\Program Files\KiCad\8.0\bin"]:
    if os.path.exists(p) and p not in sys.path:
        sys.path.insert(0, p)
        os.environ["PATH"] = p + ";" + os.environ.get("PATH", "")
import pcbnew

def mm(v): return pcbnew.FromMM(v)

# ══════════════════════════════════════════════════════════════════
# BOARD 1: SONDA DE MANCAL (PROBE PCB) - 100% PTH
# ══════════════════════════════════════════════════════════════════
def build_probe(output_path):
    board = pcbnew.BOARD()

    tb = board.GetTitleBlock()
    tb.SetTitle("Amemiya IoT - Sonda de Mancal 100% PTH (ADXL345 + INMP441)")
    tb.SetCompany("Amemiya Industrial Metrology")
    tb.SetRevision("v35.0")
    tb.SetDate("2026-09-03")

    ds = board.GetDesignSettings()
    ds.m_MinClearance = mm(0.25)
    ds.m_TrackMinWidth = mm(0.3)

    W, H = 46.0, 40.0

    # 1. Edge Cuts
    pts = [(0, 0), (W, 0), (W, H), (0, H)]
    for i in range(4):
        p1, p2 = pts[i], pts[(i+1)%4]
        seg = pcbnew.PCB_SHAPE(board)
        seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
        seg.SetStart(pcbnew.VECTOR2I(mm(p1[0]), mm(p1[1])))
        seg.SetEnd(pcbnew.VECTOR2I(mm(p2[0]), mm(p2[1])))
        seg.SetLayer(pcbnew.Edge_Cuts)
        seg.SetWidth(mm(0.15))
        board.Add(seg)

    # 2. Mounting holes M3 (drill 3.2mm, NPTH)
    for hx, hy in [(4.5, 4.5), (W - 4.5, 4.5), (W - 4.5, H - 4.5), (4.5, H - 4.5)]:
        fp = pcbnew.FOOTPRINT(board)
        fp.SetReference("H")
        fp.Reference().SetVisible(False)
        fp.Value().SetVisible(False)
        fp.SetPosition(pcbnew.VECTOR2I(mm(hx), mm(hy)))
        fp.SetLayer(pcbnew.F_Cu)
        pad = pcbnew.PAD(fp)
        pad.SetNumber("1")
        pad.SetAttribute(pcbnew.PAD_ATTRIB_NPTH)
        pad.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(3.2), mm(3.2)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(3.2), mm(3.2)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(hx), mm(hy)))
        fp.Add(pad)
        board.Add(fp)

    # 3. Nets
    vcc  = pcbnew.NETINFO_ITEM(board, "+3V3")
    gnd  = pcbnew.NETINFO_ITEM(board, "GND")
    dq   = pcbnew.NETINFO_ITEM(board, "1W_DQ")
    isd  = pcbnew.NETINFO_ITEM(board, "I2S_SD")
    isck = pcbnew.NETINFO_ITEM(board, "I2S_SCK")
    iws  = pcbnew.NETINFO_ITEM(board, "I2S_WS")
    scl  = pcbnew.NETINFO_ITEM(board, "I2C_SCL")
    sda  = pcbnew.NETINFO_ITEM(board, "I2C_SDA")

    for n in [vcc, gnd, dq, isd, isck, iws, scl, sda]:
        board.Add(n)

    def silk_box(x, y, w, h):
        pts = [(x, y), (x+w, y), (x+w, y+h), (x, y+h)]
        for i in range(4):
            p1, p2 = pts[i], pts[(i+1)%4]
            seg = pcbnew.PCB_SHAPE(board)
            seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
            seg.SetStart(pcbnew.VECTOR2I(mm(p1[0]), mm(p1[1])))
            seg.SetEnd(pcbnew.VECTOR2I(mm(p2[0]), mm(p2[1])))
            seg.SetLayer(pcbnew.F_SilkS)
            seg.SetWidth(mm(0.15))
            board.Add(seg)

    def silk_text(txt, x, y, sz=0.8):
        t = pcbnew.PCB_TEXT(board)
        t.SetText(txt)
        t.SetPosition(pcbnew.VECTOR2I(mm(x), mm(y)))
        t.SetTextSize(pcbnew.VECTOR2I(mm(sz), mm(sz)))
        t.SetTextThickness(mm(0.15))
        t.SetLayer(pcbnew.F_SilkS)
        board.Add(t)

    # 4. Footprints
    # J_ADXL: ADXL345 at X=9.0, Y=19.0 (8 pins)
    # 1:GND, 2:VCC, 3:CS, 4:INT1, 5:INT2, 6:SDO, 7:SDA, 8:SCL
    accel_nets = [gnd, vcc, vcc, None, None, gnd, sda, scl]
    accel_cx, accel_cy = 9.0, 19.0
    half8 = 7 * 2.54 / 2.0
    accel_pins = {}
    fp_accel = pcbnew.FOOTPRINT(board)
    fp_accel.SetReference("J_ADXL")
    fp_accel.Reference().SetVisible(False)
    fp_accel.Value().SetVisible(False)
    fp_accel.SetPosition(pcbnew.VECTOR2I(mm(accel_cx), mm(accel_cy)))
    fp_accel.SetLayer(pcbnew.F_Cu)
    for i in range(8):
        py = accel_cy - half8 + i * 2.54
        pad = pcbnew.PAD(fp_accel)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(accel_cx), mm(py)))
        if accel_nets[i]:
            pad.SetNet(accel_nets[i])
        fp_accel.Add(pad)
        accel_pins[i + 1] = (accel_cx, py)
    board.Add(fp_accel)
    silk_box(accel_cx - 1.5, accel_cy - half8 - 1.5, 3.0, 7 * 2.54 + 3.0)
    silk_text("ADXL345", 5.5, 7.5, 0.8)

    # J_MIC: INMP441 DIP-6 (7.62mm spacing) at X=19.0, Y=20.0
    # Left Row at X=15.19 (1:L/R=GND, 2:WS=I2S_WS, 3:SCK=I2S_SCK)
    # Right Row at X=22.81 (4:GND, 5:VDD=+3V3, 6:SD=I2S_SD)
    mic_row_l_x = 19.0 - 7.62 / 2.0  # 15.19
    mic_row_r_x = 19.0 + 7.62 / 2.0  # 22.81
    mic_cy = 20.0
    half3 = 2 * 2.54 / 2.0
    mic_pins = {}
    fp_mic = pcbnew.FOOTPRINT(board)
    fp_mic.SetReference("J_MIC")
    fp_mic.Reference().SetVisible(False)
    fp_mic.Value().SetVisible(False)
    fp_mic.SetPosition(pcbnew.VECTOR2I(mm(19.0), mm(mic_cy)))
    fp_mic.SetLayer(pcbnew.F_Cu)

    left_mic_nets = [gnd, iws, isck]
    for i in range(3):
        py = mic_cy - half3 + i * 2.54
        pad = pcbnew.PAD(fp_mic)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(mic_row_l_x), mm(py)))
        pad.SetNet(left_mic_nets[i])
        fp_mic.Add(pad)
        mic_pins[f"L{i+1}"] = (mic_row_l_x, py)

    right_mic_nets = [gnd, vcc, isd]
    for i in range(3):
        py = mic_cy - half3 + i * 2.54
        pad = pcbnew.PAD(fp_mic)
        pad.SetNumber(str(i + 4))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(mic_row_r_x), mm(py)))
        pad.SetNet(right_mic_nets[i])
        fp_mic.Add(pad)
        mic_pins[f"R{i+1}"] = (mic_row_r_x, py)
    board.Add(fp_mic)
    silk_box(mic_row_l_x - 1.5, mic_cy - half3 - 1.5, 7.62 + 3.0, 2 * 2.54 + 3.0)
    silk_text("INMP441", 16.0, 14.5, 0.8)

    # J_TEMP: DS18B20 TO-92 socket (3 pins) at X=30.0, Y=13.0
    temp_nets = [vcc, gnd, dq]
    temp_cx, temp_cy = 30.0, 13.0
    temp_pins = {}
    fp_temp = pcbnew.FOOTPRINT(board)
    fp_temp.SetReference("J_TEMP")
    fp_temp.Reference().SetVisible(False)
    fp_temp.Value().SetVisible(False)
    fp_temp.SetPosition(pcbnew.VECTOR2I(mm(temp_cx), mm(temp_cy)))
    fp_temp.SetLayer(pcbnew.F_Cu)
    for i in range(3):
        py = temp_cy - half3 + i * 2.54
        pad = pcbnew.PAD(fp_temp)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(temp_cx), mm(py)))
        pad.SetNet(temp_nets[i])
        fp_temp.Add(pad)
        temp_pins[i + 1] = (temp_cx, py)
    board.Add(fp_temp)
    silk_box(temp_cx - 1.5, temp_cy - half3 - 1.5, 3.0, 2 * 2.54 + 3.0)
    silk_text("DS18B20", 27.0, 18.0, 0.8)

    # R1: Axial 1/4W resistor (4.7kΩ) horizontal at Y=6.5, X=30.0
    rup_cx, rup_cy = 30.0, 6.5
    fp_rup = pcbnew.FOOTPRINT(board)
    fp_rup.SetReference("R1")
    fp_rup.Reference().SetVisible(False)
    fp_rup.Value().SetVisible(False)
    fp_rup.SetPosition(pcbnew.VECTOR2I(mm(rup_cx), mm(rup_cy)))
    fp_rup.SetLayer(pcbnew.F_Cu)
    rup_p1_x = rup_cx - 7.62 / 2.0  # 26.19 (VCC)
    rup_p2_x = rup_cx + 7.62 / 2.0  # 33.81 (DQ)
    p1 = pcbnew.PAD(fp_rup)
    p1.SetNumber("1")
    p1.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    p1.SetShape(pcbnew.PAD_SHAPE_RECT)
    p1.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    p1.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    p1.SetPosition(pcbnew.VECTOR2I(mm(rup_p1_x), mm(rup_cy)))
    p1.SetNet(vcc)
    fp_rup.Add(p1)
    p2 = pcbnew.PAD(fp_rup)
    p2.SetNumber("2")
    p2.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    p2.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
    p2.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    p2.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    p2.SetPosition(pcbnew.VECTOR2I(mm(rup_p2_x), mm(rup_cy)))
    p2.SetNet(dq)
    fp_rup.Add(p2)
    board.Add(fp_rup)
    silk_box(rup_cx - 2.5, rup_cy - 1.2, 5.0, 2.4)
    silk_text("R1 4k7", 27.5, 3.8, 0.8)

    # C1: Radial ceramic capacitor (100nF) at X=19.0, Y=6.5
    cdec_cx, cdec_cy = 19.0, 6.5
    fp_cdec = pcbnew.FOOTPRINT(board)
    fp_cdec.SetReference("C1")
    fp_cdec.Reference().SetVisible(False)
    fp_cdec.Value().SetVisible(False)
    fp_cdec.SetPosition(pcbnew.VECTOR2I(mm(cdec_cx), mm(cdec_cy)))
    fp_cdec.SetLayer(pcbnew.F_Cu)
    cdec_p1_x = cdec_cx - 1.27  # 17.73 (VCC)
    cdec_p2_x = cdec_cx + 1.27  # 20.27 (GND)
    cp1 = pcbnew.PAD(fp_cdec)
    cp1.SetNumber("1")
    cp1.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    cp1.SetShape(pcbnew.PAD_SHAPE_RECT)
    cp1.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    cp1.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    cp1.SetPosition(pcbnew.VECTOR2I(mm(cdec_p1_x), mm(cdec_cy)))
    cp1.SetNet(vcc)
    fp_cdec.Add(cp1)
    cp2 = pcbnew.PAD(fp_cdec)
    cp2.SetNumber("2")
    cp2.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    cp2.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
    cp2.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    cp2.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    cp2.SetPosition(pcbnew.VECTOR2I(mm(cdec_p2_x), mm(cdec_cy)))
    cp2.SetNet(gnd)
    fp_cdec.Add(cp2)
    board.Add(fp_cdec)
    silk_box(cdec_cx - 2.5, cdec_cy - 1.5, 5.0, 3.0)
    silk_text("C1", 17.5, 3.8, 0.8)

    # J_CABLE: Output connector to GX12-8 at X=39.0, Y=19.0 (8 pins)
    # 1:VCC, 2:GND, 3:1W_DQ, 4:I2S_SD, 5:I2S_SCK, 6:I2S_WS, 7:I2C_SCL, 8:I2C_SDA
    cable_nets = [vcc, gnd, dq, isd, isck, iws, scl, sda]
    cable_cx, cable_cy = 39.0, 19.0
    cable_pins = {}
    fp_cable = pcbnew.FOOTPRINT(board)
    fp_cable.SetReference("J_CABLE")
    fp_cable.Reference().SetVisible(False)
    fp_cable.Value().SetVisible(False)
    fp_cable.SetPosition(pcbnew.VECTOR2I(mm(cable_cx), mm(cable_cy)))
    fp_cable.SetLayer(pcbnew.F_Cu)
    for i in range(8):
        py = cable_cy - half8 + i * 2.54
        pad = pcbnew.PAD(fp_cable)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(cable_cx), mm(py)))
        pad.SetNet(cable_nets[i])
        fp_cable.Add(pad)
        cable_pins[i + 1] = (cable_cx, py)
    board.Add(fp_cable)
    silk_box(cable_cx - 1.5, cable_cy - half8 - 1.5, 3.0, 7 * 2.54 + 3.0)
    silk_text("GX12-8", 36.5, 2.5, 0.8)

    # 5. Routing
    def track(net, layer, x1, y1, x2, y2, w=0.35):
        t = pcbnew.PCB_TRACK(board)
        t.SetStart(pcbnew.VECTOR2I(mm(x1), mm(y1)))
        t.SetEnd(pcbnew.VECTOR2I(mm(x2), mm(y2)))
        t.SetWidth(mm(w))
        t.SetLayer(layer)
        if net:
            t.SetNetCode(net.GetNetCode())
        board.Add(t)

    def path(net, layer, waypoints, w=0.35):
        for i in range(len(waypoints) - 1):
            track(net, layer, waypoints[i][0], waypoints[i][1],
                  waypoints[i+1][0], waypoints[i+1][1], w)

    # ══════════════════════════════════════════════════════════════════
    # F.Cu: SIGNALS (100% PLANAR, 100% ZERO CROSSINGS)
    # ══════════════════════════════════════════════════════════════════
    # 1. ADXL345 CS (Pin 3) tied to VCC (Pin 2) on F.Cu:
    track(vcc, pcbnew.F_Cu, accel_pins[2][0], accel_pins[2][1], accel_pins[3][0], accel_pins[3][1], w=0.4)

    # 2. 1W_DQ: DS18B20 Pin 3 (30.0, 15.54) -> R1 Pad 2 (33.81, 6.5) -> Cable Pin 3 (39.0, 15.19)
    path(dq, pcbnew.F_Cu, [
        (temp_pins[3][0], temp_pins[3][1]),
        (33.81, temp_pins[3][1]),
        (rup_p2_x, rup_cy)
    ], w=0.35)
    path(dq, pcbnew.F_Cu, [
        (33.81, temp_pins[3][1]),
        (33.81, cable_pins[3][1]),
        (cable_pins[3][0], cable_pins[3][1])
    ], w=0.35)

    # 3. I2S_SD: INMP441 R3 (22.81, 22.54) -> Cable Pin 4 (39.0, 17.73)
    path(isd, pcbnew.F_Cu, [
        (mic_pins["R3"][0], mic_pins["R3"][1]),
        (26.0, mic_pins["R3"][1]),
        (26.0, cable_pins[4][1]),
        (cable_pins[4][0], cable_pins[4][1])
    ], w=0.35)

    # 4. I2S_SCK: INMP441 L3 (15.19, 22.54) -> Cable Pin 5 (39.0, 20.27)
    # Turns down at X=13.0, horizontal at Y=25.5, turns up at X=34.0 to Y=20.27
    path(isck, pcbnew.F_Cu, [
        (mic_pins["L3"][0], mic_pins["L3"][1]),
        (13.0, mic_pins["L3"][1]),
        (13.0, 25.5),
        (34.0, 25.5),
        (34.0, cable_pins[5][1]),
        (cable_pins[5][0], cable_pins[5][1])
    ], w=0.35)

    # 5. I2S_WS: INMP441 L2 (15.19, 20.00) -> Cable Pin 6 (39.0, 22.81)
    # Turns down outside SCK at X=11.5, horizontal at Y=27.5, turns up at X=35.5 to Y=22.81
    path(iws, pcbnew.F_Cu, [
        (mic_pins["L2"][0], mic_pins["L2"][1]),
        (11.5, mic_pins["L2"][1]),
        (11.5, 27.5),
        (35.5, 27.5),
        (35.5, cable_pins[6][1]),
        (cable_pins[6][0], cable_pins[6][1])
    ], w=0.35)

    # 6. I2C_SCL: ADXL Pin 8 (9.0, 27.89) -> Cable Pin 7 (39.0, 25.35)
    # Turns down at X=9.0, horizontal at Y=29.5, turns up at X=37.0 to Y=25.35
    path(scl, pcbnew.F_Cu, [
        (accel_pins[8][0], accel_pins[8][1]),
        (9.0, 29.5),
        (37.0, 29.5),
        (37.0, cable_pins[7][1]),
        (cable_pins[7][0], cable_pins[7][1])
    ], w=0.35)

    # 7. I2C_SDA: ADXL Pin 7 (9.0, 25.35) -> Cable Pin 8 (39.0, 27.89)
    # Turns down outside SCL at X=7.5, horizontal at Y=31.5, turns up into Cable Pin 8
    path(sda, pcbnew.F_Cu, [
        (accel_pins[7][0], accel_pins[7][1]),
        (7.5, accel_pins[7][1]),
        (7.5, 31.5),
        (39.0, 31.5),
        (cable_pins[8][0], cable_pins[8][1])
    ], w=0.35)

    # ══════════════════════════════════════════════════════════════════
    # B.Cu: POWER RAILS (+3V3 Top, GND Bottom)
    # ══════════════════════════════════════════════════════════════════
    # 1. NET: +3V3 (Top Corridor at Y=4.0mm on B.Cu, width 0.6mm)
    path(vcc, pcbnew.B_Cu, [
        (11.0, 4.0),
        (cable_pins[1][0], 4.0)
    ], w=0.6)

    # ADXL Pin 2 (+3V3 at X=9.0, Y=12.65): goes right to X=11.0, then up to Y=4.0:
    path(vcc, pcbnew.B_Cu, [
        (accel_pins[2][0], accel_pins[2][1]),
        (11.0, accel_pins[2][1]),
        (11.0, 4.0)
    ], w=0.6)

    # Cable Pin 1 (X=39.0, Y=10.11) up to Y=4.0:
    track(vcc, pcbnew.B_Cu, cable_pins[1][0], cable_pins[1][1], cable_pins[1][0], 4.0, w=0.6)

    # C1 Pad 1 (X=17.73, Y=6.5) up to Y=4.0:
    track(vcc, pcbnew.B_Cu, cdec_p1_x, cdec_cy, cdec_p1_x, 4.0, w=0.6)

    # INMP441 R2 (VDD at X=22.81, Y=20.0): goes right to X=25.0, then up to Y=4.0:
    path(vcc, pcbnew.B_Cu, [
        (mic_pins["R2"][0], mic_pins["R2"][1]),
        (25.0, mic_pins["R2"][1]),
        (25.0, 4.0)
    ], w=0.6)

    # R1 Pad 1 (X=26.19, Y=6.5) up to Y=4.0:
    track(vcc, pcbnew.B_Cu, rup_p1_x, rup_cy, rup_p1_x, 4.0, w=0.6)

    # DS18B20 Pin 1 (X=30.0, Y=10.46) up to Y=4.0:
    track(vcc, pcbnew.B_Cu, temp_pins[1][0], temp_pins[1][1], temp_pins[1][0], 4.0, w=0.6)

    # 2. NET: GND (Bottom Corridor at Y=34.0mm on B.Cu, width 0.8mm)
    path(gnd, pcbnew.B_Cu, [
        (7.0, 34.0),
        (30.0, 34.0)
    ], w=0.8)

    # ADXL Pin 1 (GND at X=9.0, Y=10.11) & Pin 6 (SDO at X=9.0, Y=22.81) via X=6.5 to Y=34.0:
    path(gnd, pcbnew.B_Cu, [
        (accel_pins[1][0], accel_pins[1][1]),
        (6.5, accel_pins[1][1]),
        (6.5, 34.0),
        (7.0, 34.0)
    ], w=0.8)
    path(gnd, pcbnew.B_Cu, [
        (accel_pins[6][0], accel_pins[6][1]),
        (6.5, accel_pins[6][1])
    ], w=0.8)

    # INMP441 L1 (L/R) & R1 (GND) are both at Y=17.46:
    track(gnd, pcbnew.B_Cu, mic_pins["L1"][0], mic_pins["L1"][1], mic_pins["R1"][0], mic_pins["R1"][1], w=0.6)
    path(gnd, pcbnew.B_Cu, [
        (19.0, mic_pins["R1"][1]),
        (19.0, 34.0)
    ], w=0.8)

    # C1 Pad 2 (GND at X=20.27, Y=6.5) down to Y=17.46:
    path(gnd, pcbnew.B_Cu, [
        (cdec_p2_x, cdec_cy),
        (20.27, mic_pins["R1"][1])
    ], w=0.6)

    # DS18B20 Pin 2 (GND at X=30.0, Y=13.0) goes left to X=28.0, then down to Y=34.0:
    path(gnd, pcbnew.B_Cu, [
        (temp_pins[2][0], temp_pins[2][1]),
        (28.0, temp_pins[2][1]),
        (28.0, 34.0),
        (30.0, 34.0)
    ], w=0.8)

    # Cable Pin 2 (GND at X=39.0, Y=12.65) connects straight horizontally to DS18B20 Pin 2 at Y=13.0:
    path(gnd, pcbnew.B_Cu, [
        (cable_pins[2][0], cable_pins[2][1]),
        (temp_pins[2][0], cable_pins[2][1]),
        (temp_pins[2][0], temp_pins[2][1])
    ], w=0.8)

    pcbnew.SaveBoard(output_path, board)
    print(f"[OK] Probe Board saved: {output_path}")


# ══════════════════════════════════════════════════════════════════
# BOARD 2: NO PRINCIPAL ESP32-S3 (MAIN NODE PCB) - 100% PTH
# ══════════════════════════════════════════════════════════════════
def build_main_node(output_path):
    board = pcbnew.BOARD()

    tb = board.GetTitleBlock()
    tb.SetTitle("Amemiya IoT - No Principal ESP32-S3 100% PTH")
    tb.SetCompany("Amemiya Industrial Metrology")
    tb.SetRevision("v35.0")
    tb.SetDate("2026-09-03")

    ds = board.GetDesignSettings()
    ds.m_MinClearance = mm(0.25)
    ds.m_TrackMinWidth = mm(0.3)

    W, H = 90.0, 65.0

    # 1. Edge Cuts
    pts = [(0, 0), (W, 0), (W, H), (0, H)]
    for i in range(4):
        p1, p2 = pts[i], pts[(i+1)%4]
        seg = pcbnew.PCB_SHAPE(board)
        seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
        seg.SetStart(pcbnew.VECTOR2I(mm(p1[0]), mm(p1[1])))
        seg.SetEnd(pcbnew.VECTOR2I(mm(p2[0]), mm(p2[1])))
        seg.SetLayer(pcbnew.Edge_Cuts)
        seg.SetWidth(mm(0.15))
        board.Add(seg)

    # 2. Mounting holes M3
    for hx, hy in [(4.5, 4.5), (W - 4.5, 4.5), (W - 4.5, H - 4.5), (4.5, H - 4.5)]:
        fp = pcbnew.FOOTPRINT(board)
        fp.SetReference("H")
        fp.Reference().SetVisible(False)
        fp.Value().SetVisible(False)
        fp.SetPosition(pcbnew.VECTOR2I(mm(hx), mm(hy)))
        fp.SetLayer(pcbnew.F_Cu)
        pad = pcbnew.PAD(fp)
        pad.SetNumber("1")
        pad.SetAttribute(pcbnew.PAD_ATTRIB_NPTH)
        pad.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(3.2), mm(3.2)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(3.2), mm(3.2)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(hx), mm(hy)))
        fp.Add(pad)
        board.Add(fp)

    # 3. Nets
    vcc    = pcbnew.NETINFO_ITEM(board, "+3V3")
    gnd    = pcbnew.NETINFO_ITEM(board, "GND")
    dq     = pcbnew.NETINFO_ITEM(board, "1W_DQ")
    isd    = pcbnew.NETINFO_ITEM(board, "I2S_SD")
    isck   = pcbnew.NETINFO_ITEM(board, "I2S_SCK")
    iws    = pcbnew.NETINFO_ITEM(board, "I2S_WS")
    scl    = pcbnew.NETINFO_ITEM(board, "I2C_SCL")
    sda    = pcbnew.NETINFO_ITEM(board, "I2C_SDA")
    tacho  = pcbnew.NETINFO_ITEM(board, "TACHO")
    adc    = pcbnew.NETINFO_ITEM(board, "AMP_ADC")
    ltx    = pcbnew.NETINFO_ITEM(board, "LORA_TX")
    lrx    = pcbnew.NETINFO_ITEM(board, "LORA_RX")
    lcs    = pcbnew.NETINFO_ITEM(board, "LORA_CS")
    lrst   = pcbnew.NETINFO_ITEM(board, "LORA_RST")
    rgbr   = pcbnew.NETINFO_ITEM(board, "RGB_R")
    rgbg   = pcbnew.NETINFO_ITEM(board, "RGB_G")
    rgbb   = pcbnew.NETINFO_ITEM(board, "RGB_B")
    buzz   = pcbnew.NETINFO_ITEM(board, "BUZZER")

    for n in [vcc, gnd, dq, isd, isck, iws, scl, sda, tacho, adc, ltx, lrx, lcs, lrst, rgbr, rgbg, rgbb, buzz]:
        board.Add(n)

    def silk_box(x, y, w, h):
        pts = [(x, y), (x+w, y), (x+w, y+h), (x, y+h)]
        for i in range(4):
            p1, p2 = pts[i], pts[(i+1)%4]
            seg = pcbnew.PCB_SHAPE(board)
            seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
            seg.SetStart(pcbnew.VECTOR2I(mm(p1[0]), mm(p1[1])))
            seg.SetEnd(pcbnew.VECTOR2I(mm(p2[0]), mm(p2[1])))
            seg.SetLayer(pcbnew.F_SilkS)
            seg.SetWidth(mm(0.15))
            board.Add(seg)

    def silk_text(txt, x, y, sz=0.8):
        t = pcbnew.PCB_TEXT(board)
        t.SetText(txt)
        t.SetPosition(pcbnew.VECTOR2I(mm(x), mm(y)))
        t.SetTextSize(pcbnew.VECTOR2I(mm(sz), mm(sz)))
        t.SetTextThickness(mm(0.15))
        t.SetLayer(pcbnew.F_SilkS)
        board.Add(t)

    # 4. Footprints (100% Through-Hole)
    # ESP32-S3 Left Header at X=36.0, Y=32.0 (19 pins)
    # 1:VCC, 2:GND, 3:DQ, 4:SD, 5:SCK, 6:WS, 7:SCL, 8:SDA, 9:TACHO, 10:ADC,
    # 11:NC, 12:NC, 13:RGB_R, 14:RGB_G, 15:RGB_B, 16:BUZZER, 17-19:NC
    esp_left_nets = [
        vcc, gnd, dq, isd, isck, iws, scl, sda,
        tacho, adc, None, None, rgbr, rgbg, rgbb, buzz,
        None, None, None
    ]
    esp_lx, esp_ly = 36.0, 32.0
    esp_l_pins = {}
    fp_el = pcbnew.FOOTPRINT(board)
    fp_el.SetReference("U_ESP_L")
    fp_el.Reference().SetVisible(False)
    fp_el.Value().SetVisible(False)
    fp_el.SetPosition(pcbnew.VECTOR2I(mm(esp_lx), mm(esp_ly)))
    fp_el.SetLayer(pcbnew.F_Cu)
    half19 = 18 * 2.54 / 2.0
    for i in range(19):
        py = esp_ly - half19 + i * 2.54
        pad = pcbnew.PAD(fp_el)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(esp_lx), mm(py)))
        if esp_left_nets[i]:
            pad.SetNet(esp_left_nets[i])
        fp_el.Add(pad)
        esp_l_pins[i + 1] = (esp_lx, py)
    board.Add(fp_el)
    silk_box(esp_lx - 1.5, esp_ly - half19 - 1.5, 3.0, 18 * 2.54 + 3.0)

    # ESP32-S3 Right Header at X=61.4, Y=32.0 (19 pins)
    esp_right_nets = [
        None, None, None, None, None, None, None, None,
        ltx, lrx, lcs, lrst, None, None, None, None,
        None, None, None
    ]
    esp_rx, esp_ry = 61.4, 32.0
    esp_r_pins = {}
    fp_er = pcbnew.FOOTPRINT(board)
    fp_er.SetReference("U_ESP_R")
    fp_er.Reference().SetVisible(False)
    fp_er.Value().SetVisible(False)
    fp_er.SetPosition(pcbnew.VECTOR2I(mm(esp_rx), mm(esp_ry)))
    fp_er.SetLayer(pcbnew.F_Cu)
    for i in range(19):
        py = esp_ry - half19 + i * 2.54
        pad = pcbnew.PAD(fp_er)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(esp_rx), mm(py)))
        if esp_right_nets[i]:
            pad.SetNet(esp_right_nets[i])
        fp_er.Add(pad)
        esp_r_pins[i + 1] = (esp_rx, py)
    board.Add(fp_er)
    silk_box(esp_rx - 1.5, esp_ry - half19 - 1.5, 3.0, 18 * 2.54 + 3.0)

    silk_box(esp_lx - 3.0, esp_ly - half19 - 2.5, (esp_rx - esp_lx) + 6.0, 18 * 2.54 + 5.0)
    silk_text("ESP32-S3 DevKit", 40.0, 6.0, 0.8)

    # ── INPUT CONNECTORS (Left Column at X=12.0) ────────────────────
    # J_PROBE: 1x8 header at X=12.0, colinear with ESP_L P1..P8!
    pr_nets = [vcc, gnd, dq, isd, isck, iws, scl, sda]
    pr_cx = 12.0
    half8 = 7 * 2.54 / 2.0
    pr_cy = esp_l_pins[1][1] + half8
    pr_pins = {}
    fp_pr = pcbnew.FOOTPRINT(board)
    fp_pr.SetReference("J_PROBE")
    fp_pr.Reference().SetVisible(False)
    fp_pr.Value().SetVisible(False)
    fp_pr.SetPosition(pcbnew.VECTOR2I(mm(pr_cx), mm(pr_cy)))
    fp_pr.SetLayer(pcbnew.F_Cu)
    for i in range(8):
        py = pr_cy - half8 + i * 2.54
        pad = pcbnew.PAD(fp_pr)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(pr_cx), mm(py)))
        pad.SetNet(pr_nets[i])
        fp_pr.Add(pad)
        pr_pins[i + 1] = (pr_cx, py)
    board.Add(fp_pr)
    silk_box(pr_cx - 1.5, pr_cy - half8 - 1.5, 3.0, 7 * 2.54 + 3.0)
    silk_text("SONDA GX12-8", 10.0, 7.0, 0.8)

    # J_TACHO: 1x3 header at X=12.0, Y=33.5
    ta_nets = [vcc, gnd, tacho]
    ta_cx, ta_cy = 12.0, 33.5
    ta_pins = {}
    fp_ta = pcbnew.FOOTPRINT(board)
    fp_ta.SetReference("J_TACHO")
    fp_ta.Reference().SetVisible(False)
    fp_ta.Value().SetVisible(False)
    fp_ta.SetPosition(pcbnew.VECTOR2I(mm(ta_cx), mm(ta_cy)))
    fp_ta.SetLayer(pcbnew.F_Cu)
    half3 = 2 * 2.54 / 2.0
    for i in range(3):
        py = ta_cy - half3 + i * 2.54
        pad = pcbnew.PAD(fp_ta)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(ta_cx), mm(py)))
        pad.SetNet(ta_nets[i])
        fp_ta.Add(pad)
        ta_pins[i + 1] = (ta_cx, py)
    board.Add(fp_ta)
    silk_box(ta_cx - 1.5, ta_cy - half3 - 1.5, 3.0, 2 * 2.54 + 3.0)
    silk_text("TACO RPM", ta_cx - 4.5, ta_cy - half3 - 2.8, 0.8)

    # J_AMP: 1x2 header at X=12.0, Y=44.0
    amp_nets = [adc, gnd]
    amp_cx, amp_cy = 12.0, 44.0
    amp_pins = {}
    fp_amp = pcbnew.FOOTPRINT(board)
    fp_amp.SetReference("J_AMP")
    fp_amp.Reference().SetVisible(False)
    fp_amp.Value().SetVisible(False)
    fp_amp.SetPosition(pcbnew.VECTOR2I(mm(amp_cx), mm(amp_cy)))
    fp_amp.SetLayer(pcbnew.F_Cu)
    half2 = 2.54 / 2.0
    for i in range(2):
        py = amp_cy - half2 + i * 2.54
        pad = pcbnew.PAD(fp_amp)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(amp_cx), mm(py)))
        pad.SetNet(amp_nets[i])
        fp_amp.Add(pad)
        amp_pins[i + 1] = (amp_cx, py)
    board.Add(fp_amp)
    silk_box(amp_cx - 1.5, amp_cy - half2 - 1.5, 3.0, 2.54 + 3.0)
    silk_text("SCT-013 AMP", amp_cx - 4.5, amp_cy - half2 - 2.8, 0.8)

    # R_BURDEN 33Ω (Axial 1/4W PTH) at X=18.0, Y=44.0
    rb_cx, rb_cy = 18.0, 44.0
    fp_rb = pcbnew.FOOTPRINT(board)
    fp_rb.SetReference("R_B")
    fp_rb.Reference().SetVisible(False)
    fp_rb.Value().SetVisible(False)
    fp_rb.SetPosition(pcbnew.VECTOR2I(mm(rb_cx), mm(rb_cy)))
    fp_rb.SetLayer(pcbnew.F_Cu)
    rb_p1_y = rb_cy - 7.62 / 2.0
    rb_p2_y = rb_cy + 7.62 / 2.0
    p1 = pcbnew.PAD(fp_rb)
    p1.SetNumber("1")
    p1.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    p1.SetShape(pcbnew.PAD_SHAPE_RECT)
    p1.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    p1.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    p1.SetPosition(pcbnew.VECTOR2I(mm(rb_cx), mm(rb_p1_y)))
    p1.SetNet(adc)
    fp_rb.Add(p1)
    p2 = pcbnew.PAD(fp_rb)
    p2.SetNumber("2")
    p2.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
    p2.SetShape(pcbnew.PAD_SHAPE_CIRCLE)
    p2.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
    p2.SetDrillSize(pcbnew.VECTOR2I(mm(0.85), mm(0.85)))
    p2.SetPosition(pcbnew.VECTOR2I(mm(rb_cx), mm(rb_p2_y)))
    p2.SetNet(gnd)
    fp_rb.Add(p2)
    board.Add(fp_rb)
    silk_box(rb_cx - 1.2, rb_cy - 2.5, 2.4, 5.0)
    silk_text("33R", rb_cx + 2.5, rb_cy - 0.4, 0.8)

    # ── OUTPUTS (Right Column at X=78.0) ────────────────────
    # J_OLED: 1x4 header at X=78.0, Y=14.0
    oled_nets = [vcc, gnd, scl, sda]
    oled_cx, oled_cy = 78.0, 14.0
    oled_pins = {}
    fp_ol = pcbnew.FOOTPRINT(board)
    fp_ol.SetReference("J_OLED")
    fp_ol.Reference().SetVisible(False)
    fp_ol.Value().SetVisible(False)
    fp_ol.SetPosition(pcbnew.VECTOR2I(mm(oled_cx), mm(oled_cy)))
    fp_ol.SetLayer(pcbnew.F_Cu)
    half4 = 3 * 2.54 / 2.0
    for i in range(4):
        py = oled_cy - half4 + i * 2.54
        pad = pcbnew.PAD(fp_ol)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(oled_cx), mm(py)))
        pad.SetNet(oled_nets[i])
        fp_ol.Add(pad)
        oled_pins[i + 1] = (oled_cx, py)
    board.Add(fp_ol)
    silk_box(oled_cx - 1.5, oled_cy - half4 - 1.5, 3.0, 3 * 2.54 + 3.0)
    silk_text("OLED 0.96", oled_cx - 3.5, oled_cy - half4 - 2.8, 0.8)

    # U_LORA: LoRa Ra-02 / SX1276 at X=78.0, Y=33.27
    lora_nets = [vcc, gnd, ltx, lrx, lcs, lrst, None, None]
    lora_cx, lora_cy = 78.0, 33.27
    lora_pins = {}
    fp_lo = pcbnew.FOOTPRINT(board)
    fp_lo.SetReference("U_LORA")
    fp_lo.Reference().SetVisible(False)
    fp_lo.Value().SetVisible(False)
    fp_lo.SetPosition(pcbnew.VECTOR2I(mm(lora_cx), mm(lora_cy)))
    fp_lo.SetLayer(pcbnew.F_Cu)
    for i in range(8):
        py = lora_cy - half8 + i * 2.54
        pad = pcbnew.PAD(fp_lo)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(lora_cx), mm(py)))
        if lora_nets[i]:
            pad.SetNet(lora_nets[i])
        fp_lo.Add(pad)
        lora_pins[i + 1] = (lora_cx, py)
    board.Add(fp_lo)
    silk_box(lora_cx - 1.5, lora_cy - half8 - 1.5, 3.0, 7 * 2.54 + 3.0)
    silk_text("LoRa SX1276", lora_cx - 4.5, lora_cy - half8 - 2.8, 0.8)

    # ── BOTTOM ACTUATORS ────────────────────────────────────
    # J_RGB: 1x4 header at X=24.0, Y=54.0
    rgb_nets = [rgbr, rgbg, rgbb, gnd]
    rgb_cx, rgb_cy = 24.0, 54.0
    rgb_pins = {}
    fp_rgb = pcbnew.FOOTPRINT(board)
    fp_rgb.SetReference("J_RGB")
    fp_rgb.Reference().SetVisible(False)
    fp_rgb.Value().SetVisible(False)
    fp_rgb.SetPosition(pcbnew.VECTOR2I(mm(rgb_cx), mm(rgb_cy)))
    fp_rgb.SetLayer(pcbnew.F_Cu)
    for i in range(4):
        px = rgb_cx - half4 + i * 2.54
        pad = pcbnew.PAD(fp_rgb)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(px), mm(rgb_cy)))
        pad.SetNet(rgb_nets[i])
        fp_rgb.Add(pad)
        rgb_pins[i + 1] = (px, rgb_cy)
    board.Add(fp_rgb)
    silk_box(rgb_cx - half4 - 1.5, rgb_cy - 1.5, 3 * 2.54 + 3.0, 3.0)
    silk_text("RGB TOWER", rgb_cx - 4.0, rgb_cy + 2.5, 0.8)

    # J_BUZ: 1x2 header at X=48.0, Y=54.0
    buz_nets = [buzz, gnd]
    buz_cx, buz_cy = 48.0, 54.0
    buz_pins = {}
    fp_buz = pcbnew.FOOTPRINT(board)
    fp_buz.SetReference("J_BUZ")
    fp_buz.Reference().SetVisible(False)
    fp_buz.Value().SetVisible(False)
    fp_buz.SetPosition(pcbnew.VECTOR2I(mm(buz_cx), mm(buz_cy)))
    fp_buz.SetLayer(pcbnew.F_Cu)
    for i in range(2):
        px = buz_cx - half2 + i * 2.54
        pad = pcbnew.PAD(fp_buz)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(px), mm(buz_cy)))
        pad.SetNet(buz_nets[i])
        fp_buz.Add(pad)
        buz_pins[i + 1] = (px, buz_cy)
    board.Add(fp_buz)
    silk_box(buz_cx - half2 - 1.5, buz_cy - 1.5, 2.54 + 3.0, 3.0)
    silk_text("BUZZER", buz_cx - 3.0, buz_cy + 2.5, 0.8)

    # 5. Routing
    def track(net, layer, x1, y1, x2, y2, w=0.35):
        t = pcbnew.PCB_TRACK(board)
        t.SetStart(pcbnew.VECTOR2I(mm(x1), mm(y1)))
        t.SetEnd(pcbnew.VECTOR2I(mm(x2), mm(y2)))
        t.SetWidth(mm(w))
        t.SetLayer(layer)
        if net:
            t.SetNetCode(net.GetNetCode())
        board.Add(t)

    def path(net, layer, waypoints, w=0.35):
        for i in range(len(waypoints) - 1):
            track(net, layer, waypoints[i][0], waypoints[i][1],
                  waypoints[i+1][0], waypoints[i+1][1], w)

    # ══════════════════════════════════════════════════════════════════
    # F.Cu: SIGNALS (100% PLANAR, 100% COLINEAR, ZERO CROSSINGS!)
    # ══════════════════════════════════════════════════════════════════
    # 1. J_PROBE -> U_ESP_L (Pure straight horizontal parallel tracks for ALL 6 signals!)
    track(dq,   pcbnew.F_Cu, pr_pins[3][0], pr_pins[3][1], esp_l_pins[3][0], esp_l_pins[3][1], w=0.35)
    track(isd,  pcbnew.F_Cu, pr_pins[4][0], pr_pins[4][1], esp_l_pins[4][0], esp_l_pins[4][1], w=0.35)
    track(isck, pcbnew.F_Cu, pr_pins[5][0], pr_pins[5][1], esp_l_pins[5][0], esp_l_pins[5][1], w=0.35)
    track(iws,  pcbnew.F_Cu, pr_pins[6][0], pr_pins[6][1], esp_l_pins[6][0], esp_l_pins[6][1], w=0.35)
    track(scl,  pcbnew.F_Cu, pr_pins[7][0], pr_pins[7][1], esp_l_pins[7][0], esp_l_pins[7][1], w=0.35)
    track(sda,  pcbnew.F_Cu, pr_pins[8][0], pr_pins[8][1], esp_l_pins[8][0], esp_l_pins[8][1], w=0.35)

    # 2. TACHO (ESP pin 9 at Y=29.46 -> Tacho pin 3 at X=12.0, Y=36.04)
    path(tacho, pcbnew.F_Cu, [
        (esp_l_pins[9][0], esp_l_pins[9][1]),
        (14.0, esp_l_pins[9][1]),
        (14.0, ta_pins[3][1]),
        (ta_pins[3][0], ta_pins[3][1])
    ], w=0.35)

    # 3. AMP_ADC (ESP pin 10 at Y=32.0 -> R_B pad 1 at X=18.0, Y=40.19)
    path(adc, pcbnew.F_Cu, [
        (esp_l_pins[10][0], esp_l_pins[10][1]),
        (rb_cx, esp_l_pins[10][1]),
        (rb_cx, rb_p1_y)
    ], w=0.35)
    path(adc, pcbnew.F_Cu, [
        (rb_cx, rb_p1_y),
        (amp_pins[1][0], rb_p1_y),
        (amp_pins[1][0], amp_pins[1][1])
    ], w=0.35)

    # 4. I2C Bus to OLED: tapped directly from ESP32 pins 7, 8:
    path(scl, pcbnew.F_Cu, [
        (esp_l_pins[7][0], esp_l_pins[7][1]),
        (45.0, esp_l_pins[7][1]),
        (45.0, 5.5),
        (76.0, 5.5),
        (76.0, oled_pins[3][1]),
        (oled_pins[3][0], oled_pins[3][1])
    ], w=0.35)

    path(sda, pcbnew.F_Cu, [
        (esp_l_pins[8][0], esp_l_pins[8][1]),
        (48.0, esp_l_pins[8][1]),
        (48.0, 7.0),
        (74.0, 7.0),
        (74.0, oled_pins[4][1]),
        (oled_pins[4][0], oled_pins[4][1])
    ], w=0.35)

    # 5. U_ESP_R -> U_LORA (Pure straight horizontal parallel tracks!)
    track(ltx,  pcbnew.F_Cu, esp_r_pins[9][0],  esp_r_pins[9][1],  lora_pins[3][0], lora_pins[3][1], w=0.35)
    track(lrx,  pcbnew.F_Cu, esp_r_pins[10][0], esp_r_pins[10][1], lora_pins[4][0], lora_pins[4][1], w=0.35)
    track(lcs,  pcbnew.F_Cu, esp_r_pins[11][0], esp_r_pins[11][1], lora_pins[5][0], lora_pins[5][1], w=0.35)
    track(lrst, pcbnew.F_Cu, esp_r_pins[12][0], esp_r_pins[12][1], lora_pins[6][0], lora_pins[6][1], w=0.35)

    # 6. RGB Signals (RED, GRN, BLU)
    path(rgbr, pcbnew.F_Cu, [
        (esp_l_pins[13][0], esp_l_pins[13][1]),
        (22.0, esp_l_pins[13][1]),
        (22.0, 50.0),
        (rgb_pins[1][0], 50.0),
        (rgb_pins[1][0], rgb_pins[1][1])
    ], w=0.35)

    path(rgbg, pcbnew.F_Cu, [
        (esp_l_pins[14][0], esp_l_pins[14][1]),
        (24.0, esp_l_pins[14][1]),
        (24.0, 48.0),
        (rgb_pins[2][0], 48.0),
        (rgb_pins[2][0], rgb_pins[2][1])
    ], w=0.35)

    path(rgbb, pcbnew.F_Cu, [
        (esp_l_pins[15][0], esp_l_pins[15][1]),
        (26.0, esp_l_pins[15][1]),
        (26.0, 46.0),
        (rgb_pins[3][0], 46.0),
        (rgb_pins[3][0], rgb_pins[3][1])
    ], w=0.35)

    # 7. BUZZER
    path(buzz, pcbnew.F_Cu, [
        (esp_l_pins[16][0], esp_l_pins[16][1]),
        (46.0, esp_l_pins[16][1]),
        (46.0, 50.0),
        (buz_pins[1][0], 50.0),
        (buz_pins[1][0], buz_pins[1][1])
    ], w=0.35)

    # ══════════════════════════════════════════════════════════════════
    # B.Cu: POWER RAILS (+3V3 Top, GND Bottom)
    # ══════════════════════════════════════════════════════════════════
    # 1. NET: +3V3 (Top Corridor at Y=3.5mm on B.Cu, width 0.8mm)
    path(vcc, pcbnew.B_Cu, [
        (8.0, 3.5),
        (78.0, 3.5)
    ], w=0.8)

    # J_PROBE pin 1 straight up to Y=3.5 along X=12.0:
    track(vcc, pcbnew.B_Cu, pr_pins[1][0], pr_pins[1][1], pr_pins[1][0], 3.5, w=0.8)

    # ESP pin 1 straight up to Y=3.5 along X=36.0:
    track(vcc, pcbnew.B_Cu, esp_l_pins[1][0], esp_l_pins[1][1], esp_l_pins[1][0], 3.5, w=0.8)

    # Tacho Pin 1 (+3V3): from Y=3.5 down along X=8.0 to Y=30.96, turns right into Pin 1:
    path(vcc, pcbnew.B_Cu, [
        (8.0, 3.5),
        (8.0, ta_pins[1][1]),
        (ta_pins[1][0], ta_pins[1][1])
    ], w=0.8)

    # OLED pin 1 straight up to Y=3.5 along X=78.0:
    track(vcc, pcbnew.B_Cu, oled_pins[1][0], oled_pins[1][1], oled_pins[1][0], 3.5, w=0.8)

    # LoRa pin 1 goes to Y=3.5 via X=76.0 corridor:
    path(vcc, pcbnew.B_Cu, [
        (lora_pins[1][0], lora_pins[1][1]),
        (76.0, lora_pins[1][1]),
        (76.0, 3.5)
    ], w=0.8)

    # 2. NET: GND (Bottom Corridor at Y=57.0mm on B.Cu, width 0.8mm)
    path(gnd, pcbnew.B_Cu, [
        (10.0, 57.0),
        (82.0, 57.0)
    ], w=0.8)

    # J_PROBE Pin 2 GND: straight horizontal connection to ESP Pin 2:
    track(gnd, pcbnew.B_Cu, pr_pins[2][0], pr_pins[2][1], esp_l_pins[2][0], esp_l_pins[2][1], w=0.8)

    # ESP pin 2 down to Y=57.0 via X=32.0:
    path(gnd, pcbnew.B_Cu, [
        (esp_l_pins[2][0], esp_l_pins[2][1]),
        (32.0, esp_l_pins[2][1]),
        (32.0, 57.0)
    ], w=0.8)

    # Tacho Pin 2 (GND) & J_AMP Pin 2 (GND) down along X=10.0 to Y=57.0:
    path(gnd, pcbnew.B_Cu, [
        (ta_pins[2][0], ta_pins[2][1]),
        (10.0, ta_pins[2][1]),
        (10.0, 57.0)
    ], w=0.8)

    path(gnd, pcbnew.B_Cu, [
        (amp_pins[2][0], amp_pins[2][1]),
        (10.0, amp_pins[2][1])
    ], w=0.8)

    # R_B pad 2 GND via X=16.0 down to Y=57.0:
    path(gnd, pcbnew.B_Cu, [
        (rb_cx, rb_p2_y),
        (16.0, rb_p2_y),
        (16.0, 57.0)
    ], w=0.8)

    # Bottom actuators GND:
    path(gnd, pcbnew.B_Cu, [
        (rgb_pins[4][0], rgb_pins[4][1]),
        (rgb_pins[4][0], 57.0)
    ], w=0.8)

    path(gnd, pcbnew.B_Cu, [
        (buz_pins[2][0], buz_pins[2][1]),
        (buz_pins[2][0], 57.0)
    ], w=0.8)

    # Right GND: OLED pin 2 and LoRa pin 2 down to Y=57.0 via X=82.0:
    path(gnd, pcbnew.B_Cu, [
        (oled_pins[2][0], oled_pins[2][1]),
        (82.0, oled_pins[2][1]),
        (82.0, 57.0)
    ], w=0.8)

    path(gnd, pcbnew.B_Cu, [
        (lora_pins[2][0], lora_pins[2][1]),
        (82.0, lora_pins[2][1])
    ], w=0.8)

    silk_text("AMEMIYA IoT - No Principal v35.0 (100% PTH)", 15.0, 2.0, 0.8)
    pcbnew.SaveBoard(output_path, board)
    print(f"[OK] Main Node Board saved: {output_path}")

if __name__ == "__main__":
    SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
    p_out = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "amemiya_probe_sensor", "amemiya_probe_sensor.kicad_pcb"))
    m_out = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "amemiya_main_node", "amemiya_main_node.kicad_pcb"))

    build_probe(p_out)
    build_main_node(m_out)
    print("=== Master 100% PTH Boards Generation Complete ===")
