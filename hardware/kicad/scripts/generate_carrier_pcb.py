#!/usr/bin/env python3
"""
Master PCB Generator for Amemiya Main Node Carrier Board
Target: 90.0 x 65.0 mm, 2-layer FR4, 0 DRC errors / 0 warnings.
"""

import os
import sys
import subprocess
import sexpdata
import pcbnew

FP_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\footprints"
NETLIST_PATH = r"hardware/kicad/amemiya_main_node/amemiya_main_node.net"
OUTPUT_PCB = r"hardware/kicad/amemiya_main_node/amemiya_main_node.kicad_pcb"
DSN_PATH = r"hardware/kicad/amemiya_main_node/amemiya_main_node.dsn"
SES_PATH = r"hardware/kicad/amemiya_main_node/amemiya_main_node.ses"
JAVA_EXE = r"C:\Program Files\Eclipse Adoptium\jdk-17.0.13.11-hotspot\bin\java.EXE"
FREEROUTING_JAR = r"C:\Users\andrl\.kicad-mcp\freerouting.jar"

# 1. Component Placement Configuration
# (X, Y in mm, orientation in degrees, layer)
# Board size: 90.0 x 75.0 mm (provides 13.5mm margin below ESP32 to completely clear DevKit overhang)
PLACEMENTS = {
    # Mounting Holes (3.2mm M3) - References hidden
    "H1": (4.5, 4.5, 0, pcbnew.F_Cu),
    "H2": (85.5, 4.5, 0, pcbnew.F_Cu),
    "H3": (85.5, 70.5, 0, pcbnew.F_Cu),
    "H4": (4.5, 70.5, 0, pcbnew.F_Cu),

    # ESP32-S3 DevKit Headers (25.4mm / 1.0" row pitch, Y: 9.0 to 62.34 mm)
    "U_ESP_L": (32.3, 9.0, 0, pcbnew.F_Cu),
    "U_ESP_R": (57.7, 9.0, 0, pcbnew.F_Cu),

    # 3.3V Power Rail Decoupling
    "C_3V3_1": (32.3, 3.5, 0, pcbnew.F_Cu),
    "C_3V3_2": (37.0, 3.5, 0, pcbnew.F_Cu),
    "C_ESP1":  (36.5, 9.0, 0, pcbnew.F_Cu),
    "C_ESP2":  (36.5, 12.5, 0, pcbnew.F_Cu),

    # Sensors - SCT-013 Current Clamp AFE (Top Left)
    "J_AMP":    (12.0, 14.0, 270, pcbnew.F_Cu),
    "R_B":      (18.0, 14.0, 90, pcbnew.F_Cu),
    "R_BIAS1":  (24.0, 9.0, 0, pcbnew.F_Cu),
    "R_BIAS2":  (24.0, 12.5, 0, pcbnew.F_Cu),
    "C_BIAS":   (24.0, 16.0, 0, pcbnew.F_Cu),
    "R_F":      (18.0, 20.0, 0, pcbnew.F_Cu),
    "C_F":      (24.0, 20.0, 0, pcbnew.F_Cu),
    "D_AMP_HI": (24.0, 23.0, 0, pcbnew.F_Cu),
    "D_AMP_LO": (28.0, 23.0, 0, pcbnew.F_Cu),

    # Sensors - GX16-8 Probe Interface (Mid Left)
    "J_PROBE":  (12.0, 28.0, 270, pcbnew.F_Cu),
    "C_PROBE":  (18.0, 28.0, 0, pcbnew.F_Cu),
    "R_PU_1W":  (20.0, 33.0, 0, pcbnew.F_Cu),
    "R_PU_SCL": (20.0, 40.0, 0, pcbnew.F_Cu),
    "R_PU_SDA": (20.0, 43.0, 0, pcbnew.F_Cu),

    # Sensors - Tachometer (Bottom Left)
    "J_TACHO":   (12.0, 54.0, 270, pcbnew.F_Cu),
    "R_PU_TACHO": (18.0, 53.0, 0, pcbnew.F_Cu),
    "R_S_TACHO":  (18.0, 57.0, 0, pcbnew.F_Cu),
    "C_F_TACHO":  (22.0, 57.0, 0, pcbnew.F_Cu),

    # LoRa RF Module (Top Right)
    "U_LORA":      (80.0, 8.0, 0, pcbnew.F_Cu),
    "C_LORA_BULK": (70.0, 11.0, 90, pcbnew.F_Cu),
    "C_LORA_DEC":  (70.0, 19.0, 0, pcbnew.F_Cu),

    # OLED Display Interface (Mid Right)
    "J_OLED": (80.0, 30.0, 0, pcbnew.F_Cu),
    "C_OLED": (74.0, 31.0, 0, pcbnew.F_Cu),

    # Status LEDs (Mid-Bottom Right)
    "J_LEDS": (80.0, 46.0, 270, pcbnew.F_Cu),
    "R_LED1": (74.0, 46.0, 0, pcbnew.F_Cu),
    "R_LED2": (74.0, 48.5, 0, pcbnew.F_Cu),
    "R_LED3": (74.0, 51.0, 0, pcbnew.F_Cu),

    # Buzzer Output
    "J_BUZ": (78.0, 62.0, 0, pcbnew.F_Cu),
    "R_BUZ": (52.0, 12.0, 0, pcbnew.F_Cu),

    # Power Input (5V from Step-Up/BMS) (Bottom Center - completely clear of ESP32)
    "J_BAT": (48.0, 69.5, 0, pcbnew.F_Cu),
    "C_IN1": (43.0, 64.0, 90, pcbnew.F_Cu),
    "C_IN2": (48.0, 64.0, 90, pcbnew.F_Cu),

    # Battery Voltage Sense (Bottom Center-Right)
    "J_BAT_SENSE": (65.0, 69.5, 0, pcbnew.F_Cu),
    "R_BAT1":      (63.0, 64.0, 90, pcbnew.F_Cu),
    "R_BAT2":      (67.0, 64.0, 90, pcbnew.F_Cu),
    "C_BAT":       (67.0, 59.0, 0, pcbnew.F_Cu),
}

# Textbook silkscreen labeling positions (X, Y in mm, text angle in degrees)
CONNECTOR_REF_POS = {
    "U_ESP_L":     (36.0, 20.0, 90),
    "U_ESP_R":     (54.0, 20.0, 90),
    "U_LORA":      (85.0, 15.0, 90),
    "J_OLED":      (85.0, 34.0, 90),
    "J_LEDS":      (85.0, 49.0, 90),
    "J_BUZ":       (78.0, 58.0, 0),
    "J_BAT":       (48.0, 66.0, 0),
    "J_BAT_SENSE": (65.0, 66.0, 0),
    "J_AMP":       (6.0, 14.0, 90),
    "J_PROBE":     (6.0, 36.0, 90),
    "J_TACHO":     (6.0, 55.0, 90),
}

def parse_netlist(netlist_file):
    with open(netlist_file, "r", encoding="utf-8", errors="ignore") as f:
        parsed = sexpdata.loads(f.read())
    
    comps = {}
    nets = {}
    for item in parsed:
        if not isinstance(item, list) or len(item) == 0:
            continue
        tag = getattr(item[0], 'value', lambda: str(item[0]))()
        if tag == 'components':
            for c in item[1:]:
                ref, val, fp = None, None, None
                for fld in c[1:]:
                    ftag = getattr(fld[0], 'value', lambda: str(fld[0]))()
                    if ftag == 'ref': ref = fld[1]
                    elif ftag == 'value': val = fld[1]
                    elif ftag == 'footprint': fp = fld[1]
                if ref:
                    comps[ref] = {'value': val, 'footprint': fp}
        elif tag == 'nets':
            for n in item[1:]:
                nname = None
                nodes = []
                for fld in n[1:]:
                    ftag = getattr(fld[0], 'value', lambda: str(fld[0]))()
                    if ftag == 'name': nname = fld[1]
                    elif ftag == 'node':
                        nref, npin = None, None
                        for nf in fld[1:]:
                            nftag = getattr(nf[0], 'value', lambda: str(nf[0]))()
                            if nftag == 'ref': nref = nf[1]
                            elif nftag == 'pin': npin = str(nf[1])
                        if nref and npin:
                            nodes.append((nref, npin))
                if nname:
                    nets[nname] = nodes
    return comps, nets

def build_board():
    print("[1/6] Initializing KiCad PCB Board (90.0 x 75.0 mm)...")
    b = pcbnew.BOARD()
    
    # Board thickness & layers
    b.GetDesignSettings().SetBoardThickness(pcbnew.FromMM(1.6))
    
    # Board Outline (Edge.Cuts): 90.0 x 75.0 mm rectangle
    # Let's make a precise rectangle 0 to 90, 0 to 75
    seg_top = pcbnew.PCB_SHAPE(b)
    seg_top.SetShape(pcbnew.SHAPE_T_SEGMENT)
    seg_top.SetLayer(pcbnew.Edge_Cuts)
    seg_top.SetWidth(pcbnew.FromMM(0.15))
    seg_top.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(0), pcbnew.FromMM(0)))
    seg_top.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(90), pcbnew.FromMM(0)))
    b.Add(seg_top)

    seg_right = pcbnew.PCB_SHAPE(b)
    seg_right.SetShape(pcbnew.SHAPE_T_SEGMENT)
    seg_right.SetLayer(pcbnew.Edge_Cuts)
    seg_right.SetWidth(pcbnew.FromMM(0.15))
    seg_right.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(90), pcbnew.FromMM(0)))
    seg_right.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(90), pcbnew.FromMM(75)))
    b.Add(seg_right)

    seg_bottom = pcbnew.PCB_SHAPE(b)
    seg_bottom.SetShape(pcbnew.SHAPE_T_SEGMENT)
    seg_bottom.SetLayer(pcbnew.Edge_Cuts)
    seg_bottom.SetWidth(pcbnew.FromMM(0.15))
    seg_bottom.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(90), pcbnew.FromMM(75)))
    seg_bottom.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(0), pcbnew.FromMM(75)))
    b.Add(seg_bottom)

    seg_left = pcbnew.PCB_SHAPE(b)
    seg_left.SetShape(pcbnew.SHAPE_T_SEGMENT)
    seg_left.SetLayer(pcbnew.Edge_Cuts)
    seg_left.SetWidth(pcbnew.FromMM(0.15))
    seg_left.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(0), pcbnew.FromMM(75)))
    seg_left.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(0), pcbnew.FromMM(0)))
    b.Add(seg_left)

    # Design Rules & Netclasses
    net_settings = b.GetDesignSettings().m_NetSettings
    default_nc = net_settings.GetDefaultNetclass()
    default_nc.SetTrackWidth(pcbnew.FromMM(0.30))  # 12 mil
    default_nc.SetClearance(pcbnew.FromMM(0.20))   # 8 mil
    default_nc.SetViaDiameter(pcbnew.FromMM(0.80)) # 31 mil
    default_nc.SetViaDrill(pcbnew.FromMM(0.40))    # 16 mil

    b.GetDesignSettings().m_MinResolvedSpokes = 1

    pnc = pcbnew.NETCLASS("Power")
    pnc.SetTrackWidth(pcbnew.FromMM(0.60))         # 24 mil
    pnc.SetClearance(pcbnew.FromMM(0.25))          # 10 mil
    pnc.SetViaDiameter(pcbnew.FromMM(0.80))
    pnc.SetViaDrill(pcbnew.FromMM(0.40))
    net_settings.SetNetclass("Power", pnc)

    for pnet in ["+5V", "+3V3", "GND", "/Power_Supply/VBAT_RAW"]:
        net_settings.SetNetclassPatternAssignment(pnet, "Power")

    # Parse Netlist
    print("[2/6] Parsing netlist & populating nets...")
    comps, nets = parse_netlist(NETLIST_PATH)
    
    # Add all nets to board
    net_obj_map = {}
    for netname in nets.keys():
        net_item = pcbnew.NETINFO_ITEM(b, netname)
        b.Add(net_item)
        net_obj_map[netname] = net_item

    # Add components
    print(f"[3/6] Placing {len(comps)} components...")
    missing_placements = [r for r in comps if r not in PLACEMENTS]
    if missing_placements:
        raise ValueError(f"Missing placement configuration for: {missing_placements}")

    for ref, cdata in comps.items():
        fp_str = cdata["footprint"]
        val = cdata["value"]
        lib, fp_name = fp_str.split(":")
        lib_path = os.path.join(FP_DIR, f"{lib}.pretty")
        
        fp = pcbnew.FootprintLoad(lib_path, fp_name)
        if not fp:
            raise FileNotFoundError(f"Could not load footprint: {lib_path} / {fp_name}")
        
        fp.SetReference(ref)
        fp.Reference().SetText(ref)
        
        # Silkscreen reference visibility:
        # Show on connectors & modules for assembly/wiring clarity;
        # Hide for small SMD passives & mounting holes to eliminate silk overlap/clearance violations
        is_connector_or_module = ref.startswith("J_") or ref.startswith("U_")
        if is_connector_or_module:
            fp.Reference().SetVisible(True)
            fp.Reference().SetTextSize(pcbnew.VECTOR2I(pcbnew.FromMM(0.8), pcbnew.FromMM(0.8)))
            fp.Reference().SetTextThickness(pcbnew.FromMM(0.15))
        else:
            fp.Reference().SetVisible(False)

        fp.SetValue(val)
        fp.Value().SetText(val)
        fp.Value().SetVisible(False)  # Hide value on silk to avoid clutter

        px, py, rot, layer = PLACEMENTS[ref]
        fp.SetPosition(pcbnew.VECTOR2I(pcbnew.FromMM(px), pcbnew.FromMM(py)))
        fp.SetOrientationDegrees(rot)
        if layer == pcbnew.B_Cu:
            fp.SetLayerAndFlip(pcbnew.B_Cu)

        # Precise textbook silkscreen reference placement for connectors
        if ref in CONNECTOR_REF_POS:
            tx, ty, tang = CONNECTOR_REF_POS[ref]
            fp.Reference().SetPosition(pcbnew.VECTOR2I(pcbnew.FromMM(tx), pcbnew.FromMM(ty)))
            fp.Reference().SetTextAngleDegrees(tang)
        
        b.Add(fp)

    # Assign nets to pads
    print("[4/6] Connecting nets to component pads...")
    for netname, nodes in nets.items():
        net_item = net_obj_map[netname]
        for ref, pin in nodes:
            fp = b.FindFootprintByReference(ref)
            if not fp:
                print(f"Warning: footprint {ref} not found on board")
                continue
            pad = fp.FindPadByNumber(pin)
            if not pad:
                print(f"Warning: pad {pin} not found on {ref}")
                continue
            pad.SetNet(net_item)

    # Save unrouted board first
    os.makedirs(os.path.dirname(OUTPUT_PCB), exist_ok=True)
    pcbnew.SaveBoard(OUTPUT_PCB, b)
    print(f"Unrouted board saved to {OUTPUT_PCB}")
    return b

def autoroute(b, force=False):
    print("[5/6] Handling routing (DSN / Freerouting / SES)...")
    dsn_abs = os.path.abspath(DSN_PATH)
    ses_abs = os.path.abspath(SES_PATH)

    if not os.path.exists(ses_abs) or force:
        ret = pcbnew.ExportSpecctraDSN(b, dsn_abs)
        if not ret or not os.path.exists(dsn_abs):
            raise RuntimeError("Failed to export Specctra DSN file")

        # Run Freerouting
        cmd = [JAVA_EXE, "-jar", FREEROUTING_JAR, "-de", dsn_abs, "-do", ses_abs, "-mp", "30", "-l", "1"]
        print("Running Freerouting:", " ".join(cmd))
        res = subprocess.run(cmd, capture_output=True, text=True)
        print("Freerouting output:\n", res.stdout)
        if res.stderr:
            print("Freerouting stderr:\n", res.stderr)
        
        if not os.path.exists(ses_abs):
            raise RuntimeError("Freerouting did not generate SES file")
    else:
        print(f"Using existing routed SES file: {ses_abs}")

    # Import SES into board
    print("Importing SES routes into board...")
    ret_ses = pcbnew.ImportSpecctraSES(b, ses_abs)
    if not ret_ses:
        raise RuntimeError("Failed to import Specctra SES file into board")
    print(f"Successfully imported routes. Board now has {len(b.GetTracks())} tracks and vias.")

def add_ground_planes(b):
    print("[6/6] Adding solid GND copper planes on F.Cu and B.Cu...")
    gnd_net = b.FindNet("GND")
    if not gnd_net:
        raise ValueError("GND net not found on board")

    # Add B.Cu GND zone
    z_b = pcbnew.ZONE(b)
    z_b.SetLayer(pcbnew.B_Cu)
    z_b.SetNet(gnd_net)
    z_b.SetAssignedPriority(0)
    z_b.SetPadConnection(pcbnew.ZONE_CONNECTION_THERMAL)
    z_b.SetThermalReliefSpokeWidth(pcbnew.FromMM(0.4))
    z_b.SetThermalReliefGap(pcbnew.FromMM(0.35))
    z_b.SetLocalClearance(pcbnew.FromMM(0.35))
    z_b.SetMinThickness(pcbnew.FromMM(0.20))
    ol_b = z_b.Outline()
    ol_b.NewOutline()
    ol_b.Append(pcbnew.FromMM(0.5), pcbnew.FromMM(0.5))
    ol_b.Append(pcbnew.FromMM(89.5), pcbnew.FromMM(0.5))
    ol_b.Append(pcbnew.FromMM(89.5), pcbnew.FromMM(74.5))
    ol_b.Append(pcbnew.FromMM(0.5), pcbnew.FromMM(74.5))
    b.Add(z_b)

    # Add F.Cu GND zone
    z_f = pcbnew.ZONE(b)
    z_f.SetLayer(pcbnew.F_Cu)
    z_f.SetNet(gnd_net)
    z_f.SetAssignedPriority(0)
    z_f.SetPadConnection(pcbnew.ZONE_CONNECTION_THERMAL)
    z_f.SetThermalReliefSpokeWidth(pcbnew.FromMM(0.4))
    z_f.SetThermalReliefGap(pcbnew.FromMM(0.35))
    z_f.SetLocalClearance(pcbnew.FromMM(0.35))
    z_f.SetMinThickness(pcbnew.FromMM(0.20))
    ol_f = z_f.Outline()
    ol_f.NewOutline()
    ol_f.Append(pcbnew.FromMM(0.5), pcbnew.FromMM(0.5))
    ol_f.Append(pcbnew.FromMM(89.5), pcbnew.FromMM(0.5))
    ol_f.Append(pcbnew.FromMM(89.5), pcbnew.FromMM(74.5))
    ol_f.Append(pcbnew.FromMM(0.5), pcbnew.FromMM(74.5))
    b.Add(z_f)

    # Fill zones
    pcbnew.SaveBoard(OUTPUT_PCB, b)
    # Reload and fill to ensure all geometry caches are clean
    b_loaded = pcbnew.LoadBoard(OUTPUT_PCB)
    zf = pcbnew.ZONE_FILLER(b_loaded)
    zf.Fill(b_loaded.Zones())
    pcbnew.SaveBoard(OUTPUT_PCB, b_loaded)
    print("GND Copper planes filled and saved successfully.")

def run_drc():
    print("[DRC] Running KiCad Design Rules Check (DRC)...")
    kicad_cli = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
    report_json = r"hardware/kicad/amemiya_main_node/pcb_drc_report.json"
    cmd = [
        kicad_cli, "pcb", "drc",
        "--output", report_json,
        "--format", "json",
        OUTPUT_PCB
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    print(res.stdout)
    if res.stderr:
        print(res.stderr)
    return report_json

if __name__ == "__main__":
    force_route = "--force-route" in sys.argv
    board = build_board()
    autoroute(board, force=force_route)
    add_ground_planes(board)
    print("PCB Generation complete!")
    run_drc()
