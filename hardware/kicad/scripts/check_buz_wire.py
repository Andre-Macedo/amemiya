sch_path = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\04_Peripherals_LoRa.kicad_sch'
txt = open(sch_path, encoding='utf-8').read()

import re
wires = re.findall(r'\(wire[\s\S]*?\(pts[\s\S]*?\)', txt)
for w in wires:
    if any(p in w for p in ["209.55", "210.82", "213.36", "217.17", "205.82"]):
        print("Buzzer wire:", w.strip().replace('\n', ' '))
