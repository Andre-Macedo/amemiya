import os
import sys
import re
import uuid
import subprocess
import json

KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2"
AFE_SCH = os.path.join(BASE_DIR, "02_Sensors_AFE.kicad_sch")
MCU_SCH = os.path.join(BASE_DIR, "03_MCU_ESP32S3.kicad_sch")
ROOT_SCH = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_sch")

def u(): return str(uuid.uuid4())

def delete_sexp_containing(text, substring, keyword):
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

def delete_matching_sexps(text, predicate):
    idx = 0
    while idx < len(text):
        if text[idx] == '(':
            count = 1
            start = idx
            for j in range(start + 1, len(text)):
                if text[j] == '(':
                    count += 1
                elif text[j] == ')':
                    count -= 1
                    if count == 0:
                        end = j + 1
                        block = text[start:end]
                        if predicate(block):
                            text = text[:start] + text[end:]
                            idx = start - 1
                        else:
                            idx = start
                        break
        idx += 1
    return text

# 1. Adapt AFE
with open(AFE_SCH, "r", encoding="utf-8") as f:
    afe = f.read()

# Add Conn_01x05
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

# Replace J_PROBE with 5-pin symbol
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

# Connect pin 4 and 5 wires to I2C lines
new_wires = f"""	(wire (pts (xy 27.94 190.5) (xy 25.4 190.5)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 25.4 190.5) (xy 25.4 198.12)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 25.4 198.12) (xy 27.94 198.12)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 27.94 193.04) (xy 22.86 193.04)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 22.86 193.04) (xy 22.86 200.66)) (stroke (width 0) (type default)) (uuid "{u()}"))
	(wire (pts (xy 22.86 200.66) (xy 27.94 200.66)) (stroke (width 0) (type default)) (uuid "{u()}"))
"""
last_p = afe.rfind(")")
afe = afe[:last_p] + new_wires + "\n)"

# Remove I2S hierarchical labels
afe = delete_sexp_containing(afe, '"I2S_SD"', "(hierarchical_label")
afe = delete_sexp_containing(afe, '"I2S_SCK"', "(hierarchical_label")
afe = delete_sexp_containing(afe, '"I2S_WS"', "(hierarchical_label")

# Remove I2S wires
afe = delete_sexp_containing(afe, "(xy 27.94 190.5) (xy 146.05 190.5)", "(wire")
afe = delete_sexp_containing(afe, "(xy 27.94 193.04) (xy 146.05 193.04)", "(wire")
afe = delete_sexp_containing(afe, "(xy 27.94 195.58) (xy 146.05 195.58)", "(wire")

# Remove AMP and TACHO symbols and circuits
refs_to_remove = {
    'J_AMP', 'R_B', 'R_BIAS1', 'R_BIAS2', 'C_BIAS', 'R_F1', 'C_F',
    'D_AMP_HI', 'D_AMP_LO', 'J_TACHO', 'R_PU_TACHO', 'R_S_TACHO', 'C_F_TACHO'
}

power_pts_to_remove = [
    "(at 27.94 76.2", "(at 40.64 76.2", "(at 134.62 27.94",
    "(at 111.76 69.85", "(at 134.62 68.58", "(at 22.86 132.08",
    "(at 111.76 146.05"
]

def afe_filter(block):
    if block.startswith("(symbol"):
        for ref in refs_to_remove:
            if f'(property "Reference" "{ref}"' in block:
                return True
        for p in power_pts_to_remove:
            if p in block:
                return True
    if block.startswith("(hierarchical_label"):
        if '"AMP_ADC"' in block or '"TACHO"' in block:
            return True
    if block.startswith("(label"):
        if '"TACHO_RAW"' in block:
            return True
        m = re.search(r'\(at ([0-9.-]+) ([0-9.-]+)', block)
        if m and float(m.group(1)) < 150.0 and float(m.group(2)) < 150.0:
            return True
    if block.startswith("(text"):
        if any(w in block for w in ["ALICATE", "Polarizacao", "TACOMETRO", "Passa-Baixa"]):
            return True
    if block.startswith("(wire"):
        pts = re.findall(r'\(xy ([0-9.-]+) ([0-9.-]+)\)', block)
        if pts:
            for xs, ys in pts:
                x, y = float(xs), float(ys)
                if y < 85.0:
                    return True
                if 85.0 <= y <= 150.0 and x < 155.0:
                    return True
    if block.startswith("(junction"):
        m = re.search(r'\(at ([0-9.-]+) ([0-9.-]+)\)', block)
        if m:
            x, y = float(m.group(1)), float(m.group(2))
            if y < 85.0:
                return True
            if 85.0 <= y <= 150.0 and x < 155.0:
                return True
    return False

afe_clean = delete_matching_sexps(afe, afe_filter)
afe_clean = afe_clean.replace("Sinais: +3V3, GND, 1-Wire DS18B20, I2S Digital Mic / Vibrometro, Barramento I2C Sensores",
                              "Sinais: +3V3_SWITCHED, GND, 1-Wire DS18B20, Barramento I2C Sensores (ADXL345)")
afe_clean = afe_clean.replace('"amemiya_carrier_board"', '"amemiya_carrier_v2"')

with open(AFE_SCH, "w", encoding="utf-8") as f:
    f.write(afe_clean)
print("02_Sensors_AFE.kicad_sch fully updated!")

# Verify ERC!
erc_json = os.path.join(BASE_DIR, "test_erc.json")
cmd = [KICAD_CLI, "sch", "erc", "--severity-all", "--format", "json", "-o", erc_json, ROOT_SCH]
subprocess.run(cmd)
with open(erc_json) as f:
    rep = json.load(f)
errs = sum(1 for s in rep.get('sheets', []) for v in s.get('violations', []) if v.get('severity') == 'error')
warns = sum(1 for s in rep.get('sheets', []) for v in s.get('violations', []) if v.get('severity') == 'warning')
print(f"ERC STATUS: Errors={errs}, Warnings={warns}")
