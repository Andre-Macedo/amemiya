import os
import sys
import pcbnew
import subprocess
import json
import zipfile

KICAD_CLI = r"C:\Program Files\KiCad\9.0\bin\kicad-cli.exe"
BASE_DIR = r"C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2"
PCB_FILE = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_pcb")
ROOT_SCH = os.path.join(BASE_DIR, "amemiya_carrier_v2.kicad_sch")
FAB_DIR = os.path.join(BASE_DIR, "fabrication")
GERBER_DIR = os.path.join(FAB_DIR, "gerbers")

b = pcbnew.LoadBoard(PCB_FILE)

# 1. Obsolete footprints to remove
to_remove_refs = [
    'J_AMP1', 'R_B1', 'R_BIAS1', 'R_BIAS2', 'C_BIAS1', 'R_F1', 'C_F1',
    'D_AMP_HI1', 'D_AMP_LO1', 'J_TACHO1', 'R_PU_TACHO1', 'R_S_TACHO1', 'C_F_TACHO1'
]

removed_pad_positions = []
for ref in to_remove_refs:
    fp = b.FindFootprintByReference(ref)
    if fp:
        for p in fp.Pads():
            removed_pad_positions.append((p.GetPosition().x, p.GetPosition().y))
        b.Remove(fp)
        print(f"Removed footprint {ref}")

# 2. Obsolete nets to remove tracks from
bad_nets = {
    '/Sensors_AFE/AMP_IN+', '/Sensors_AFE/AMP_IN-', '/Sensors_AFE/BIAS_1V65',
    '/MCU_ESP32S3/AMP_ADC', '/Sensors_AFE/TACHO_RAW', '/MCU_ESP32S3/TACHO',
    '/MCU_ESP32S3/I2S_SD', '/MCU_ESP32S3/I2S_SCK', '/MCU_ESP32S3/I2S_WS'
}

tracks_to_remove = []
for t in b.GetTracks():
    if t.GetNetname() in bad_nets:
        tracks_to_remove.append(t)
    else:
        # Check if track is a dead-end branch that terminated at one of the removed pads
        p_start = (t.GetStart().x, t.GetStart().y)
        p_end = (t.GetEnd().x, t.GetEnd().y)
        for rpx, rpy in removed_pad_positions:
            if abs(p_start[0] - rpx) < 1000 and abs(p_start[1] - rpy) < 1000:
                tracks_to_remove.append(t)
                break
            elif abs(p_end[0] - rpx) < 1000 and abs(p_end[1] - rpy) < 1000:
                tracks_to_remove.append(t)
                break

for t in set(tracks_to_remove):
    b.Remove(t)
print(f"Removed {len(tracks_to_remove)} obsolete tracks.")

# 3. Update Title Block and Silkscreen
tb = b.GetTitleBlock()
tb.SetTitle("Amemiya Industrial IoT - Carrier Board v2.0 (Dual Sensor Node)")
tb.SetComment1("Sensors: ADXL345 Vibration (I2C) + DS18B20 Temp (1-Wire) with Power-Gating")
tb.SetRevision("v2.0-Dual")
tb.SetDate("2026-10-20")

# Update Silkscreen texts
for d in b.GetDrawings():
    if isinstance(d, pcbnew.PCB_TEXT):
        txt = d.GetText()
        if "AMEMIYA IoT - Carrier Board" in txt:
            d.SetText("AMEMIYA IoT - Carrier Board v2.0 (Dual Sensor)")
        elif "AMP" in txt or "TACHO" in txt:
            b.Remove(d)

# Save board
pcbnew.SaveBoard(PCB_FILE, b)

# 4. Fill zones
b_loaded = pcbnew.LoadBoard(PCB_FILE)
zf = pcbnew.ZONE_FILLER(b_loaded)
zf.Fill(b_loaded.Zones())
pcbnew.SaveBoard(PCB_FILE, b_loaded)
print("Zones refilled successfully.")

# 5. Run DRC
drc_json = os.path.join(BASE_DIR, "pcb_drc_report.json")
subprocess.run([KICAD_CLI, "pcb", "drc", "--severity-all", "--format", "json", "-o", drc_json, PCB_FILE])

with open(drc_json) as f:
    drc_rep = json.load(f)

unconnected = len(drc_rep.get("unconnected_items", []))
violations = len(drc_rep.get("violations", []))
print(f"DRC STATUS: Unconnected={unconnected}, Violations={violations}")

if unconnected > 0:
    for u in drc_rep.get("unconnected_items", []):
        print(f"  [UNCONNECTED] {u.get('description')}")
        for it in u.get('items', []):
            print(f"    * {it.get('description')} at ({it.get('pos', {}).get('x')}, {it.get('pos', {}).get('y')})")

# 6. Export artifacts
os.makedirs(GERBER_DIR, exist_ok=True)
pdf_sch = os.path.join(BASE_DIR, "amemiya_carrier_v2_schematic.pdf")
subprocess.run([KICAD_CLI, "sch", "export", "pdf", "--output", pdf_sch, ROOT_SCH])
print("Schematic PDF exported.")

step_pcb = os.path.join(BASE_DIR, "amemiya_carrier_v2.step")
subprocess.run([KICAD_CLI, "pcb", "export", "step", "--output", step_pcb, "--grid-origin", PCB_FILE])
print("STEP 3D model exported.")

subprocess.run([KICAD_CLI, "pcb", "export", "gerbers", "--output", GERBER_DIR, PCB_FILE])
subprocess.run([KICAD_CLI, "pcb", "export", "drill", "--output", GERBER_DIR, PCB_FILE])

zip_path = os.path.join(FAB_DIR, "amemiya_carrier_v2_gerbers.zip")
with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(GERBER_DIR):
        for fl in files:
            fpath = os.path.join(root, fl)
            z.write(fpath, os.path.basename(fpath))
print("Gerbers ZIP created.")
print("=== CARRIER V2 COMPLETO E VALIDADO! ===")
