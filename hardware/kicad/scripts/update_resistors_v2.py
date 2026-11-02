import os
import re
import subprocess
import json

BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2"
SCH_04 = os.path.join(BASE_DIR, "04_Peripherals_LoRa.kicad_sch")
PCB_FILE = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_pcb")
KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"

# 1. Update 04_Peripherals_LoRa.kicad_sch
with open(SCH_04, "r", encoding="utf-8") as f:
    sch = f.read()

# Update R_LED1 to 2.2k
# Update R_LED2 to 2.2k
# Update R_LED3 to 1k (for blue LED forward voltage headroom)
# Update R_BUZ to 330R

def update_symbol_value(text, ref, new_val):
    pos = text.find(f'(property "Reference" "{ref}"')
    if pos == -1:
        print(f"Ref {ref} not found!")
        return text
    # find property "Value" after or before pos
    sym_start = text.rfind("(symbol", 0, pos)
    c = 0
    sym_end = -1
    for i in range(sym_start, len(text)):
        if text[i] == '(': c += 1
        elif text[i] == ')':
            c -= 1
            if c == 0:
                sym_end = i + 1
                break
    block = text[sym_start:sym_end]
    new_block = re.sub(r'\(property "Value" "([^"]+)"', f'(property "Value" "{new_val}"', block)
    print(f"Updated {ref} to {new_val}")
    return text[:sym_start] + new_block + text[sym_end:]

sch = update_symbol_value(sch, "R_LED1", "2.2k")
sch = update_symbol_value(sch, "R_LED2", "2.2k")
sch = update_symbol_value(sch, "R_LED3", "1k")
sch = update_symbol_value(sch, "R_BUZ", "330R")

with open(SCH_04, "w", encoding="utf-8") as f:
    f.write(sch)

# 2. Update PCB
with open(PCB_FILE, "r", encoding="utf-8") as f:
    pcb = f.read()

pcb = update_symbol_value(pcb, "R_LED1", "2.2k")
pcb = update_symbol_value(pcb, "R_LED2", "2.2k")
pcb = update_symbol_value(pcb, "R_LED3", "1k")
pcb = update_symbol_value(pcb, "R_BUZ1", "330R")

with open(PCB_FILE, "w", encoding="utf-8") as f:
    f.write(pcb)

print("Values updated in schematic and PCB.")

# 3. Verify ERC
erc_json = os.path.join(BASE_DIR, "erc_test.json")
subprocess.run([KICAD_CLI, "sch", "erc", "--severity-all", "--format", "json", "-o", erc_json, os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_sch")])
with open(erc_json) as f:
    rep = json.load(f)
errs = sum(1 for s in rep.get('sheets', []) for v in s.get('violations', []) if v.get('severity') == 'error')
warns = sum(1 for s in rep.get('sheets', []) for v in s.get('violations', []) if v.get('severity') == 'warning')
print(f"ERC Status: Errors={errs}, Warnings={warns}")

# 4. Verify DRC
drc_json = os.path.join(BASE_DIR, "drc_test.json")
subprocess.run([KICAD_CLI, "pcb", "drc", "--severity-all", "--format", "json", "-o", drc_json, PCB_FILE])
with open(drc_json) as f:
    rep = json.load(f)
unc = len(rep.get('unconnected_items', []))
vio = len(rep.get('violations', []))
print(f"DRC Status: Unconnected={unc}, Violations={vio}")
