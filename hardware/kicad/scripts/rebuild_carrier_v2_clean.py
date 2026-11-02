#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Amemiya Industrial IoT - Carrier Board v2.0 Clean Rebuild
Clona o projeto amemiya_carrier_board (110x80mm) já validado e adapta:
1. Conector J_PROBE passa de 8 pinos para 5 pinos (JST-XH B5B-XH-A)
2. Desconecta barramento I2S (GPIO 6, 7, 8 do ESP32-S3 liberados com no_connect)
3. Conecta I2C_SCL no pino 4 e I2C_SDA no pino 5
4. Roteia trilhas I2C aos pads 4 e 5 na PCB
5. Preenche planos e valida DRC/ERC
6. Exporta PDF esquemático, STEP 3D e Gerbers ZIP
"""

import os
import sys
import shutil
import uuid
import subprocess
import zipfile
import re
import json

for p in [r"C:\Program Files\KiCad\9.0\bin", r"C:\Program Files\KiCad\8.0\bin"]:
    if os.path.exists(p) and p not in sys.path:
        sys.path.insert(0, p)
        os.environ["PATH"] = p + ";" + os.environ.get("PATH", "")

import pcbnew

KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad"
SRC_DIR = os.path.join(BASE_DIR, "amemiya_carrier_board")
DST_DIR = os.path.join(BASE_DIR, "amemiya_carrier_v2")

ROOT_SCH = os.path.join(DST_DIR, "amemiya_carrier_v2.kicad_sch")
AFE_SCH = os.path.join(DST_DIR, "02_Sensors_AFE.kicad_sch")
MCU_SCH = os.path.join(DST_DIR, "03_MCU_ESP32S3.kicad_sch")
PCB_FILE = os.path.join(DST_DIR, "amemiya_carrier_v2.kicad_pcb")
PRO_FILE = os.path.join(DST_DIR, "amemiya_carrier_v2.kicad_pro")
FAB_DIR = os.path.join(DST_DIR, "fabrication")
GERBER_DIR = os.path.join(FAB_DIR, "gerbers")

def u(): return str(uuid.uuid4())

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

def step1_clone_carrier_board():
    print("--> [1/6] Clonando amemiya_carrier_board para amemiya_carrier_v2...")
    if os.path.exists(DST_DIR):
        shutil.rmtree(DST_DIR)
    os.makedirs(DST_DIR, exist_ok=True)

    for item in os.listdir(SRC_DIR):
        s = os.path.join(SRC_DIR, item)
        d = os.path.join(DST_DIR, item)
        if item in ["amemiya_carrier_board-backups", "logs"]:
            continue
        if os.path.isdir(s):
            shutil.copytree(s, d)
        else:
            shutil.copy2(s, d)

    # Renomear arquivos principais
    for ext in [".kicad_pro", ".kicad_sch", ".kicad_pcb", ".kicad_prl"]:
        old_f = os.path.join(DST_DIR, f"amemiya_carrier_board{ext}")
        new_f = os.path.join(DST_DIR, f"amemiya_carrier_v2{ext}")
        if os.path.exists(old_f):
            if os.path.exists(new_f): os.remove(new_f)
            os.rename(old_f, new_f)

    # Atualizar nome no arquivo de projeto .kicad_pro
    with open(PRO_FILE, "r", encoding="utf-8") as f:
        pro_text = f.read()
    pro_text = pro_text.replace("amemiya_carrier_board", "amemiya_carrier_v2")
    with open(PRO_FILE, "w", encoding="utf-8") as f:
        f.write(pro_text)

    print("  [OK] Clonagem concluida com sucesso.")

def step2_adapt_schematics():
    print("--> [2/6] Adaptando esquemáticos hierárquicos...")

    # 1. 02_Sensors_AFE.kicad_sch
    with open(AFE_SCH, "r", encoding="utf-8") as f:
        afe = f.read()

    # Adicionar Conn_01x05 na lib_symbols se ausente
    if 'symbol "Connector_Generic:Conn_01x05"' not in afe:
        sym_file = r"C:\Program Files\KiCad\9.0\share\kicad\symbols\Connector_Generic.kicad_sym"
        with open(sym_file, "r", encoding="utf-8") as sf:
            sym_content = sf.read()
        p1 = sym_content.find('(symbol "Conn_01x05"')
        c = 0
        p2 = -1
        for i in range(p1, len(sym_content)):
            if sym_content[i] == '(': c += 1
            elif sym_content[i] == ')':
                c -= 1
                if c == 0:
                    p2 = i + 1
                    break
        conn05_def = sym_content[p1:p2].replace('(symbol "Conn_01x05"', '(symbol "Connector_Generic:Conn_01x05"', 1)
        indented = "\n".join("    " + line if line.strip() else "" for line in conn05_def.split("\n"))
        afe = afe.replace("(lib_symbols", f"(lib_symbols\n{indented}\n", 1)

    # Substituir símbolo J_PROBE
    probe_ref_pos = afe.find('(property "Reference" "J_PROBE"')
    if probe_ref_pos != -1:
        sym_start = afe.rfind("(symbol", 0, probe_ref_pos)
        c = 0
        sym_end = -1
        for i in range(sym_start, len(afe)):
            if afe[i] == '(': c += 1
            elif afe[i] == ')':
                c -= 1
                if c == 0:
                    sym_end = i + 1
                    break

        new_probe_sym = f"""(symbol
		(lib_id "Connector_Generic:Conn_01x05")
		(at 33.02 187.96 0)
		(unit 1)
		(exclude_from_sim no)
		(in_bom yes)
		(on_board yes)
		(dnp no)
		(uuid "{u()}")
		(property "Reference" "J_PROBE"
			(at 33.02 173.96 0)
			(effects
				(font
					(size 1.27 1.27)
				)
			)
		)
		(property "Value" "Sonda_Torre_Dual_5P"
			(at 33.02 201.96 0)
			(effects
				(font
					(size 1.27 1.27)
				)
			)
		)
		(property "Footprint" "Connector_JST:JST_XH_B5B-XH-A_1x05_P2.50mm_Vertical"
			(at 33.02 187.96 0)
			(effects
				(font
					(size 1.27 1.27)
				)
				(hide yes)
			)
		)
		(property "Datasheet" "~"
			(at 33.02 187.96 0)
			(effects
				(font
					(size 1.27 1.27)
				)
				(hide yes)
			)
		)
		(property "Description" "Conector ligado a Sonda Torre Dual (ADXL345 + DS18B20)"
			(at 33.02 187.96 0)
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
        afe = afe[:sym_start] + new_probe_sym + afe[sym_end:]

    # Remover hierarchical labels I2S de AFE
    afe = delete_sexp_containing(afe, '"I2S_SD"', "(hierarchical_label")
    afe = delete_sexp_containing(afe, '"I2S_SCK"', "(hierarchical_label")
    afe = delete_sexp_containing(afe, '"I2S_WS"', "(hierarchical_label")

    # Remover os fios I2S
    afe = delete_sexp_containing(afe, "(xy 27.94 190.5) (xy 146.05 190.5)", "(wire")
    afe = delete_sexp_containing(afe, "(xy 27.94 193.04) (xy 146.05 193.04)", "(wire")
    afe = delete_sexp_containing(afe, "(xy 27.94 195.58) (xy 146.05 195.58)", "(wire")

    # Adicionar fios ligando pinos 4 e 5 do novo J_PROBE ao I2C_SCL e I2C_SDA
    # Pin 4 (27.94, 190.50) -> conecta na linha I2C_SCL (27.94, 198.12)
    # Pin 5 (27.94, 193.04) -> conecta na linha I2C_SDA (27.94, 200.66)
    new_wires = f"""	(wire (pts (xy 27.94 190.5) (xy 25.4 190.5)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 25.4 190.5) (xy 25.4 198.12)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 25.4 198.12) (xy 27.94 198.12)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 27.94 193.04) (xy 22.86 193.04)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 22.86 193.04) (xy 22.86 200.66)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 22.86 200.66) (xy 27.94 200.66)) (stroke (width 0) (type default)) (uuid "{u()}"))
"""
    last_p = afe.rfind(")")
    afe = afe[:last_p] + new_wires + "\n)"

    # Atualizar instâncias de projeto em AFE
    afe = afe.replace('"amemiya_carrier_board"', '"amemiya_carrier_v2"')
    with open(AFE_SCH, "w", encoding="utf-8") as f:
        f.write(afe)
    print("  [OK] 02_Sensors_AFE.kicad_sch adaptado.")

    # 2. 03_MCU_ESP32S3.kicad_sch
    with open(MCU_SCH, "r", encoding="utf-8") as f:
        mcu = f.read()

    mcu = delete_sexp_containing(mcu, '"I2S_SD"', "(hierarchical_label")
    mcu = delete_sexp_containing(mcu, '"I2S_SCK"', "(hierarchical_label")
    mcu = delete_sexp_containing(mcu, '"I2S_WS"', "(hierarchical_label")

    mcu = delete_sexp_containing(mcu, "(xy 170.18 125.73) (xy 139.70 125.73)", "(wire")
    mcu = delete_sexp_containing(mcu, "(xy 170.18 128.27) (xy 139.70 128.27)", "(wire")
    mcu = delete_sexp_containing(mcu, "(xy 170.18 140.97) (xy 139.70 140.97)", "(wire")

    nc_str = f"""	(no_connect (at 170.18 125.73) (uuid "{u()}"))
	(no_connect (at 170.18 128.27) (uuid "{u()}"))
	(no_connect (at 170.18 140.97) (uuid "{u()}"))
"""
    last_p = mcu.rfind(")")
    mcu = mcu[:last_p] + nc_str + "\n)"
    mcu = mcu.replace('"amemiya_carrier_board"', '"amemiya_carrier_v2"')
    with open(MCU_SCH, "w", encoding="utf-8") as f:
        f.write(mcu)
    print("  [OK] 03_MCU_ESP32S3.kicad_sch adaptado.")

    # 3. amemiya_carrier_v2.kicad_sch
    with open(ROOT_SCH, "r", encoding="utf-8") as f:
        root = f.read()

    root = delete_sexp_containing(root, '"I2S_SD"', "(pin")
    root = delete_sexp_containing(root, '"I2S_SCK"', "(pin")
    root = delete_sexp_containing(root, '"I2S_WS"', "(pin")

    root = delete_sexp_containing(root, "(xy 91.44 121.92) (xy 129.54 121.92)", "(wire")
    root = delete_sexp_containing(root, "(xy 91.44 134.62) (xy 129.54 134.62)", "(wire")
    root = delete_sexp_containing(root, "(xy 91.44 147.32) (xy 129.54 147.32)", "(wire")

    root = root.replace('"amemiya_carrier_board"', '"amemiya_carrier_v2"')
    with open(ROOT_SCH, "w", encoding="utf-8") as f:
        f.write(root)
    print("  [OK] amemiya_carrier_v2.kicad_sch adaptado.")

    # Atualizar os outros esquemáticos de folha
    for fsch in ["01_Power_Supply.kicad_sch", "04_Peripherals_LoRa.kicad_sch"]:
        fp = os.path.join(DST_DIR, fsch)
        if os.path.exists(fp):
            with open(fp, "r", encoding="utf-8") as ff:
                content = ff.read()
            content = content.replace('"amemiya_carrier_board"', '"amemiya_carrier_v2"')
            with open(fp, "w", encoding="utf-8") as ff:
                ff.write(content)

def step3_verify_erc():
    print("--> [3/6] Verificando Regras Elétricas (ERC)...")
    erc_json = os.path.join(DST_DIR, "erc_report.json")
    cmd = [KICAD_CLI, "sch", "erc", "--severity-all", "--format", "json", "-o", erc_json, ROOT_SCH]
    subprocess.run(cmd, capture_output=True, text=True)

    with open(erc_json, "r", encoding="utf-8") as f:
        report = json.load(f)

    total_errors = sum(1 for s in report.get("sheets", []) for v in s.get("violations", []) if v.get("severity") == "error")
    total_warnings = sum(1 for s in report.get("sheets", []) for v in s.get("violations", []) if v.get("severity") == "warning")
    print(f"  [ERC Status] Erros: {total_errors}, Avisos: {total_warnings}")
    if total_errors > 0:
        for s in report.get("sheets", []):
            for v in s.get("violations", []):
                print(f"    - {v.get('type')}: {v.get('description')} ({s.get('path')})")

def step4_adapt_pcb():
    print("--> [4/6] Adaptando layout da PCB (110.0 x 80.0 mm)...")
    b = pcbnew.LoadBoard(PCB_FILE)

    tb = b.GetTitleBlock()
    tb.SetTitle("Amemiya Industrial IoT - Main Node Carrier Board v2.0")
    tb.SetCompany("Lean Tech Metrology & Industrial IoT")
    tb.SetRevision("v2.0")
    tb.SetDate("2026-10-20")

    # 1. Carregar footprint do conector JST-XH 5 pinos
    fp_lib = r"C:\Program Files\KiCad\9.0\share\kicad\footprints\Connector_JST.pretty"
    new_fp = pcbnew.FootprintLoad(fp_lib, "JST_XH_B5B-XH-A_1x05_P2.50mm_Vertical")
    if not new_fp:
        raise FileNotFoundError("Não foi possível carregar o footprint JST_XH_B5B-XH-A_1x05_P2.50mm_Vertical")

    # Localizar footprint antigo J_PROBE1
    old_fp = b.FindFootprintByReference("J_PROBE1")
    if not old_fp:
        old_fp = b.FindFootprintByReference("J_PROBE")

    if old_fp:
        pos = old_fp.GetPosition()
        rot = old_fp.GetOrientation()
        b.Remove(old_fp)

        new_fp.SetReference("J_PROBE1")
        new_fp.SetValue("Sonda_Torre_Dual_5P")
        new_fp.SetPosition(pos)
        new_fp.SetOrientation(rot)
        b.Add(new_fp)
        print("  [OK] Footprint J_PROBE1 substituído para JST-XH 5P.")

    # 2. Atribuir nets aos pinos do J_PROBE1
    net_pwr = b.FindNet("/Sensors_AFE/+3V3_SWITCHED")
    net_gnd = b.FindNet("GND")
    net_1w = b.FindNet("/MCU_ESP32S3/1W_DQ")
    net_scl = b.FindNet("/MCU_ESP32S3/I2C_SCL")
    net_sda = b.FindNet("/MCU_ESP32S3/I2C_SDA")

    fp_probe = b.FindFootprintByReference("J_PROBE1")
    if fp_probe:
        p1 = fp_probe.FindPadByNumber("1")
        if p1 and net_pwr: p1.SetNet(net_pwr)
        p2 = fp_probe.FindPadByNumber("2")
        if p2 and net_gnd: p2.SetNet(net_gnd)
        p3 = fp_probe.FindPadByNumber("3")
        if p3 and net_1w: p3.SetNet(net_1w)
        p4 = fp_probe.FindPadByNumber("4")
        if p4 and net_scl: p4.SetNet(net_scl)
        p5 = fp_probe.FindPadByNumber("5")
        if p5 and net_sda: p5.SetNet(net_sda)

    # 3. Remover trilhas dos barramentos I2S agora obsoletos
    tracks_to_remove = []
    for t in b.GetTracks():
        netname = t.GetNetname()
        if "I2S" in netname:
            tracks_to_remove.append(t)
        # Remover trilha antiga que terminava em (29.00, 75.50) e (31.50, 75.50)
        elif "I2C_SCL" in netname:
            # Remover o segmento vertical final que descia até o antigo pad 7
            if abs(t.GetEnd().x - pcbnew.FromMM(29.0)) < 1000 and abs(t.GetEnd().y - pcbnew.FromMM(75.5)) < 1000:
                tracks_to_remove.append(t)
        elif "I2C_SDA" in netname:
            # Remover o segmento final que descia até o antigo pad 8
            if abs(t.GetStart().x - pcbnew.FromMM(31.5)) < 1000 and abs(t.GetStart().y - pcbnew.FromMM(75.5)) < 1000:
                tracks_to_remove.append(t)
            elif abs(t.GetEnd().x - pcbnew.FromMM(31.5)) < 1000 and abs(t.GetEnd().y - pcbnew.FromMM(75.5)) < 1000:
                tracks_to_remove.append(t)

    for t in tracks_to_remove:
        b.Remove(t)
    print(f"  [OK] {len(tracks_to_remove)} trilhas I2S/antigas removidas.")

    # 4. Conectar novas rotas para I2C_SCL (Pad 4: 21.5, 75.5) e I2C_SDA (Pad 5: 24.0, 75.5)
    # I2C_SCL: de (29.00, 69.50) -> (24.00, 69.50) -> (21.50, 72.00) -> (21.50, 75.50)
    def add_track(b, net, start_xy, end_xy, layer=pcbnew.B_Cu, width_mm=0.30):
        t = pcbnew.PCB_TRACK(b)
        t.SetLayer(layer)
        t.SetNet(net)
        t.SetWidth(pcbnew.FromMM(width_mm))
        t.SetStart(pcbnew.VECTOR2I(pcbnew.FromMM(start_xy[0]), pcbnew.FromMM(start_xy[1])))
        t.SetEnd(pcbnew.VECTOR2I(pcbnew.FromMM(end_xy[0]), pcbnew.FromMM(end_xy[1])))
        b.Add(t)

    if net_scl:
        add_track(b, net_scl, (29.00, 69.50), (24.00, 69.50))
        add_track(b, net_scl, (24.00, 69.50), (21.50, 72.00))
        add_track(b, net_scl, (21.50, 72.00), (21.50, 75.50))
        print("  [OK] Trilha I2C_SCL conectada ao Pad 4.")

    # I2C_SDA: da via em (38.62, 68.00) ou (32.40, 74.22)
    # (32.40, 74.22) -> (26.50, 74.22) -> (24.00, 74.22) -> (24.00, 75.50)
    if net_sda:
        add_track(b, net_sda, (32.40, 74.22), (24.00, 74.22))
        add_track(b, net_sda, (24.00, 74.22), (24.00, 75.50))
        print("  [OK] Trilha I2C_SDA conectada ao Pad 5.")

    pcbnew.SaveBoard(PCB_FILE, b)

    # Recarregar e preencher zonas
    b_loaded = pcbnew.LoadBoard(PCB_FILE)
    zf = pcbnew.ZONE_FILLER(b_loaded)
    zf.Fill(b_loaded.Zones())
    pcbnew.SaveBoard(PCB_FILE, b_loaded)
    print("  [OK] Planos de terra recalculados e preenchidos.")

def step5_verify_drc():
    print("--> [5/6] Verificando Regras de Design da PCB (DRC)...")
    drc_json = os.path.join(DST_DIR, "pcb_drc_report.json")
    cmd = [KICAD_CLI, "pcb", "drc", "--severity-all", "--format", "json", "-o", drc_json, PCB_FILE]
    subprocess.run(cmd, capture_output=True, text=True)

    with open(drc_json, "r", encoding="utf-8") as f:
        report = json.load(f)

    unconnected = len(report.get("unconnected_items", []))
    violations = len(report.get("violations", []))
    print(f"  [DRC Status] Desconectados: {unconnected}, Violações: {violations}")
    if unconnected > 0:
        for u in report.get("unconnected_items", []):
            print(f"    - DESCONECTADO: {u.get('description')}")
            for item in u.get('items', []):
                print(f"        * {item.get('description')} em ({item.get('pos', {}).get('x')}, {item.get('pos', {}).get('y')})")

def step6_export_artifacts():
    print("--> [6/6] Exportando entregáveis de manufatura...")
    os.makedirs(GERBER_DIR, exist_ok=True)

    # 1. PDF Esquemático
    pdf_sch = os.path.join(DST_DIR, "amemiya_carrier_v2_schematic.pdf")
    subprocess.run([KICAD_CLI, "sch", "export", "pdf", "--output", pdf_sch, ROOT_SCH], capture_output=True)
    if os.path.exists(pdf_sch): print(f"  [OK] PDF Esquemático: {pdf_sch}")

    # 2. STEP 3D
    step_pcb = os.path.join(DST_DIR, "amemiya_carrier_v2.step")
    subprocess.run([KICAD_CLI, "pcb", "export", "step", "--output", step_pcb, "--grid-origin", PCB_FILE], capture_output=True)
    if os.path.exists(step_pcb): print(f"  [OK] Modelo 3D STEP: {step_pcb}")

    # 3. Gerbers & Drill
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
    print("=== ADAPTAÇÃO COMPLETA: AMEMIYA CARRIER BOARD V2 ===")
    step1_clone_carrier_board()
    step2_adapt_schematics()
    step3_verify_erc()
    step4_adapt_pcb()
    step5_verify_drc()
    step6_export_artifacts()
    print("=== PROJETO V2 CONCLUÍDO COM SUCESSO! ===")

if __name__ == "__main__":
    main()
