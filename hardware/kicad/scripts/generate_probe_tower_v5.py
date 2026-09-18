"""
Amemiya Industrial IoT - Sonda Cartucho Torre v5.0 (amemiya_probe_tower)
- Roteamento 100% Planar, Zero Cruzamentos, Zero Erros DRC
- 24.0 mm x 48.0 mm (100% Through-Hole)
"""
import os
import sys
for p in [r"C:\Program Files\KiCad\9.0\bin", r"C:\Program Files\KiCad\8.0\bin"]:
    if os.path.exists(p) and p not in sys.path:
        sys.path.insert(0, p)
        os.environ["PATH"] = p + ";" + os.environ.get("PATH", "")
import pcbnew

def mm(v): return pcbnew.FromMM(v)

def build_probe_tower(output_path):
    board = pcbnew.BOARD()

    tb = board.GetTitleBlock()
    tb.SetTitle("Amemiya IoT - Sonda Cartucho Torre (Tractian Style) 100% PTH")
    tb.SetCompany("Amemiya Industrial Metrology")
    tb.SetRevision("v5.0")
    tb.SetDate("2026-09-03")

    ds = board.GetDesignSettings()
    ds.m_MinClearance = mm(0.25)
    ds.m_TrackMinWidth = mm(0.3)

    W, H = 24.0, 48.0

    # 1. Edge Cuts (24.0 mm x 48.0 mm)
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

    # 2. Nets
    vcc  = pcbnew.NETINFO_ITEM(board, "+3V3")
    gnd  = pcbnew.NETINFO_ITEM(board, "GND")
    dq   = pcbnew.NETINFO_ITEM(board, "1W_DQ")
    scl  = pcbnew.NETINFO_ITEM(board, "I2C_SCL")
    sda  = pcbnew.NETINFO_ITEM(board, "I2C_SDA")
    isck = pcbnew.NETINFO_ITEM(board, "I2S_SCK")
    isd  = pcbnew.NETINFO_ITEM(board, "I2S_SD")
    iws  = pcbnew.NETINFO_ITEM(board, "I2S_WS")

    for n in [vcc, gnd, dq, scl, sda, isck, isd, iws]:
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

    # 3. Footprints (100% Through-Hole)
    # ── A. J_CABLE: Horizontal 1x8 at Y=43.0 mm (centered at X=12.0)
    # 1:VCC, 2:GND, 3:SCL, 4:SDA, 5:DQ, 6:SCK, 7:SD, 8:WS
    cable_nets = [vcc, gnd, scl, sda, dq, isck, isd, iws]
    cable_cx, cable_cy = 12.0, 43.0
    half8 = 7 * 2.54 / 2.0  # 8.89 mm
    cable_pins = {}
    fp_cable = pcbnew.FOOTPRINT(board)
    fp_cable.SetReference("J_CABLE")
    fp_cable.Reference().SetVisible(False)
    fp_cable.Value().SetVisible(False)
    fp_cable.SetPosition(pcbnew.VECTOR2I(mm(cable_cx), mm(cable_cy)))
    fp_cable.SetLayer(pcbnew.F_Cu)
    for i in range(8):
        px = cable_cx - half8 + i * 2.54
        pad = pcbnew.PAD(fp_cable)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(px), mm(cable_cy)))
        pad.SetNet(cable_nets[i])
        fp_cable.Add(pad)
        cable_pins[i + 1] = (px, cable_cy)
    board.Add(fp_cable)
    silk_box(cable_cx - half8 - 1.5, cable_cy - 1.5, 7 * 2.54 + 3.0, 3.0)
    silk_text("GX12-8", 8.5, 46.5, 0.8)

    # ── B. J_ADXL: Vertical 1x8 on Left Column at X=4.5 mm (from Y=13.0 to Y=30.78)
    # 1:GND, 2:VCC, 3:CS(VCC), 4:INT1(NC), 5:INT2(NC), 6:SDO(GND), 7:SDA, 8:SCL
    accel_nets = [gnd, vcc, vcc, None, None, gnd, sda, scl]
    accel_cx = 4.5
    accel_cy = 21.89
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
    silk_text("ADXL", 1.5, 9.5, 0.8)

    # ── C. J_MIC: Vertical 1x6 on Right Column at X=19.5 mm (from Y=18.08 to Y=30.78)
    # 1:WS, 2:SCK, 3:SD, 4:GND(L/R), 5:GND, 6:VCC
    mic_nets = [iws, isck, isd, gnd, gnd, vcc]
    mic_cx = 19.5
    half6 = 5 * 2.54 / 2.0  # 6.35 mm
    mic_cy = 24.43
    mic_pins = {}
    fp_mic = pcbnew.FOOTPRINT(board)
    fp_mic.SetReference("J_MIC")
    fp_mic.Reference().SetVisible(False)
    fp_mic.Value().SetVisible(False)
    fp_mic.SetPosition(pcbnew.VECTOR2I(mm(mic_cx), mm(mic_cy)))
    fp_mic.SetLayer(pcbnew.F_Cu)
    for i in range(6):
        py = mic_cy - half6 + i * 2.54
        pad = pcbnew.PAD(fp_mic)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(mic_cx), mm(py)))
        pad.SetNet(mic_nets[i])
        fp_mic.Add(pad)
        mic_pins[i + 1] = (mic_cx, py)
    board.Add(fp_mic)
    silk_box(mic_cx - 1.5, mic_cy - half6 - 1.5, 3.0, 5 * 2.54 + 3.0)
    silk_text("MIC", 17.5, 14.5, 0.8)

    # ── D. J_TEMP: DS18B20 1x3 at Bottom Center (Y=4.5 mm, X=11.0 mm)
    temp_nets = [vcc, gnd, dq]
    temp_cx, temp_cy = 11.0, 4.5
    half3 = 2 * 2.54 / 2.0  # 2.54 mm
    temp_pins = {}
    fp_temp = pcbnew.FOOTPRINT(board)
    fp_temp.SetReference("J_TEMP")
    fp_temp.Reference().SetVisible(False)
    fp_temp.Value().SetVisible(False)
    fp_temp.SetPosition(pcbnew.VECTOR2I(mm(temp_cx), mm(temp_cy)))
    fp_temp.SetLayer(pcbnew.F_Cu)
    for i in range(3):
        px = temp_cx - half3 + i * 2.54
        pad = pcbnew.PAD(fp_temp)
        pad.SetNumber(str(i + 1))
        pad.SetAttribute(pcbnew.PAD_ATTRIB_PTH)
        pad.SetShape(pcbnew.PAD_SHAPE_RECT if i == 0 else pcbnew.PAD_SHAPE_CIRCLE)
        pad.SetSize(pcbnew.VECTOR2I(mm(1.8), mm(1.8)))
        pad.SetDrillSize(pcbnew.VECTOR2I(mm(1.0), mm(1.0)))
        pad.SetPosition(pcbnew.VECTOR2I(mm(px), mm(temp_cy)))
        pad.SetNet(temp_nets[i])
        fp_temp.Add(pad)
        temp_pins[i + 1] = (px, temp_cy)
    board.Add(fp_temp)
    silk_box(temp_cx - half3 - 1.5, temp_cy - 1.5, 2 * 2.54 + 3.0, 3.0)
    silk_text("DS18B20", 7.0, 1.5, 0.8)

    # ── E. R1: Axial resistor (4.7kΩ) horizontal at Y=10.0 mm, X=14.0 mm
    rup_cx, rup_cy = 14.0, 10.0
    fp_rup = pcbnew.FOOTPRINT(board)
    fp_rup.SetReference("R1")
    fp_rup.Reference().SetVisible(False)
    fp_rup.Value().SetVisible(False)
    fp_rup.SetPosition(pcbnew.VECTOR2I(mm(rup_cx), mm(rup_cy)))
    fp_rup.SetLayer(pcbnew.F_Cu)
    rup_p1_x = rup_cx - 7.62 / 2.0  # 10.19 (VCC)
    rup_p2_x = rup_cx + 7.62 / 2.0  # 17.81 (DQ)
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
    silk_text("R1", 13.0, 12.5, 0.8)

    # ── F. C1: Radial capacitor (100nF) at Y=4.5 mm, X=19.0 mm
    cdec_cx, cdec_cy = 19.0, 4.5
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
    silk_text("C1", 18.0, 1.5, 0.8)

    # 4. Routing
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

    # 2. I2C_SCL: ADXL Pin 8 (4.5, 30.78) -> Cable Pin 3 (8.19, 43.0)
    path(scl, pcbnew.F_Cu, [
        (accel_pins[8][0], accel_pins[8][1]),
        (cable_pins[3][0], accel_pins[8][1]),
        (cable_pins[3][0], cable_pins[3][1])
    ], w=0.35)

    # 3. I2C_SDA: ADXL Pin 7 (4.5, 28.24) -> Cable Pin 4 (10.73, 43.0)
    path(sda, pcbnew.F_Cu, [
        (accel_pins[7][0], accel_pins[7][1]),
        (cable_pins[4][0], accel_pins[7][1]),
        (cable_pins[4][0], cable_pins[4][1])
    ], w=0.35)

    # 4. 1W_DQ: DS18B20 Pin 3 (13.54, 4.5) -> R1 Pad 2 (17.81, 10.0) -> Cable Pin 5 (13.27, 43.0)
    # Exits UP from temp_pins[3] to Y=7.0, then goes to R1 Pad 2 and Cable Pin 5:
    path(dq, pcbnew.F_Cu, [
        (temp_pins[3][0], temp_pins[3][1]),
        (temp_pins[3][0], 7.0),
        (rup_p2_x, 7.0),
        (rup_p2_x, rup_cy)
    ], w=0.35)
    path(dq, pcbnew.F_Cu, [
        (temp_pins[3][0], 7.0),
        (cable_pins[5][0], 7.0),
        (cable_pins[5][0], cable_pins[5][1])
    ], w=0.35)

    # 5. I2S_SCK: J_MIC Pin 2 (19.5, 20.62) -> Cable Pin 6 (15.81, 43.0)
    path(isck, pcbnew.F_Cu, [
        (mic_pins[2][0], mic_pins[2][1]),
        (cable_pins[6][0], mic_pins[2][1]),
        (cable_pins[6][0], cable_pins[6][1])
    ], w=0.35)

    # 6. I2S_SD: J_MIC Pin 3 (19.5, 23.16) -> Cable Pin 7 (18.35, 43.0)
    path(isd, pcbnew.F_Cu, [
        (mic_pins[3][0], mic_pins[3][1]),
        (17.5, mic_pins[3][1]),
        (17.5, 38.0),
        (cable_pins[7][0], 38.0),
        (cable_pins[7][0], cable_pins[7][1])
    ], w=0.35)

    # 7. I2S_WS: J_MIC Pin 1 (19.5, 18.08) -> Cable Pin 8 (20.89, 43.0)
    path(iws, pcbnew.F_Cu, [
        (mic_pins[1][0], mic_pins[1][1]),
        (cable_pins[8][0], mic_pins[1][1]),
        (cable_pins[8][0], cable_pins[8][1])
    ], w=0.35)

    # ══════════════════════════════════════════════════════════════════
    # B.Cu: POWER RAILS (+3V3 Left, GND Right)
    # ══════════════════════════════════════════════════════════════════
    # 1. NET: +3V3
    # Left vertical corridor at X=2.0 mm on B.Cu from Cable Pin 1 (3.11, 43.0) down to Y=6.5:
    path(vcc, pcbnew.B_Cu, [
        (cable_pins[1][0], cable_pins[1][1]),
        (2.0, cable_pins[1][1]),
        (2.0, 6.5)
    ], w=0.6)

    # ADXL Pin 2 (+3V3 at 4.5, 15.54) connects to X=2.0:
    track(vcc, pcbnew.B_Cu, accel_pins[2][0], accel_pins[2][1], 2.0, accel_pins[2][1], w=0.6)

    # Highway at Y=6.5 feeding DS18B20 Pin 1 (8.46), R1 Pad 1 (10.19), C1 Pad 1 (17.73):
    path(vcc, pcbnew.B_Cu, [
        (2.0, 6.5),
        (cdec_p1_x, 6.5)
    ], w=0.6)
    track(vcc, pcbnew.B_Cu, temp_pins[1][0], temp_pins[1][1], temp_pins[1][0], 6.5, w=0.6)
    track(vcc, pcbnew.B_Cu, rup_p1_x, rup_cy, rup_p1_x, 6.5, w=0.6)
    track(vcc, pcbnew.B_Cu, cdec_p1_x, cdec_cy, cdec_p1_x, 6.5, w=0.6)

    # +3V3 to J_MIC Pin 6 (19.5, 30.78) via top corridor at Y=35.0 mm:
    path(vcc, pcbnew.B_Cu, [
        (2.0, 35.0),
        (mic_pins[6][0], 35.0),
        (mic_pins[6][0], mic_pins[6][1])
    ], w=0.6)

    # 2. NET: GND
    # Right vertical corridor at X=22.0 mm on B.Cu from Y=41.0 down to Y=2.5:
    path(gnd, pcbnew.B_Cu, [
        (cable_pins[2][0], cable_pins[2][1]),
        (cable_pins[2][0], 41.0),
        (22.0, 41.0),
        (22.0, 2.5)
    ], w=0.6)

    # J_MIC Pin 4 (GND at 19.5, 25.70) and Pin 5 (GND at 19.5, 28.24) connect to X=22.0:
    track(gnd, pcbnew.B_Cu, mic_pins[4][0], mic_pins[4][1], 22.0, mic_pins[4][1], w=0.6)
    track(gnd, pcbnew.B_Cu, mic_pins[5][0], mic_pins[5][1], 22.0, mic_pins[5][1], w=0.6)

    # ADXL Pin 6 (SDO GND at 4.5, 25.70) connects horizontally to J_MIC Pin 4 at Y=25.70 on B.Cu!
    track(gnd, pcbnew.B_Cu, accel_pins[6][0], accel_pins[6][1], mic_pins[4][0], mic_pins[4][1], w=0.6)

    # ADXL Pin 1 (GND at 4.5, 13.0) connects horizontally to X=22.0 at Y=13.0 on B.Cu!
    track(gnd, pcbnew.B_Cu, accel_pins[1][0], accel_pins[1][1], 22.0, accel_pins[1][1], w=0.6)

    # Bottom GND highway at Y=2.5 mm from X=22.0 to X=11.0 mm:
    path(gnd, pcbnew.B_Cu, [
        (22.0, 2.5),
        (temp_pins[2][0], 2.5)
    ], w=0.6)

    # C1 Pad 2 (GND at 20.27, 4.5) down to Y=2.5:
    track(gnd, pcbnew.B_Cu, cdec_p2_x, cdec_cy, cdec_p2_x, 2.5, w=0.6)

    # DS18B20 Pin 2 (GND at 11.0, 4.5) down to Y=2.5:
    track(gnd, pcbnew.B_Cu, temp_pins[2][0], temp_pins[2][1], temp_pins[2][0], 2.5, w=0.6)

    pcbnew.SaveBoard(output_path, board)
    print(f"[OK] Probe Tower Board saved: {output_path}")

if __name__ == "__main__":
    SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
    out_dir = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "amemiya_probe_tower"))
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "amemiya_probe_tower.kicad_pcb")
    build_probe_tower(out_file)
    print("=== Probe Tower v5 Generation Complete ===")
