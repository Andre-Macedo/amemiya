import pcbnew
import subprocess
import json
import os

pcb_file = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\amemiya_carrier_v2.kicad_pcb'
drc_json = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\pcb_drc_report.json'

b = pcbnew.LoadBoard(pcb_file)
tb = b.GetTitleBlock()
tb.SetTitle('Amemiya Industrial IoT - Main Node Carrier Board v2.0 (Dual Sensor)')
tb.SetComment(0, 'Sonda Torre Dual (ADXL345 I2C + DS18B20 1-Wire) com Power-Gating')
tb.SetRevision('v2.0-Dual')
tb.SetDate('2026-10-20')

zf = pcbnew.ZONE_FILLER(b)
zf.Fill(b.Zones())
pcbnew.SaveBoard(pcb_file, b)
print('Zones filled and board saved.')

subprocess.run([r'C:\Program Files\KiCad\9.0\bin\kicad-cli.exe', 'pcb', 'drc', '--severity-all', '--format', 'json', '-o', drc_json, pcb_file])
with open(drc_json) as f:
    rep = json.load(f)
unc = len(rep.get('unconnected_items', []))
vio = len(rep.get('violations', []))
print(f'DRC RESULTS: Unconnected={unc}, Violations={vio}')
if unc > 0:
    for u in rep.get('unconnected_items', []):
        desc = u.get('description')
        print(f'  [UNCONNECTED] {desc}')
        for it in u.get('items', []):
            print(f'    * {it.get("description")} at ({it.get("pos", {}).get("x")}, {it.get("pos", {}).get("y")})')
