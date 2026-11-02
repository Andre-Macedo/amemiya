import os
import re

sch_path = r'C:\Users\andrl\OneDrive\Documentos\Projetos Pessoal\amemiya\hardware\kicad\amemiya_carrier_v2\04_Peripherals_LoRa.kicad_sch'
txt = open(sch_path, encoding='utf-8').read()

print("Buzzer matches:")
for line in txt.splitlines():
    if "BUZ" in line or "buz" in line.lower():
        print("  ", line.strip())
