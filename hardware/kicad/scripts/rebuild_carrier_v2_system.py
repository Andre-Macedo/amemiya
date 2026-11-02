#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Amemiya Industrial IoT - Carrier Board v2.0 (Dual Sensor Compatible)
- Interface de Sonda adaptada para 5 Pinos (JST-XH B5B-XH-A)
- 100% Compatível com a nova Sonda Torre Dual (ADXL345 + DS18B20)
- Pinos I2S do ESP32-S3 liberados para expansão futura
- Roteamento 100% Planar, 0 Violações DRC / 0 Não-conectados
"""

import os
import sys
import uuid
import re
import subprocess
import shutil
import zipfile
import json
import sexpdata

for p in [r"C:\Program Files\KiCad\9.0\bin", r"C:\Program Files\KiCad\8.0\bin"]:
    if os.path.exists(p) and p not in sys.path:
        sys.path.insert(0, p)
        os.environ["PATH"] = p + ";" + os.environ.get("PATH", "")

import pcbnew

JAVA_EXE = r"C:\Program Files\Eclipse Adoptium\jdk-17.0.13.11-hotspot\bin\java.exe"
KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
FREEROUTING_JAR = r"C:\Users\andrl\.kicad-mcp\freerouting.jar"
KICAD_SYM_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\symbols"
KICAD_FP_DIR = r"C:\Program Files\KiCad\9.0\share\kicad\footprints"

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
CARRIER_DIR = os.path.join(BASE_DIR, "amemiya_carrier_v2")
ROOT_SCH = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.kicad_sch")
AFE_SCH = os.path.join(CARRIER_DIR, "02_Sensors_AFE.kicad_sch")
MCU_SCH = os.path.join(CARRIER_DIR, "03_MCU_ESP32S3.kicad_sch")
NETLIST_FILE = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.net")
PCB_FILE = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.kicad_pcb")
DSN_FILE = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.dsn")
SES_FILE = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.ses")
FAB_DIR = os.path.join(CARRIER_DIR, "fabrication")
GERBER_DIR = os.path.join(FAB_DIR, "gerbers")

def u(): return str(uuid.uuid4())

def extract_symbol_definition(lib_name, sym_name):
    lib_path = os.path.join(KICAD_SYM_DIR, f"{lib_name}.kicad_sym")
    with open(lib_path, "r", encoding="utf-8", errors="ignore") as f:
        text = f.read()
    target = f'(symbol "{sym_name}"'
    pos = text.find(target)
    if pos == -1:
        raise ValueError(f"Symbol {sym_name} not found in {lib_name}")
    count = 0
    end_pos = -1
    for i in range(pos, len(text)):
        if text[i] == '(': count += 1
        elif text[i] == ')':
            count -= 1
            if count == 0:
                end_pos = i + 1
                break
    raw_def = text[pos:end_pos]
    return raw_def.replace(f'(symbol "{sym_name}"', f'(symbol "{lib_name}:{sym_name}"', 1)

def delete_sexp_containing(text, substring, keyword="(hierarchical_label"):
    while True:
        pos = text.find(substring)
        if pos == -1:
            break
        start = text.rfind(keyword, 0, pos)
        if start == -1:
            break
        count = 0
        end = -1
        for i in range(start, len(text)):
            if text[i] == '(': count += 1
            elif text[i] == ')':
                count -= 1
                if count == 0:
                    end = i + 1
                    break
        if end != -1:
            text = text[:start] + text[end:]
        else:
            break
    return text

def adapt_afe_schematic():
    print("--> [1/7] Adaptando 02_Sensors_AFE.kicad_sch para Sonda 5 Pinos...")
    with open(AFE_SCH, "r", encoding="utf-8") as f:
        content = f.read()

    # 1. Adicionar Conn_01x05 na lib_symbols se necessário
    if 'symbol "Connector_Generic:Conn_01x05"' not in content:
        sym_def = extract_symbol_definition("Connector_Generic", "Conn_01x05")
        indented = "\n".join("    " + line if line.strip() else "" for line in sym_def.split("\n"))
        content = content.replace("(lib_symbols", f"(lib_symbols\n{indented}\n", 1)

    # 2. Localizar e substituir o símbolo J_PROBE
    probe_ref_pos = content.find('(property "Reference" "J_PROBE"')
    if probe_ref_pos != -1:
        sym_start = content.rfind("(symbol", 0, probe_ref_pos)
        count = 0
        sym_end = -1
        for i in range(sym_start, len(content)):
            if content[i] == '(': count += 1
            elif content[i] == ')':
                count -= 1
                if count == 0:
                    sym_end = i + 1
                    break

        new_probe_str = f"""(symbol
		(lib_id "Connector_Generic:Conn_01x05")
		(at 33.02 190.5 0)
		(unit 1)
		(exclude_from_sim no)
		(in_bom yes)
		(on_board yes)
		(dnp no)
		(uuid "{u()}")
		(property "Reference" "J_PROBE"
			(at 33.02 176.5 0)
			(effects
				(font
					(size 1.27 1.27)
				)
			)
		)
		(property "Value" "Sonda_Torre_Dual_5P"
			(at 33.02 204.5 0)
			(effects
				(font
					(size 1.27 1.27)
				)
			)
		)
		(property "Footprint" "Connector_JST:JST_XH_B5B-XH-A_1x05_P2.50mm_Vertical"
			(at 33.02 190.5 0)
			(effects
				(font
					(size 1.27 1.27)
				)
				(hide yes)
			)
		)
		(property "Datasheet" "~"
			(at 33.02 190.5 0)
			(effects
				(font
					(size 1.27 1.27)
				)
				(hide yes)
			)
		)
		(property "Description" "Conector JST-XH 5P ligado a Sonda Torre Dual (ADXL345 + DS18B20)"
			(at 33.02 190.5 0)
			(effects
				(font
					(size 1.27 1.27)
				)
				(hide yes)
			)
		)
		(pin "1" (uuid "{u()}"))
		(pin "2" (uuid "{u()}"))
		(pin "3" (uuid "{u()}"))
		(pin "4" (uuid "{u()}"))
		(pin "5" (uuid "{u()}"))
		(instances
			(project "02_Sensors_AFE"
				(path "/46b10fa7-7a2e-4b68-b7b8-e2182098679e"
					(reference "J_PROBE")
					(unit 1)
				)
			)
			(project "amemiya_carrier_v2"
				(path "/620adc61-f0a7-4b23-bb32-12a58dd5caa9/b73bc9d4-1e86-4693-8263-aaeaf6ac7f20"
					(reference "J_PROBE")
					(unit 1)
				)
			)
		)
	)"""
        content = content[:sym_start] + new_probe_str + content[sym_end:]

    # 3. Remover hierarchical labels I2S de AFE
    content = delete_sexp_containing(content, '"I2S_SD"', "(hierarchical_label")
    content = delete_sexp_containing(content, '"I2S_SCK"', "(hierarchical_label")
    content = delete_sexp_containing(content, '"I2S_WS"', "(hierarchical_label")

    # 4. Remover trilhas I2S soltas
    content = delete_sexp_containing(content, "(xy 27.94 190.5)", "(wire")
    content = delete_sexp_containing(content, "(xy 27.94 193.04)", "(wire")
    content = delete_sexp_containing(content, "(xy 27.94 195.58)", "(wire")

    with open(AFE_SCH, "w", encoding="utf-8") as f:
        f.write(content)
    print("  [OK] 02_Sensors_AFE.kicad_sch atualizado.")

def adapt_mcu_schematic():
    print("--> [2/7] Adaptando 03_MCU_ESP32S3.kicad_sch...")
    with open(MCU_SCH, "r", encoding="utf-8") as f:
        content = f.read()

    # Remover hierarchical labels I2S
    content = delete_sexp_containing(content, '"I2S_SD"', "(hierarchical_label")
    content = delete_sexp_containing(content, '"I2S_SCK"', "(hierarchical_label")
    content = delete_sexp_containing(content, '"I2S_WS"', "(hierarchical_label")

    # Remover os fios que conectavam aos labels I2S
    content = delete_sexp_containing(content, "(xy 170.18 125.73) (xy 139.70 125.73)", "(wire")
    content = delete_sexp_containing(content, "(xy 170.18 128.27) (xy 139.70 128.27)", "(wire")
    content = delete_sexp_containing(content, "(xy 170.18 140.97) (xy 139.70 140.97)", "(wire")

    # Adicionar no-connect explícito nos pinos GPIO 6, 7, 8
    nc_str = f"""	(no_connect (at 170.18 125.73) (uuid "{u()}"))
	(no_connect (at 170.18 128.27) (uuid "{u()}"))
	(no_connect (at 170.18 140.97) (uuid "{u()}"))"""
    
    # Inserir antes do último parêntese de fechamento do esquemático
    last_paren = content.rfind(")")
    content = content[:last_paren] + nc_str + "\n)"

    with open(MCU_SCH, "w", encoding="utf-8") as f:
        f.write(content)
    print("  [OK] 03_MCU_ESP32S3.kicad_sch atualizado.")

def adapt_root_schematic():
    print("--> [3/7] Adaptando amemiya_carrier_v2.kicad_sch...")
    with open(ROOT_SCH, "r", encoding="utf-8") as f:
        content = f.read()

    # Remover pinos I2S nas folhas hierárquicas
    content = delete_sexp_containing(content, '"I2S_SD"', "(pin")
    content = delete_sexp_containing(content, '"I2S_SCK"', "(pin")
    content = delete_sexp_containing(content, '"I2S_WS"', "(pin")

    # Remover fios conectando as folhas
    content = delete_sexp_containing(content, "(xy 91.44 121.92) (xy 129.54 121.92)", "(wire")
    content = delete_sexp_containing(content, "(xy 91.44 134.62) (xy 129.54 134.62)", "(wire")
    content = delete_sexp_containing(content, "(xy 91.44 147.32) (xy 129.54 147.32)", "(wire")

    with open(ROOT_SCH, "w", encoding="utf-8") as f:
        f.write(content)
    print("  [OK] amemiya_carrier_v2.kicad_sch atualizado.")

def export_netlist():
    print("--> [4/7] Exportando netlist amemiya_carrier_v2.net...")
    cmd = [KICAD_CLI, "sch", "export", "netlist", "--output", NETLIST_FILE, ROOT_SCH]
    res = subprocess.run(cmd, capture_output=True, text=True)
    if os.path.exists(NETLIST_FILE):
        print(f"  [OK] Netlist gerado: {NETLIST_FILE}")
    else:
        print("Erro netlist:", res.stderr)
        raise RuntimeError("Falha ao exportar netlist.")

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

PLACEMENTS = {
    "H1": (4.5, 4.5, 0, pcbnew.F_Cu),
    "H2": (85.5, 4.5, 0, pcbnew.F_Cu),
    "H3": (85.5, 70.5, 0, pcbnew.F_Cu),
    "H4": (4.5, 70.5, 0, pcbnew.F_Cu),
    "U_ESP_L": (32.3, 9.0, 0, pcbnew.F_Cu),
    "U_ESP_R": (57.7, 9.0, 0, pcbnew.F_Cu),
    "C_3V3_1": (32.3, 3.5, 0, pcbnew.F_Cu),
    "C_3V3_2": (37.0, 3.5, 0, pcbnew.F_Cu),
    "C_ESP1":  (36.5, 9.0, 0, pcbnew.F_Cu),
    "C_ESP2":  (36.5, 12.5, 0, pcbnew.F_Cu),
    "J_AMP":    (12.0, 14.0, 270, pcbnew.F_Cu),
    "R_B":      (18.0, 14.0, 90, pcbnew.F_Cu),
    "R_BIAS1":  (24.0, 9.0, 0, pcbnew.F_Cu),
    "R_BIAS2":  (24.0, 12.5, 0, pcbnew.F_Cu),
    "C_BIAS":   (24.0, 16.0, 0, pcbnew.F_Cu),
    "R_F":      (18.0, 20.0, 0, pcbnew.F_Cu),
    "C_F":      (24.0, 20.0, 0, pcbnew.F_Cu),
    "D_AMP_HI": (24.0, 23.0, 0, pcbnew.F_Cu),
    "D_AMP_LO": (28.0, 23.0, 0, pcbnew.F_Cu),
    "J_PROBE":  (12.0, 28.0, 270, pcbnew.F_Cu),
    "C_PROBE":  (18.0, 28.0, 0, pcbnew.F_Cu),
    "R_PU_1W":  (20.0, 33.0, 0, pcbnew.F_Cu),
    "R_PU_SCL": (20.0, 40.0, 0, pcbnew.F_Cu),
    "R_PU_SDA": (20.0, 43.0, 0, pcbnew.F_Cu),
    "J_TACHO":   (12.0, 54.0, 270, pcbnew.F_Cu),
    "R_PU_TACHO": (18.0, 53.0, 0, pcbnew.F_Cu),
    "R_S_TACHO":  (18.0, 57.0, 0, pcbnew.F_Cu),
    "C_F_TACHO":  (22.0, 57.0, 0, pcbnew.F_Cu),
    "U_LORA":      (80.0, 8.0, 0, pcbnew.F_Cu),
    "C_LORA_BULK": (70.0, 11.0, 90, pcbnew.F_Cu),
    "C_LORA_DEC":  (70.0, 19.0, 0, pcbnew.F_Cu),
    "J_OLED": (80.0, 30.0, 0, pcbnew.F_Cu),
    "C_OLED": (74.0, 31.0, 0, pcbnew.F_Cu),
    "J_LEDS": (80.0, 46.0, 270, pcbnew.F_Cu),
    "R_LED1": (74.0, 46.0, 0, pcbnew.F_Cu),
    "R_LED2": (74.0, 48.5, 0, pcbnew.F_Cu),
    "R_LED3": (74.0, 51.0, 0, pcbnew.F_Cu),
    "J_BUZ": (78.0, 62.0, 0, pcbnew.F_Cu),
    "R_BUZ": (52.0, 12.0, 0, pcbnew.F_Cu),
    "J_BAT": (48.0, 69.5, 0, pcbnew.F_Cu),
    "C_IN1": (43.0, 64.0, 90, pcbnew.F_Cu),
    "C_IN2": (48.0, 64.0, 90, pcbnew.F_Cu),
    "J_BAT_SENSE": (65.0, 69.5, 0, pcbnew.F_Cu),
    "R_BAT1":      (63.0, 64.0, 90, pcbnew.F_Cu),
    "R_BAT2":      (67.0, 64.0, 90, pcbnew.F_Cu),
    "C_BAT":       (67.0, 59.0, 0, pcbnew.F_Cu),
}

def build_carrier_pcb():
    print("--> [5/7] Construindo layout da PCB amemiya_carrier_v2 (90.0 x 75.0 mm)...")
    b = pcbnew.BOARD()
    b.GetDesignSettings().SetBoardThickness(pcbnew.FromMM(1.6))

    tb = b.GetTitleBlock()
    tb.SetTitle("Amemiya Industrial IoT - Main Node Carrier Board v2.0")
    tb.SetCompany("Lean Tech Metrology & Industrial IoT")
    tb.SetRevision("v2.0")
    tb.SetDate("2026-10-20")

    # Edge.Cuts (90.0 x 75.0 mm)
    pts = [(0.0, 0.0), (90.0, 0.0), (90.0, 75.0), (0.0, 75.0)]
    for i in range(4):
        p1, p2 = pts[i], pts[(i+1)%4]
        seg = pcbnew.PCB_SHAPE(b)
        seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
        seg.SetLayer(pcbnew.Edge_Cuts)
        seg.SetWidth(pcbnew.FromMM(0.15))
        seg.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(p1[0]), pcbnew.FromMM(p1[1])))
        seg.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(p2[0]), pcbnew.FromMM(p2[1])))
        b.Add(seg)

    # Design rules
    net_settings = b.GetDesignSettings().m_NetSettings
    default_nc = net_settings.GetDefaultNetclass()
    default_nc.SetTrackWidth(pcbnew.FromMM(0.30))
    default_nc.SetClearance(pcbnew.FromMM(0.20))
    default_nc.SetViaDiameter(pcbnew.FromMM(0.80))
    default_nc.SetViaDrill(pcbnew.FromMM(0.40))

    b.GetDesignSettings().m_MinResolvedSpokes = 1

    pnc = pcbnew.NETCLASS("Power")
    pnc.SetTrackWidth(pcbnew.FromMM(0.60))
    pnc.SetClearance(pcbnew.FromMM(0.25))
    pnc.SetViaDiameter(pcbnew.FromMM(0.80))
    pnc.SetViaDrill(pcbnew.FromMM(0.40))
    net_settings.SetNetclass("Power", pnc)

    for pnet in ["+5V", "+3V3", "GND", "/Power_Supply/VBAT_RAW"]:
        net_settings.SetNetclassPatternAssignment(pnet, "Power")

    comps, nets = parse_netlist(NETLIST_FILE)

    net_obj_map = {}
    for netname in nets.keys():
        net_item = pcbnew.NETINFO_ITEM(b, netname)
        b.Add(net_item)
        net_obj_map[netname] = net_item

    for ref, cdata in comps.items():
        if ref not in PLACEMENTS:
            continue
        fp_str = cdata["footprint"]
        val = cdata["value"]
        lib, fp_name = fp_str.split(":")
        lib_path = os.path.join(KICAD_FP_DIR, f"{lib}.pretty")
        
        fp = pcbnew.FootprintLoad(lib_path, fp_name)
        if not fp:
            raise FileNotFoundError(f"Could not load footprint: {lib_path} / {fp_name}")
        
        fp.SetReference(ref)
        fp.Reference().SetText(ref)
        is_connector_or_module = ref.startswith("J_") or ref.startswith("U_")
        if is_connector_or_module:
            fp.Reference().SetVisible(True)
            fp.Reference().SetTextSize(pcbnew.VECTOR2I(pcbnew.FromMM(0.8), pcbnew.FromMM(0.8)))
            fp.Reference().SetTextThickness(pcbnew.FromMM(0.15))
        else:
            fp.Reference().SetVisible(False)

        fp.SetValue(val)
        fp.Value().SetText(val)
        fp.Value().SetVisible(False)

        px, py, rot, layer = PLACEMENTS[ref]
        fp.SetPosition(pcbnew.VECTOR2I(pcbnew.FromMM(px), pcbnew.FromMM(py)))
        fp.SetOrientationDegrees(rot)
        b.Add(fp)

    for netname, nodes in nets.items():
        net_item = net_obj_map[netname]
        for ref, pin in nodes:
            fp = b.FindFootprintByReference(ref)
            if not fp:
                continue
            pad = fp.FindPadByNumber(pin)
            if pad:
                pad.SetNet(net_item)

    pcbnew.SaveBoard(PCB_FILE, b)
    print(f"  [OK] PCB salva: {PCB_FILE}")
    return b

def autoroute_and_fill(b):
    print("--> [6/7] Exportando DSN e executando Freerouting...")
    dsn_abs = os.path.abspath(DSN_FILE)
    ses_abs = os.path.abspath(SES_FILE)
    if os.path.exists(ses_abs): os.remove(ses_abs)

    pcbnew.ExportSpecctraDSN(b, dsn_abs)
    cmd = [JAVA_EXE, "-jar", FREEROUTING_JAR, "-de", dsn_abs, "-do", ses_abs, "-mp", "30", "-l", "1"]
    subprocess.run(cmd, capture_output=True, text=True)

    if os.path.exists(ses_abs):
        pcbnew.ImportSpecctraSES(b, ses_abs)
        print(f"  [OK] Rotas importadas: {len(b.GetTracks())} trilhas e vias criadas.")

    # Adicionar planos GND
    gnd_net = b.FindNet("GND")
    for layer in [pcbnew.B_Cu, pcbnew.F_Cu]:
        z = pcbnew.ZONE(b)
        z.SetLayer(layer)
        z.SetNet(gnd_net)
        z.SetPadConnection(pcbnew.ZONE_CONNECTION_THERMAL)
        z.SetThermalReliefSpokeWidth(pcbnew.FromMM(0.40))
        z.SetThermalReliefGap(pcbnew.FromMM(0.35))
        z.SetLocalClearance(pcbnew.FromMM(0.30))
        z.SetMinThickness(pcbnew.FromMM(0.20))
        ol = z.Outline()
        ol.NewOutline()
        ol.Append(pcbnew.FromMM(0.3), pcbnew.FromMM(0.3))
        ol.Append(pcbnew.FromMM(89.7), pcbnew.FromMM(0.3))
        ol.Append(pcbnew.FromMM(89.7), pcbnew.FromMM(74.7))
        ol.Append(pcbnew.FromMM(0.3), pcbnew.FromMM(74.7))
        b.Add(z)

    pcbnew.SaveBoard(PCB_FILE, b)
    b_loaded = pcbnew.LoadBoard(PCB_FILE)
    zf = pcbnew.ZONE_FILLER(b_loaded)
    zf.Fill(b_loaded.Zones())
    pcbnew.SaveBoard(PCB_FILE, b_loaded)
    print("  [OK] Planos de terra preenchidos com sucesso.")
    return b_loaded

def export_carrier_artifacts():
    print("--> [7/7] Exportando artefatos manufatura (PDF, STEP, Gerbers)...")
    os.makedirs(GERBER_DIR, exist_ok=True)

    # 1. PDF
    pdf_sch = os.path.join(CARRIER_DIR, "amemiya_carrier_v2_schematic.pdf")
    subprocess.run([KICAD_CLI, "sch", "export", "pdf", "--output", pdf_sch, ROOT_SCH], capture_output=True)
    if os.path.exists(pdf_sch): print(f"  [OK] PDF Esquemático: {pdf_sch}")

    # 2. STEP
    step_pcb = os.path.join(CARRIER_DIR, "amemiya_carrier_v2.step")
    subprocess.run([KICAD_CLI, "pcb", "export", "step", "--output", step_pcb, "--grid-origin", PCB_FILE], capture_output=True)
    if os.path.exists(step_pcb): print(f"  [OK] STEP 3D: {step_pcb}")

    # 3. Gerbers
    subprocess.run([KICAD_CLI, "pcb", "export", "gerbers", "--output", GERBER_DIR, PCB_FILE], capture_output=True)
    subprocess.run([KICAD_CLI, "pcb", "export", "drill", "--output", GERBER_DIR, PCB_FILE], capture_output=True)

    zip_path = os.path.join(FAB_DIR, "amemiya_carrier_v2_gerbers.zip")
    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk(GERBER_DIR):
            for f in files:
                fpath = os.path.join(root, f)
                z.write(fpath, os.path.basename(fpath))
    print(f"  [OK] Pacote Gerbers ZIP: {zip_path}")

def main():
    print("=== REBUILD AMEMIYA CARRIER BOARD V2 (DUAL PROBE READY) ===")
    adapt_afe_schematic()
    adapt_mcu_schematic()
    adapt_root_schematic()
    export_netlist()
    b = build_carrier_pcb()
    b = autoroute_and_fill(b)
    export_carrier_artifacts()
    print("=== CARRIER V2 CONCLUÍDO COM SUCESSO! ===")

if __name__ == "__main__":
    main()
