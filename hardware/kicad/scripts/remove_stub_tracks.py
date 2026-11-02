import pcbnew
import subprocess
import json
import os

pcb_file = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb'
drc_json = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\pcb_drc_report.json'

b = pcbnew.LoadBoard(pcb_file)

to_delete = []
for t in b.GetTracks():
    net = t.GetNetname()
    if "J_AMP" in net:
        to_delete.append(t)
    elif "+3V3_SWITCHED" in net:
        sy = t.GetStart().y / 1e6
        ey = t.GetEnd().y / 1e6
        if sy < 55.0 or ey < 55.0:
            to_delete.append(t)

for t in to_delete:
    b.Remove(t)

print(f"Removed {len(to_delete)} stub tracks.")

# Refill zones
zf = pcbnew.ZONE_FILLER(b)
zf.Fill(b.Zones())
pcbnew.SaveBoard(pcb_file, b)

subprocess.run([r'C:\Program Files\KiCad\9.0\bin\kicad-cli.exe', 'pcb', 'drc', '--severity-all', '--format', 'json', '-o', drc_json, pcb_file])
with open(drc_json) as f:
    rep = json.load(f)
unc = len(rep.get('unconnected_items', []))
vio = len(rep.get('violations', []))
print(f"DRC RESULTS AFTER CLEANUP: Unconnected={unc}, Violations={vio}")

if unc > 0:
    for u in rep.get('unconnected_items', []):
        print(f"  [UNCONNECTED] {u.get('description')}")
